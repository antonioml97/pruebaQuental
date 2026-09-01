<?php

declare(strict_types=1);

/**
 * Define los endpoints REST públicos de la aplicación.
 */

use App\Http\Controllers\Api\Authentication\AuthenticationController;
use App\Http\Controllers\Api\Authentication\CsrfCookieController;
use App\Http\Controllers\Api\CharacterController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('api.auth.')->middleware('csrf')->group(function (): void {
    Route::get('csrf-cookie', CsrfCookieController::class)->name('csrf-cookie');

    Route::middleware('throttle:authentication')->group(function (): void {
        Route::post('register', [AuthenticationController::class, 'register'])->name('register');
        Route::post('login', [AuthenticationController::class, 'login'])->name('login');
    });

    Route::middleware('auth.token')->group(function (): void {
        Route::get('user', [AuthenticationController::class, 'user'])->name('user');
        Route::post('logout', [AuthenticationController::class, 'logout'])->name('logout');
    });
});

Route::get('characters', [CharacterController::class, 'index'])
    ->name('api.characters.index');
Route::get('characters/{externalId}', [CharacterController::class, 'show'])
    ->whereNumber('externalId')
    ->name('api.characters.show');
