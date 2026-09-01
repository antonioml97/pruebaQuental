<?php

declare(strict_types=1);

/**
 * Proporciona el arranque común de Laravel a los tests de aplicación.
 */

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base compartida por las pruebas que necesitan el contenedor de Laravel.
 */
abstract class TestCase extends BaseTestCase {}
