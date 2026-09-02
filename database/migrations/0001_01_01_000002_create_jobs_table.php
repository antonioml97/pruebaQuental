<?php

declare(strict_types=1);

/**
 * Crea las tablas utilizadas por el sistema de colas de Laravel.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestiona trabajos pendientes, lotes y trabajos fallidos.
 */
return new class extends Migration
{
    /**
     * Crea las tablas necesarias para procesar trabajos en cola.
     */
    public function up(): void
    {
        Schema::create('jobs',
            /**
             * Define columnas, índices y restricciones de la tabla jobs.
             *
             * @param  Blueprint  $table  Definición de jobs que Laravel convertirá en SQL.
             */
            function (Blueprint $table): void {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });

        Schema::create('job_batches',
            /**
             * Define columnas, índices y restricciones de la tabla job_batches.
             *
             * @param  Blueprint  $table  Definición de job_batches que Laravel convertirá en SQL.
             */
            function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });

        Schema::create('failed_jobs',
            /**
             * Define columnas, índices y restricciones de la tabla failed_jobs.
             *
             * @param  Blueprint  $table  Definición de failed_jobs que Laravel convertirá en SQL.
             */
            function (Blueprint $table): void {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
    }

    /**
     * Elimina todas las tablas del sistema de colas.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
