<?php

declare(strict_types=1);

/**
 * Crea las tablas necesarias para usuarios, sesiones y recuperación de acceso.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestiona la estructura persistente de autenticación incluida con Laravel.
 */
return new class extends Migration
{
    /**
     * Crea las tablas de usuarios, tokens de recuperación y sesiones.
     */
    public function up(): void
    {
        Schema::create('users',
            /**
             * Define columnas, índices y restricciones de la tabla users.
             *
             * @param  Blueprint  $table  Definición de users que Laravel convertirá en SQL.
             */
            function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });

        Schema::create('password_reset_tokens',
            /**
             * Define columnas, índices y restricciones de la tabla password_reset_tokens.
             *
             * @param  Blueprint  $table  Definición de password_reset_tokens que Laravel convertirá en SQL.
             */
            function (Blueprint $table): void {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });

        Schema::create('sessions',
            /**
             * Define columnas, índices y restricciones de la tabla sessions.
             *
             * @param  Blueprint  $table  Definición de sessions que Laravel convertirá en SQL.
             */
            function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
    }

    /**
     * Elimina las tablas de autenticación.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
