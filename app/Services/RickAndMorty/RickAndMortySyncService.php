<?php

declare(strict_types=1);

/**
 * Orquesta una sincronización completa del catálogo de Rick and Morty.
 */

namespace App\Services\RickAndMorty;

use App\Domain\RickAndMorty\Contracts\RickAndMortyCatalogFetcherInterface;
use App\Domain\RickAndMorty\Contracts\RickAndMortyCatalogPersisterInterface;
use App\Domain\RickAndMorty\DTO\RickAndMortySyncResultData;
use App\Domain\RickAndMorty\Exceptions\RickAndMortySynchronizationException;

/**
 * Coordina lectura y persistencia sin conocer HTTP, paginación, Eloquent ni transacciones.
 */
final class RickAndMortySyncService
{
    /**
     * Crea el orquestador sobre abstracciones de lectura y persistencia.
     */
    public function __construct(
        private readonly RickAndMortyCatalogFetcherInterface $fetcher,
        private readonly RickAndMortyCatalogPersisterInterface $persister,
    ) {}

    /**
     * Obtiene una fotografía completa y delega su persistencia atómica.
     *
     * @throws RickAndMortySynchronizationException Si falla cualquier etapa.
     */
    public function synchronize(): RickAndMortySyncResultData
    {
        return $this->persister->persist($this->fetcher->fetch());
    }
}
