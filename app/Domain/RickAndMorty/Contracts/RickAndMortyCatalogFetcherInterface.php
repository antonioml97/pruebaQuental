<?php

declare(strict_types=1);

/**
 * Define la obtención de una fotografía completa del catálogo externo.
 */

namespace App\Domain\RickAndMorty\Contracts;

use App\Domain\RickAndMorty\DTO\RickAndMortyCatalogData;
use App\Domain\RickAndMorty\Exceptions\RickAndMortySynchronizationException;

/**
 * Separa la lectura y validación del proveedor de la orquestación de sincronización.
 */
interface RickAndMortyCatalogFetcherInterface
{
    /**
     * Descarga todos los recursos necesarios para una sincronización.
     *
     * @throws RickAndMortySynchronizationException Si la fotografía externa es incompleta.
     */
    public function fetch(): RickAndMortyCatalogData;
}
