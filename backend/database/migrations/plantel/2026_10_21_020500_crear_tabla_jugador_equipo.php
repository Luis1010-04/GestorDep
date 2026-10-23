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
        Schema::create('jugador_equipo', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_jugador_equipo');
            $table->uuid('jugador_id');
            $table->uuid('equipo_id');
            $table->uuid('posicion_id');
            $table->date('desde');
            $table->date('hasta')->nullable();
            $table->auditoriaBasica();

            $table->foreign('jugador_id', 'fk_jugador_equipo_jugador_id')->references('id')->on('jugadores')->restrictOnDelete();
            $table->foreign('equipo_id', 'fk_jugador_equipo_equipo_id')->references('id')->on('equipos')->restrictOnDelete();
            $table->foreign('posicion_id', 'fk_jugador_equipo_posicion_id')->references('id')->on('posiciones')->restrictOnDelete();
            $table->index('equipo_id', 'ix_jugador_equipo_equipo_id');
        });

        DB::statement('create unique index uq_jugador_equipo_vigente on jugador_equipo (jugador_id, equipo_id) where hasta is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('jugador_equipo');
    }
};
