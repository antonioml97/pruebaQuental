<?php

declare(strict_types=1);

/**
 * Define los endpoints REST públicos de la aplicación.
 */

use App\Http\Controllers\Api\CharacterController;
use Illuminate\Support\Facades\Route;

Route::get('characters', [CharacterController::class, 'index'])
    ->name('api.characters.index');
Route::get('characters/{externalId}', [CharacterController::class, 'show'])
    ->whereNumber('externalId')
    ->name('api.characters.show');
