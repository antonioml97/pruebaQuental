<?php

declare(strict_types=1);

/**
 * Verifica el contrato HTTP de autenticación propia para clientes Vue.
 */

namespace Tests\Feature\Authentication;

use App\Models\AccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

/**
 * Cubre CSRF, registro, acceso, cookies HttpOnly, caducidad y revocación.
 */
final class AuthenticationApiTest extends TestCase
{
    use RefreshDatabase;

    /** Token determinista que simula el valor obtenido por Axios. */
    private const CSRF_TOKEN = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    /**
     * Verifica que el endpoint CSRF entrega una cookie legible y segura para Axios.
     */
    public function test_it_issues_a_csrf_cookie_for_axios(): void
    {
        $response = $this->getJson('/api/auth/csrf-cookie')->assertNoContent();
        $cookie = $response->getCookie('XSRF-TOKEN', false);

        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertFalse($cookie->isHttpOnly());
        self::assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', (string) $cookie->getValue());
    }

    /**
     * Verifica que las operaciones de escritura rechazan el doble envío ausente.
     */
    public function test_it_rejects_a_write_request_without_csrf_protection(): void
    {
        $this->postJson('/api/auth/register', $this->registrationPayload())
            ->assertStatus(419)
            ->assertExactJson([
                'error' => [
                    'code' => 'csrf_token_mismatch',
                    'message' => 'La protección CSRF de la petición no es válida.',
                    'details' => [],
                ],
            ]);

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Verifica registro, hash de contraseña y secreto reservado a HttpOnly.
     */
    public function test_it_registers_a_user_without_exposing_or_storing_the_plain_token(): void
    {
        $response = $this->withCsrf()->postJson('/api/auth/register', $this->registrationPayload())
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'rick@example.test')
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email'], 'expires_at']]);

        /** @var array<string, mixed> $data */
        $data = $response->json('data');
        self::assertArrayNotHasKey('token', $data);
        self::assertArrayNotHasKey('access_token', $data);

        $cookie = $response->getCookie('auth_token', false);
        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        self::assertFalse($cookie->isSecure());

        [$identifier, $secret] = explode('|', (string) $cookie->getValue(), 2);
        $accessToken = AccessToken::query()->findOrFail((int) $identifier);
        $user = User::query()->where('email', 'rick@example.test')->firstOrFail();

        self::assertTrue(Hash::check('Portal123', $user->password));
        self::assertSame(hash('sha256', $secret), $accessToken->token_hash);
        self::assertNotSame($secret, $accessToken->token_hash);
    }

