<?php

declare(strict_types=1);

/**
 * Traduce localizaciones conservando los campos descriptivos vacíos válidos.
 */

namespace App\Services\RickAndMorty\Mapping;

use App\Domain\Locations\DTO\LocationData;

/**
 * Traduce localizaciones conservando los campos descriptivos vacíos válidos.
 */
final class LocationResponseMapper
{
    /** Lector común de campos y referencias externas. */
    private readonly ResponsePayloadReader $payload;

    /**
     * Recibe el colaborador específico del caso de uso.
     *
     * @param  ResponsePayloadReader  $payload  Lector compartido que valida tipos, campos y referencias del proveedor.
     */
    public function __construct(ResponsePayloadReader $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Transforma la respuesta de una localización.
     *
     * @param  array<string, mixed>  $payload  Objeto JSON externo todavía no validado; solo se aceptan los campos del contrato.
     */
    public function map(array $payload): LocationData
    {
        return new LocationData(
            externalId: $this->payload->requirePositiveInt($payload, 'id'),
            name: $this->payload->requireString($payload, 'name'),
            type: $this->payload->requireString($payload, 'type', allowEmpty: true),
            dimension: $this->payload->requireString($payload, 'dimension', allowEmpty: true),
        );
    }
}
