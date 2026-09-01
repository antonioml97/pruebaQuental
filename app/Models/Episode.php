<?php

declare(strict_types=1);

/**
 * Define el modelo persistente de un episodio de Rick and Morty.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Representa un episodio sincronizado y sus personajes.
 */
final class Episode extends Model
{
    /** @var list<string> Atributos asignables de forma masiva. */
    protected $fillable = [
        'external_id',
        'name',
        'air_date',
        'code',
    ];

    /**
     * Obtiene los personajes que aparecen en el episodio.
     *
     * @return BelongsToMany<Character, $this>
     */
    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class);
    }

    /**
     * Define las conversiones de atributos del episodio.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'external_id' => 'integer',
            'air_date' => 'immutable_date',
        ];
    }
}