    /**
     * Verifica validación de usuario duplicado y contraseña insuficiente.
     */
    public function test_it_validates_registration_data(): void
    {
        User::factory()->create(['email' => 'rick@example.test']);

        $payload = $this->registrationPayload();
        $payload['password'] = 'weak';
        $payload['password_confirmation'] = 'different';

        $this->withCsrf()->postJson('/api/auth/register', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['details' => ['email', 'password']]]);
    }

    /**
     * Verifica que las credenciales incorrectas no emiten tokens ni revelan su causa.
     */
    public function test_it_rejects_invalid_credentials_without_issuing_a_token(): void
    {
        User::factory()->create([
            'email' => 'rick@example.test',
            'password' => 'Portal123',
        ]);

        $this->withCsrf()->postJson('/api/auth/login', [
            'email' => 'rick@example.test',
            'password' => 'incorrect-password',
        ])->assertUnauthorized()->assertExactJson([
            'error' => [
                'code' => 'invalid_credentials',
                'message' => 'Las credenciales proporcionadas no son válidas.',
                'details' => [],
            ],
        ]);

        $this->assertDatabaseCount('access_tokens', 0);
    }

    /**
     * Verifica que una cookie válida permite recuperar el usuario actual.
     */
    public function test_it_authenticates_a_valid_cookie(): void
    {
        $authCookie = $this->registerAndGetAuthenticationCookie();

        $this->withUnencryptedCookie('auth_token', $authCookie)
            ->getJson('/api/auth/user')
            ->assertOk()
            ->assertJsonPath('data.email', 'rick@example.test');

        self::assertNotNull(AccessToken::query()->firstOrFail()->last_used_at);
    }

    /**
     * Verifica que cookies ausentes, manipuladas o caducadas no autentican.
     */
    public function test_it_rejects_invalid_and_expired_authentication_cookies(): void
    {
        $this->getJson('/api/auth/user')->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');

        $user = User::factory()->create();
        $secret = str_repeat('a', 64);
        $accessToken = $user->accessTokens()->create([
            'name' => 'vue-web',
            'token_hash' => hash('sha256', $secret),
            'expires_at' => now()->subMinute(),
        ]);

        $this->withUnencryptedCookie('auth_token', $accessToken->getKey().'|'.$secret)
            ->withCredentials()
            ->getJson('/api/auth/user')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');

        $this->withUnencryptedCookie('auth_token', $accessToken->getKey().'|'.str_repeat('b', 64))
            ->withCredentials()
            ->getJson('/api/auth/user')
            ->assertUnauthorized();
    }

    /**
     * Verifica que logout revoca solo la sesión actual y caduca su cookie.
     */
    public function test_it_revokes_only_the_current_token_on_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'rick@example.test',
            'password' => 'Portal123',
        ]);
        $firstCookie = $this->loginAndGetAuthenticationCookie();
        $secondCookie = $this->loginAndGetAuthenticationCookie();
        [$firstIdentifier] = explode('|', $firstCookie, 2);
        [$secondIdentifier] = explode('|', $secondCookie, 2);

        $this->withCsrf()
            ->withUnencryptedCookie('auth_token', $firstCookie)
            ->postJson('/api/auth/logout')
            ->assertNoContent()
            ->assertCookieExpired('auth_token');

        $this->assertDatabaseMissing('access_tokens', ['id' => (int) $firstIdentifier]);
        $this->assertDatabaseHas('access_tokens', ['id' => (int) $secondIdentifier, 'user_id' => $user->getKey()]);

        $this->withUnencryptedCookie('auth_token', $firstCookie)
            ->getJson('/api/auth/user')
            ->assertUnauthorized();
        $this->withUnencryptedCookie('auth_token', $secondCookie)
            ->getJson('/api/auth/user')
            ->assertOk();
    }

    /**
     * Verifica el límite de intentos por combinación de correo e IP.
     */
    public function test_it_rate_limits_authentication_attempts(): void
    {
        $this->withCsrf();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/auth/login', [
                'email' => 'limited@example.test',
                'password' => 'incorrect-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/auth/login', [
            'email' => 'limited@example.test',
            'password' => 'incorrect-password',
        ])->assertTooManyRequests()->assertJsonPath('error.code', 'too_many_requests');
    }

    /**
     * Verifica CORS con credenciales para el origen permitido del frontend.
     */
    public function test_it_allows_credentialed_cors_from_the_configured_frontend(): void
    {
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type,x-xsrf-token',
        ])->options('/api/auth/login')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    /**
     * Añade a la siguiente petición la cookie y cabecera del patrón double-submit.
     */
    private function withCsrf(): self
    {
        return $this
            ->withCredentials()
            ->withUnencryptedCookie('XSRF-TOKEN', self::CSRF_TOKEN)
            ->withHeader('X-XSRF-TOKEN', self::CSRF_TOKEN);
    }

    /**
     * Registra un usuario válido y devuelve el valor opaco de su cookie.
     */
    private function registerAndGetAuthenticationCookie(): string
    {
        $cookie = $this->withCsrf()
            ->postJson('/api/auth/register', $this->registrationPayload())
            ->getCookie('auth_token', false);

        self::assertInstanceOf(Cookie::class, $cookie);

        return (string) $cookie->getValue();
    }

    /**
     * Inicia sesión con el usuario conocido y devuelve el valor de la cookie.
     */
    private function loginAndGetAuthenticationCookie(): string
    {
        $cookie = $this->withCsrf()->postJson('/api/auth/login', [
            'email' => 'rick@example.test',
            'password' => 'Portal123',
        ])->assertOk()->getCookie('auth_token', false);

        self::assertInstanceOf(Cookie::class, $cookie);

        return (string) $cookie->getValue();
    }

    /**
     * Construye un registro válido con contraseña confirmada.
     *
     * @return array<string, string>
     */
    private function registrationPayload(): array
    {
        return [
            'name' => 'Rick Sanchez',
            'email' => 'rick@example.test',
            'password' => 'Portal123',
            'password_confirmation' => 'Portal123',
        ];
    }
}
