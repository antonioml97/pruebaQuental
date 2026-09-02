<?php

declare(strict_types=1);

/**
 * Consume páginas de recursos de Rick and Morty y las transforma al dominio.
 */

namespace App\Services\RickAndMorty;

use App\Domain\Characters\DTO\CharacterData;
use App\Domain\Episodes\DTO\EpisodeData;
use App\Domain\Locations\DTO\LocationData;
use App\Domain\RickAndMorty\Contracts\RickAndMortyClientInterface;
use App\Domain\RickAndMorty\DTO\PaginatedResponseData;
use App\Domain\RickAndMorty\Exceptions\InvalidRickAndMortyResponseException;
use App\Domain\RickAndMorty\Exceptions\RickAndMortyRequestException;
use App\Services\RickAndMorty\Mapping\CharacterResponseMapper;
use App\Services\RickAndMorty\Mapping\EpisodeResponseMapper;
use App\Services\RickAndMorty\Mapping\LocationResponseMapper;
use App\Services\RickAndMorty\Mapping\PaginatedResponseMapper;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use LogicException;
use Throwable;

/**
 * Encapsula transporte, resiliencia y traducción de la API externa.
 */
final class RickAndMortyClient implements RickAndMortyClientInterface
{
    /** Tiempo máximo predeterminado de una petición completa, en segundos. */
    private const DEFAULT_TIMEOUT_SECONDS = 10;

    /** Tiempo máximo predeterminado para establecer conexión, en segundos. */
    private const DEFAULT_CONNECT_TIMEOUT_SECONDS = 5;

    /** Número total predeterminado de intentos para errores recuperables. */
    private const DEFAULT_RETRY_TIMES = 3;

    /** Espera predeterminada entre intentos, en milisegundos. */
    private const DEFAULT_RETRY_SLEEP_MILLISECONDS = 100;

    /**
     * Recibe los transformadores de recursos, paginación y política de reintentos.
     *
     * @param  CharacterResponseMapper  $characters  Traductor de los campos y referencias externos de personajes.
     * @param  EpisodeResponseMapper  $episodes  Traductor de episodios y sus fechas de emisión.
     * @param  LocationResponseMapper  $locations  Traductor de localizaciones que conserva los campos vacíos válidos.
     * @param  PaginatedResponseMapper  $pages  Validador de metadatos que compone la página de DTOs.
     * @param  RetryPolicy  $retryPolicy  Política que decide qué fallos reintentar y cuánto esperar.
     */
    public function __construct(
        /** Transformador de personajes. */
        private readonly CharacterResponseMapper $characters,
        /** Transformador de episodios. */
        private readonly EpisodeResponseMapper $episodes,
        /** Transformador de localizaciones. */
        private readonly LocationResponseMapper $locations,
        /** Transformador común de páginas. */
        private readonly PaginatedResponseMapper $pages,
        /** Política de errores recuperables y esperas. */
        private readonly RetryPolicy $retryPolicy,
    ) {}

    /**
     * Obtiene y valida una página de personajes.
     *
     * @param  int  $page  Número de página del proveedor, comenzando en uno.
     * @return PaginatedResponseData<CharacterData>
     *
     * @throws InvalidArgumentException
     * @throws InvalidRickAndMortyResponseException
     * @throws RickAndMortyRequestException
     */
    public function fetchCharacters(int $page = 1): PaginatedResponseData
    {
        return $this->pages->map(
            $this->fetchPage('character', $page),
            $page,
            $this->characters->map(...),
        );
    }

    /**
     * Obtiene y valida una página de episodios.
     *
     * @param  int  $page  Número de página del proveedor, comenzando en uno.
     * @return PaginatedResponseData<EpisodeData>
     *
     * @throws InvalidArgumentException
     * @throws InvalidRickAndMortyResponseException
     * @throws RickAndMortyRequestException
     */
    public function fetchEpisodes(int $page = 1): PaginatedResponseData
    {
        return $this->pages->map(
            $this->fetchPage('episode', $page),
            $page,
            $this->episodes->map(...),
        );
    }

    /**
     * Obtiene y valida una página de localizaciones.
     *
     * @param  int  $page  Número de página del proveedor, comenzando en uno.
     * @return PaginatedResponseData<LocationData>
     *
     * @throws InvalidArgumentException
     * @throws InvalidRickAndMortyResponseException
     * @throws RickAndMortyRequestException
     */
    public function fetchLocations(int $page = 1): PaginatedResponseData
    {
        return $this->pages->map(
            $this->fetchPage('location', $page),
            $page,
            $this->locations->map(...),
        );
    }

