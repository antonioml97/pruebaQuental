<?php

declare(strict_types=1);

/**
 * Verifica mensajes en castellano sin cambiar los códigos ni el contexto de los errores.
 */

namespace Tests\Unit\RickAndMorty;

use App\Domain\RickAndMorty\Exceptions\InvalidRickAndMortyResponseException;
use App\Domain\RickAndMorty\Exceptions\RickAndMortyRequestException;
use App\Domain\RickAndMorty\Exceptions\RickAndMortySynchronizationException;
use App\Services\RickAndMorty\Mapping\ResponsePayloadReader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/** Mantiene mensajes legibles y conserva las causas originales para diagnóstico. */
final class ExceptionMessagesTest extends TestCase
{
    /** Conserva el recurso, estado y causa al traducir errores del transporte externo. */
    public function test_request_failures_use_spanish_messages(): void
    {
        $cause = new RuntimeException('Fallo de conexión simulado');
        $connection = RickAndMortyRequestException::connectionFailed('character', $cause);
        $status = RickAndMortyRequestException::unexpectedStatus('episode', 503);

        $this->assertSame('No se pudo conectar con la API de Rick and Morty al solicitar el recurso [character].', $connection->getMessage());
        $this->assertSame('character', $connection->resource);
        $this->assertSame($cause, $connection->getPrevious());
        $this->assertNull($connection->statusCode);
        $this->assertSame('La petición del recurso [episode] a la API de Rick and Morty falló con el estado HTTP [503].', $status->getMessage());
        $this->assertSame(503, $status->statusCode);
    }

    /** Conserva las etapas y referencias que consumen el comando y las pruebas. */
    public function test_synchronization_failures_use_spanish_messages(): void
    {
        $cause = new RuntimeException('Fallo original simulado');
        $source = RickAndMortySynchronizationException::sourceFailed('location', 6, $cause);
        $pagination = RickAndMortySynchronizationException::invalidPagination('character', 2);
        $duplicate = RickAndMortySynchronizationException::duplicateExternalId('episode', 1);
        $reference = RickAndMortySynchronizationException::missingReference('location', 20, 1);
        $persistence = RickAndMortySynchronizationException::persistenceFailed($cause);

        $this->assertSame('La sincronización de Rick and Morty falló al obtener la página [6] del recurso [location].', $source->getMessage());
        $this->assertSame('fetch', $source->stage);
        $this->assertSame('location', $source->resource);
        $this->assertSame(6, $source->page);
        $this->assertSame($cause, $source->getPrevious());
        $this->assertSame('La paginación del recurso [character] de Rick and Morty es inconsistente en la página [2].', $pagination->getMessage());
        $this->assertSame('El identificador externo [1] del recurso [episode] de Rick and Morty está duplicado.', $duplicate->getMessage());
        $this->assertSame('El personaje [1] de Rick and Morty referencia el recurso inexistente [location] con identificador [20].', $reference->getMessage());
        $this->assertSame('validation', $reference->stage);
        $this->assertSame('No se pudo guardar la sincronización de Rick and Morty.', $persistence->getMessage());
        $this->assertSame('persistence', $persistence->stage);
        $this->assertSame($cause, $persistence->getPrevious());
    }

    /** Traduce la envoltura y el motivo sin alterar el nombre original del campo externo. */
    public function test_payload_validation_uses_spanish_messages(): void
    {
        $this->expectException(InvalidRickAndMortyResponseException::class);
        $this->expectExceptionMessage('El campo [name] de la respuesta de Rick and Morty no es válido: debe ser una cadena de texto.');

        (new ResponsePayloadReader)->requireString([], 'name');
    }
}
