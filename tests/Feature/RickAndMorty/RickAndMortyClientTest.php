<?php

declare(strict_types=1);

/**
 * Verifica el cliente HTTP de Rick and Morty sin realizar peticiones reales.
 */

namespace Tests\Feature\RickAndMorty;

use App\Domain\Characters\DTO\CharacterData;
use App\Domain\Episodes\DTO\EpisodeData;
use App\Domain\Locations\DTO\LocationData;
use App\Domain\RickAndMorty\Contracts\RickAndMortyClientInterface;
use App\Domain\RickAndMorty\Exceptions\InvalidRickAndMortyResponseException;
use App\Domain\RickAndMorty\Exceptions\RickAndMortyRequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Cubre configuración, paginación, transformación, reintentos y errores controlados.
 */
final class RickAndMortyClientTest extends TestCase
{
    /** Cliente resuelto mediante su contrato de aplicación. */
    private RickAndMortyClientInterface $client;

    /**
     * Configura un proveedor ficticio y prohíbe cualquier acceso accidental a la red.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.rick_and_morty', [
            'url' => 'https://provider.test/api',
            'timeout' => 10,
            'connect_timeout' => 5,
            'retry_times' => 3,
            'retry_sleep_milliseconds' => 0,
        ]);

        Http::preventStrayRequests();

        $this->client = $this->app->make(RickAndMortyClientInterface::class);
    }

    /**
     * Verifica que personajes, episodios y localizaciones se transforman en sus DTOs.
     */
    public function test_it_fetches_every_supported_resource_as_domain_data(): void
    {
        Http::fake([
            'provider.test/api/character*' => Http::response($this->characterPage(), 200),
            'provider.test/api/episode*' => Http::response($this->episodePage(), 200),
            'provider.test/api/location*' => Http::response($this->locationPage(), 200),
        ]);

        $characters = $this->client->fetchCharacters();
        $episodes = $this->client->fetchEpisodes();
        $locations = $this->client->fetchLocations();

        $this->assertContainsOnlyInstancesOf(CharacterData::class, $characters->items);
        $this->assertContainsOnlyInstancesOf(EpisodeData::class, $episodes->items);
        $this->assertContainsOnlyInstancesOf(LocationData::class, $locations->items);
        Http::assertSentCount(3);
    }

    /**
     * Verifica que la URL configurada y el número de página forman la petición.
     */
    public function test_it_uses_the_configured_url_and_requested_page(): void
    {
        Http::fake([
            '*' => Http::response($this->characterPage(
                currentPage: 2,
                totalPages: 2,
                previousPage: 1,
            ), 200),
        ]);

        $page = $this->client->fetchCharacters(2);

        $this->assertSame(2, $page->currentPage);
        $this->assertSame(1, $page->previousPage);
        Http::assertSent(static function (Request $request): bool {
            return $request->url() === 'https://provider.test/api/character?page=2'
                && $request['page'] === 2
                && $request->hasHeader('Accept', 'application/json');
        });
    }

    /**
     * Verifica que un cuerpo no interpretable como objeto JSON se rechaza de forma controlada.
     */
    public function test_it_rejects_an_invalid_response_body(): void
    {
        Http::fake([
            '*' => Http::response('{invalid-json', 200, ['Content-Type' => 'application/json']),
        ]);

        $this->expectException(InvalidRickAndMortyResponseException::class);
        $this->expectExceptionMessage('[body]');

        $this->client->fetchCharacters();
    }

    /**
     * Verifica que los errores HTTP permanentes no se reintentan y conservan su estado.
     */
    public function test_it_translates_a_client_http_error_without_retrying(): void
    {
        Http::fake(['*' => Http::response(['error' => 'Not found'], 404)]);

        try {
            $this->client->fetchEpisodes();
            $this->fail('A controlled request exception was expected.');
        } catch (RickAndMortyRequestException $exception) {
            $this->assertSame('episode', $exception->resource);
            $this->assertSame(404, $exception->statusCode);
        }

        Http::assertSentCount(1);
    }

    /**
     * Verifica que un error de servidor temporal permite recuperarse en el siguiente intento.
     */
    public function test_it_retries_a_recoverable_http_error(): void
    {
        Http::fakeSequence()
            ->push(['error' => 'Unavailable'], 503)
            ->push($this->locationPage(), 200);

        $page = $this->client->fetchLocations();

        $this->assertContainsOnlyInstancesOf(LocationData::class, $page->items);
        Http::assertSentCount(2);
    }

