<?php

declare(strict_types=1);

/**
 * Verifica las relaciones Eloquent del dominio Rick and Morty.
 */

namespace Tests\Feature\RickAndMorty;

use App\Models\Character;
use App\Models\Episode;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprueba las relaciones entre personajes, episodios y localizaciones.
 */
final class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /** Verifica que origen y ubicación actual son relaciones independientes. */
    public function test_character_distinguishes_origin_and_current_location(): void
    {
        $origin = $this->createLocation(1, 'Earth (C-137)');
        $currentLocation = $this->createLocation(20, 'Earth (Replacement Dimension)');
        $character = $this->createCharacter($origin, $currentLocation);

        $this->assertTrue($character->origin->is($origin));
        $this->assertTrue($character->currentLocation->is($currentLocation));
        $this->assertTrue($origin->originCharacters->contains($character));
        $this->assertTrue($currentLocation->residents->contains($character));
        $this->assertFalse($currentLocation->originCharacters->contains($character));
    }

    /** Verifica la relación muchos a muchos entre personajes y episodios. */
    public function test_characters_and_episodes_have_a_bidirectional_many_to_many_relation(): void
    {
        $character = $this->createCharacter();
        $episode = Episode::query()->create([
            'external_id' => 1,
            'name' => 'Pilot',
            'air_date' => '2013-12-02',
            'code' => 'S01E01',
        ]);

        $character->episodes()->attach($episode);

        $this->assertTrue($character->episodes->contains($episode));
        $this->assertTrue($episode->characters->contains($character));
        $this->assertSame('2013-12-02', $episode->air_date->format('Y-m-d'));
    }

    /** Verifica que eliminar una localización conserva el personaje sin referencias rotas. */
    public function test_deleting_a_location_nulls_character_references(): void
    {
        $location = $this->createLocation(1, 'Earth (C-137)');
        $character = $this->createCharacter($location, $location);

        $location->delete();
        $character->refresh();

        $this->assertNull($character->origin_location_id);
        $this->assertNull($character->current_location_id);
    }

    /** Crea una localización mínima para las pruebas de relaciones. */
    private function createLocation(int $externalId, string $name): Location
    {
        return Location::query()->create([
            'external_id' => $externalId,
            'name' => $name,
            'type' => 'Planet',
            'dimension' => 'Dimension C-137',
        ]);
    }

    /** Crea un personaje mínimo para las pruebas de relaciones. */
    private function createCharacter(?Location $origin = null, ?Location $currentLocation = null): Character
    {
        return Character::query()->create([
            'external_id' => 1,
            'name' => 'Rick Sanchez',
            'status' => 'Alive',
            'species' => 'Human',
            'type' => '',
            'gender' => 'Male',
            'image_url' => 'https://rickandmortyapi.com/api/character/avatar/1.jpeg',
            'origin_location_id' => $origin?->getKey(),
            'current_location_id' => $currentLocation?->getKey(),
        ]);
    }
}
