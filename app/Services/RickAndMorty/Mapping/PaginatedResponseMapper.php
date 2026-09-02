<?php

declare(strict_types=1);

/**
 * Valida los metadatos de una página y delega la transformación de sus elementos.
 */

namespace App\Services\RickAndMorty\Mapping;

use App\Domain\RickAndMorty\DTO\PaginatedResponseData;

/**
 * Valida los metadatos de una página y delega la transformación de sus elementos.
 */
final class PaginatedResponseMapper
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
     * Transforma los metadatos de paginación y los recursos del proveedor.
     *
     * @template T
     *
     * @param  array<string, mixed>  $payload  Objeto JSON externo todavía no validado; solo se aceptan los campos del contrato.
     * @param  int  $currentPage  Número de la página representada, comenzando en uno.
     * @param  callable(array<string, mixed>): T  $itemMapper  Transformador de cada recurso al tipo de dominio de esta página.
     * @return PaginatedResponseData<T>
     */
    public function map(array $payload, int $currentPage, callable $itemMapper): PaginatedResponseData
    {
        if ($currentPage < 1) {
            throw $this->payload->invalid('page', 'debe ser un entero positivo');
        }

        $info = $this->payload->requireArray($payload, 'info');
        $results = $this->payload->requireArray($payload, 'results');

        if (! array_is_list($results)) {
            throw $this->payload->invalid('results', 'debe ser una lista');
        }

        $totalPages = $this->payload->requirePositiveInt($info, 'pages');
        $totalItems = $this->payload->requireNonNegativeInt($info, 'count');

        if ($currentPage > $totalPages) {
            throw $this->payload->invalid('page', 'no puede superar el número total de páginas');
        }

        $nextPage = $this->mapPageReference($info, 'next');
        $previousPage = $this->mapPageReference($info, 'prev');

        if ($nextPage !== null && $nextPage !== $currentPage + 1) {
            throw $this->payload->invalid('info.next', 'no corresponde a la página actual');
        }

        if ($previousPage !== null && $previousPage !== $currentPage - 1) {
            throw $this->payload->invalid('info.prev', 'no corresponde a la página actual');
        }

        if ($currentPage < $totalPages && $nextPage === null) {
            throw $this->payload->invalid('info.next', 'es obligatorio antes de la última página');
        }

        if ($currentPage === $totalPages && $nextPage !== null) {
            throw $this->payload->invalid('info.next', 'debe ser null en la última página');
        }

        if ($currentPage > 1 && $previousPage === null) {
            throw $this->payload->invalid('info.prev', 'es obligatorio después de la primera página');
        }

        $items = [];

        foreach ($results as $index => $result) {
            if (! is_array($result)) {
                throw $this->payload->invalid("results.$index", 'debe ser un objeto');
            }

            /** @var array<string, mixed> $result */
            $items[] = $itemMapper($result);
        }

        return new PaginatedResponseData(
            currentPage: $currentPage,
            totalPages: $totalPages,
            totalItems: $totalItems,
            nextPage: $nextPage,
            previousPage: $previousPage,
            items: $items,
        );
    }

    /**
     * Extrae el número de página desde una URL de paginación opcional.
     *
     * @param  array<string, mixed>  $info  Metadatos externos con las referencias de paginación.
     * @param  string  $field  Nombre del campo que se valida y se identifica en los errores.
     */
    private function mapPageReference(array $info, string $field): ?int
    {
        if (! array_key_exists($field, $info)) {
            throw $this->payload->invalid("info.$field", 'es obligatorio');
        }

        if ($info[$field] === null) {
            return null;
        }

        if (! is_string($info[$field]) || ! $this->payload->isHttpUrl($info[$field])) {
            throw $this->payload->invalid("info.$field", 'debe ser null o una URL HTTP válida');
        }

        $query = parse_url($info[$field], PHP_URL_QUERY);
        parse_str(is_string($query) ? $query : '', $parameters);
        $page = filter_var($parameters['page'] ?? null, FILTER_VALIDATE_INT);

        if (! is_int($page) || $page < 1) {
            throw $this->payload->invalid("info.$field", 'debe contener un parámetro de página positivo');
        }

        return $page;
    }
}
