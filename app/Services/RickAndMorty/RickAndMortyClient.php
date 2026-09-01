<?php

declare(strict_types=1);

/**
 * Consume páginas de recursos de Rick and Morty y las transforma al dominio.
 */

namespace App\Services\RickAndMorty;

use App\Domain\RickAndMorty\Contracts\RickAndMortyClientInterface;
use App\Domain\RickAndMorty\DTO\CharacterData;
use App\Domain\RickAndMorty\DTO\EpisodeData;
use App\Domain\RickAndMorty\DTO\LocationData;
use App\Domain\RickAndMorty\DTO\PaginatedResponseData;
use App\Domain\RickAndMorty\Exceptions\InvalidRickAndMortyResponseException;
use App\Domain\RickAndMorty\Exceptions\RickAndMortyRequestException;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
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

    /** Códigos HTTP recuperables que no pertenecen al rango de errores de servidor. */
    private const RETRYABLE_STATUS_CODES = [429];

    /**
     * Crea el cliente con el transformador del proveedor.
     */
    public function __construct(
        private readonly RickAndMortyResponseMapper $mapper,
    ) {}

    /**
     * Obtiene y valida una página de personajes.
     *
     * @return PaginatedResponseData<CharacterData>
     *
     * @throws InvalidArgumentException
     * @throws InvalidRickAndMortyResponseException
     * @throws RickAndMortyRequestException
     */
    public function fetchCharacters(int $page = 1): PaginatedResponseData
    {
        return $this->fetchPage(
            resource: 'character',
            page: $page,
            mapper: $this->mapper->mapCharacterPage(...),
        );
    }

    /**
     * Obtiene y valida una página de episodios.
     *
     * @return PaginatedResponseData<EpisodeData>
     *
     * @throws InvalidArgumentException
     * @throws InvalidRickAndMortyResponseException
     * @throws RickAndMortyRequestException
     */
    public function fetchEpisodes(int $page = 1): PaginatedResponseData
    {
        return $this->fetchPage(
            resource: 'episode',
            page: $page,
            mapper: $this->mapper->mapEpisodePage(...),
        );
    }

    /**
     * Obtiene y valida una página de localizaciones.
     *
     * @return PaginatedResponseData<LocationData>
     *
     * @throws InvalidArgumentException
     * @throws InvalidRickAndMortyResponseException
     * @throws RickAndMortyRequestException
     */
    public function fetchLocations(int $page = 1): PaginatedResponseData
    {
        return $this->fetchPage(
            resource: 'location',
            page: $page,
            mapper: $this->mapper->mapLocationPage(...),
        );
    }

    /**
     * Ejecuta una petición paginada y delega la traducción de su contenido.
     *
     * @template T
     *
     * @param  Closure(array<string, mixed>, int): PaginatedResponseData<T>  $mapper
     * @return PaginatedResponseData<T>
     *
     * @throws InvalidArgumentException
     * @throws InvalidRickAndMortyResponseException
     * @throws RickAndMortyRequestException
     */
    private function fetchPage(string $resource, int $page, Closure $mapper): PaginatedResponseData
    {
        if ($page < 1) {
            throw new InvalidArgumentException('The requested page must be a positive integer.');
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
                'Invalid Rick and Morty response field [body]: must be a JSON object.',
            );
        }

        /** @var array<string, mixed> $payload */
        return $mapper($payload, $page);
    }

    /**
     * Configura y envía una petición HTTP con reintentos limitados.
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
                $this->positiveConfiguration(
                    'retry_sleep_milliseconds',
                    self::DEFAULT_RETRY_SLEEP_MILLISECONDS,
                ),
                fn (Throwable $exception): bool => $this->shouldRetry($exception),
                throw: false,
            )
            ->get($resource, ['page' => $page]);
    }

    /**
     * Determina si un fallo es temporal y admite otro intento.
     */
    private function shouldRetry(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if (! $exception instanceof RequestException) {
            return false;
        }

        return $exception->response->serverError()
            || in_array($exception->response->status(), self::RETRYABLE_STATUS_CODES, true);
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
            throw new LogicException('The Rick and Morty API URL configuration is invalid.');
        }

        return rtrim($url, '/');
    }

    /**
     * Obtiene un entero positivo desde la configuración del proveedor.
     *
     * @throws LogicException Si el valor configurado no es un entero positivo.
     */
    private function positiveConfiguration(string $key, int $default): int
    {
        $value = config("services.rick_and_morty.$key", $default);
        $integer = filter_var($value, FILTER_VALIDATE_INT);

        if (! is_int($integer) || $integer < 1) {
            throw new LogicException("The Rick and Morty [$key] configuration must be a positive integer.");
        }

        return $integer;
    }
}
