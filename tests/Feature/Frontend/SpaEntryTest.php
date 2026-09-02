<?php

declare(strict_types=1);

/**
 * Verifica el documento HTML que permite montar la aplicación Vue.
 */

namespace Tests\Feature\Frontend;

use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * Permite enlaces directos, recargas y la resolución de rutas desconocidas en Vue.
     *
     * @param  string  $path  URL del cliente, incluidos parámetros o segmentos desconocidos.
     */
    #[DataProvider('spaPaths')]
    public function test_spa_paths_serve_the_entry_document(string $path): void
    {
        $this->withoutVite()->get($path)->assertOk()->assertViewIs('app');
    }

    /**
     * Proporciona rutas conocidas y desconocidas que pertenecen al cliente.
     *
     * @return array<string, array{string}>
     */
    public static function spaPaths(): array
    {
        return [
            'catálogo' => ['/characters?name=Rick&page=2'],
            'detalle' => ['/characters/42'],
            'login' => ['/login'],
            'registro' => ['/register'],
            'favoritos' => ['/favorites'],
            'desconocida' => ['/portal/desconocido'],
            'prefijo similar a api' => ['/apiary'],
            'prefijo similar a docs' => ['/docs-extra'],
            'prefijo similar a up' => ['/updates'],
        ];
    }

    /**
     * No convierte errores de rutas reservadas ni recursos estáticos en HTML de la SPA.
     *
     * @param  string  $path  Ruta reservada inexistente que debe conservar su error HTTP.
     */
    #[DataProvider('reservedPaths')]
    public function test_reserved_paths_do_not_use_the_spa_fallback(string $path): void
    {
        $this->withoutVite()->get($path)->assertNotFound()->assertDontSee('<div id="app"></div>', false);
    }

    /**
     * Cubre los límites de los prefijos del servidor y sus descendientes.
     *
     * @return array<string, array{string}>
     */
    public static function reservedPaths(): array
    {
        return [
            'raíz api' => ['/api'],
            'api desconocida' => ['/api/no-existe'],
            'documentación desconocida' => ['/docs/no-existe'],
            'health desconocido' => ['/up/no-existe'],
            'compilado desconocido' => ['/build/no-existe.js'],
        ];
    }

    /** Conserva Swagger, el contrato y la comprobación de salud fuera de Vue. */
    public function test_existing_server_pages_remain_available(): void
    {
        $this->withoutVite()->get('/docs')->assertOk()->assertViewIs('swagger');
        $this->get('/docs/openapi.yaml')->assertOk()->assertHeader('Content-Type', 'application/yaml; charset=UTF-8');
        $this->get('/up')->assertOk()->assertDontSee('<div id="app"></div>', false);
    }

    /** Conserva el formato JSON y la protección de la API privada. */
    public function test_api_responses_are_not_replaced_by_the_spa(): void
    {
        $this->getJson('/api/no-existe')->assertNotFound()->assertJsonStructure(['error' => ['code', 'message', 'details']]);
        $this->getJson('/api/favorites')->assertUnauthorized()->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }

    /** Conserva el rechazo del disco privado de Laravel cuando falta la firma. */
    public function test_private_storage_is_not_replaced_by_the_spa(): void
    {
        $this->getJson('/storage/no-existe.png')->assertForbidden()->assertDontSee('<div id="app"></div>', false);
    }

    /** Solo las consultas de documentos pueden utilizar el fallback. */
    public function test_the_spa_fallback_does_not_accept_state_changes(): void
    {
        $this->postJson('/characters')->assertStatus(405)->assertDontSee('<div id="app"></div>', false);
        $this->putJson('/favorites')->assertStatus(405)->assertDontSee('<div id="app"></div>', false);
        $this->deleteJson('/characters/42')->assertStatus(405)->assertDontSee('<div id="app"></div>', false);
    }

    /** HEAD permite comprobar una URL interna sin transferir el documento. */
    public function test_head_requests_to_spa_paths_are_supported(): void
    {
        $this->withoutVite()->head('/characters/42')->assertOk()->assertContent('');
    }
}
