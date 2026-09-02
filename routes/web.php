<?php

declare(strict_types=1);

/**
 * Define las rutas web servidas por la aplicación.
 */

use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

Route::get('/',
    /** Entrega el documento inicial donde se monta la aplicación Vue. */
    function (): View {
        return view('app');
    });

Route::get('/docs',
    /** Entrega la interfaz interactiva generada desde el contrato OpenAPI. */
    function (): View {
        return view('swagger');
    })->name('docs.swagger');

Route::get('/docs/openapi.yaml',
    /** Sirve la fuente YAML consumida por Swagger UI y otras herramientas. */
    function (): BinaryFileResponse {
        return response()->file(base_path('docs/openapi.yaml'), [
            'Content-Type' => 'application/yaml; charset=UTF-8',
        ]);
    })->name('docs.openapi');
