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
     */
    public static function sourceFailed(
        string $resource,
        int $page,
        Throwable $previous,
    ): self {
        return new self(
            message: "Rick and Morty synchronization failed while fetching [$resource] page [$page].",
            stage: 'fetch',
            resource: $resource,
            page: $page,
            previous: $previous,
        );
    }

    /**
     * Indica que la paginación cambió o quedó incompleta durante la descarga.
     */
    public static function invalidPagination(string $resource, int $page): self
    {
        return new self(
            message: "Rick and Morty [$resource] pagination is inconsistent at page [$page].",
            stage: 'validation',
            resource: $resource,
            page: $page,
        );
    }

    /**
     * Indica que el proveedor repitió un identificador entre páginas.
     */
    public static function duplicateExternalId(string $resource, int $externalId): self
    {
        return new self(
            message: "Rick and Morty [$resource] external identifier [$externalId] is duplicated.",
            stage: 'validation',
            resource: $resource,
        );
    }

    /**
     * Indica que un personaje referencia un recurso externo inexistente.
     */
    public static function missingReference(
        string $resource,
        int $externalId,
        int $characterExternalId,
    ): self {
        return new self(
            message: "Rick and Morty character [$characterExternalId] references missing [$resource] [$externalId].",
            stage: 'validation',
            resource: $resource,
        );
    }

    /**
     * Traduce un fallo de base de datos después de revertir la transacción.
     */
    public static function persistenceFailed(Throwable $previous): self
    {
        return new self(
            message: 'Rick and Morty synchronization could not be persisted.',
            stage: 'persistence',
            previous: $previous,
        );
    }
}
