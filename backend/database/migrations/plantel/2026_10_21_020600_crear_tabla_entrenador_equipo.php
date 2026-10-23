<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * [Decision por defecto] `desde` y `hasta` son `date` (docs/hitos/f1-h1.md, T07).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrenador_equipo', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_entrenador_equipo');
            $table->uuid('usuario_id');
            $table->uuid('equipo_id');
            $table->date('desde');
            $table->date('hasta')->nullable();
            $table->auditoriaBasica();

            $table->foreign('usuario_id', 'fk_entrenador_equipo_usuario_id')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('equipo_id', 'fk_entrenador_equipo_equipo_id')->references('id')->on('equipos')->restrictOnDelete();
            $table->index('equipo_id', 'ix_entrenador_equipo_equipo_id');
        });

        DB::statement('create unique index uq_entrenador_equipo_vigente on entrenador_equipo (usuario_id, equipo_id) where hasta is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('entrenador_equipo');
    }
};
