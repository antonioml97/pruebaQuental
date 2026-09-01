<?php

declare(strict_types=1);

/**
 * Contiene la representación de dominio de un personaje de Rick and Morty.
 */

namespace App\Domain\Characters\DTO;

/**
 * Datos inmutables de un personaje necesarios para persistencia y sincronización.
 */
final readonly class CharacterData
{
    /** Identificador externo asignado por el proveedor. */
    public int $externalId;

    /** Nombre visible del personaje. */
    public string $name;

    /** Estado vital del personaje. */
    public string $status;

    /** Especie del personaje. */
    public string $species;

    /** Subtipo del personaje, que puede estar vacío. */
    public string $type;

    /** Género del personaje. */
    public string $gender;

    /** URL pública de la imagen del personaje. */
    public string $imageUrl;

    /** Identificador externo de la localización de origen, si se conoce. */
    public ?int $originLocationExternalId;

    /** Identificador externo de la localización actual, si se conoce. */
    public ?int $currentLocationExternalId;

    /** @var list<int> Identificadores externos de los episodios del personaje. */
    public array $episodeExternalIds;

    /**
     * Crea una representación inmutable de un personaje.
     *
     * @param  list<int>  $episodeExternalIds
     */
    public function __construct(
        int $externalId,
        string $name,
        string $status,
        string $species,
        string $type,
        string $gender,
        string $imageUrl,
        ?int $originLocationExternalId,
        ?int $currentLocationExternalId,
        array $episodeExternalIds,
    ) {
        $this->externalId = $externalId;
        $this->name = $name;
        $this->status = $status;
        $this->species = $species;
        $this->type = $type;
        $this->gender = $gender;
        $this->imageUrl = $imageUrl;
        $this->originLocationExternalId = $originLocationExternalId;
        $this->currentLocationExternalId = $currentLocationExternalId;
        $this->episodeExternalIds = $episodeExternalIds;
    }
}
