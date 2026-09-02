<?php

declare(strict_types=1);

/**
 * Traduce exclusivamente personajes del proveedor a datos de dominio.
 */

namespace App\Services\RickAndMorty\Mapping;

use App\Domain\Characters\DTO\CharacterData;

/**
 * Traduce exclusivamente personajes del proveedor a datos de dominio.
 */
final class CharacterResponseMapper
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
     * Transforma la respuesta de un personaje.
     *
     * @param  array<string, mixed>  $payload  Objeto JSON externo todavía no validado; solo se aceptan los campos del contrato.
     */
    public function map(array $payload): CharacterData
    {
        return new CharacterData(
            externalId: $this->payload->requirePositiveInt($payload, 'id'),
            name: $this->payload->requireString($payload, 'name'),
            status: $this->payload->requireOneOf($payload, 'status', ['Alive', 'Dead', 'unknown']),
            species: $this->payload->requireString($payload, 'species'),
            type: $this->payload->requireString($payload, 'type', allowEmpty: true),
            gender: $this->payload->requireOneOf($payload, 'gender', ['Female', 'Male', 'Genderless', 'unknown']),
            imageUrl: $this->payload->requireUrl($payload, 'image'),
            originLocationExternalId: $this->payload->mapLocationReference($payload, 'origin'),
            currentLocationExternalId: $this->payload->mapLocationReference($payload, 'location'),
            episodeExternalIds: $this->payload->mapResourceReferences($payload, 'episode', 'episode'),
        );
    }
}
