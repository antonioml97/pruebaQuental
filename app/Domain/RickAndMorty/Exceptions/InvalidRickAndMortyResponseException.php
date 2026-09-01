<?php

declare(strict_types=1);

/**
 * Define la excepción para datos malformados del proveedor Rick and Morty.
 */

namespace App\Domain\RickAndMorty\Exceptions;

use RuntimeException;

/**
 * Indica que una respuesta externa no puede transformarse al dominio con seguridad.
 */
final class InvalidRickAndMortyResponseException extends RuntimeException {}
