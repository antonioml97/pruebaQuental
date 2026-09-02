<?php

declare(strict_types=1);

/**
 * Persiste una fotografía de Rick and Morty mediante Eloquent.
 */

namespace App\Services\RickAndMorty;

use App\Domain\Characters\DTO\CharacterData;
use App\Domain\Episodes\DTO\EpisodeData;
use App\Domain\Locations\DTO\LocationData;
use App\Domain\RickAndMorty\Contracts\RickAndMortyCatalogPersisterInterface;
use App\Domain\RickAndMorty\DTO\RickAndMortyCatalogData;
use App\Domain\RickAndMorty\DTO\RickAndMortySyncResultData;
use App\Domain\RickAndMorty\Exceptions\RickAndMortySynchronizationException;
use App\Services\Characters\CharacterPersister;
use App\Services\Episodes\EpisodePersister;
use App\Services\Locations\LocationPersister;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Resuelve claves locales y guarda recursos y relaciones en una transacción.
 */
final class EloquentRickAndMortyCatalogPersister implements RickAndMortyCatalogPersisterInterface
{
    /**
     * Recibe los escritores por entidad sin delegar la frontera transaccional.
     *
     * @param  LocationPersister  $locations  Escritor de localizaciones que participa en la transacción del catálogo.
     * @param  EpisodePersister  $episodes  Escritor de episodios que participa en la transacción del catálogo.
     * @param  CharacterPersister  $characters  Escritor de personajes y relaciones dentro de la transacción del catálogo.
     */
    public function __construct(
        /** Escritor de localizaciones. */
        private readonly LocationPersister $locations,
        /** Escritor de episodios. */
        private readonly EpisodePersister $episodes,
        /** Escritor de personajes y sus relaciones. */
        private readonly CharacterPersister $characters,
    ) {}

    /**
     * Persiste atómicamente una fotografía completa del proveedor.
     *
     * @param  RickAndMortyCatalogData  $catalog  Catálogo completo descargado antes de iniciar las escrituras.
     *
     * @throws RickAndMortySynchronizationException
     */
    public function persist(RickAndMortyCatalogData $catalog): RickAndMortySyncResultData
    {
        try {
            return DB::transaction(
                /**
                 * Ejecuta todos los guardados bajo la misma transacción del catálogo.
                 */
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
     * @param  RickAndMortyCatalogData  $catalog  Catálogo completo descargado antes de iniciar las escrituras.
     *
     * @throws RickAndMortySynchronizationException Si una referencia no puede resolverse.
     */
    private function persistWithinTransaction(RickAndMortyCatalogData $catalog): RickAndMortySyncResultData
    {
        /** @var array{created: int, updated: int, unchanged: int} $statistics */
        $statistics = ['created' => 0, 'updated' => 0, 'unchanged' => 0];
        $locationIds = $this->persistLocations($catalog->locations, $statistics);
        $episodeIds = $this->persistEpisodes($catalog->episodes, $statistics);
        $relationsProcessed = $this->persistCharacters(
            $catalog->characters, $locationIds, $episodeIds, $statistics,
        );

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
     * Guarda localizaciones y construye la correspondencia de claves para los personajes.
     *
     * @param  list<LocationData>  $locations  Localizaciones externas del catálogo completo.
     * @param  array{created: int, updated: int, unchanged: int}  $statistics  Contadores compartidos que se actualizan por referencia.
     * @return array<int, int> Claves locales indexadas por el identificador externo.
     */
    private function persistLocations(array $locations, array &$statistics): array
    {
        $locationIds = [];

        foreach ($locations as $data) {
            $location = $this->locations->persist($data);

            $this->recordPersistenceResult($location, $statistics);
            $locationIds[$data->externalId] = (int) $location->getKey();
        }

        return $locationIds;
    }

    /**
     * Guarda episodios antes de resolver las relaciones de los personajes.
     *
     * @param  list<EpisodeData>  $episodes  Episodios externos del catálogo completo.
     * @param  array{created: int, updated: int, unchanged: int}  $statistics  Contadores compartidos que se actualizan por referencia.
     * @return array<int, int> Claves locales indexadas por el identificador externo.
     */
    private function persistEpisodes(array $episodes, array &$statistics): array
    {
        $episodeIds = [];

        foreach ($episodes as $data) {
            $episode = $this->episodes->persist($data);

            $this->recordPersistenceResult($episode, $statistics);
            $episodeIds[$data->externalId] = (int) $episode->getKey();
        }

        return $episodeIds;
    }

    /**
     * Resuelve referencias y guarda los personajes dentro de la transacción del catálogo.
     *
     * @param  list<CharacterData>  $characters  Personajes externos y sus referencias.
     * @param  array<int, int>  $locationIds  Claves locales de localizaciones indexadas por su identificador externo.
     * @param  array<int, int>  $episodeIds  Claves locales de episodios indexadas por su identificador externo.
     * @param  array{created: int, updated: int, unchanged: int}  $statistics  Contadores compartidos que se actualizan por referencia.
     * @return int Número de relaciones personaje-episodio sincronizadas.
     *
     * @throws RickAndMortySynchronizationException Si una referencia no está presente en el catálogo.
     */
    private function persistCharacters(
        array $characters,
        array $locationIds,
        array $episodeIds,
        array &$statistics,
    ): int {
        $relationsProcessed = 0;

        foreach ($characters as $data) {
            $originLocationId = $this->optionalReference(
                $locationIds, $data->originLocationExternalId, 'location', $data->externalId,
            );
            $currentLocationId = $this->optionalReference(
                $locationIds, $data->currentLocationExternalId, 'location', $data->externalId,
            );
            $characterEpisodeIds = [];

            foreach ($data->episodeExternalIds as $episodeExternalId) {
                $characterEpisodeIds[] = $this->requiredReference(
                    $episodeIds, $episodeExternalId, 'episode', $data->externalId,
                );
            }

            $character = $this->characters->persist(
                $data, $originLocationId, $currentLocationId, $characterEpisodeIds,
            );
            $this->recordPersistenceResult($character, $statistics);
            $relationsProcessed += count($characterEpisodeIds);
        }

        return $relationsProcessed;
    }

    /**
     * Clasifica un guardado como creación, actualización o ausencia de cambios.
     *
     * @param  Model  $model  Modelo recién guardado cuyo estado permite distinguir altas y cambios.
     * @param  array{created: int, updated: int, unchanged: int}  $statistics  Contadores acumulados que se modifican por referencia tras cada guardado.
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
     * @param  array<int, int>  $idsByExternalId  Correspondencia entre identificadores externos y claves locales ya persistidas.
     * @param  int|null  $externalId  Identificador público del proveedor, o null si se desconoce la referencia.
     * @param  string  $resource  Nombre del recurso del proveedor implicado en la operación.
     * @param  int  $characterExternalId  Identificador externo del personaje cuya referencia se está resolviendo.
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
     * @param  array<int, int>  $idsByExternalId  Correspondencia entre identificadores externos y claves locales ya persistidas.
     * @param  int  $externalId  Identificador público del proveedor, no la clave local de Eloquent.
     * @param  string  $resource  Nombre del recurso del proveedor implicado en la operación.
     * @param  int  $characterExternalId  Identificador externo del personaje cuya referencia se está resolviendo.
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
