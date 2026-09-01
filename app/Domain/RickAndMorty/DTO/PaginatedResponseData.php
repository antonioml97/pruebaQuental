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
    /** Número de página actual, comenzando en uno. */
    public int $currentPage;

    /** Número total de páginas disponibles. */
    public int $totalPages;

    /** Número total de recursos entre todas las páginas. */
    public int $totalItems;

    /** Número de la página siguiente o null al alcanzar el final. */
    public ?int $nextPage;

    /** Número de la página anterior o null al alcanzar el inicio. */
    public ?int $previousPage;

    /** @var list<T> Recursos de dominio contenidos en esta página. */
    public array $items;

    /**
     * Crea una respuesta paginada inmutable.
     *
     * @param  list<T>  $items
     */
    public function __construct(
        int $currentPage,
        int $totalPages,
        int $totalItems,
        ?int $nextPage,
        ?int $previousPage,
        array $items,
    ) {
        $this->currentPage = $currentPage;
        $this->totalPages = $totalPages;
        $this->totalItems = $totalItems;
        $this->nextPage = $nextPage;
        $this->previousPage = $previousPage;
        $this->items = $items;
    }
}
