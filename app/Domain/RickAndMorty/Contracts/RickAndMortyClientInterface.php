<?php

declare(strict_types=1);

/**
 * Define la frontera del cliente de la API externa de Rick and Morty.
 */

namespace App\Domain\RickAndMorty\Contracts;

use App\Domain\RickAndMorty\DTO\CharacterData;
use App\Domain\RickAndMorty\DTO\EpisodeData;
use App\Domain\RickAndMorty\DTO\LocationData;
use App\Domain\RickAndMorty\DTO\PaginatedResponseData;

/**
 * Define los recursos paginados necesarios para el proceso de sincronización.
 */
interface RickAndMortyClientInterface
{
    /**
     * Obtiene una página de personajes desde la fuente externa.
     *
     * @return PaginatedResponseData<CharacterData>
     */
    public function fetchCharacters(int $page = 1): PaginatedResponseData;

    /**
     * Obtiene una página de episodios desde la fuente externa.
     *
     * @return PaginatedResponseData<EpisodeData>
     */
    public function fetchEpisodes(int $page = 1): PaginatedResponseData;

    /**
     * Obtiene una página de localizaciones desde la fuente externa.
     *
     * @return PaginatedResponseData<LocationData>
     */
    public function fetchLocations(int $page = 1): PaginatedResponseData;
}
