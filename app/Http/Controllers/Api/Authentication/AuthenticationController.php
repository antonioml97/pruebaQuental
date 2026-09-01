<?php

declare(strict_types=1);

/**
 * Adapta registro, inicio y cierre de sesión al transporte HTTP.
 */

namespace App\Http\Controllers\Api\Authentication;

use App\Domain\Authentication\DTO\AuthenticationResultData;
use App\Http\Controllers\Controller;
use App\Http\Cookies\AuthenticationCookieFactory;
use App\Http\Requests\Api\Authentication\LoginRequest;
use App\Http\Requests\Api\Authentication\RegisterRequest;
use App\Http\Resources\Authentication\AuthenticatedUserResource;
use App\Http\Resources\Authentication\AuthenticationResultResource;
use App\Models\AccessToken;
use App\Models\User;
use App\Services\Authentication\TokenAuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use LogicException;

/**
 * Mantiene la lógica de credenciales y tokens fuera del controlador.
 */
final class AuthenticationController extends Controller
{
    /**
     * Registra un usuario, establece su cookie privada y devuelve su identidad.
     */
    public function register(
        RegisterRequest $request,
        TokenAuthenticationService $authentication,
        AuthenticationCookieFactory $cookies,
    ): JsonResponse {
        $result = $authentication->register($request->registration());

        return $this->authenticatedResponse($result, $cookies, Response::HTTP_CREATED);
    }

    /**
     * Comprueba credenciales y establece una nueva sesión independiente.
     */
    public function login(
        LoginRequest $request,
        TokenAuthenticationService $authentication,
        AuthenticationCookieFactory $cookies,
    ): JsonResponse {
        $result = $authentication->login($request->credentials());

        return $this->authenticatedResponse($result, $cookies, Response::HTTP_OK);
    }

    /**
     * Devuelve la identidad asociada a la cookie autenticada.
     */
    public function user(Request $request): AuthenticatedUserResource
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new LogicException('El middleware no resolvió un usuario autenticado.');
        }

        return new AuthenticatedUserResource($user);
    }

    /**
     * Revoca el token actual y retira la cookie del navegador.
     */
    public function logout(
        Request $request,
        TokenAuthenticationService $authentication,
        AuthenticationCookieFactory $cookies,
    ): Response {
        $accessToken = $request->attributes->get(AccessToken::class);

        if (! $accessToken instanceof AccessToken) {
            throw new LogicException('El middleware no resolvió un token autenticado.');
        }

        $authentication->logout($accessToken);

        return response()->noContent()->withCookie($cookies->forgetAccessToken());
    }

    /**
     * Construye la respuesta JSON sin exponer el secreto y adjunta su cookie.
     */
    private function authenticatedResponse(
        AuthenticationResultData $result,
        AuthenticationCookieFactory $cookies,
        int $status,
    ): JsonResponse {
        return (new AuthenticationResultResource($result))
            ->response()
            ->setStatusCode($status)
            ->withCookie($cookies->accessToken($result->plainTextToken, $result->expiresAt));
    }
}
