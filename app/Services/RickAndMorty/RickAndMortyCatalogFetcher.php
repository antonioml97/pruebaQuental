<?php

declare(strict_types=1);

/**
 * Descarga y valida una fotografía completa del catálogo de Rick and Morty.
 */

namespace App\Services\RickAndMorty;

use App\Domain\Characters\DTO\CharacterData;
use App\Domain\Episodes\DTO\EpisodeData;
use App\Domain\Locations\DTO\LocationData;
use App\Domain\RickAndMorty\Contracts\RickAndMortyCatalogFetcherInterface;
use App\Domain\RickAndMorty\Contracts\RickAndMortyClientInterface;
use App\Domain\RickAndMorty\DTO\PaginatedResponseData;
use App\Domain\RickAndMorty\DTO\RickAndMortyCatalogData;
use App\Domain\RickAndMorty\Exceptions\RickAndMortySynchronizationException;
use Closure;
use Throwable;

/**
 * Recorre páginas externas sin conocer Eloquent ni abrir transacciones.
 */
final class RickAndMortyCatalogFetcher implements RickAndMortyCatalogFetcherInterface
{
    /**
     * Crea el lector sobre el contrato del cliente externo.
     *
     * @param  RickAndMortyClientInterface  $client  Cliente intercambiable del proveedor, sustituible por un doble en pruebas.
     */
    public function __construct(
        /** Cliente del proveedor usado para recorrer páginas de recursos. */
        private readonly RickAndMortyClientInterface $client,
    ) {}

    /**
     * Descarga localizaciones, episodios y personajes completos y validados.
     *
     * @throws RickAndMortySynchronizationException
     */
    public function fetch(): RickAndMortyCatalogData
    {
        return new RickAndMortyCatalogData(
            locations: $this->fetchAll('location', $this->client->fetchLocations(...)),
            episodes: $this->fetchAll('episode', $this->client->fetchEpisodes(...)),
            characters: $this->fetchAll('character', $this->client->fetchCharacters(...)),
        );
    }

    /**
     * Recorre todas las páginas y rechaza conjuntos incompletos o duplicados.
     *
     * @template T of CharacterData|EpisodeData|LocationData
     *
     * @param  string  $resource  Nombre del recurso del proveedor implicado en la operación.
     * @param  Closure(int): PaginatedResponseData<T>  $fetchPage  Lector de una página numerada que devuelve DTOs del recurso solicitado.
     * @return list<T>
     *
     * @throws RickAndMortySynchronizationException
     */
    private function fetchAll(string $resource, Closure $fetchPage): array
    {
        $pageNumber = 1;
        $expectedTotalPages = null;
        $expectedTotalItems = null;
        $externalIds = [];
        $items = [];

        do {
            try {
                $page = $fetchPage($pageNumber);
            } catch (Throwable $exception) {
                throw RickAndMortySynchronizationException::sourceFailed(
                    resource: $resource,
                    page: $pageNumber,
                    previous: $exception,
                );
            }

            $expectedTotalPages ??= $page->totalPages;
            $expectedTotalItems ??= $page->totalItems;

            if (! $this->hasConsistentPagination(
                page: $page,
                requestedPage: $pageNumber,
                expectedTotalPages: $expectedTotalPages,
                expectedTotalItems: $expectedTotalItems,
            )) {
                throw RickAndMortySynchronizationException::invalidPagination($resource, $pageNumber);
            }

            foreach ($page->items as $item) {
                $externalId = $this->externalId($item);

                if (isset($externalIds[$externalId])) {
                    throw RickAndMortySynchronizationException::duplicateExternalId(
                        resource: $resource,
                        externalId: $externalId,
                    );
                }

                $externalIds[$externalId] = true;
                $items[] = $item;
            }

            $pageNumber = $page->nextPage;
        } while ($pageNumber !== null);

        if (count($items) !== $expectedTotalItems) {
            throw RickAndMortySynchronizationException::invalidPagination(
                resource: $resource,
                page: $expectedTotalPages,
            );
        }

        return $items;
    }

    /**
     * Comprueba que la fuente permite recorrer exactamente todas sus páginas.
     *
     * @param  PaginatedResponseData<CharacterData|EpisodeData|LocationData>  $page  Página recibida cuyos metadatos se comparan con los de la descarga.
     * @param  int  $requestedPage  Página solicitada al cliente externo, comenzando en uno.
     * @param  int  $expectedTotalPages  Total de páginas fijado por la primera respuesta de la descarga.
     * @param  int  $expectedTotalItems  Total de recursos fijado por la primera respuesta de la descarga.
     */
    private function hasConsistentPagination(
        PaginatedResponseData $page,
        int $requestedPage,
        int $expectedTotalPages,
        int $expectedTotalItems,
    ): bool {
        if (
            $page->currentPage !== $requestedPage
            || $page->totalPages !== $expectedTotalPages
            || $page->totalItems !== $expectedTotalItems
        ) {
            return false;
        }

        if ($requestedPage < $expectedTotalPages) {
            return $page->nextPage === $requestedPage + 1;
        }

        return $requestedPage === $expectedTotalPages && $page->nextPage === null;
    }

    /**
     * Obtiene el identificador común de los DTOs sincronizables.
     *
     * @param  CharacterData|EpisodeData|LocationData  $item  Recurso de dominio del que se extrae el identificador del proveedor.
     */
    private function externalId(CharacterData|EpisodeData|LocationData $item): int
    {
        return $item->externalId;
    }
}
