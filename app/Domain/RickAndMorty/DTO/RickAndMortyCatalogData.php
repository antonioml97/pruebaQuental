<?php

declare(strict_types=1);

/**
 * Agrupa una fotografía completa y validada del catálogo de Rick and Morty.
 */

namespace App\Domain\RickAndMorty\DTO;

use App\Domain\Characters\DTO\CharacterData;
use App\Domain\Episodes\DTO\EpisodeData;
use App\Domain\Locations\DTO\LocationData;

/**
 * Transporta los recursos externos entre la descarga y la persistencia.
 */
final readonly class RickAndMortyCatalogData
{
    /** @var list<LocationData> Localizaciones externas. */
    public array $locations;

    /** @var list<EpisodeData> Episodios externos. */
    public array $episodes;

    /** @var list<CharacterData> Personajes externos. */
    public array $characters;

    /**
     * Crea una fotografía inmutable del catálogo externo.
     *
     * @param  list<LocationData>  $locations
     * @param  list<EpisodeData>  $episodes
     * @param  list<CharacterData>  $characters
     */
    public function __construct(array $locations, array $episodes, array $characters)
    {
        $this->locations = $locations;
        $this->episodes = $episodes;
        $this->characters = $characters;
    }
}
