<?php

declare(strict_types=1);

/**
 * Adapta el inicio de sesión al contrato HTTP de autenticación.
 */

namespace App\Http\Controllers\Api\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Authentication\LoginRequest;
use App\Http\Responses\Authentication\AuthenticationResponseFactory;
use App\Services\Authentication\LoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Atiende exclusivamente la autenticación mediante credenciales.
 */
final class LoginController extends Controller
{
    /**
     * Delega la comprobación de credenciales y entrega una sesión independiente.
     *
     * @param  LoginRequest  $request  Petición con credenciales validadas y correo normalizado.
     * @param  LoginService  $authentication  Caso de uso que valida credenciales y emite una sesión independiente.
     * @param  AuthenticationResponseFactory  $responses  Compositor de identidad pública y cookie privada para la respuesta.
     */
    public function __invoke(
        LoginRequest $request,
        LoginService $authentication,
        AuthenticationResponseFactory $responses,
    ): JsonResponse {
        $result = $authentication->login($request->credentials());

        return $responses->make($result, Response::HTTP_OK);
    }
}
