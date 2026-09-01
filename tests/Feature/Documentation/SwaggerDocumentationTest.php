<?php

declare(strict_types=1);

/**
 * Verifica la publicación local de la documentación interactiva de la API.
 */

namespace Tests\Feature\Documentation;

use Tests\TestCase;

/**
 * Garantiza que Swagger UI y su contrato OpenAPI sean accesibles por HTTP.
 */
final class SwaggerDocumentationTest extends TestCase
{
    /**
     * Comprueba que la página contiene el contenedor y la URL del contrato.
     */
    public function test_swagger_ui_is_available(): void
    {
        $this->withoutVite();

        $response = $this->get('/docs');

        $response
            ->assertOk()
            ->assertSee('id="swagger-ui"', false)
            ->assertSee(route('docs.openapi'), false);
    }

    /**
     * Comprueba que el documento OpenAPI se entrega como YAML.
     */
    public function test_openapi_contract_is_available(): void
    {
        $response = $this->get('/docs/openapi.yaml');

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/yaml; charset=UTF-8');

        $contract = file_get_contents(base_path('docs/openapi.yaml'));

        self::assertIsString($contract);
        self::assertStringContainsString('openapi: 3.1.0', $contract);
    }
}
