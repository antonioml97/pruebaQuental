<?php

declare(strict_types=1);

/**
 * Contiene la representación de dominio de una localización de Rick and Morty.
 */

namespace App\Domain\RickAndMorty\DTO;

/**
 * Datos inmutables de una localización necesarios para persistencia y sincronización.
 */
final readonly class LocationData
{
    /** Identificador externo asignado por el proveedor. */
    public int $externalId;

    /** Nombre visible de la localización. */
    public string $name;

    /** Tipo de localización definido por el proveedor. */
    public string $type;

    /** Dimensión que contiene la localización. */
    public string $dimension;

    /** Crea una representación inmutable de una localización. */
    public function __construct(int $externalId, string $name, string $type, string $dimension)
    {
        $this->externalId = $externalId;
        $this->name = $name;
        $this->type = $type;
        $this->dimension = $dimension;
    }
}
