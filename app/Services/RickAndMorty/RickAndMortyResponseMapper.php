<?php

declare(strict_types=1);

/**
 * Transforma y valida las respuestas del proveedor antes de acceder al dominio.
 */

namespace App\Services\RickAndMorty;

use App\Domain\Characters\DTO\CharacterData;
use App\Domain\Episodes\DTO\EpisodeData;
use App\Domain\Locations\DTO\LocationData;
use App\Domain\RickAndMorty\DTO\PaginatedResponseData;
use App\Domain\RickAndMorty\Exceptions\InvalidRickAndMortyResponseException;
use DateTimeImmutable;

/**
 * Traduce la representación REST externa a DTOs independientes del proveedor.
 */
final class RickAndMortyResponseMapper
{
    /**
     * Transforma la respuesta de un personaje.
     *
     * @param  array<string, mixed>  $payload
     */
    public function mapCharacter(array $payload): CharacterData
    {
        return new CharacterData(
            externalId: $this->requirePositiveInt($payload, 'id'),
            name: $this->requireString($payload, 'name'),
            status: $this->requireOneOf($payload, 'status', ['Alive', 'Dead', 'unknown']),
            species: $this->requireString($payload, 'species'),
            type: $this->requireString($payload, 'type', allowEmpty: true),
            gender: $this->requireOneOf($payload, 'gender', ['Female', 'Male', 'Genderless', 'unknown']),
            imageUrl: $this->requireUrl($payload, 'image'),
            originLocationExternalId: $this->mapLocationReference($payload, 'origin'),
            currentLocationExternalId: $this->mapLocationReference($payload, 'location'),
            episodeExternalIds: $this->mapResourceReferences($payload, 'episode', 'episode'),
        );
    }

    /**
     * Transforma la respuesta de un episodio.
     *
     * @param  array<string, mixed>  $payload
     */
    public function mapEpisode(array $payload): EpisodeData
    {
        $code = $this->requireString($payload, 'episode');

        if (preg_match('/^S\d+E\d+$/', $code) !== 1) {
            throw $this->invalid('episode', 'must contain a valid episode code');
        }

        return new EpisodeData(
            externalId: $this->requirePositiveInt($payload, 'id'),
            name: $this->requireString($payload, 'name'),
            airDate: $this->mapAirDate($payload),
            code: $code,
        );
    }

    /**
     * Transforma la respuesta de una localización.
     *
     * @param  array<string, mixed>  $payload
     */
    public function mapLocation(array $payload): LocationData
    {
        return new LocationData(
            externalId: $this->requirePositiveInt($payload, 'id'),
            name: $this->requireString($payload, 'name'),
            type: $this->requireString($payload, 'type'),
            dimension: $this->requireString($payload, 'dimension'),
        );
    }

    /**
     * Transforma una respuesta paginada de personajes.
     *
     * @param  array<string, mixed>  $payload
     * @return PaginatedResponseData<CharacterData>
     */
    public function mapCharacterPage(array $payload, int $currentPage): PaginatedResponseData
    {
        return $this->mapPage($payload, $currentPage, $this->mapCharacter(...));
    }

    /**
     * Transforma una respuesta paginada de episodios.
     *
     * @param  array<string, mixed>  $payload
     * @return PaginatedResponseData<EpisodeData>
     */
    public function mapEpisodePage(array $payload, int $currentPage): PaginatedResponseData
    {
        return $this->mapPage($payload, $currentPage, $this->mapEpisode(...));
    }

    /**
     * Transforma una respuesta paginada de localizaciones.
     *
     * @param  array<string, mixed>  $payload
     * @return PaginatedResponseData<LocationData>
     */
    public function mapLocationPage(array $payload, int $currentPage): PaginatedResponseData
    {
        return $this->mapPage($payload, $currentPage, $this->mapLocation(...));
    }

