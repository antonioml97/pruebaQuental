<?php

declare(strict_types=1);

/**
 * Recibe las peticiones HTTP y las entrega a la aplicación Laravel.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Carga la respuesta de mantenimiento antes de iniciar la aplicación.
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Registra el cargador automático de Composer.
require __DIR__.'/../vendor/autoload.php';

// Arranca Laravel y procesa la petición entrante.
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
