<?php

declare(strict_types=1);

/**
 * Define el modelo persistente de un personaje de Rick and Morty.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Representa un personaje sincronizado y sus relaciones principales.
 */
final class Character extends Model
{
    /** @var list<string> Atributos asignables de forma masiva. */
    protected $fillable = [
        'external_id',
        'name',
        'status',
        'species',
        'type',
        'gender',
        'image_url',
        'origin_location_id',
        'current_location_id',
    ];

    /**
     * Obtiene la localización de origen del personaje.
     *
     * @return BelongsTo<Location, $this>
     */
    public function origin(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'origin_location_id');
    }

    /**
     * Obtiene la última localización conocida del personaje.
     *
     * @return BelongsTo<Location, $this>
     */
    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    /**
     * Obtiene los episodios en los que aparece el personaje.
     *
     * @return BelongsToMany<Episode, $this>
     */
    public function episodes(): BelongsToMany
    {
        return $this->belongsToMany(Episode::class);
    }

    /**
     * Obtiene los usuarios que han marcado el personaje como favorito.
     *
     * @return BelongsToMany<User, $this>
     */
    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorite_characters')->withTimestamps();
    }

    /**
     * Define las conversiones de atributos del personaje.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'external_id' => 'integer',
            'origin_location_id' => 'integer',
            'current_location_id' => 'integer',
        ];
    }
}
