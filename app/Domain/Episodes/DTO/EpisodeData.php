<?php

declare(strict_types=1);

/**
 * Contiene la representación de dominio de un episodio de Rick and Morty.
 */

namespace App\Domain\Episodes\DTO;

use DateTimeImmutable;

/**
 * Datos inmutables de un episodio necesarios para persistencia y sincronización.
 */
final readonly class EpisodeData
{
    /**
     * Crea una representación inmutable de un episodio.
     *
     * @param  int  $externalId  Identificador externo asignado por el proveedor.
     * @param  string  $name  Título del episodio.
     * @param  DateTimeImmutable  $airDate  Fecha de emisión normalizada con independencia del formato del proveedor.
     * @param  string  $code  Código canónico del episodio, como S01E01.
     */
    public function __construct(
        /** Identificador externo asignado por el proveedor. */
        public int $externalId,
        /** Título del episodio. */
        public string $name,
        /** Fecha de emisión normalizada con independencia del formato del proveedor. */
        public DateTimeImmutable $airDate,
        /** Código canónico del episodio, como S01E01. */
        public string $code,
    ) {}
}
