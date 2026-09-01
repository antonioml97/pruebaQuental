<?php

declare(strict_types=1);

/**
 * Crea la relación persistente entre usuarios y personajes favoritos.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Garantiza favoritos únicos y elimina relaciones huérfanas en cascada.
 */
return new class extends Migration
{
    /**
     * Crea la tabla pivote de favoritos.
     */
    public function up(): void
    {
        Schema::create('favorite_characters', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'character_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Elimina la relación de favoritos.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorite_characters');
    }
};
