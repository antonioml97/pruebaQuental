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
    /**
     * Crea una representación inmutable de un personaje.
     *
     * @param  int  $externalId  Identificador externo asignado por el proveedor.
     * @param  string  $name  Nombre visible del personaje.
     * @param  string  $status  Estado vital del personaje.
     * @param  string  $species  Especie del personaje.
     * @param  string  $type  Subtipo del personaje, que puede estar vacío.
     * @param  string  $gender  Género del personaje.
     * @param  string  $imageUrl  URL pública de la imagen del personaje.
     * @param  int|null  $originLocationExternalId  Identificador externo de la localización de origen, si se conoce.
     * @param  int|null  $currentLocationExternalId  Identificador externo de la localización actual, si se conoce.
     * @param  list<int>  $episodeExternalIds  Identificadores externos de los episodios asociados, no claves locales.
     */
    public function __construct(
        /** Identificador externo asignado por el proveedor. */
        public int $externalId,
        /** Nombre visible del personaje. */
        public string $name,
        /** Estado vital del personaje. */
        public string $status,
        /** Especie del personaje. */
        public string $species,
        /** Subtipo del personaje, que puede estar vacío. */
        public string $type,
        /** Género del personaje. */
        public string $gender,
        /** URL pública de la imagen del personaje. */
        public string $imageUrl,
        /** Identificador externo de la localización de origen, si se conoce. */
        public ?int $originLocationExternalId,
        /** Identificador externo de la localización actual, si se conoce. */
        public ?int $currentLocationExternalId,
        /** @var list<int> Identificadores externos de los episodios del personaje. */
        public array $episodeExternalIds,
    ) {}
}
