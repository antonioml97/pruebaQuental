<?php

declare(strict_types=1);

/**
 * Contiene los filtros validados para consultar personajes.
 */

namespace App\Domain\RickAndMorty\DTO;

/**
 * Transporta criterios de consulta independientes del protocolo HTTP.
 */
final readonly class CharacterFiltersData
{
    /** Nombre parcial solicitado o null cuando no se filtra. */
    public ?string $name;

    /** Estado vital exacto o null cuando no se filtra. */
    public ?string $status;

    /** Especie exacta o null cuando no se filtra. */
    public ?string $species;

    /** Género exacto o null cuando no se filtra. */
    public ?string $gender;

    /** Número máximo de elementos por página. */
    public int $perPage;

    /**
     * Crea filtros de consulta ya validados por la frontera HTTP.
     */
    public function __construct(
        ?string $name,
        ?string $status,
        ?string $species,
        ?string $gender,
        int $perPage,
    ) {
        $this->name = $name;
        $this->status = $status;
        $this->species = $species;
        $this->gender = $gender;
        $this->perPage = $perPage;
    }
}
