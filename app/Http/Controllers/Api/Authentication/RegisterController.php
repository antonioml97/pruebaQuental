<?php

declare(strict_types=1);

/**
 * Adapta el registro de usuarios al contrato HTTP de autenticación.
 */

namespace App\Http\Controllers\Api\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Authentication\RegisterRequest;
use App\Http\Responses\Authentication\AuthenticationResponseFactory;
use App\Services\Authentication\RegisterUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Atiende exclusivamente el alta de usuarios y la entrega de su primera sesión.
 */
final class RegisterController extends Controller
{
    /**
     * Delega el registro y responde con identidad pública y cookie privada.
     *
     * @param  RegisterRequest  $request  Petición con datos de alta validados y correo normalizado.
     * @param  RegisterUserService  $authentication  Caso de uso que crea usuario y sesión en una sola transacción.
     * @param  AuthenticationResponseFactory  $responses  Compositor de identidad pública y cookie privada para la respuesta.
     */
    public function __invoke(
        RegisterRequest $request,
        RegisterUserService $authentication,
        AuthenticationResponseFactory $responses,
    ): JsonResponse {
        $result = $authentication->register($request->registration());

        return $responses->make($result, Response::HTTP_CREATED);
    }
}
