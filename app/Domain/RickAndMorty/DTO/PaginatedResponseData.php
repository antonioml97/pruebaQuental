<?php

declare(strict_types=1);

/**
 * Contiene datos de paginación independientes del proveedor.
 */

namespace App\Domain\RickAndMorty\DTO;

/**
 * Página inmutable de objetos del dominio.
 *
 * @template T
 */
final readonly class PaginatedResponseData
{
    /**
     * Crea una respuesta paginada inmutable.
     *
     * @param  int  $currentPage  Número de página actual, comenzando en uno.
     * @param  int  $totalPages  Número total de páginas disponibles.
     * @param  int  $totalItems  Número total de recursos entre todas las páginas.
     * @param  int|null  $nextPage  Número de la página siguiente o null al alcanzar el final.
     * @param  int|null  $previousPage  Número de la página anterior o null al alcanzar el inicio.
     * @param  list<T>  $items  DTOs de los recursos contenidos en la página.
     */
    public function __construct(
        /** Número de página actual, comenzando en uno. */
        public int $currentPage,
        /** Número total de páginas disponibles. */
        public int $totalPages,
        /** Número total de recursos entre todas las páginas. */
        public int $totalItems,
        /** Número de la página siguiente o null al alcanzar el final. */
        public ?int $nextPage,
        /** Número de la página anterior o null al alcanzar el inicio. */
        public ?int $previousPage,
        /** @var list<T> Recursos de dominio contenidos en esta página. */
        public array $items,
    ) {}
}
