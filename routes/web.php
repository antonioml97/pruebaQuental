<?php

declare(strict_types=1);

/**
 * Define las rutas web servidas por la aplicación.
 */

use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Muestra la página de bienvenida incluida en el esqueleto de Laravel. */
Route::get('/', function (): View {
    return view('welcome');
});

/** Entrega la interfaz interactiva generada desde el contrato OpenAPI. */
Route::get('/docs', function (): View {
    return view('swagger');
})->name('docs.swagger');

/** Sirve la fuente YAML consumida por Swagger UI y otras herramientas. */
Route::get('/docs/openapi.yaml', function (): BinaryFileResponse {
    return response()->file(base_path('docs/openapi.yaml'), [
        'Content-Type' => 'application/yaml; charset=UTF-8',
    ]);
})->name('docs.openapi');
