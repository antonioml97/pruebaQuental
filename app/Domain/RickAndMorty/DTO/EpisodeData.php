<?php

declare(strict_types=1);

/**
 * Contiene la representación de dominio de un episodio de Rick and Morty.
 */

namespace App\Domain\RickAndMorty\DTO;

use DateTimeImmutable;

/**
 * Datos inmutables de un episodio necesarios para persistencia y sincronización.
 */
final readonly class EpisodeData
{
    /** Identificador externo asignado por el proveedor. */
    public int $externalId;

    /** Título del episodio. */
    public string $name;

    /** Fecha de emisión normalizada con independencia del formato del proveedor. */
    public DateTimeImmutable $airDate;

    /** Código canónico del episodio, como S01E01. */
    public string $code;

    /** Crea una representación inmutable de un episodio. */
    public function __construct(int $externalId, string $name, DateTimeImmutable $airDate, string $code)
    {
        $this->externalId = $externalId;
        $this->name = $name;
        $this->airDate = $airDate;
        $this->code = $code;
    }
}
