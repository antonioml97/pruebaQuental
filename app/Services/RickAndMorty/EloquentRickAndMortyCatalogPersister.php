<?php

declare(strict_types=1);

/**
 * Persiste una fotografía de Rick and Morty mediante Eloquent.
 */

namespace App\Services\RickAndMorty;

use App\Domain\RickAndMorty\Contracts\RickAndMortyCatalogPersisterInterface;
use App\Domain\RickAndMorty\DTO\RickAndMortyCatalogData;
use App\Domain\RickAndMorty\DTO\RickAndMortySyncResultData;
use App\Domain\RickAndMorty\Exceptions\RickAndMortySynchronizationException;
use App\Models\Character;
use App\Models\Episode;
use App\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Resuelve claves locales y guarda recursos y relaciones en una transacción.
 */
final class EloquentRickAndMortyCatalogPersister implements RickAndMortyCatalogPersisterInterface
{
    /**
     * Persiste atómicamente una fotografía completa del proveedor.
     *
     * @throws RickAndMortySynchronizationException
     */
    public function persist(RickAndMortyCatalogData $catalog): RickAndMortySyncResultData
    {
        try {
            return DB::transaction(
                fn (): RickAndMortySyncResultData => $this->persistWithinTransaction($catalog),
            );
        } catch (RickAndMortySynchronizationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw RickAndMortySynchronizationException::persistenceFailed($exception);
        }
    }

    /**
     * Guarda recursos y relaciones dentro de la transacción activa.
     *
     * @throws RickAndMortySynchronizationException Si una referencia no puede resolverse.
     */
    private function persistWithinTransaction(RickAndMortyCatalogData $catalog): RickAndMortySyncResultData
    {
        /** @var array{created: int, updated: int, unchanged: int} $statistics */
        $statistics = ['created' => 0, 'updated' => 0, 'unchanged' => 0];
        $locationIds = [];
        $episodeIds = [];
        $relationsProcessed = 0;

        foreach ($catalog->locations as $data) {
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

        foreach ($catalog->episodes as $data) {
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

        foreach ($catalog->characters as $data) {
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
            locationsProcessed: count($catalog->locations),
            episodesProcessed: count($catalog->episodes),
            charactersProcessed: count($catalog->characters),
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
