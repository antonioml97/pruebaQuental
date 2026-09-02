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
    /**
     * Crea una fotografía inmutable del catálogo externo.
     *
     * @param  list<LocationData>  $locations  Localizaciones completas del catálogo externo.
     * @param  list<EpisodeData>  $episodes  Episodios completos del catálogo externo.
     * @param  list<CharacterData>  $characters  Personajes completos con sus referencias externas.
     */
    public function __construct(
        /** @var list<LocationData> Localizaciones externas. */
        public array $locations,
        /** @var list<EpisodeData> Episodios externos. */
        public array $episodes,
        /** @var list<CharacterData> Personajes externos. */
        public array $characters,
    ) {}
}
