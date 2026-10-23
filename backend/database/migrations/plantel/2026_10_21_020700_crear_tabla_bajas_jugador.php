<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * [Decision por defecto] `motivo` es `text` y `fecha` es `date` (docs/hitos/f1-h1.md, T07).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bajas_jugador', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_bajas_jugador');
            $table->uuid('jugador_id');
            $table->text('motivo');
            $table->date('fecha');
            $table->uuid('registrada_por');
            $table->auditoriaBasica();

            $table->foreign('jugador_id', 'fk_bajas_jugador_jugador_id')->references('id')->on('jugadores')->restrictOnDelete();
            $table->foreign('registrada_por', 'fk_bajas_jugador_registrada_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->index('jugador_id', 'ix_bajas_jugador_jugador_id');
        });

        DB::statement("alter table bajas_jugador add constraint ck_bajas_jugador_motivo check (btrim(motivo) <> '')");
    }

    public function down(): void
    {
        Schema::dropIfExists('bajas_jugador');
    }
};
