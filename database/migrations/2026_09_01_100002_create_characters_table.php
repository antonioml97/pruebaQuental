<?php

declare(strict_types=1);

/**
 * Crea la tabla que almacena personajes de Rick and Morty.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Crea la tabla de personajes y sus referencias a localizaciones. */
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('external_id')->unique();
            $table->string('name');
            $table->string('status', 20);
            $table->string('species');
            $table->string('type')->default('');
            $table->string('gender', 20);
            $table->string('image_url', 2048);
            $table->foreignId('origin_location_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete();
            $table->foreignId('current_location_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /** Elimina la tabla de personajes. */
    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
