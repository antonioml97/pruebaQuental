<?php

declare(strict_types=1);

/**
 * Define el usuario autenticable persistido por Eloquent.
 */

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Representa un usuario local y protege sus credenciales serializadas.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Atributos asignables de forma masiva.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Atributos ocultos durante la serialización.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Obtiene los tokens de acceso emitidos al usuario.
     *
     * @return HasMany<AccessToken, $this>
     */
    public function accessTokens(): HasMany
    {
        return $this->hasMany(AccessToken::class);
    }

    /**
     * Obtiene los personajes marcados como favoritos por el usuario.
     *
     * @return BelongsToMany<Character, $this>
     */
    public function favoriteCharacters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'favorite_characters')->withTimestamps();
    }

    /**
     * Define las conversiones de atributos del usuario.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
