<?php

declare(strict_types=1);

/**
 * Define la frontera del cliente de la API externa de Rick and Morty.
 */

namespace App\Domain\RickAndMorty\Contracts;

use App\Domain\Characters\DTO\CharacterData;
use App\Domain\Episodes\DTO\EpisodeData;
use App\Domain\Locations\DTO\LocationData;
use App\Domain\RickAndMorty\DTO\PaginatedResponseData;
use App\Domain\RickAndMorty\Exceptions\InvalidRickAndMortyResponseException;
use App\Domain\RickAndMorty\Exceptions\RickAndMortyRequestException;
use InvalidArgumentException;

/**
 * Define los recursos paginados necesarios para el proceso de sincronización.
 */
interface RickAndMortyClientInterface
{
    /**
     * Obtiene una página de personajes desde la fuente externa.
     *
     * @return PaginatedResponseData<CharacterData>
     *
     * @throws InvalidArgumentException Si la página no es positiva.
     * @throws InvalidRickAndMortyResponseException Si el proveedor devuelve datos inválidos.
     * @throws RickAndMortyRequestException Si la petición HTTP no puede completarse.
     */
    public function fetchCharacters(int $page = 1): PaginatedResponseData;

    /**
     * Obtiene una página de episodios desde la fuente externa.
     *
     * @return PaginatedResponseData<EpisodeData>
     *
     * @throws InvalidArgumentException Si la página no es positiva.
     * @throws InvalidRickAndMortyResponseException Si el proveedor devuelve datos inválidos.
     * @throws RickAndMortyRequestException Si la petición HTTP no puede completarse.
     */
    public function fetchEpisodes(int $page = 1): PaginatedResponseData;

    /**
     * Obtiene una página de localizaciones desde la fuente externa.
     *
     * @return PaginatedResponseData<LocationData>
     *
     * @throws InvalidArgumentException Si la página no es positiva.
     * @throws InvalidRickAndMortyResponseException Si el proveedor devuelve datos inválidos.
     * @throws RickAndMortyRequestException Si la petición HTTP no puede completarse.
     */
    public function fetchLocations(int $page = 1): PaginatedResponseData;
}
