<?php

declare(strict_types=1);

/**
 * Contiene los datos validados necesarios para registrar un usuario.
 */

namespace App\Domain\Authentication\DTO;

/**
 * Transporta datos de registro independientes del protocolo HTTP.
 */
final readonly class RegistrationData
{
    /**
     * Crea una solicitud de registro ya validada.
     *
     * @param  string  $name  Nombre público del usuario.
     * @param  string  $email  Correo normalizado que identifica al usuario.
     * @param  string  $password  Contraseña en claro que solo se conserva durante el registro.
     */
    public function __construct(
        /** Nombre público del usuario. */
        public string $name,
        /** Correo normalizado que identifica al usuario. */
        public string $email,
        /** Contraseña en claro que solo se conserva durante el registro. */
        public string $password,
    ) {}
}
