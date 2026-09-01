<?php

declare(strict_types=1);

/**
 * Crea la tabla que almacena episodios de Rick and Morty.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Crea la tabla de episodios. */
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('external_id')->unique();
            $table->string('name');
            $table->date('air_date');
            $table->string('code')->unique();
            $table->timestamps();
        });
    }

    /** Elimina la tabla de episodios. */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
