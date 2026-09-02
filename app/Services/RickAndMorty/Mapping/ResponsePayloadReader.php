<?php

declare(strict_types=1);

/**
 * Extrae campos y referencias validados de las respuestas externas.
 */

namespace App\Services\RickAndMorty\Mapping;

use App\Domain\RickAndMorty\Exceptions\InvalidRickAndMortyResponseException;

/**
 * Extrae campos y referencias validados de las respuestas externas.
 */
final class ResponsePayloadReader
{
    /**
     * Extrae un identificador de localización opcional desde una referencia anidada.
     *
     * @param  array<string, mixed>  $payload  Objeto JSON externo todavía no validado; solo se aceptan los campos del contrato.
     * @param  string  $field  Nombre del campo que se valida y se identifica en los errores.
     */
    public function mapLocationReference(array $payload, string $field): ?int
    {
        $reference = $this->requireArray($payload, $field);
        $this->requireString($reference, 'name');
        $url = $this->requireString($reference, 'url', allowEmpty: true);

        return $url === '' ? null : $this->resourceIdFromUrl($url, 'location', "$field.url");
    }

    /**
     * Extrae una lista de identificadores de recurso desde URLs del proveedor.
     *
     * @param  array<string, mixed>  $payload  Objeto JSON externo todavía no validado; solo se aceptan los campos del contrato.
     * @param  string  $field  Nombre del campo que se valida y se identifica en los errores.
     * @param  string  $resource  Nombre del recurso del proveedor implicado en la operación.
     * @return list<int>
     */
    public function mapResourceReferences(array $payload, string $field, string $resource): array
    {
        $references = $this->requireArray($payload, $field);

        if (! array_is_list($references)) {
            throw $this->invalid($field, 'debe ser una lista');
        }

        $ids = [];

        foreach ($references as $index => $reference) {
            if (! is_string($reference) || $reference === '') {
                throw $this->invalid("$field.$index", 'debe ser una URL no vacía');
            }

            $ids[] = $this->resourceIdFromUrl($reference, $resource, "$field.$index");
        }

        if (count($ids) !== count(array_unique($ids))) {
            throw $this->invalid($field, 'no debe contener recursos duplicados');
        }

        return $ids;
    }

    /**
     * Extrae un identificador de recurso positivo desde una URL del proveedor.
     *
     * @param  string  $url  URL externa cuyo protocolo o referencia se debe validar.
     * @param  string  $resource  Nombre del recurso del proveedor implicado en la operación.
     * @param  string  $field  Nombre del campo que se valida y se identifica en los errores.
     */
    private function resourceIdFromUrl(string $url, string $resource, string $field): int
    {
        if (! $this->isHttpUrl($url)) {
            throw $this->invalid($field, 'debe ser una URL HTTP válida');
        }

        $path = parse_url($url, PHP_URL_PATH);
        $pattern = sprintf('~/%s/(\d+)/?$~', preg_quote($resource, '~'));

        if (! is_string($path) || preg_match($pattern, $path, $matches) !== 1) {
            throw $this->invalid($field, "debe referenciar un recurso de tipo [$resource]");
        }

        $id = filter_var($matches[1], FILTER_VALIDATE_INT);

        if (! is_int($id) || $id < 1) {
            throw $this->invalid($field, 'debe referenciar un identificador positivo');
        }

        return $id;
    }

    /**
     * Exige que un campo contenga un array.
     *
     * @param  array<string, mixed>  $payload  Objeto JSON externo todavía no validado; solo se aceptan los campos del contrato.
     * @param  string  $field  Nombre del campo que se valida y se identifica en los errores.
     * @return array<string, mixed>|list<mixed>
     */
    public function requireArray(array $payload, string $field): array
    {
        if (! array_key_exists($field, $payload) || ! is_array($payload[$field])) {
            throw $this->invalid($field, 'debe ser un array');
        }

        return $payload[$field];
    }

