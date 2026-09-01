<?php

declare(strict_types=1);

/**
 * Define la persistencia de una fotografía del catálogo de Rick and Morty.
 */

namespace App\Domain\RickAndMorty\Contracts;

use App\Domain\RickAndMorty\DTO\RickAndMortyCatalogData;
use App\Domain\RickAndMorty\DTO\RickAndMortySyncResultData;
use App\Domain\RickAndMorty\Exceptions\RickAndMortySynchronizationException;

/**
 * Aísla la estrategia de persistencia respecto al orquestador de sincronización.
 */
interface RickAndMortyCatalogPersisterInterface
{
    /**
     * Persiste atómicamente una fotografía externa completa.
     *
     * @throws RickAndMortySynchronizationException Si la fotografía no puede persistirse.
     */
    public function persist(RickAndMortyCatalogData $catalog): RickAndMortySyncResultData;
}
