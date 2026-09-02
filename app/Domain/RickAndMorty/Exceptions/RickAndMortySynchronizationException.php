<?php

declare(strict_types=1);

/**
 * Define errores controlados durante una sincronización de Rick and Morty.
 */

namespace App\Domain\RickAndMorty\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Identifica la etapa y el recurso que impidieron completar la sincronización.
 */
final class RickAndMortySynchronizationException extends RuntimeException
{
    /** Etapa funcional en la que ocurrió el fallo. */
    public readonly string $stage;

    /** Recurso implicado, si el fallo puede asociarse a uno. */
    public readonly ?string $resource;

    /** Página externa implicada, si el fallo ocurrió durante la descarga. */
    public readonly ?int $page;

    /**
     * Crea una excepción controlada con contexto observable y seguro.
     *
     * @param  string  $message  Descripción controlada del fallo, sin el cuerpo de la respuesta externa.
     * @param  string  $stage  Etapa de la sincronización: descarga, validación o persistencia.
     * @param  string|null  $resource  Nombre del recurso del proveedor implicado en la operación.
     * @param  int|null  $page  Página externa implicada, o null cuando el fallo no corresponde a una descarga.
     * @param  Throwable|null  $previous  Causa original que se conserva en la cadena de excepciones. Null cuando no existe una causa previa.
     */
    private function __construct(
        string $message,
        string $stage,
        ?string $resource = null,
        ?int $page = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);

        $this->stage = $stage;
        $this->resource = $resource;
        $this->page = $page;
    }

    /**
     * Indica que una página externa no pudo descargarse o validarse.
     *
     * @param  string  $resource  Nombre del recurso del proveedor implicado en la operación.
     * @param  int  $page  Número de página del proveedor, comenzando en uno.
     * @param  Throwable  $previous  Causa original que se conserva en la cadena de excepciones.
     */
    public static function sourceFailed(
        string $resource,
        int $page,
        Throwable $previous,
    ): self {
        return new self(
            message: "La sincronización de Rick and Morty falló al obtener la página [$page] del recurso [$resource].",
            stage: 'fetch',
            resource: $resource,
            page: $page,
            previous: $previous,
        );
    }

    /**
     * Indica que la paginación cambió o quedó incompleta durante la descarga.
     *
     * @param  string  $resource  Nombre del recurso del proveedor implicado en la operación.
     * @param  int  $page  Número de página del proveedor, comenzando en uno.
     */
    public static function invalidPagination(string $resource, int $page): self
    {
        return new self(
            message: "La paginación del recurso [$resource] de Rick and Morty es inconsistente en la página [$page].",
            stage: 'validation',
            resource: $resource,
            page: $page,
        );
    }

    /**
     * Indica que el proveedor repitió un identificador entre páginas.
     *
     * @param  string  $resource  Nombre del recurso del proveedor implicado en la operación.
     * @param  int  $externalId  Identificador público del proveedor, no la clave local de Eloquent.
     */
    public static function duplicateExternalId(string $resource, int $externalId): self
    {
        return new self(
            message: "El identificador externo [$externalId] del recurso [$resource] de Rick and Morty está duplicado.",
            stage: 'validation',
            resource: $resource,
        );
    }

    /**
     * Indica que un personaje referencia un recurso externo inexistente.
     *
     * @param  string  $resource  Nombre del recurso del proveedor implicado en la operación.
     * @param  int  $externalId  Identificador público del proveedor, no la clave local de Eloquent.
     * @param  int  $characterExternalId  Identificador externo del personaje cuya referencia se está resolviendo.
     */
    public static function missingReference(
        string $resource,
        int $externalId,
        int $characterExternalId,
    ): self {
        return new self(
            message: "El personaje [$characterExternalId] de Rick and Morty referencia el recurso inexistente [$resource] con identificador [$externalId].",
            stage: 'validation',
            resource: $resource,
        );
    }

    /**
     * Traduce un fallo de base de datos después de revertir la transacción.
     *
     * @param  Throwable  $previous  Causa original que se conserva en la cadena de excepciones.
     */
    public static function persistenceFailed(Throwable $previous): self
    {
        return new self(
            message: 'No se pudo guardar la sincronización de Rick and Morty.',
            stage: 'persistence',
            previous: $previous,
        );
    }
}
