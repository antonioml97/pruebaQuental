<?php

declare(strict_types=1);

/**
 * Define el fallo controlado del patrón CSRF de doble envío.
 */

namespace App\Domain\Authentication\Exceptions;

use RuntimeException;

/**
 * Indica que la cookie CSRF y su cabecera asociada no son válidas.
 */
final class CsrfTokenMismatchException extends RuntimeException {}
