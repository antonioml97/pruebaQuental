<?php

declare(strict_types=1);

/**
 * Crea la tabla que almacena localizaciones de Rick and Morty.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Crea la tabla de localizaciones. */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('external_id')->unique();
            $table->string('name');
            $table->string('type');
            $table->string('dimension');
            $table->timestamps();
        });
    }

    /** Elimina la tabla de localizaciones. */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
