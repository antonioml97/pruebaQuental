<?php

declare(strict_types=1);

/**
 * Define el modelo persistente de una localización de Rick and Morty.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa una localización sincronizada y sus personajes relacionados.
 */
final class Location extends Model
{
    /** @var list<string> Atributos asignables de forma masiva. */
    protected $fillable = [
        'external_id',
        'name',
        'type',
        'dimension',
    ];

    /**
     * Obtiene los personajes cuyo origen es esta localización.
     *
     * @return HasMany<Character, $this>
     */
    public function originCharacters(): HasMany
    {
        return $this->hasMany(Character::class, 'origin_location_id');
    }

    /**
     * Obtiene los personajes cuya última ubicación conocida es esta localización.
     *
     * @return HasMany<Character, $this>
     */
    public function residents(): HasMany
    {
        return $this->hasMany(Character::class, 'current_location_id');
    }

    /**
     * Define las conversiones de atributos de la localización.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'external_id' => 'integer',
        ];
    }
}
