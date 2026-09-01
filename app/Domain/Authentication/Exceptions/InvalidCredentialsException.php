<?php

declare(strict_types=1);

/**
 * Define el fallo controlado producido por credenciales incorrectas.
 */

namespace App\Domain\Authentication\Exceptions;

use RuntimeException;

/**
 * Evita revelar desde el caso de uso si falló el correo o la contraseña.
 */
final class InvalidCredentialsException extends RuntimeException {}
