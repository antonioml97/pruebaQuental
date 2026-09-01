<?php

declare(strict_types=1);

/**
 * Verifica las restricciones de integridad del esquema Rick and Morty.
 */

namespace Tests\Feature\RickAndMorty;

use App\Models\Character;
use App\Models\Episode;
use App\Models\Location;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Comprueba que la base de datos impide duplicados y relaciones inválidas.
 */
final class DatabaseConstraintsTest extends TestCase
{
    use RefreshDatabase;

    /** Verifica que dos personajes no pueden compartir identificador externo. */
    public function test_character_external_identifier_is_unique(): void
    {
        $attributes = $this->characterAttributes();
        Character::query()->create($attributes);

        $this->expectException(QueryException::class);

        Character::query()->create([...$attributes, 'name' => 'Morty Smith']);
    }

    /** Verifica que dos episodios no pueden compartir identificador externo. */
    public function test_episode_external_identifier_is_unique(): void
    {
        $attributes = [
            'external_id' => 1,
            'name' => 'Pilot',
            'air_date' => '2013-12-02',
            'code' => 'S01E01',
        ];
        Episode::query()->create($attributes);

        $this->expectException(QueryException::class);

        Episode::query()->create([...$attributes, 'code' => 'S01E02']);
    }

    /** Verifica que dos localizaciones no pueden compartir identificador externo. */
    public function test_location_external_identifier_is_unique(): void
    {
        $attributes = [
            'external_id' => 1,
            'name' => 'Earth (C-137)',
            'type' => 'Planet',
            'dimension' => 'Dimension C-137',
        ];
        Location::query()->create($attributes);

        $this->expectException(QueryException::class);

        Location::query()->create([...$attributes, 'name' => 'Another Earth']);
    }

    /** Verifica que la tabla pivote no admite relaciones duplicadas. */
    public function test_character_episode_relation_is_unique(): void
    {
        $character = Character::query()->create($this->characterAttributes());
        $episode = Episode::query()->create([
            'external_id' => 1,
            'name' => 'Pilot',
            'air_date' => '2013-12-02',
            'code' => 'S01E01',
        ]);
        $relation = [
            'character_id' => $character->getKey(),
            'episode_id' => $episode->getKey(),
        ];
        DB::table('character_episode')->insert($relation);

        $this->expectException(QueryException::class);

        DB::table('character_episode')->insert($relation);
    }

    /** Verifica que un personaje no puede referenciar una localización inexistente. */
    public function test_character_location_references_require_an_existing_location(): void
    {
        $this->expectException(QueryException::class);

        Character::query()->create([
            ...$this->characterAttributes(),
            'origin_location_id' => 999,
        ]);
    }

    /**
     * Devuelve los atributos mínimos de un personaje de prueba.
     *
     * @return array<string, int|string|null>
     */
    private function characterAttributes(): array
    {
        return [
            'external_id' => 1,
            'name' => 'Rick Sanchez',
            'status' => 'Alive',
            'species' => 'Human',
            'type' => '',
            'gender' => 'Male',
            'image_url' => 'https://rickandmortyapi.com/api/character/avatar/1.jpeg',
            'origin_location_id' => null,
            'current_location_id' => null,
        ];
    }
}
