<?php

declare(strict_types=1);

/**
 * Define el token de acceso persistido por la autenticación propia.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa un secreto revocable almacenado exclusivamente mediante su hash.
 */
final class AccessToken extends Model
{
    /** @var list<string> Atributos asignables de forma masiva. */
    protected $fillable = [
        'name',
        'token_hash',
        'last_used_at',
        'expires_at',
    ];

    /** @var list<string> Atributos que nunca deben serializarse. */
    protected $hidden = [
        'token_hash',
    ];

    /**
     * Obtiene el propietario del token.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Define fechas inmutables para controlar uso y caducidad.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
