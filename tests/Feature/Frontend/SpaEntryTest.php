<?php

declare(strict_types=1);

/**
 * Verifica el documento HTML que permite montar la aplicación Vue.
 */

namespace Tests\Feature\Frontend;

use Tests\TestCase;

/**
 * Comprueba la entrada SPA sin depender de recursos compilados en la suite PHP.
 */
final class SpaEntryTest extends TestCase
{
    /**
     * Conserva el punto de montaje, el idioma y la alternativa sin JavaScript.
     */
    public function test_the_root_serves_the_vue_entry_document(): void
    {
        $this->withoutVite()
            ->get('/')
            ->assertOk()
            ->assertViewIs('app')
            ->assertSee('<html lang="es">', false)
            ->assertSee('<div id="app"></div>', false)
            ->assertSee('Activa JavaScript')
            ->assertDontSee('id="swagger-ui"', false);
    }
}
