<?php

declare(strict_types=1);

/**
 * Expone la identidad pública del usuario autenticado en la petición.
 */

namespace App\Http\Controllers\Api\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Resources\Authentication\AuthenticatedUserResource;
use App\Models\User;
use Illuminate\Http\Request;
use LogicException;

/**
 * Consulta el usuario actual sin emitir ni revocar sesiones.
 */
final class AuthenticatedUserController extends Controller
{
    /**
     * Serializa únicamente la identidad resuelta por el middleware de autenticación.
     *
     * @param  Request  $request  Petición cuya identidad y sesión ya resolvió el middleware auth.token.
     */
    public function __invoke(Request $request): AuthenticatedUserResource
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new LogicException('El middleware no resolvió un usuario autenticado.');
        }

        return new AuthenticatedUserResource($user);
    }
}
