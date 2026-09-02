<?php

declare(strict_types=1);

/**
 * Verifica los metadatos paginados y su composición con recursos del dominio.
 */

namespace Tests\Unit\RickAndMorty\Mapping;

use App\Domain\Characters\DTO\CharacterData;
use App\Domain\RickAndMorty\Exceptions\InvalidRickAndMortyResponseException;
use App\Services\RickAndMorty\Mapping\CharacterResponseMapper;
use App\Services\RickAndMorty\Mapping\PaginatedResponseMapper;
use App\Services\RickAndMorty\Mapping\ResponsePayloadReader;
use PHPUnit\Framework\TestCase;
use Tests\Support\RickAndMortyPayloads;

/** Verifica los metadatos paginados y su composición con recursos del dominio. */
final class PaginatedResponseMapperTest extends TestCase
{
    /** Transformador bajo prueba, sin acceso HTTP ni persistencia. */
    private PaginatedResponseMapper $pages;

    /** Construye el transformador con el lector común de campos externos. */
    protected function setUp(): void
    {
        parent::setUp();

        $this->pages = new PaginatedResponseMapper(new ResponsePayloadReader);
    }

    /** Verifica que las URLs de paginación se convierten en números independientes. */
    public function test_it_maps_a_paginated_response(): void
    {
        $page = $this->pages->map([
            'info' => [
                'count' => 41,
                'pages' => 3,
                'next' => 'https://rickandmortyapi.com/api/character?page=3',
                'prev' => 'https://rickandmortyapi.com/api/character?page=1',
            ],
            'results' => [RickAndMortyPayloads::character()],
        ], 2, (new CharacterResponseMapper(new ResponsePayloadReader))->map(...));

        $this->assertSame(2, $page->currentPage);
        $this->assertSame(3, $page->totalPages);
        $this->assertSame(41, $page->totalItems);
        $this->assertSame(3, $page->nextPage);
        $this->assertSame(1, $page->previousPage);
        $this->assertContainsOnlyInstancesOf(CharacterData::class, $page->items);
    }

    /** Verifica que los enlaces de paginación coinciden con la página solicitada. */
    public function test_it_rejects_inconsistent_pagination(): void
    {
        $this->expectException(InvalidRickAndMortyResponseException::class);
        $this->expectExceptionMessage('[info.next]');

        $this->pages->map([
            'info' => [
                'count' => 10,
                'pages' => 2,
                'next' => 'https://rickandmortyapi.com/api/location?page=9',
                'prev' => null,
            ],
            'results' => [],
        ], 1, (new CharacterResponseMapper(new ResponsePayloadReader))->map(...));
    }

    /** Verifica que una página intermedia no puede omitir el enlace siguiente. */
    public function test_it_rejects_incomplete_pagination(): void
    {
        $this->expectException(InvalidRickAndMortyResponseException::class);
        $this->expectExceptionMessage('[info.next]');

        $this->pages->map([
            'info' => [
                'count' => 10,
                'pages' => 2,
                'next' => null,
                'prev' => null,
            ],
            'results' => [],
        ], 1, (new CharacterResponseMapper(new ResponsePayloadReader))->map(...));
    }
}
