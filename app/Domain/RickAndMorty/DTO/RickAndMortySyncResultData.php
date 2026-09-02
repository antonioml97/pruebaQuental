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
    /**
     * Crea el resumen inmutable de una sincronización satisfactoria.
     *
     * @param  int  $locationsProcessed  Número de localizaciones procesadas.
     * @param  int  $episodesProcessed  Número de episodios procesados.
     * @param  int  $charactersProcessed  Número de personajes procesados.
     * @param  int  $relationsProcessed  Número final de relaciones personaje-episodio sincronizadas.
     * @param  int  $createdRecords  Número de registros creados.
     * @param  int  $updatedRecords  Número de registros existentes cuyos atributos cambiaron.
     * @param  int  $unchangedRecords  Número de registros existentes que ya estaban actualizados.
     */
    public function __construct(
        /** Número de localizaciones procesadas. */
        public int $locationsProcessed,
        /** Número de episodios procesados. */
        public int $episodesProcessed,
        /** Número de personajes procesados. */
        public int $charactersProcessed,
        /** Número final de relaciones personaje-episodio sincronizadas. */
        public int $relationsProcessed,
        /** Número de registros creados. */
        public int $createdRecords,
        /** Número de registros existentes cuyos atributos cambiaron. */
        public int $updatedRecords,
        /** Número de registros existentes que ya estaban actualizados. */
        public int $unchangedRecords,
    ) {}
}
