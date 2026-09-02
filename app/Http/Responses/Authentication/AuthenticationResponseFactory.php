<?php

declare(strict_types=1);

/**
 * Construye la respuesta HTTP compartida por registro e inicio de sesión.
 */

namespace App\Http\Responses\Authentication;

use App\Domain\Authentication\DTO\AuthenticationResultData;
use App\Http\Cookies\AuthenticationCookieFactory;
use App\Http\Resources\Authentication\AuthenticationResultResource;
use Illuminate\Http\JsonResponse;

/**
 * Mantiene el token fuera del JSON y lo entrega exclusivamente en su cookie privada.
 */
final class AuthenticationResponseFactory
{
    /** Constructor de cookies que centraliza sus atributos de seguridad. */
    private readonly AuthenticationCookieFactory $cookies;

    /**
     * Recibe el adaptador HTTP que construye la cookie de sesión.
     *
     * @param  AuthenticationCookieFactory  $cookies  Adaptador HTTP que aplica los atributos de seguridad de las cookies.
     */
    public function __construct(AuthenticationCookieFactory $cookies)
    {
        $this->cookies = $cookies;
    }

    /**
     * Combina identidad pública, estado HTTP y cookie HttpOnly sin duplicar el contrato.
     *
     * @param  AuthenticationResultData  $result  Identidad y credencial emitidas; el secreto se entrega solo en la cookie.
     * @param  int  $status  Código de estado HTTP que llevará la respuesta pública.
     */
    public function make(AuthenticationResultData $result, int $status): JsonResponse
    {
        return (new AuthenticationResultResource($result))
            ->response()
            ->setStatusCode($status)
            ->withCookie($this->cookies->accessToken($result->plainTextToken, $result->expiresAt));
    }
}
