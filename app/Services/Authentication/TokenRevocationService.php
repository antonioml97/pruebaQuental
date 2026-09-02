<?php

declare(strict_types=1);

/**
 * Revoca sesiones propias sin gestionar cookies ni respuestas HTTP.
 */

namespace App\Services\Authentication;

use App\Models\AccessToken;

/**
 * Limita la revocación al token seleccionado por el flujo autenticado.
 */
final class TokenRevocationService
{
    /**
     * Invalida únicamente la sesión indicada, conservando las demás.
     *
     * @param  AccessToken  $accessToken  Sesión actual resuelta por autenticación; solo se elimina este registro.
     */
    public function revoke(AccessToken $accessToken): void
    {
        $accessToken->delete();
    }
}