    /**
     * Exige que un campo contenga un entero positivo.
     *
     * @param  array<string, mixed>  $payload  Objeto JSON externo todavía no validado; solo se aceptan los campos del contrato.
     * @param  string  $field  Nombre del campo que se valida y se identifica en los errores.
     */
    public function requirePositiveInt(array $payload, string $field): int
    {
        $value = $this->requireNonNegativeInt($payload, $field);

        if ($value < 1) {
            throw $this->invalid($field, 'debe ser un entero positivo');
        }

        return $value;
    }

    /**
     * Exige que un campo contenga un entero no negativo.
     *
     * @param  array<string, mixed>  $payload  Objeto JSON externo todavía no validado; solo se aceptan los campos del contrato.
     * @param  string  $field  Nombre del campo que se valida y se identifica en los errores.
     */
    public function requireNonNegativeInt(array $payload, string $field): int
    {
        if (! array_key_exists($field, $payload) || ! is_int($payload[$field]) || $payload[$field] < 0) {
            throw $this->invalid($field, 'debe ser un entero no negativo');
        }

        return $payload[$field];
    }

    /**
     * Exige que un campo contenga texto, admitiendo opcionalmente un valor vacío.
     *
     * @param  array<string, mixed>  $payload  Objeto JSON externo todavía no validado; solo se aceptan los campos del contrato.
     * @param  string  $field  Nombre del campo que se valida y se identifica en los errores.
     * @param  bool  $allowEmpty  Permite cadenas vacías o en blanco cuando el contrato del campo lo admite.
     */
    public function requireString(array $payload, string $field, bool $allowEmpty = false): string
    {
        if (! array_key_exists($field, $payload) || ! is_string($payload[$field])) {
            throw $this->invalid($field, 'debe ser una cadena de texto');
        }

        if (! $allowEmpty && trim($payload[$field]) === '') {
            throw $this->invalid($field, 'no debe estar vacío');
        }

        return $payload[$field];
    }

    /**
     * Exige que un campo contenga uno de los valores de texto admitidos.
     *
     * @param  array<string, mixed>  $payload  Objeto JSON externo todavía no validado; solo se aceptan los campos del contrato.
     * @param  string  $field  Nombre del campo que se valida y se identifica en los errores.
     * @param  list<string>  $allowedValues  Valores de texto admitidos; la comparación distingue mayúsculas.
     */
    public function requireOneOf(array $payload, string $field, array $allowedValues): string
    {
        $value = $this->requireString($payload, $field);

        if (! in_array($value, $allowedValues, true)) {
            throw $this->invalid($field, 'contiene un valor no admitido');
        }

        return $value;
    }

    /**
     * Exige que un campo contenga una URL absoluta válida.
     *
     * @param  array<string, mixed>  $payload  Objeto JSON externo todavía no validado; solo se aceptan los campos del contrato.
     * @param  string  $field  Nombre del campo que se valida y se identifica en los errores.
     */
    public function requireUrl(array $payload, string $field): string
    {
        $url = $this->requireString($payload, $field);

        if (! $this->isHttpUrl($url)) {
            throw $this->invalid($field, 'debe ser una URL HTTP válida');
        }

        return $url;
    }

    /**
     * Comprueba que una URL absoluta utiliza un protocolo admitido por la integración.
     *
     * @param  string  $url  URL externa cuyo protocolo o referencia se debe validar.
     */
    public function isHttpUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true);
    }

    /**
     * Construye una excepción coherente para datos no válidos del proveedor.
     *
     * @param  string  $field  Nombre del campo que se valida y se identifica en los errores.
     * @param  string  $reason  Motivo de rechazo del campo, sin incluir el contenido de la respuesta.
     */
    public function invalid(string $field, string $reason): InvalidRickAndMortyResponseException
    {
        return new InvalidRickAndMortyResponseException("El campo [$field] de la respuesta de Rick and Morty no es válido: $reason.");
    }
}
