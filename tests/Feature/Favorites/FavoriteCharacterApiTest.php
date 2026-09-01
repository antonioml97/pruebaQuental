<?php

declare(strict_types=1);

/**
 * Verifica el contrato privado de gestión de personajes favoritos.
 */

namespace Tests\Feature\Favorites;

use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Cubre autenticación, CSRF, aislamiento, idempotencia y restricciones persistentes.
 */
final class FavoriteCharacterApiTest extends TestCase
{
    use RefreshDatabase;

    /** Token determinista que representa la cookie CSRF obtenida por Axios. */
    private const CSRF_TOKEN = 'abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789';

    /**
     * Verifica que listado y mutaciones requieren una sesión autenticada.
     */
    public function test_favorite_routes_require_authentication(): void
    {
        $character = $this->createCharacter(1);

        $this->getJson('/api/favorites')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');

        $this->withCsrf()
            ->putJson('/api/favorites/'.$character->external_id)
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    /**
     * Verifica que cada usuario solo lista sus favoritos con orden y paginación estables.
     */
    public function test_it_lists_only_the_authenticated_users_favorites(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $firstCharacter = $this->createCharacter(1);
        $secondCharacter = $this->createCharacter(2);
        $otherCharacter = $this->createCharacter(3);

        Carbon::setTestNow('2026-09-01 10:00:00');
        $firstUser->favoriteCharacters()->attach($firstCharacter);
        Carbon::setTestNow('2026-09-01 11:00:00');
        $firstUser->favoriteCharacters()->attach($secondCharacter);
        $secondUser->favoriteCharacters()->attach($otherCharacter);
        Carbon::setTestNow();

        $this->authenticateAs($firstUser)
            ->getJson('/api/favorites?per_page=1&page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 2)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 2);

        $this->getJson('/api/favorites?per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.id', 1);
    }

    /**
     * Verifica que PUT es idempotente y la base de datos impide duplicados.
     */
    public function test_it_adds_a_favorite_idempotently(): void
    {
        $user = User::factory()->create();
        $character = $this->createCharacter(42);
        $this->authenticateAs($user)->withCsrf();

        $this->putJson('/api/favorites/42')
            ->assertOk()
            ->assertJsonPath('data.id', 42);
        $this->putJson('/api/favorites/42')
            ->assertOk()
            ->assertJsonPath('data.id', 42);

        $this->assertDatabaseCount('favorite_characters', 1);
        $this->assertDatabaseHas('favorite_characters', [
            'user_id' => $user->getKey(),
            'character_id' => $character->getKey(),
        ]);
    }

    /**
     * Verifica un error homogéneo cuando el identificador público no existe.
     */
    public function test_it_rejects_an_unknown_character(): void
    {
        $this->authenticateAs(User::factory()->create())->withCsrf();

        $this->putJson('/api/favorites/999')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'resource_not_found');
        $this->deleteJson('/api/favorites/999')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'resource_not_found');
    }

    /**
     * Verifica que eliminar es idempotente y nunca afecta al favorito de otra cuenta.
     */
    public function test_it_removes_only_the_authenticated_users_favorite(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $character = $this->createCharacter(7);
        $firstUser->favoriteCharacters()->attach($character);
        $secondUser->favoriteCharacters()->attach($character);

        $this->authenticateAs($firstUser)->withCsrf();

        $this->deleteJson('/api/favorites/7')->assertNoContent();
        $this->deleteJson('/api/favorites/7')->assertNoContent();

        $this->assertDatabaseMissing('favorite_characters', [
            'user_id' => $firstUser->getKey(),
            'character_id' => $character->getKey(),
        ]);
        $this->assertDatabaseHas('favorite_characters', [
            'user_id' => $secondUser->getKey(),
            'character_id' => $character->getKey(),
        ]);
    }

    /**
     * Verifica que una sesión válida no puede mutar favoritos sin CSRF.
     */
    public function test_favorite_mutations_require_csrf_protection(): void
    {
        $user = User::factory()->create();
        $character = $this->createCharacter(8);

        $this->authenticateAs($user)
            ->putJson('/api/favorites/8')
            ->assertStatus(419)
            ->assertJsonPath('error.code', 'csrf_token_mismatch');

        $this->assertDatabaseMissing('favorite_characters', [
            'user_id' => $user->getKey(),
            'character_id' => $character->getKey(),
        ]);
    }

    /**
     * Verifica límites de paginación con el contrato homogéneo de validación.
     */
    public function test_it_validates_favorite_pagination(): void
    {
        $this->authenticateAs(User::factory()->create())
            ->getJson('/api/favorites?per_page=101&page=0')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['details' => ['per_page', 'page']]]);
    }

    /**
     * Verifica que las cascadas eliminan favoritos cuyo usuario o personaje desaparece.
     */
    public function test_favorite_foreign_keys_cascade_on_delete(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $firstCharacter = $this->createCharacter(10);
        $secondCharacter = $this->createCharacter(11);
        $firstUser->favoriteCharacters()->attach([$firstCharacter->getKey(), $secondCharacter->getKey()]);
        $secondUser->favoriteCharacters()->attach($firstCharacter);

        $firstUser->delete();
        $firstCharacter->delete();

        $this->assertDatabaseCount('favorite_characters', 0);
    }

    /**
     * Configura una cookie opaca válida para el usuario indicado.
     */
    private function authenticateAs(User $user): self
    {
        $secret = str_pad(dechex((int) $user->getKey()), 64, 'a');
        $accessToken = $user->accessTokens()->create([
            'name' => 'favorite-test',
            'token_hash' => hash('sha256', $secret),
            'expires_at' => now()->addHour(),
        ]);

        return $this
            ->withCredentials()
            ->withUnencryptedCookie('auth_token', $accessToken->getKey().'|'.$secret);
    }

    /**
     * Añade la cookie y cabecera CSRF a las operaciones de escritura siguientes.
     */
    private function withCsrf(): self
    {
        return $this
            ->withCredentials()
            ->withUnencryptedCookie('XSRF-TOKEN', self::CSRF_TOKEN)
            ->withHeader('X-XSRF-TOKEN', self::CSRF_TOKEN);
    }

    /**
     * Persiste un personaje identificable mediante la API pública.
     */
    private function createCharacter(int $externalId): Character
    {
        return Character::query()->create([
            'external_id' => $externalId,
            'name' => "Character $externalId",
            'status' => 'Alive',
            'species' => 'Human',
            'type' => '',
            'gender' => 'unknown',
            'image_url' => "https://example.test/characters/$externalId.jpeg",
            'origin_location_id' => null,
            'current_location_id' => null,
        ]);
    }
}
