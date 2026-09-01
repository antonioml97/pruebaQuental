<?php

declare(strict_types=1);

/**
 * Contiene el resumen observable de una sincronización de Rick and Morty.
 */

namespace App\Domain\RickAndMorty\DTO;

/**
 * Resume recursos procesados y cambios persistidos durante una ejecución completa.
 */
final readonly class RickAndMortySyncResultData
{
    /** Número de localizaciones procesadas. */
    public int $locationsProcessed;

    /** Número de episodios procesados. */
    public int $episodesProcessed;

    /** Número de personajes procesados. */
    public int $charactersProcessed;

    /** Número final de relaciones personaje-episodio sincronizadas. */
    public int $relationsProcessed;

    /** Número de registros creados. */
    public int $createdRecords;

    /** Número de registros existentes cuyos atributos cambiaron. */
    public int $updatedRecords;

    /** Número de registros existentes que ya estaban actualizados. */
    public int $unchangedRecords;

    /**
     * Crea el resumen inmutable de una sincronización satisfactoria.
     */
    public function __construct(
        int $locationsProcessed,
        int $episodesProcessed,
        int $charactersProcessed,
        int $relationsProcessed,
        int $createdRecords,
        int $updatedRecords,
        int $unchangedRecords,
    ) {
        $this->locationsProcessed = $locationsProcessed;
        $this->episodesProcessed = $episodesProcessed;
        $this->charactersProcessed = $charactersProcessed;
        $this->relationsProcessed = $relationsProcessed;
        $this->createdRecords = $createdRecords;
        $this->updatedRecords = $updatedRecords;
        $this->unchangedRecords = $unchangedRecords;
    }
}
