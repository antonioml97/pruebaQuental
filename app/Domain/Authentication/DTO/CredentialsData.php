<?php

declare(strict_types=1);

/**
 * Contiene credenciales validadas para iniciar sesión.
 */

namespace App\Domain\Authentication\DTO;

/**
 * Transporta credenciales sin acoplar el servicio de autenticación a HTTP.
 */
final readonly class CredentialsData
{
    /**
     * Crea unas credenciales ya validadas estructuralmente.
     *
     * @param  string  $email  Correo normalizado presentado por el usuario.
     * @param  string  $password  Contraseña presentada para su comprobación inmediata.
     */
    public function __construct(
        /** Correo normalizado presentado por el usuario. */
        public string $email,
        /** Contraseña presentada para su comprobación inmediata. */
        public string $password,
    ) {}
}