    /**
     * Obtiene el objeto JSON de una página sin transformar entidades.
     *
     * @param  string  $resource  Nombre del recurso del proveedor implicado en la operación.
     * @param  int  $page  Número de página del proveedor, comenzando en uno.
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     * @throws InvalidRickAndMortyResponseException
     * @throws RickAndMortyRequestException
     */
    private function fetchPage(string $resource, int $page): array
    {
        if ($page < 1) {
            throw new InvalidArgumentException('La página solicitada debe ser un entero positivo.');
        }

        try {
            $response = $this->request($resource, $page);
        } catch (ConnectionException $exception) {
            throw RickAndMortyRequestException::connectionFailed($resource, $exception);
        }

        if (! $response->successful()) {
            throw RickAndMortyRequestException::unexpectedStatus($resource, $response->status());
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new InvalidRickAndMortyResponseException(
                'El campo [body] de la respuesta de Rick and Morty no es válido: debe ser un objeto JSON.',
            );
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }

    /**
     * Configura y envía una petición HTTP con reintentos limitados.
     *
     * @param  string  $resource  Nombre del recurso del proveedor implicado en la operación.
     * @param  int  $page  Número de página del proveedor, comenzando en uno.
     *
     * @throws ConnectionException
     */
    private function request(string $resource, int $page): Response
    {
        return Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->timeout($this->positiveConfiguration('timeout', self::DEFAULT_TIMEOUT_SECONDS))
            ->connectTimeout($this->positiveConfiguration(
                'connect_timeout',
                self::DEFAULT_CONNECT_TIMEOUT_SECONDS,
            ))
            ->retry(
                $this->positiveConfiguration('retry_times', self::DEFAULT_RETRY_TIMES),
                /**
                 * Calcula la espera antes de repetir una petición al proveedor.
                 *
                 * @param  int  $attempt  Número de intento facilitado por Laravel; la política no usa espera exponencial.
                 * @param  Throwable  $exception  Fallo del intento actual, incluido Retry-After cuando exista.
                 * @return int Espera en milisegundos antes del siguiente intento.
                 */
                fn (int $attempt, Throwable $exception): int => $this->retryPolicy->delayMilliseconds(
                    $exception,
                    $this->nonNegativeConfiguration('retry_sleep_milliseconds', self::DEFAULT_RETRY_SLEEP_MILLISECONDS),
                ),
                $this->retryPolicy->shouldRetry(...),
                throw: false,
            )
            ->get($resource, ['page' => $page]);
    }

    /**
     * Obtiene y valida la URL base configurada para el proveedor.
     *
     * @throws LogicException Si la configuración no contiene una URL HTTP válida.
     */
    private function baseUrl(): string
    {
        $url = config('services.rick_and_morty.url');
        $scheme = is_string($url) ? parse_url($url, PHP_URL_SCHEME) : null;

        if (
            ! is_string($url)
            || filter_var($url, FILTER_VALIDATE_URL) === false
            || ! in_array($scheme, ['http', 'https'], true)
        ) {
            throw new LogicException('La URL configurada para la API de Rick and Morty no es válida.');
        }

        return rtrim($url, '/');
    }

    /**
     * Obtiene un entero positivo desde la configuración del proveedor.
     *
     * @param  string  $key  Nombre de la opción dentro de services.rick_and_morty.
     * @param  int  $default  Valor de respaldo cuando la clave no está configurada; se valida con el mismo mínimo.
     *
     * @throws LogicException Si el valor configurado no es un entero positivo.
     */
    private function positiveConfiguration(string $key, int $default): int
    {
        return $this->integerConfiguration($key, $default, 1);
    }

    /**
     * Obtiene un entero no negativo desde la configuración del proveedor.
     *
     * @param  string  $key  Nombre de la opción dentro de services.rick_and_morty.
     * @param  int  $default  Valor de respaldo cuando la clave no está configurada; se valida con el mismo mínimo.
     *
     * @throws LogicException Si el valor configurado es negativo o no es entero.
     */
    private function nonNegativeConfiguration(string $key, int $default): int
    {
        return $this->integerConfiguration($key, $default, 0);
    }

    /**
     * Obtiene un entero configurado respetando su valor mínimo.
     *
     * @param  string  $key  Nombre de la opción dentro de services.rick_and_morty.
     * @param  int  $default  Valor de respaldo cuando la clave no está configurada; se valida con el mismo mínimo.
     * @param  int  $minimum  Límite inferior inclusivo admitido para el entero configurado.
     *
     * @throws LogicException Si el valor configurado no respeta el contrato.
     */
    private function integerConfiguration(string $key, int $default, int $minimum): int
    {
        $value = config("services.rick_and_morty.$key", $default);
        $integer = filter_var($value, FILTER_VALIDATE_INT);

        if (! is_int($integer) || $integer < $minimum) {
            throw new LogicException(
                "La configuración [$key] de Rick and Morty debe ser un entero mayor o igual que [$minimum].",
            );
        }

        return $integer;
    }
}
