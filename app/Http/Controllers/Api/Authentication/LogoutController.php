<?php

declare(strict_types=1);

/**
 * Adapta el cierre de la sesión actual al transporte HTTP.
 */

namespace App\Http\Controllers\Api\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Cookies\AuthenticationCookieFactory;
use App\Models\AccessToken;
use App\Services\Authentication\TokenRevocationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use LogicException;

/**
 * Revoca la sesión autenticada y retira su cookie del navegador.
 */
final class LogoutController extends Controller
{
    /**
     * Delega la revocación del token resuelto previamente por el middleware.
     *
     * @param  Request  $request  Petición cuya identidad y sesión ya resolvió el middleware auth.token.
     * @param  TokenRevocationService  $revocation  Revocador de la sesión actual que conserva las demás sesiones.
     * @param  AuthenticationCookieFactory  $cookies  Adaptador HTTP que aplica los atributos de seguridad de las cookies.
     */
    public function __invoke(
        Request $request,
        TokenRevocationService $revocation,
        AuthenticationCookieFactory $cookies,
    ): Response {
        $accessToken = $request->attributes->get(AccessToken::class);

        if (! $accessToken instanceof AccessToken) {
            throw new LogicException('El middleware no resolvió un token autenticado.');
        }

        $revocation->revoke($accessToken);

        return response()->noContent()->withCookie($cookies->forgetAccessToken());
    }
}
