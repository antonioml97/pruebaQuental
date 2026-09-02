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
     *
     * @param  string  $message  Descripción controlada del fallo, sin el cuerpo de la respuesta externa.
     * @param  string  $resource  Nombre del recurso del proveedor implicado en la operación.
     * @param  int|null  $statusCode  Estado HTTP recibido del proveedor, o null si no hubo respuesta.
     * @param  Throwable|null  $previous  Causa original que se conserva en la cadena de excepciones. Null cuando no existe una causa previa.
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
     *
     * @param  string  $resource  Nombre del recurso del proveedor implicado en la operación.
     * @param  Throwable  $previous  Causa original que se conserva en la cadena de excepciones.
     */
    public static function connectionFailed(string $resource, Throwable $previous): self
    {
        return new self(
            message: "No se pudo conectar con la API de Rick and Morty al solicitar el recurso [$resource].",
            resource: $resource,
            previous: $previous,
        );
    }

    /**
     * Representa una respuesta HTTP no satisfactoria sin exponer su cuerpo.
     *
     * @param  string  $resource  Nombre del recurso del proveedor implicado en la operación.
     * @param  int  $statusCode  Estado HTTP no satisfactorio recibido del proveedor.
     */
    public static function unexpectedStatus(string $resource, int $statusCode): self
    {
        return new self(
            message: "La petición del recurso [$resource] a la API de Rick and Morty falló con el estado HTTP [$statusCode].",
            resource: $resource,
            statusCode: $statusCode,
        );
    }
}
