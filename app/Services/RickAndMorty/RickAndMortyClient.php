<?php

declare(strict_types=1);

/**
 * Proporciona la futura implementación del cliente externo de Rick and Morty.
 */

namespace App\Services\RickAndMorty;

use App\Domain\RickAndMorty\Contracts\RickAndMortyClientInterface;
use App\Domain\RickAndMorty\DTO\CharacterData;
use App\Domain\RickAndMorty\DTO\EpisodeData;
use App\Domain\RickAndMorty\DTO\LocationData;
use App\Domain\RickAndMorty\DTO\PaginatedResponseData;
use LogicException;

/**
 * Esqueleto del cliente pendiente de implementar la integración HTTP.
 */
final class RickAndMortyClient implements RickAndMortyClientInterface
{
    /**
     * Obtiene una página de personajes.
     *
     * @return PaginatedResponseData<CharacterData>
     */
    public function fetchCharacters(int $page = 1): PaginatedResponseData
    {
        throw new LogicException('The Rick and Morty HTTP client is not implemented yet.');
    }

    /**
     * Obtiene una página de episodios.
     *
     * @return PaginatedResponseData<EpisodeData>
     */
    public function fetchEpisodes(int $page = 1): PaginatedResponseData
    {
        throw new LogicException('The Rick and Morty HTTP client is not implemented yet.');
    }

    /**
     * Obtiene una página de localizaciones.
     *
     * @return PaginatedResponseData<LocationData>
     */
    public function fetchLocations(int $page = 1): PaginatedResponseData
    {
        throw new LogicException('The Rick and Morty HTTP client is not implemented yet.');
    }
}
