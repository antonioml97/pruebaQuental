<?php

declare(strict_types=1);

/**
 * Define los datos iniciales opcionales del entorno local.
 */

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Crea un usuario de ejemplo para facilitar el desarrollo local.
 */
final class DatabaseSeeder extends Seeder
{
    /** Evita disparar eventos de modelos durante la carga inicial. */
    use WithoutModelEvents;

    /**
     * Inserta los datos iniciales de la aplicación.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
