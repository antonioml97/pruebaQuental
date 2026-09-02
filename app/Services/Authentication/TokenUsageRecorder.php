<?php

declare(strict_types=1);

/**
 * Registra actividad de una sesión ya validada, sin renovar su caducidad.
 */

namespace App\Services\Authentication;

use App\Models\AccessToken;

/**
 * Registra actividad de una sesión ya validada, sin renovar su caducidad.
 */
final class TokenUsageRecorder
{
    /** Minutos mínimos entre escrituras de actividad. */
    private const LAST_USED_UPDATE_INTERVAL = 5;

    /**
     * Registra autenticaciones satisfactorias sin escribir en cada petición.
     *
     * @param  AccessToken  $accessToken  Sesión ya validada cuya última actividad se actualiza como máximo cada cinco minutos.
     */
    public function record(AccessToken $accessToken): void
    {
        if ($accessToken->last_used_at !== null
            && $accessToken->last_used_at->isAfter(now()->subMinutes(self::LAST_USED_UPDATE_INTERVAL))
        ) {
            return;
        }

        $accessToken->forceFill(['last_used_at' => now()])->saveQuietly();
    }
}
