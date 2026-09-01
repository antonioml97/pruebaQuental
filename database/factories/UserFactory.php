<?php

declare(strict_types=1);

/**
 * Genera usuarios válidos para pruebas y datos de desarrollo.
 */

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Construye estados predeterminados del modelo de usuario.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /** Contraseña cifrada reutilizada durante una ejecución de la factoría. */
    protected static ?string $password;

    /**
     * Define el estado predeterminado de un usuario.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Crea un estado cuyo correo todavía no está verificado.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
