<?php

declare(strict_types=1);

/**
 * Sincroniza el catálogo externo de Rick and Morty con la base de datos local.
 */

namespace App\Services\RickAndMorty;

use App\Domain\RickAndMorty\Contracts\RickAndMortyClientInterface;
use App\Domain\RickAndMorty\DTO\CharacterData;
use App\Domain\RickAndMorty\DTO\EpisodeData;
use App\Domain\RickAndMorty\DTO\LocationData;
use App\Domain\RickAndMorty\DTO\PaginatedResponseData;
use App\Domain\RickAndMorty\DTO\RickAndMortySyncResultData;
use App\Domain\RickAndMorty\Exceptions\RickAndMortySynchronizationException;
use App\Models\Character;
use App\Models\Episode;
use App\Models\Location;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Orquesta la descarga completa y una persistencia atómica e idempotente.
 */
final class RickAndMortySyncService
{
    /**
     * Crea el servicio con una dependencia del contrato del proveedor.
     */
    public function __construct(
        private readonly RickAndMortyClientInterface $client,
    ) {}

    /**
     * Descarga todos los recursos y los persiste como una única operación lógica.
     *
     * @throws RickAndMortySynchronizationException Si falla cualquier etapa.
     */
    public function synchronize(): RickAndMortySyncResultData
    {
        $locations = $this->fetchAll(
            resource: 'location',
            fetchPage: $this->client->fetchLocations(...),
        );
        $episodes = $this->fetchAll(
            resource: 'episode',
            fetchPage: $this->client->fetchEpisodes(...),
        );
        $characters = $this->fetchAll(
            resource: 'character',
            fetchPage: $this->client->fetchCharacters(...),
        );

        try {
            return DB::transaction(
                fn (): RickAndMortySyncResultData => $this->persist(
                    locations: $locations,
                    episodes: $episodes,
                    characters: $characters,
                ),
            );
        } catch (RickAndMortySynchronizationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw RickAndMortySynchronizationException::persistenceFailed($exception);
        }
    }

    /**
     * Recorre todas las páginas y rechaza conjuntos incompletos o duplicados.
     *
     * @template T of CharacterData|EpisodeData|LocationData
     *
     * @param  Closure(int): PaginatedResponseData<T>  $fetchPage
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
     * @param  PaginatedResponseData<CharacterData|EpisodeData|LocationData>  $page
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
     */
    private function externalId(CharacterData|EpisodeData|LocationData $item): int
    {
        return $item->externalId;
    }

    /**
     * Persiste recursos y relaciones dentro de la transacción activa.
     *
     * @param  list<LocationData>  $locations
     * @param  list<EpisodeData>  $episodes
     * @param  list<CharacterData>  $characters
     *
     * @throws RickAndMortySynchronizationException Si una referencia no puede resolverse.
     */
    private function persist(array $locations, array $episodes, array $characters): RickAndMortySyncResultData
    {
        /** @var array{created: int, updated: int, unchanged: int} $statistics */
        $statistics = ['created' => 0, 'updated' => 0, 'unchanged' => 0];
        $locationIds = [];
        $episodeIds = [];
        $relationsProcessed = 0;

        foreach ($locations as $data) {
            $location = Location::query()->updateOrCreate(
                ['external_id' => $data->externalId],
                [
                    'name' => $data->name,
                    'type' => $data->type,
                    'dimension' => $data->dimension,
                ],
            );

            $this->recordPersistenceResult($location, $statistics);
            $locationIds[$data->externalId] = (int) $location->getKey();
        }

        foreach ($episodes as $data) {
            $episode = Episode::query()->updateOrCreate(
                ['external_id' => $data->externalId],
                [
                    'name' => $data->name,
                    'air_date' => $data->airDate,
                    'code' => $data->code,
                ],
            );

            $this->recordPersistenceResult($episode, $statistics);
            $episodeIds[$data->externalId] = (int) $episode->getKey();
        }

        foreach ($characters as $data) {
            $character = Character::query()->updateOrCreate(
                ['external_id' => $data->externalId],
                [
                    'name' => $data->name,
                    'status' => $data->status,
                    'species' => $data->species,
                    'type' => $data->type,
                    'gender' => $data->gender,
                    'image_url' => $data->imageUrl,
                    'origin_location_id' => $this->optionalReference(
                        idsByExternalId: $locationIds,
                        externalId: $data->originLocationExternalId,
                        resource: 'location',
                        characterExternalId: $data->externalId,
                    ),
                    'current_location_id' => $this->optionalReference(
                        idsByExternalId: $locationIds,
                        externalId: $data->currentLocationExternalId,
                        resource: 'location',
                        characterExternalId: $data->externalId,
                    ),
                ],
            );

            $this->recordPersistenceResult($character, $statistics);

            $characterEpisodeIds = [];

            foreach ($data->episodeExternalIds as $episodeExternalId) {
                $characterEpisodeIds[] = $this->requiredReference(
                    idsByExternalId: $episodeIds,
                    externalId: $episodeExternalId,
                    resource: 'episode',
                    characterExternalId: $data->externalId,
                );
            }

            $character->episodes()->sync($characterEpisodeIds);
            $relationsProcessed += count($characterEpisodeIds);
        }

        return new RickAndMortySyncResultData(
            locationsProcessed: count($locations),
            episodesProcessed: count($episodes),
            charactersProcessed: count($characters),
            relationsProcessed: $relationsProcessed,
            createdRecords: $statistics['created'],
            updatedRecords: $statistics['updated'],
            unchangedRecords: $statistics['unchanged'],
        );
    }

    /**
     * Clasifica un guardado como creación, actualización o ausencia de cambios.
     *
     * @param  array{created: int, updated: int, unchanged: int}  $statistics
     */
    private function recordPersistenceResult(Model $model, array &$statistics): void
    {
        if ($model->wasRecentlyCreated) {
            $statistics['created']++;

            return;
        }

        if ($model->wasChanged()) {
            $statistics['updated']++;

            return;
        }

        $statistics['unchanged']++;
    }

    /**
     * Resuelve una referencia externa opcional como clave local.
     *
     * @param  array<int, int>  $idsByExternalId
     *
     * @throws RickAndMortySynchronizationException Si la referencia no existe.
     */
    private function optionalReference(
        array $idsByExternalId,
        ?int $externalId,
        string $resource,
        int $characterExternalId,
    ): ?int {
        if ($externalId === null) {
            return null;
        }

        return $this->requiredReference(
            idsByExternalId: $idsByExternalId,
            externalId: $externalId,
            resource: $resource,
            characterExternalId: $characterExternalId,
        );
    }

    /**
     * Resuelve una referencia externa obligatoria como clave local.
     *
     * @param  array<int, int>  $idsByExternalId
     *
     * @throws RickAndMortySynchronizationException Si la referencia no existe.
     */
    private function requiredReference(
        array $idsByExternalId,
        int $externalId,
        string $resource,
        int $characterExternalId,
    ): int {
        if (! array_key_exists($externalId, $idsByExternalId)) {
            throw RickAndMortySynchronizationException::missingReference(
                resource: $resource,
                externalId: $externalId,
                characterExternalId: $characterExternalId,
            );
        }

        return $idsByExternalId[$externalId];
    }
}
