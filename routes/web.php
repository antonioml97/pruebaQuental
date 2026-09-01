<?php

declare(strict_types=1);

/**
 * Define las rutas web servidas por la aplicación.
 */

use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/** Muestra la página de bienvenida incluida en el esqueleto de Laravel. */
Route::get('/', function (): View {
    return view('welcome');
});
