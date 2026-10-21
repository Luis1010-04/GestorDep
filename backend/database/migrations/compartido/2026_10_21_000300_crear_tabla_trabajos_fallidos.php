<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trabajos fallidos de la cola `database` (docs/arquitectura.md §11), generada con make:queue-failed-table.
 * Las columnas y el id numerico los exige el driver de cola de Laravel; solo el nombre de la tabla va en espanol.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trabajos_fallidos', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('connection');
            $table->string('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();

            $table->index(['connection', 'queue', 'failed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajos_fallidos');
    }
};
