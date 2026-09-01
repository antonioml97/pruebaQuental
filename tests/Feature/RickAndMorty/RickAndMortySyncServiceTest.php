<?php

declare(strict_types=1);

/**
 * Verifica la sincronización transaccional de Rick and Morty.
 */

namespace Tests\Feature\RickAndMorty;

use App\Domain\RickAndMorty\Contracts\RickAndMortyClientInterface;
use App\Domain\RickAndMorty\DTO\CharacterData;
use App\Domain\RickAndMorty\DTO\EpisodeData;
use App\Domain\RickAndMorty\DTO\LocationData;
use App\Domain\RickAndMorty\DTO\PaginatedResponseData;
use App\Domain\RickAndMorty\Exceptions\RickAndMortyRequestException;
use App\Domain\RickAndMorty\Exceptions\RickAndMortySynchronizationException;
use App\Models\Character;
use App\Models\Location;
use App\Services\RickAndMorty\RickAndMortySyncService;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Cubre paginación, idempotencia, actualizaciones, relaciones y rollback ante fallos.
 */
final class RickAndMortySyncServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que se recorren todas las páginas sin duplicar registros al repetir la ejecución.
     */
    public function test_it_synchronizes_all_pages_idempotently(): void
    {
        $client = $this->clientMock();
        $firstLocationPage = $this->page(
            items: [$this->locationData(1, 'Earth')],
            currentPage: 1,
            totalPages: 2,
            totalItems: 2,
            nextPage: 2,
        );
        $secondLocationPage = $this->page(
            items: [$this->locationData(2, 'Citadel of Ricks')],
            currentPage: 2,
            totalPages: 2,
            totalItems: 2,
            previousPage: 1,
        );
        $episodes = $this->page(items: [
            $this->episodeData(1, 'Pilot', 'S01E01'),
            $this->episodeData(2, 'Lawnmower Dog', 'S01E02'),
        ]);
        $characters = $this->page(items: [
            $this->characterData(
                externalId: 1,
                name: 'Rick Sanchez',
                originLocationExternalId: 1,
                currentLocationExternalId: 2,
                episodeExternalIds: [1, 2],
            ),
        ]);

        $client->shouldReceive('fetchLocations')->with(1)->twice()->andReturn($firstLocationPage);
        $client->shouldReceive('fetchLocations')->with(2)->twice()->andReturn($secondLocationPage);
        $client->shouldReceive('fetchEpisodes')->with(1)->twice()->andReturn($episodes);
        $client->shouldReceive('fetchCharacters')->with(1)->twice()->andReturn($characters);

        $service = new RickAndMortySyncService($client);
        $firstResult = $service->synchronize();
        $secondResult = $service->synchronize();

        $this->assertSame(5, $firstResult->createdRecords);
        $this->assertSame(0, $firstResult->updatedRecords);
        $this->assertSame(5, $secondResult->unchangedRecords);
        $this->assertSame(2, $secondResult->locationsProcessed);
        $this->assertSame(2, $secondResult->relationsProcessed);
        $this->assertDatabaseCount('locations', 2);
        $this->assertDatabaseCount('episodes', 2);
        $this->assertDatabaseCount('characters', 1);
        $this->assertDatabaseCount('character_episode', 2);

        $character = Character::query()->where('external_id', 1)->firstOrFail();
        $this->assertSame(1, $character->origin?->external_id);
        $this->assertSame(2, $character->currentLocation?->external_id);
        $this->assertEqualsCanonicalizing([1, 2], $character->episodes->pluck('external_id')->all());
    }

    /**
     * Verifica que los cambios externos actualizan atributos y sustituyen relaciones obsoletas.
     */
    public function test_it_updates_existing_records_and_relationships(): void
    {
        $client = $this->clientMock();
        $initialLocations = $this->page(items: [
            $this->locationData(1, 'Earth'),
            $this->locationData(2, 'Citadel'),
        ]);
        $updatedLocations = $this->page(items: [
            $this->locationData(1, 'Earth C-137'),
            $this->locationData(2, 'Citadel'),
        ]);
        $initialEpisodes = $this->page(items: [
            $this->episodeData(1, 'Pilot', 'S01E01'),
            $this->episodeData(2, 'Lawnmower Dog', 'S01E02'),
        ]);
        $updatedEpisodes = $this->page(items: [
            $this->episodeData(1, 'Pilot remastered', 'S01E01'),
            $this->episodeData(2, 'Lawnmower Dog', 'S01E02'),
        ]);
        $initialCharacters = $this->page(items: [
            $this->characterData(1, 'Rick', 1, 1, [1]),
        ]);
        $updatedCharacters = $this->page(items: [
            $this->characterData(1, 'Rick Sanchez', 1, 2, [2]),
        ]);

        $client->shouldReceive('fetchLocations')->with(1)->twice()
            ->andReturn($initialLocations, $updatedLocations);
        $client->shouldReceive('fetchEpisodes')->with(1)->twice()
            ->andReturn($initialEpisodes, $updatedEpisodes);
        $client->shouldReceive('fetchCharacters')->with(1)->twice()
            ->andReturn($initialCharacters, $updatedCharacters);

        $service = new RickAndMortySyncService($client);
        $service->synchronize();
        $result = $service->synchronize();

        $this->assertSame(3, $result->updatedRecords);
        $this->assertSame(2, $result->unchangedRecords);
        $this->assertSame(1, $result->relationsProcessed);
        $this->assertDatabaseHas('locations', ['external_id' => 1, 'name' => 'Earth C-137']);
        $this->assertDatabaseHas('episodes', ['external_id' => 1, 'name' => 'Pilot remastered']);

        $character = Character::query()->where('external_id', 1)->firstOrFail();
        $this->assertSame('Rick Sanchez', $character->name);
        $this->assertSame(2, $character->currentLocation?->external_id);
        $this->assertSame([2], $character->episodes->pluck('external_id')->all());
        $this->assertDatabaseCount('locations', 2);
        $this->assertDatabaseCount('episodes', 2);
        $this->assertDatabaseCount('characters', 1);
        $this->assertDatabaseCount('character_episode', 1);
    }

    /**
     * Verifica que un fallo de descarga se contextualiza antes de modificar la base de datos.
     */
    public function test_it_leaves_the_database_untouched_when_fetching_fails(): void
    {
        Location::query()->create([
            'external_id' => 99,
            'name' => 'Existing location',
            'type' => 'Planet',
            'dimension' => 'Existing dimension',
        ]);

        $client = $this->clientMock();
        $client->shouldReceive('fetchLocations')->with(1)->once()->andReturn(
            $this->page(items: [$this->locationData(1, 'Earth')]),
        );
        $client->shouldReceive('fetchEpisodes')->with(1)->once()->andThrow(
            RickAndMortyRequestException::unexpectedStatus('episode', 503),
        );
        $client->shouldNotReceive('fetchCharacters');

        try {
            (new RickAndMortySyncService($client))->synchronize();
            $this->fail('A controlled synchronization exception was expected.');
        } catch (RickAndMortySynchronizationException $exception) {
            $this->assertSame('fetch', $exception->stage);
            $this->assertSame('episode', $exception->resource);
            $this->assertSame(1, $exception->page);
            $this->assertInstanceOf(RickAndMortyRequestException::class, $exception->getPrevious());
        }

        $this->assertDatabaseCount('locations', 1);
        $this->assertDatabaseHas('locations', ['external_id' => 99]);
        $this->assertDatabaseCount('episodes', 0);
        $this->assertDatabaseCount('characters', 0);
    }

    /**
     * Verifica que una referencia externa ausente revierte todos los cambios de la transacción.
     */
    public function test_it_rolls_back_when_a_character_reference_is_missing(): void
    {
        $client = $this->clientMock();
        $client->shouldReceive('fetchLocations')->with(1)->once()->andReturn(
            $this->page(items: [$this->locationData(1, 'Earth')]),
        );
        $client->shouldReceive('fetchEpisodes')->with(1)->once()->andReturn(
            $this->page(items: [$this->episodeData(1, 'Pilot', 'S01E01')]),
        );
        $client->shouldReceive('fetchCharacters')->with(1)->once()->andReturn(
            $this->page(items: [$this->characterData(1, 'Rick', null, 999, [1])]),
        );

        try {
            (new RickAndMortySyncService($client))->synchronize();
            $this->fail('A controlled synchronization exception was expected.');
        } catch (RickAndMortySynchronizationException $exception) {
            $this->assertSame('validation', $exception->stage);
            $this->assertSame('location', $exception->resource);
            $this->assertStringContainsString('character [1]', $exception->getMessage());
        }

        $this->assertDatabaseCount('locations', 0);
        $this->assertDatabaseCount('episodes', 0);
        $this->assertDatabaseCount('characters', 0);
        $this->assertDatabaseCount('character_episode', 0);
    }

    /**
     * Crea un doble del contrato externo sin permitir llamadas no declaradas.
     *
     * @return RickAndMortyClientInterface&MockInterface
     */
    private function clientMock(): RickAndMortyClientInterface
    {
        /** @var RickAndMortyClientInterface&MockInterface $client */
        $client = Mockery::mock(RickAndMortyClientInterface::class);

        return $client;
    }

    /**
     * Crea una página de DTOs coherente para el doble del cliente.
     *
     * @template T
     *
     * @param  list<T>  $items
     * @return PaginatedResponseData<T>
     */
    private function page(
        array $items,
        int $currentPage = 1,
        int $totalPages = 1,
        ?int $totalItems = null,
        ?int $nextPage = null,
        ?int $previousPage = null,
    ): PaginatedResponseData {
        return new PaginatedResponseData(
            currentPage: $currentPage,
            totalPages: $totalPages,
            totalItems: $totalItems ?? count($items),
            nextPage: $nextPage,
            previousPage: $previousPage,
            items: $items,
        );
    }

    /**
     * Crea datos externos de una localización.
     */
    private function locationData(int $externalId, string $name): LocationData
    {
        return new LocationData(
            externalId: $externalId,
            name: $name,
            type: 'Planet',
            dimension: 'Dimension C-137',
        );
    }

    /**
     * Crea datos externos de un episodio.
     */
    private function episodeData(int $externalId, string $name, string $code): EpisodeData
    {
        return new EpisodeData(
            externalId: $externalId,
            name: $name,
            airDate: new DateTimeImmutable('2013-12-02'),
            code: $code,
        );
    }

    /**
     * Crea datos externos de un personaje y sus referencias.
     *
     * @param  list<int>  $episodeExternalIds
     */
    private function characterData(
        int $externalId,
        string $name,
        ?int $originLocationExternalId,
        ?int $currentLocationExternalId,
        array $episodeExternalIds,
    ): CharacterData {
        return new CharacterData(
            externalId: $externalId,
            name: $name,
            status: 'Alive',
            species: 'Human',
            type: '',
            gender: 'Male',
            imageUrl: "https://rickandmortyapi.com/api/character/avatar/$externalId.jpeg",
            originLocationExternalId: $originLocationExternalId,
            currentLocationExternalId: $currentLocationExternalId,
            episodeExternalIds: $episodeExternalIds,
        );
    }
}
