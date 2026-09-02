<?php

declare(strict_types=1);

/**
 * Crea la persistencia de tokens opacos emitidos a usuarios.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestiona tokens revocables sin almacenar sus secretos en texto plano.
 */
return new class extends Migration
{
    /**
     * Crea la tabla de tokens de acceso.
     */
    public function up(): void
    {
        Schema::create('access_tokens',
            /**
             * Define columnas, índices y restricciones de la tabla access_tokens.
             *
             * @param  Blueprint  $table  Definición de access_tokens que Laravel convertirá en SQL.
             */
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->char('token_hash', 64)->unique();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->index();
                $table->timestamps();
            });
    }

    /**
     * Elimina la tabla de tokens de acceso.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_tokens');
    }
};
