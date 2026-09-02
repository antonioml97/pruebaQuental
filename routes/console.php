<?php

declare(strict_types=1);

/**
 * Define comandos de consola basados en closures.
 */

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire',
    /** Muestra una cita inspiradora desde el comando de ejemplo de Laravel. */
    function (): void {
        $this->comment(Inspiring::quote());
    })->purpose('Display an inspiring quote');
