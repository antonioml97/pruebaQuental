<?php

declare(strict_types=1);

/**
 * Contiene la representación de dominio de una localización de Rick and Morty.
 */

namespace App\Domain\Locations\DTO;

/**
 * Datos inmutables de una localización necesarios para persistencia y sincronización.
 */
final readonly class LocationData
{
    /**
     * Crea una representación inmutable de una localización.
     *
     * @param  int  $externalId  Identificador externo asignado por el proveedor.
     * @param  string  $name  Nombre visible de la localización.
     * @param  string  $type  Tipo de localización definido por el proveedor.
     * @param  string  $dimension  Dimensión que contiene la localización.
     */
    public function __construct(
        /** Identificador externo asignado por el proveedor. */
        public int $externalId,
        /** Nombre visible de la localización. */
        public string $name,
        /** Tipo de localización definido por el proveedor. */
        public string $type,
        /** Dimensión que contiene la localización. */
        public string $dimension,
    ) {}
}
