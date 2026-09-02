<?php

declare(strict_types=1);

/**
 * Proporciona respuestas externas canónicas para las pruebas de transformación.
 */

namespace Tests\Support;

/** Datos de ejemplo compartidos, sin aserciones ni lógica de producción. */
final class RickAndMortyPayloads
{
    /**
     * Devuelve un personaje completo conforme al esquema documentado del proveedor.
     *
     * @return array<string, mixed>
     */
    public static function character(): array
    {
        return [
            'id' => 1,
            'name' => 'Rick Sanchez',
            'status' => 'Alive',
            'species' => 'Human',
            'type' => '',
            'gender' => 'Male',
            'origin' => [
                'name' => 'unknown',
                'url' => '',
            ],
            'location' => [
                'name' => 'Earth (Replacement Dimension)',
                'url' => 'https://rickandmortyapi.com/api/location/20',
            ],
            'image' => 'https://rickandmortyapi.com/api/character/avatar/1.jpeg',
            'episode' => [
                'https://rickandmortyapi.com/api/episode/1',
                'https://rickandmortyapi.com/api/episode/2',
            ],
        ];
    }

    /**
     * Devuelve un episodio con una fecha y un código válidos.
     *
     * @return array<string, mixed>
     */
    public static function episode(): array
    {
        return [
            'id' => 1,
            'name' => 'Pilot',
            'air_date' => 'December 2, 2013',
            'episode' => 'S01E01',
        ];
    }

    /**
     * Devuelve una localización completa del proveedor.
     *
     * @return array<string, mixed>
     */
    public static function location(): array
    {
        return [
            'id' => 20,
            'name' => 'Earth (Replacement Dimension)',
            'type' => 'Planet',
            'dimension' => 'Replacement Dimension',
        ];
    }
}
