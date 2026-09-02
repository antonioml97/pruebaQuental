<?php

declare(strict_types=1);

/**
 * Decide qué errores temporales reintentar y cuánto esperar según el proveedor.
 */

namespace App\Services\RickAndMorty;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Throwable;

/**
 * Decide qué errores temporales reintentar y cuánto esperar según el proveedor.
 */
final class RetryPolicy
{
    /** Estados transitorios adicionales a los errores de servidor. */
    private const RETRYABLE_STATUS_CODES = [408, 429];

    /** Límite defensivo de Retry-After, en segundos. */
    private const MAX_RETRY_AFTER_SECONDS = 60;

    /**
     * Respeta `Retry-After` para límites HTTP 429 y usa la espera configurada en el resto.
     *
     * @param  Throwable  $exception  Fallo del intento actual que se evalúa para decidir el reintento.
     * @param  int  $configuredDelay  Espera de respaldo en milisegundos, previamente validada como no negativa.
     * @return int Espera en milisegundos; Retry-After se convierte desde segundos y se limita a 60000.
     */
    public function delayMilliseconds(Throwable $exception, int $configuredDelay): int
    {
        if (! $exception instanceof RequestException || $exception->response->status() !== 429) {
            return $configuredDelay;
        }

        $retryAfter = filter_var(
            $exception->response->header('Retry-After'),
            FILTER_VALIDATE_INT,
        );

        if (! is_int($retryAfter) || $retryAfter < 1) {
            return $configuredDelay;
        }

        return min($retryAfter, self::MAX_RETRY_AFTER_SECONDS) * 1000;
    }

    /**
     * Determina si un fallo es temporal y admite otro intento.
     *
     * @param  Throwable  $exception  Fallo del intento actual que se evalúa para decidir el reintento.
     */
    public function shouldRetry(Throwable $exception): bool
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
}
