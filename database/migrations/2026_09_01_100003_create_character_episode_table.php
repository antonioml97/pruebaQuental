<?php

declare(strict_types=1);

/**
 * Crea la tabla pivote entre personajes y episodios.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestiona la relación muchos a muchos entre personajes y episodios.
 */
return new class extends Migration
{
    /** Crea la relación muchos a muchos entre personajes y episodios. */
    public function up(): void
    {
        Schema::create('character_episode',
            /**
             * Define columnas, índices y restricciones de la tabla character_episode.
             *
             * @param  Blueprint  $table  Definición de character_episode que Laravel convertirá en SQL.
             */
            function (Blueprint $table): void {
                $table->foreignId('character_id')->constrained()->cascadeOnDelete();
                $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
                $table->primary(['character_id', 'episode_id']);
            });
    }

    /** Elimina la tabla pivote entre personajes y episodios. */
    public function down(): void
    {
        Schema::dropIfExists('character_episode');
    }
};
