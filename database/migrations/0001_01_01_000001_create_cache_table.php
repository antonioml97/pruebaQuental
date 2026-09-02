<?php

declare(strict_types=1);

/**
 * Crea las tablas del almacén de caché basado en base de datos.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestiona valores de caché y bloqueos atómicos persistentes.
 */
return new class extends Migration
{
    /**
     * Crea las tablas de caché y bloqueos.
     */
    public function up(): void
    {
        Schema::create('cache',
            /**
             * Define columnas, índices y restricciones de la tabla cache.
             *
             * @param  Blueprint  $table  Definición de cache que Laravel convertirá en SQL.
             */
            function (Blueprint $table): void {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration')->index();
            });

        Schema::create('cache_locks',
            /**
             * Define columnas, índices y restricciones de la tabla cache_locks.
             *
             * @param  Blueprint  $table  Definición de cache_locks que Laravel convertirá en SQL.
             */
            function (Blueprint $table): void {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration')->index();
            });
    }

    /**
     * Elimina las tablas de caché y bloqueos.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
