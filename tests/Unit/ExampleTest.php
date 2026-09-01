<?php

declare(strict_types=1);

/**
 * Conserva una comprobación unitaria mínima del entorno PHPUnit.
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Confirma que PHPUnit puede ejecutar pruebas unitarias aisladas.
 */
final class ExampleTest extends TestCase
{
    /**
     * Verifica una afirmación booleana elemental.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
