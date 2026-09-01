<?php

declare(strict_types=1);

/**
 * Verifica la ruta web inicial incluida con Laravel.
 */

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Comprueba que el esqueleto web permanece accesible.
 */
final class ExampleTest extends TestCase
{
    /**
     * Verifica que la página inicial responde correctamente.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
