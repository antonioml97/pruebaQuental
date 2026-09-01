<?php

declare(strict_types=1);

/**
 * Verifica el contrato REST de consulta de personajes sincronizados.
 */

namespace Tests\Feature\RickAndMorty;

use App\Models\Character;
use App\Models\Episode;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre listados, filtros, paginación, detalle y errores públicos de la API.
 */
final class CharacterApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que un catálogo vacío mantiene la estructura paginada.
     */
    public function test_it_returns_an_empty_paginated_character_list(): void
    {
        $response = $this->getJson('/api/characters');

        $response
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 0);

        $this->assertSame(['first', 'last', 'prev', 'next'], array_keys($response->json('links')));
        $this->assertSame(
            ['current_page', 'last_page', 'per_page', 'total'],
            array_keys($response->json('meta')),
        );
    }

    /**
     * Verifica una paginación estable ordenada por el identificador público.
     */
    public function test_it_paginates_characters_in_external_identifier_order(): void
    {
        $this->createCharacter(['external_id' => 3, 'name' => 'Summer Smith']);
        $this->createCharacter(['external_id' => 1, 'name' => 'Rick Sanchez']);
        $this->createCharacter(['external_id' => 2, 'name' => 'Morty Smith']);

        $response = $this->getJson('/api/characters?per_page=2&page=2');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 3)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);

        $this->assertStringContainsString('per_page=2', $response->json('links.first'));
    }

    /**
     * Verifica que todos los filtros admitidos se combinan mediante intersección.
     */
    public function test_it_filters_characters_by_supported_fields(): void
    {
        $this->createCharacter([
            'external_id' => 1,
            'name' => 'Rick Sanchez',
            'status' => 'Alive',
            'species' => 'Human',
            'gender' => 'Male',
        ]);
        $this->createCharacter([
            'external_id' => 2,
            'name' => 'Rick Robot',
            'status' => 'Dead',
            'species' => 'Robot',
            'gender' => 'Genderless',
        ]);
        $this->createCharacter([
            'external_id' => 3,
            'name' => 'Morty Smith',
            'status' => 'Alive',
            'species' => 'Human',
            'gender' => 'Male',
        ]);

        $this->getJson('/api/characters?name=Rick&status=Alive&species=Human&gender=Male')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 1)
            ->assertJsonPath('data.0.name', 'Rick Sanchez');
    }

    /**
     * Verifica el formato homogéneo de parámetros de filtro no válidos.
     */
    public function test_it_rejects_invalid_filters_and_pagination(): void
    {
        $this->getJson('/api/characters?status=Sleeping&gender=Robot&per_page=101&page=0')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonPath('error.message', 'Los parámetros enviados no son válidos.')
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                    'details' => ['status', 'gender', 'per_page', 'page'],
                ],
            ]);
    }

    /**
     * Verifica que el detalle usa el identificador externo y carga relaciones ordenadas.
     */
    public function test_it_returns_character_detail_with_locations_and_episodes(): void
    {
        $origin = $this->createLocation(10, 'Earth C-137');
        $currentLocation = $this->createLocation(20, 'Replacement Dimension');
        $firstEpisode = $this->createEpisode(1, 'Pilot', 'S01E01');
        $secondEpisode = $this->createEpisode(2, 'Lawnmower Dog', 'S01E02');
        $character = $this->createCharacter([
            'external_id' => 100,
            'name' => 'Rick Sanchez',
            'origin_location_id' => $origin->getKey(),
            'current_location_id' => $currentLocation->getKey(),
        ]);
        $character->episodes()->attach([$secondEpisode->getKey(), $firstEpisode->getKey()]);

        $this->getJson('/api/characters/100')
            ->assertOk()
            ->assertJsonPath('data.id', 100)
            ->assertJsonPath('data.origin.id', 10)
            ->assertJsonPath('data.current_location.id', 20)
            ->assertJsonPath('data.episodes.0.id', 1)
            ->assertJsonPath('data.episodes.0.air_date', '2013-12-02')
            ->assertJsonPath('data.episodes.1.id', 2)
            ->assertJsonCount(2, 'data.episodes');
    }

    /**
     * Verifica el error estable de un personaje inexistente.
     */
    public function test_it_returns_a_homogeneous_not_found_error(): void
    {
        $this->getJson('/api/characters/999')
            ->assertNotFound()
            ->assertExactJson([
                'error' => [
                    'code' => 'resource_not_found',
                    'message' => 'El recurso solicitado no existe.',
                    'details' => [],
                ],
            ]);
    }

    /**
     * Verifica el error estable cuando se utiliza un método HTTP no soportado.
     */
    public function test_it_returns_a_homogeneous_method_not_allowed_error(): void
    {
        $this->postJson('/api/characters')
            ->assertStatus(405)
            ->assertExactJson([
                'error' => [
                    'code' => 'method_not_allowed',
                    'message' => 'El método HTTP no está permitido para este recurso.',
                    'details' => [],
                ],
            ]);
    }

    /**
     * Persiste un personaje con valores válidos y atributos personalizables.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createCharacter(array $attributes): Character
    {
        $externalId = (int) ($attributes['external_id'] ?? 1);

        return Character::query()->create(array_merge([
            'external_id' => $externalId,
            'name' => "Character $externalId",
            'status' => 'unknown',
            'species' => 'unknown',
            'type' => '',
            'gender' => 'unknown',
            'image_url' => "https://example.test/characters/$externalId.jpeg",
            'origin_location_id' => null,
            'current_location_id' => null,
        ], $attributes));
    }

    /**
     * Persiste una localización identificable desde el contrato público.
     */
    private function createLocation(int $externalId, string $name): Location
    {
        return Location::query()->create([
            'external_id' => $externalId,
            'name' => $name,
            'type' => 'Planet',
            'dimension' => 'Dimension C-137',
        ]);
    }

    /**
     * Persiste un episodio con una fecha normalizada.
     */
    private function createEpisode(int $externalId, string $name, string $code): Episode
    {
        return Episode::query()->create([
            'external_id' => $externalId,
            'name' => $name,
            'air_date' => '2013-12-02',
            'code' => $code,
        ]);
    }
}
