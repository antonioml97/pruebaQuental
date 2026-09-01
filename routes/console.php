<?php

declare(strict_types=1);

/**
 * Define comandos de consola basados en closures.
 */

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/** Muestra una cita inspiradora desde el comando de ejemplo de Laravel. */
Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
