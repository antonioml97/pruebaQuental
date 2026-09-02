<?php

declare(strict_types=1);

/**
 * Verifica los servicios de tokens sin depender de peticiones ni cookies.
 */

namespace Tests\Feature\Authentication;

use App\Domain\Authentication\DTO\RegistrationData;
use App\Models\AccessToken;
use App\Models\User;
use App\Services\Authentication\RegisterUserService;
use App\Services\Authentication\TokenGenerator;
use App\Services\Authentication\TokenRevocationService;
use App\Services\Authentication\TokenUsageRecorder;
use App\Services\Authentication\TokenValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/**
 * Cubre emisión, validación, actividad y revocación de sesiones independientes.
 */
final class TokenServicesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Emite secretos independientes y conserva solo el hash en persistencia.
     */
    public function test_it_generates_independent_tokens_with_the_configured_expiration(): void
    {
        $this->travelTo(now()->startOfSecond());
        config(['auth_tokens.lifetime_minutes' => 30]);
        $user = User::factory()->create();
        $generator = new TokenGenerator;

        $first = $generator->generate($user);
        $second = $generator->generate($user);
        [$identifier, $secret] = explode('|', $first->plainTextToken, 2);
        $stored = AccessToken::query()->findOrFail((int) $identifier);

        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $secret);
        self::assertNotSame($first->plainTextToken, $second->plainTextToken);
        self::assertSame(hash('sha256', $secret), $stored->token_hash);
        self::assertSame($user->getKey(), $first->userId);
        self::assertSame($user->email, $first->email);
        self::assertEquals(now()->addMinutes(30), $first->expiresAt);
        self::assertTrue($stored->expires_at->equalTo($first->expiresAt));
        self::assertNull($stored->last_used_at);
        $this->assertDatabaseCount('access_tokens', 2);
    }

    /**
     * Rechaza duraciones no positivas sin persistir una sesión inutilizable.
     */
    public function test_it_rejects_an_invalid_lifetime_before_persisting_a_token(): void
    {
        $user = User::factory()->create();
        config(['auth_tokens.lifetime_minutes' => 0]);
        $this->expectException(LogicException::class);

        try {
            (new TokenGenerator)->generate($user);
        } finally {
            $this->assertDatabaseCount('access_tokens', 0);
        }
    }

    /** El registro revierte también el usuario cuando no puede emitir su primera sesión. */
    public function test_registration_rolls_back_when_token_generation_fails(): void
    {
        config(['auth_tokens.lifetime_minutes' => 0]);
        $this->expectException(LogicException::class);

        try {
            $this->app->make(RegisterUserService::class)->register(
                new RegistrationData('Rick', 'rick@example.test', 'Portal123'),
            );
        } finally {
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('access_tokens', 0);
        }
    }

    /** Consultar la validez de una sesión no provoca escrituras de actividad. */
    public function test_validation_does_not_record_usage(): void
    {
        $result = (new TokenGenerator)->generate(User::factory()->create());
        $stored = AccessToken::query()->firstOrFail();
        $updatedAt = $stored->updated_at;
        $this->travel(10)->minutes();

        self::assertInstanceOf(AccessToken::class, (new TokenValidator)->validate($result->plainTextToken));
        $stored->refresh();
        self::assertNull($stored->last_used_at);
        self::assertEquals($updatedAt, $stored->updated_at);
    }

    /**
     * Descarta credenciales malformadas o manipuladas sin registrar actividad.
     */
    public function test_it_rejects_invalid_tokens_without_recording_usage(): void
    {
        $result = (new TokenGenerator)->generate(User::factory()->create());
        [$identifier, $secret] = explode('|', $result->plainTextToken, 2);
        $differentSecret = ($secret[0] === 'a' ? 'b' : 'a').substr($secret, 1);
        $validator = new TokenValidator;

        foreach ([null, '', 'invalid', 'x|'.$secret, $identifier.'|short', $identifier.'|'.$secret.'|extra', $identifier.'|'.$differentSecret] as $invalid) {
            self::assertNull($validator->validate($invalid));
        }

        self::assertNull(AccessToken::query()->findOrFail((int) $identifier)->last_used_at);
    }

    /**
     * Conserva la escritura de actividad cada cinco minutos sin prolongar la sesión.
     */
    public function test_it_records_usage_at_intervals_without_refreshing_expiration(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = User::factory()->create();
        $result = (new TokenGenerator)->generate($user);
        $validator = new TokenValidator;

        $first = $validator->validate($result->plainTextToken);
        self::assertInstanceOf(AccessToken::class, $first);
        self::assertTrue($first->user->is($user));
        self::assertNull($first->last_used_at);
        $usage = new TokenUsageRecorder;
        $usage->record($first);
        self::assertTrue($first->last_used_at->equalTo(now()));

        $this->travel(4)->minutes();
        $second = $validator->validate($result->plainTextToken);
        self::assertInstanceOf(AccessToken::class, $second);
        $usage->record($second);
        self::assertEquals($first->last_used_at, $second->last_used_at);

        $this->travel(1)->minutes();
        $third = $validator->validate($result->plainTextToken);
        self::assertInstanceOf(AccessToken::class, $third);
        $usage->record($third);
        self::assertTrue($third->last_used_at->equalTo(now()));
        self::assertTrue($third->expires_at->equalTo($result->expiresAt));
    }

    /**
     * Una sesión caducada nunca se reactiva ni registra actividad.
     */
    public function test_it_rejects_expired_tokens_without_recording_usage(): void
    {
        $this->travelTo(now()->startOfSecond());
        config(['auth_tokens.lifetime_minutes' => 1]);
        $result = (new TokenGenerator)->generate(User::factory()->create());
        $this->travel(2)->minutes();

        self::assertNull((new TokenValidator)->validate($result->plainTextToken));
        self::assertNull(AccessToken::query()->firstOrFail()->last_used_at);
    }

    /**
     * Revoca un token sin afectar otras sesiones del mismo usuario ni de terceros.
     */
    public function test_it_revokes_only_the_selected_session(): void
    {
        $user = User::factory()->create();
        $generator = new TokenGenerator;
        $first = $generator->generate($user);
        $second = $generator->generate($user);
        $otherUser = $generator->generate(User::factory()->create());
        $validator = new TokenValidator;
        $selected = $validator->validate($first->plainTextToken);
        self::assertInstanceOf(AccessToken::class, $selected);

        (new TokenRevocationService)->revoke($selected);

        self::assertNull($validator->validate($first->plainTextToken));
        self::assertInstanceOf(AccessToken::class, $validator->validate($second->plainTextToken));
        self::assertInstanceOf(AccessToken::class, $validator->validate($otherUser->plainTextToken));
        $this->assertDatabaseCount('access_tokens', 2);
    }
}