    /**
     * Transforma los metadatos de paginación y los recursos del proveedor.
     *
     * @template T
     *
     * @param  array<string, mixed>  $payload
     * @param  callable(array<string, mixed>): T  $itemMapper
     * @return PaginatedResponseData<T>
     */
    private function mapPage(array $payload, int $currentPage, callable $itemMapper): PaginatedResponseData
    {
        if ($currentPage < 1) {
            throw $this->invalid('page', 'must be a positive integer');
        }

        $info = $this->requireArray($payload, 'info');
        $results = $this->requireArray($payload, 'results');

        if (! array_is_list($results)) {
            throw $this->invalid('results', 'must be a list');
        }

        $totalPages = $this->requirePositiveInt($info, 'pages');
        $totalItems = $this->requireNonNegativeInt($info, 'count');

        if ($currentPage > $totalPages) {
            throw $this->invalid('page', 'cannot exceed the total number of pages');
        }

        $nextPage = $this->mapPageReference($info, 'next');
        $previousPage = $this->mapPageReference($info, 'prev');

        if ($nextPage !== null && $nextPage !== $currentPage + 1) {
            throw $this->invalid('info.next', 'does not match the current page');
        }

        if ($previousPage !== null && $previousPage !== $currentPage - 1) {
            throw $this->invalid('info.prev', 'does not match the current page');
        }

        if ($currentPage < $totalPages && $nextPage === null) {
            throw $this->invalid('info.next', 'is required before the last page');
        }

        if ($currentPage === $totalPages && $nextPage !== null) {
            throw $this->invalid('info.next', 'must be null on the last page');
        }

        if ($currentPage > 1 && $previousPage === null) {
            throw $this->invalid('info.prev', 'is required after the first page');
        }

        $items = [];

        foreach ($results as $index => $result) {
            if (! is_array($result)) {
                throw $this->invalid("results.$index", 'must be an object');
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
     * Extrae un identificador de localización opcional desde una referencia anidada.
     *
     * @param  array<string, mixed>  $payload
     */
    private function mapLocationReference(array $payload, string $field): ?int
    {
        $reference = $this->requireArray($payload, $field);
        $this->requireString($reference, 'name');
        $url = $this->requireString($reference, 'url', allowEmpty: true);

        return $url === '' ? null : $this->resourceIdFromUrl($url, 'location', "$field.url");
    }

    /**
     * Extrae una lista de identificadores de recurso desde URLs del proveedor.
     *
     * @param  array<string, mixed>  $payload
     * @return list<int>
     */
    private function mapResourceReferences(array $payload, string $field, string $resource): array
    {
        $references = $this->requireArray($payload, $field);

        if (! array_is_list($references)) {
            throw $this->invalid($field, 'must be a list');
        }

        $ids = [];

        foreach ($references as $index => $reference) {
            if (! is_string($reference) || $reference === '') {
                throw $this->invalid("$field.$index", 'must be a non-empty URL');
            }

            $ids[] = $this->resourceIdFromUrl($reference, $resource, "$field.$index");
        }

        if (count($ids) !== count(array_unique($ids))) {
            throw $this->invalid($field, 'must not contain duplicate resources');
        }

        return $ids;
    }

    /**
     * Extrae el número de página desde una URL de paginación opcional.
     *
     * @param  array<string, mixed>  $info
     */
    private function mapPageReference(array $info, string $field): ?int
    {
        if (! array_key_exists($field, $info)) {
            throw $this->invalid("info.$field", 'is required');
        }

        if ($info[$field] === null) {
            return null;
        }

        if (! is_string($info[$field]) || ! $this->isHttpUrl($info[$field])) {
            throw $this->invalid("info.$field", 'must be null or a valid HTTP URL');
        }

        $query = parse_url($info[$field], PHP_URL_QUERY);
        parse_str(is_string($query) ? $query : '', $parameters);
        $page = filter_var($parameters['page'] ?? null, FILTER_VALIDATE_INT);

        if (! is_int($page) || $page < 1) {
            throw $this->invalid("info.$field", 'must contain a positive page parameter');
        }

        return $page;
    }

    /**
     * Normaliza la fecha de emisión del proveedor como un valor inmutable del dominio.
     *
     * @param  array<string, mixed>  $payload
     */
    private function mapAirDate(array $payload): DateTimeImmutable
    {
        $value = $this->requireString($payload, 'air_date');
        $date = DateTimeImmutable::createFromFormat('!F j, Y', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw $this->invalid('air_date', 'must contain a valid date');
        }

        return $date;
    }

    /**
     * Extrae un identificador de recurso positivo desde una URL del proveedor.
     */
    private function resourceIdFromUrl(string $url, string $resource, string $field): int
    {
        if (! $this->isHttpUrl($url)) {
            throw $this->invalid($field, 'must be a valid HTTP URL');
        }

        $path = parse_url($url, PHP_URL_PATH);
        $pattern = sprintf('~/%s/(\d+)/?$~', preg_quote($resource, '~'));

        if (! is_string($path) || preg_match($pattern, $path, $matches) !== 1) {
            throw $this->invalid($field, "must reference a $resource resource");
        }

        $id = filter_var($matches[1], FILTER_VALIDATE_INT);

        if (! is_int($id) || $id < 1) {
            throw $this->invalid($field, 'must reference a positive identifier');
        }

        return $id;
    }

    /**
     * Exige que un campo contenga un array.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|list<mixed>
     */
    private function requireArray(array $payload, string $field): array
    {
        if (! array_key_exists($field, $payload) || ! is_array($payload[$field])) {
            throw $this->invalid($field, 'must be an array');
        }

        return $payload[$field];
    }

    /**
     * Exige que un campo contenga un entero positivo.
     *
     * @param  array<string, mixed>  $payload
     */
    private function requirePositiveInt(array $payload, string $field): int
    {
        $value = $this->requireNonNegativeInt($payload, $field);

        if ($value < 1) {
            throw $this->invalid($field, 'must be a positive integer');
        }

        return $value;
    }

    /**
     * Exige que un campo contenga un entero no negativo.
     *
     * @param  array<string, mixed>  $payload
     */
    private function requireNonNegativeInt(array $payload, string $field): int
    {
        if (! array_key_exists($field, $payload) || ! is_int($payload[$field]) || $payload[$field] < 0) {
            throw $this->invalid($field, 'must be a non-negative integer');
        }

        return $payload[$field];
    }

    /**
     * Exige que un campo contenga texto, admitiendo opcionalmente un valor vacío.
     *
     * @param  array<string, mixed>  $payload
     */
    private function requireString(array $payload, string $field, bool $allowEmpty = false): string
    {
        if (! array_key_exists($field, $payload) || ! is_string($payload[$field])) {
            throw $this->invalid($field, 'must be a string');
        }

        if (! $allowEmpty && trim($payload[$field]) === '') {
            throw $this->invalid($field, 'must not be empty');
        }

        return $payload[$field];
    }

    /**
     * Exige que un campo contenga uno de los valores de texto admitidos.
     *
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $allowedValues
     */
    private function requireOneOf(array $payload, string $field, array $allowedValues): string
    {
        $value = $this->requireString($payload, $field);

        if (! in_array($value, $allowedValues, true)) {
            throw $this->invalid($field, 'contains an unsupported value');
        }

        return $value;
    }

    /**
     * Exige que un campo contenga una URL absoluta válida.
     *
     * @param  array<string, mixed>  $payload
     */
    private function requireUrl(array $payload, string $field): string
    {
        $url = $this->requireString($payload, $field);

        if (! $this->isHttpUrl($url)) {
            throw $this->invalid($field, 'must be a valid HTTP URL');
        }

        return $url;
    }

    /**
     * Comprueba que una URL absoluta utiliza un protocolo admitido por la integración.
     */
    private function isHttpUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true);
    }

    /**
     * Construye una excepción coherente para datos no válidos del proveedor.
     */
    private function invalid(string $field, string $reason): InvalidRickAndMortyResponseException
    {
        return new InvalidRickAndMortyResponseException("Invalid Rick and Morty response field [$field]: $reason.");
    }
}
