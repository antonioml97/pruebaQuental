<?php

declare(strict_types=1);

/**
 * Verifica la transformación y validación de respuestas del proveedor Rick and Morty.
 */

namespace Tests\Unit\RickAndMorty;

use App\Domain\RickAndMorty\DTO\CharacterData;
use App\Domain\RickAndMorty\DTO\EpisodeData;
use App\Domain\RickAndMorty\DTO\LocationData;
use App\Domain\RickAndMorty\Exceptions\InvalidRickAndMortyResponseException;
use App\Services\RickAndMorty\RickAndMortyResponseMapper;
use Error;
use PHPUnit\Framework\TestCase;

/**
 * Cubre datos externos válidos, ausentes e inconsistentes sin acceder a la red.
 */
final class RickAndMortyResponseMapperTest extends TestCase
{
    /** Transformador sometido a pruebas. */
    private RickAndMortyResponseMapper $mapper;

    /** Prepara un transformador de respuestas aislado. */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = new RickAndMortyResponseMapper;
    }

    /** Verifica que un personaje completo del proveedor se convierte en datos de dominio. */
    public function test_it_maps_a_character_response(): void
    {
        $character = $this->mapper->mapCharacter($this->validCharacterPayload());

        $this->assertInstanceOf(CharacterData::class, $character);
        $this->assertSame(1, $character->externalId);
        $this->assertSame('Rick Sanchez', $character->name);
        $this->assertNull($character->originLocationExternalId);
        $this->assertSame(20, $character->currentLocationExternalId);
        $this->assertSame([1, 2], $character->episodeExternalIds);
    }

    /** Verifica que episodios y localizaciones exponen únicamente campos del dominio. */
    public function test_it_maps_episode_and_location_responses(): void
    {
        $episode = $this->mapper->mapEpisode([
            'id' => 1,
            'name' => 'Pilot',
            'air_date' => 'December 2, 2013',
            'episode' => 'S01E01',
        ]);
        $location = $this->mapper->mapLocation([
            'id' => 20,
            'name' => 'Earth (Replacement Dimension)',
            'type' => 'Planet',
            'dimension' => 'Replacement Dimension',
        ]);

        $this->assertInstanceOf(EpisodeData::class, $episode);
        $this->assertSame('S01E01', $episode->code);
        $this->assertSame('2013-12-02', $episode->airDate->format('Y-m-d'));
        $this->assertInstanceOf(LocationData::class, $location);
        $this->assertSame(20, $location->externalId);
    }

    /** Verifica que las URLs de paginación se convierten en números independientes. */
    public function test_it_maps_a_paginated_response(): void
    {
        $page = $this->mapper->mapCharacterPage([
            'info' => [
                'count' => 41,
                'pages' => 3,
                'next' => 'https://rickandmortyapi.com/api/character?page=3',
                'prev' => 'https://rickandmortyapi.com/api/character?page=1',
            ],
            'results' => [$this->validCharacterPayload()],
        ], 2);

        $this->assertSame(2, $page->currentPage);
        $this->assertSame(3, $page->totalPages);
        $this->assertSame(41, $page->totalItems);
        $this->assertSame(3, $page->nextPage);
        $this->assertSame(1, $page->previousPage);
        $this->assertContainsOnlyInstancesOf(CharacterData::class, $page->items);
    }

    /** Verifica que los campos externos obligatorios no pueden estar ausentes. */
    public function test_it_rejects_a_missing_required_field(): void
    {
        $payload = $this->validCharacterPayload();
        unset($payload['name']);

        $this->expectException(InvalidRickAndMortyResponseException::class);
        $this->expectExceptionMessage('[name]');

        $this->mapper->mapCharacter($payload);
    }

    /** Verifica que los campos externos deben tener el tipo primitivo esperado. */
    public function test_it_rejects_an_invalid_field_type(): void
    {
        $payload = $this->validCharacterPayload();
        $payload['id'] = '1';

        $this->expectException(InvalidRickAndMortyResponseException::class);
        $this->expectExceptionMessage('[id]');

        $this->mapper->mapCharacter($payload);
    }

    /** Verifica el formato de fecha del proveedor antes de acceder al dominio. */
    public function test_it_rejects_an_invalid_air_date(): void
    {
        $this->expectException(InvalidRickAndMortyResponseException::class);
        $this->expectExceptionMessage('[air_date]');

        $this->mapper->mapEpisode([
            'id' => 1,
            'name' => 'Pilot',
            'air_date' => 'not-a-date',
            'episode' => 'S01E01',
        ]);
    }

    /** Verifica que una referencia no puede apuntar a un tipo de recurso diferente. */
    public function test_it_rejects_an_inconsistent_resource_reference(): void
    {
        $payload = $this->validCharacterPayload();
        $payload['episode'] = ['https://rickandmortyapi.com/api/location/1'];

        $this->expectException(InvalidRickAndMortyResponseException::class);
        $this->expectExceptionMessage('must reference a episode resource');

        $this->mapper->mapCharacter($payload);
    }

    /** Verifica que los enlaces de paginación coinciden con la página solicitada. */
    public function test_it_rejects_inconsistent_pagination(): void
    {
        $this->expectException(InvalidRickAndMortyResponseException::class);
        $this->expectExceptionMessage('[info.next]');

        $this->mapper->mapLocationPage([
            'info' => [
                'count' => 10,
                'pages' => 2,
                'next' => 'https://rickandmortyapi.com/api/location?page=9',
                'prev' => null,
            ],
            'results' => [],
        ], 1);
    }

    /** Verifica que un DTO de dominio no puede modificarse después de crearlo. */
    public function test_domain_data_is_immutable(): void
    {
        $character = $this->mapper->mapCharacter($this->validCharacterPayload());

        $this->expectException(Error::class);

        $character->name = 'Morty Smith';
    }

    /**
     * Devuelve un personaje completo conforme al esquema documentado del proveedor.
     *
     * @return array<string, mixed>
     */
    private function validCharacterPayload(): array
    {
        return [
            'id' => 1,
            'name' => 'Rick Sanchez',
            'status' => 'Alive',
            'species' => 'Human',
            'type' => '',
            'gender' => 'Male',
            'origin' => [
                'name' => 'unknown',
                'url' => '',
            ],
            'location' => [
                'name' => 'Earth (Replacement Dimension)',
                'url' => 'https://rickandmortyapi.com/api/location/20',
            ],
            'image' => 'https://rickandmortyapi.com/api/character/avatar/1.jpeg',
            'episode' => [
                'https://rickandmortyapi.com/api/episode/1',
                'https://rickandmortyapi.com/api/episode/2',
            ],
        ];
    }
}
