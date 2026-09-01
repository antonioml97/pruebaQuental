<?php

declare(strict_types=1);

/**
 * Define los fallos controlados al comunicarse con la API de Rick and Morty.
 */

namespace App\Domain\RickAndMorty\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Oculta los detalles del cliente HTTP y conserva contexto seguro del fallo externo.
 */
final class RickAndMortyRequestException extends RuntimeException
{
    /** Recurso externo que no pudo obtenerse. */
    public readonly string $resource;

    /** Estado HTTP recibido o null cuando no hubo respuesta. */
    public readonly ?int $statusCode;

    /**
     * Crea una excepción controlada para una petición externa.
     */
    private function __construct(
        string $message,
        string $resource,
        ?int $statusCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);

        $this->resource = $resource;
        $this->statusCode = $statusCode;
    }

    /**
     * Representa una petición que no llegó a recibir respuesta.
     */
    public static function connectionFailed(string $resource, Throwable $previous): self
    {
        return new self(
            message: "Could not connect to the Rick and Morty API while requesting [$resource].",
            resource: $resource,
            previous: $previous,
        );
    }

    /**
     * Representa una respuesta HTTP no satisfactoria sin exponer su cuerpo.
     */
    public static function unexpectedStatus(string $resource, int $statusCode): self
    {
        return new self(
            message: "Rick and Morty API request [$resource] failed with HTTP status [$statusCode].",
            resource: $resource,
            statusCode: $statusCode,
        );
    }
}
