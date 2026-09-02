<?php

declare(strict_types=1);

/**
 * Verifica decisiones de reintento sin llamadas HTTP ni esperas reales.
 */

namespace Tests\Unit\RickAndMorty;

use App\Services\RickAndMorty\RetryPolicy;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Cubre estados transitorios, Retry-After y la espera alternativa configurada.
 */
final class RetryPolicyTest extends TestCase
{
    /** Solo los fallos de conexión, 408, 429 y errores de servidor son recuperables. */
    public function test_it_retries_only_transient_failures(): void
    {
        $policy = new RetryPolicy;

        foreach ([400 => false, 401 => false, 404 => false, 408 => true, 429 => true, 500 => true, 503 => true] as $status => $expected) {
            self::assertSame($expected, $policy->shouldRetry($this->httpFailure($status)));
        }

        self::assertTrue($policy->shouldRetry(new ConnectionException('Timeout')));
        self::assertFalse($policy->shouldRetry(new RuntimeException('Fallo ajeno al transporte')));
    }

    /** El límite del proveedor se interpreta en segundos y se acota a sesenta. */
    public function test_it_respects_and_caps_retry_after_for_rate_limits(): void
    {
        $policy = new RetryPolicy;

        self::assertSame(2000, $policy->delayMilliseconds($this->httpFailure(429, '2'), 100));
        self::assertSame(60000, $policy->delayMilliseconds($this->httpFailure(429, '120'), 100));
    }

    /** Cabeceras ausentes o inválidas y otros errores conservan la espera configurada. */
    public function test_it_preserves_the_fallback_delay(): void
    {
        $policy = new RetryPolicy;

        foreach ([null, '', '0', '-1', '1.5', 'invalid'] as $header) {
            self::assertSame(100, $policy->delayMilliseconds($this->httpFailure(429, $header), 100));
        }

        self::assertSame(100, $policy->delayMilliseconds($this->httpFailure(503, '2'), 100));
        self::assertSame(100, $policy->delayMilliseconds($this->httpFailure(408, '2'), 100));
        self::assertSame(0, $policy->delayMilliseconds(new ConnectionException('Timeout'), 0));
    }

    /**
     * Construye una respuesta fallida en memoria con una cabecera opcional.
     *
     * @param  int  $status  Código de estado HTTP que debe simular la respuesta fallida.
     * @param  string|null  $retryAfter  Valor crudo de Retry-After, o null para simular la ausencia de cabecera.
     */
    private function httpFailure(int $status, ?string $retryAfter = null): RequestException
    {
        $headers = $retryAfter === null ? [] : ['Retry-After' => $retryAfter];

        return new RequestException(new Response(new PsrResponse($status, $headers)));
    }
}
