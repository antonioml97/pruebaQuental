<?php

declare(strict_types=1);

/**
 * Contiene los filtros validados para consultar personajes.
 */

namespace App\Domain\Characters\DTO;

/**
 * Transporta criterios de consulta independientes del protocolo HTTP.
 */
final readonly class CharacterFiltersData
{
    /**
     * Crea filtros de consulta ya validados por la frontera HTTP.
     *
     * @param  string|null  $name  Nombre parcial solicitado o null cuando no se filtra.
     * @param  string|null  $status  Estado vital exacto o null cuando no se filtra.
     * @param  string|null  $species  Especie exacta o null cuando no se filtra.
     * @param  string|null  $gender  Género exacto o null cuando no se filtra.
     * @param  int  $perPage  Número máximo de elementos por página.
     */
    public function __construct(
        /** Nombre parcial solicitado o null cuando no se filtra. */
        public ?string $name,
        /** Estado vital exacto o null cuando no se filtra. */
        public ?string $status,
        /** Especie exacta o null cuando no se filtra. */
        public ?string $species,
        /** Género exacto o null cuando no se filtra. */
        public ?string $gender,
        /** Número máximo de elementos por página. */
        public int $perPage,
    ) {}
}
