<?php

declare(strict_types=1);

/**
 * Define los endpoints REST públicos de la aplicación.
 */

use App\Http\Controllers\Api\Authentication\AuthenticatedUserController;
use App\Http\Controllers\Api\Authentication\CsrfCookieController;
use App\Http\Controllers\Api\Authentication\LoginController;
use App\Http\Controllers\Api\Authentication\LogoutController;
use App\Http\Controllers\Api\Authentication\RegisterController;
use App\Http\Controllers\Api\CharacterController;
use App\Http\Controllers\Api\Favorites\FavoriteCharacterController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('api.auth.')->middleware('csrf')->group(
    /**
     * Declara las rutas de autenticación con protección CSRF compartida.
     */
    function (): void {
        Route::get('csrf-cookie', CsrfCookieController::class)->name('csrf-cookie');

        Route::middleware('throttle:authentication')->group(
            /**
             * Agrupa registro y acceso bajo el límite de intentos de autenticación.
             */
            function (): void {
                Route::post('register', RegisterController::class)->name('register');
                Route::post('login', LoginController::class)->name('login');
            });

        Route::middleware('auth.token')->group(
            /**
             * Declara las operaciones que requieren una sesión opaca válida.
             */
            function (): void {
                Route::get('user', AuthenticatedUserController::class)->name('user');
                Route::post('logout', LogoutController::class)->name('logout');
            });
    });

Route::prefix('favorites')
    ->name('api.favorites.')
    ->middleware(['csrf', 'auth.token'])
    ->group(
        /**
         * Declara favoritos privados con autenticación y protección CSRF.
         */
        function (): void {
            Route::get('/', [FavoriteCharacterController::class, 'index'])->name('index');
            Route::put('{externalId}', [FavoriteCharacterController::class, 'store'])
                ->whereNumber('externalId')
                ->name('store');
            Route::delete('{externalId}', [FavoriteCharacterController::class, 'destroy'])
                ->whereNumber('externalId')
                ->name('destroy');
        });

Route::get('characters', [CharacterController::class, 'index'])
    ->name('api.characters.index');
Route::get('characters/{externalId}', [CharacterController::class, 'show'])
    ->whereNumber('externalId')
    ->name('api.characters.show');
