<?php

declare(strict_types=1);

/**
 * Verifica la interfaz Artisan de la sincronización de Rick and Morty.
 */

namespace Tests\Feature\RickAndMorty;

use App\Domain\RickAndMorty\Contracts\RickAndMortyClientInterface;
use App\Domain\RickAndMorty\DTO\PaginatedResponseData;
use App\Domain\RickAndMorty\Exceptions\RickAndMortyRequestException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Cubre registro, salida, resumen y códigos de salida sin utilizar la red.
 */
final class SyncRickAndMortyCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica la salida de progreso, el resumen y el código cero en una ejecución correcta.
     */
    public function test_it_reports_a_successful_synchronization(): void
    {
        $client = $this->clientMock();
        $emptyPage = $this->emptyPage();

        $client->shouldReceive('fetchLocations')->with(1)->once()->andReturn($emptyPage);
        $client->shouldReceive('fetchEpisodes')->with(1)->once()->andReturn($emptyPage);
        $client->shouldReceive('fetchCharacters')->with(1)->once()->andReturn($emptyPage);
        $this->app->instance(RickAndMortyClientInterface::class, $client);

        $this->artisan('rick-and-morty:sync')
            ->expectsOutputToContain('Sincronizando Rick and Morty')
            ->expectsOutputToContain('Sincronización completada.')
            ->expectsTable(
                ['Métrica', 'Cantidad'],
                [
                    ['Localizaciones procesadas', 0],
                    ['Episodios procesados', 0],
                    ['Personajes procesados', 0],
                    ['Relaciones procesadas', 0],
                    ['Registros creados', 0],
                    ['Registros actualizados', 0],
                    ['Registros sin cambios', 0],
                ],
            )
            ->assertSuccessful();
    }

    /**
     * Verifica que un fallo controlado produce contexto útil y código distinto de cero.
     */
    public function test_it_reports_a_controlled_failure(): void
    {
        $client = $this->clientMock();
        $client->shouldReceive('fetchLocations')->with(1)->once()->andThrow(
            RickAndMortyRequestException::unexpectedStatus('location', 503),
        );
        $client->shouldNotReceive('fetchEpisodes');
        $client->shouldNotReceive('fetchCharacters');
        $this->app->instance(RickAndMortyClientInterface::class, $client);

        $this->artisan('rick-and-morty:sync')
            ->expectsOutputToContain('Sincronizando Rick and Morty')
            ->expectsOutputToContain('No se pudo completar la sincronización')
            ->expectsOutputToContain('Etapa: fetch')
            ->expectsOutputToContain('Recurso: location')
            ->expectsOutputToContain('Página: 1')
            ->assertFailed();
    }

    /**
     * Crea un doble del cliente para impedir peticiones HTTP en los tests del comando.
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
     * Crea una página externa vacía y válida.
     *
     * @return PaginatedResponseData<never>
     */
    private function emptyPage(): PaginatedResponseData
    {
        return new PaginatedResponseData(
            currentPage: 1,
            totalPages: 1,
            totalItems: 0,
            nextPage: null,
            previousPage: null,
            items: [],
        );
    }
}