    /**
     * Verifica que un timeout comunicado por HTTP también se considera recuperable.
     */
    public function test_it_retries_an_http_request_timeout(): void
    {
        Http::fakeSequence()
            ->push(['error' => 'Request timeout'], 408)
            ->push($this->episodePage(), 200);

        $page = $this->client->fetchEpisodes();

        $this->assertContainsOnlyInstancesOf(EpisodeData::class, $page->items);
        Http::assertSentCount(2);
    }

    /**
     * Verifica que los errores recuperables dejan de reintentarse al alcanzar el límite.
     */
    public function test_it_limits_retries_for_server_errors(): void
    {
        Http::fake(['*' => Http::response(['error' => 'Unavailable'], 503)]);

        try {
            $this->client->fetchLocations();
            $this->fail('A controlled request exception was expected.');
        } catch (RickAndMortyRequestException $exception) {
            $this->assertSame('location', $exception->resource);
            $this->assertSame(503, $exception->statusCode);
        }

        Http::assertSentCount(3);
    }

    /**
     * Verifica que un timeout se reintenta y se traduce sin filtrar la excepción HTTP.
     */
    public function test_it_translates_a_timeout_after_limited_retries(): void
    {
        Http::fake(Http::failedConnection('Connection timed out.'));

        try {
            $this->client->fetchCharacters();
            $this->fail('A controlled request exception was expected.');
        } catch (RickAndMortyRequestException $exception) {
            $this->assertSame('character', $exception->resource);
            $this->assertNull($exception->statusCode);
            $this->assertInstanceOf(ConnectionException::class, $exception->getPrevious());
        }

        Http::assertSentCount(3);
    }

    /**
     * Verifica que una página no válida falla antes de construir una petición externa.
     */
    public function test_it_rejects_a_non_positive_page_without_network_access(): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->client->fetchCharacters(0);
        } finally {
            Http::assertNothingSent();
        }
    }

    /**
     * Construye una página válida de personajes.
     *
     * @return array<string, mixed>
     */
    private function characterPage(
        int $currentPage = 1,
        int $totalPages = 1,
        ?int $nextPage = null,
        ?int $previousPage = null,
    ): array {
        return $this->pagePayload(
            currentPage: $currentPage,
            totalPages: $totalPages,
            nextPage: $nextPage,
            previousPage: $previousPage,
            resource: 'character',
            results: [[
                'id' => 1,
                'name' => 'Rick Sanchez',
                'status' => 'Alive',
                'species' => 'Human',
                'type' => '',
                'gender' => 'Male',
                'origin' => ['name' => 'unknown', 'url' => ''],
                'location' => [
                    'name' => 'Earth (Replacement Dimension)',
                    'url' => 'https://rickandmortyapi.com/api/location/20',
                ],
                'image' => 'https://rickandmortyapi.com/api/character/avatar/1.jpeg',
                'episode' => ['https://rickandmortyapi.com/api/episode/1'],
            ]],
        );
    }

    /**
     * Construye una página válida de episodios.
     *
     * @return array<string, mixed>
     */
    private function episodePage(): array
    {
        return $this->pagePayload(results: [[
            'id' => 1,
            'name' => 'Pilot',
            'air_date' => 'December 2, 2013',
            'episode' => 'S01E01',
        ]], resource: 'episode');
    }

    /**
     * Construye una página válida de localizaciones.
     *
     * @return array<string, mixed>
     */
    private function locationPage(): array
    {
        return $this->pagePayload(results: [[
            'id' => 20,
            'name' => 'Earth (Replacement Dimension)',
            'type' => 'Planet',
            'dimension' => 'Replacement Dimension',
        ]], resource: 'location');
    }

    /**
     * Construye metadatos de paginación coherentes con la página solicitada.
     *
     * @param  list<array<string, mixed>>  $results
     * @return array<string, mixed>
     */
    private function pagePayload(
        array $results,
        string $resource,
        int $currentPage = 1,
        int $totalPages = 1,
        ?int $nextPage = null,
        ?int $previousPage = null,
    ): array {
        return [
            'info' => [
                'count' => count($results),
                'pages' => $totalPages,
                'next' => $nextPage === null
                    ? null
                    : "https://rickandmortyapi.com/api/$resource?page=$nextPage",
                'prev' => $previousPage === null
                    ? null
                    : "https://rickandmortyapi.com/api/$resource?page=$previousPage",
            ],
            'results' => $results,
        ];
    }
}
