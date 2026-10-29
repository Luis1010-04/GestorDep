<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Índice único parcial, como pide la arquitectura §7.1 y §7.5 (decisión R-2 del responsable, 29/10/2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias_entrenamiento', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_asistencias_entrenamiento');
            $table->uuid('entrenamiento_id');
            $table->uuid('jugador_id');
            $table->string('estado', 20);
            $table->auditoriaBasica();

            $table->foreign('entrenamiento_id', 'fk_asistencias_entrenamiento_entrenamiento_id')->references('id')->on('entrenamientos')->restrictOnDelete();
            $table->foreign('jugador_id', 'fk_asistencias_entrenamiento_jugador_id')->references('id')->on('jugadores')->restrictOnDelete();
            $table->index('jugador_id', 'ix_asistencias_entrenamiento_jugador_id');
        });

        DB::statement("alter table asistencias_entrenamiento add constraint ck_asistencias_entrenamiento_estado check (estado in ('presente', 'falta', 'falta_justificada'))");
        DB::statement('create unique index uq_asistencias_entrenamiento_entrenamiento_jugador on asistencias_entrenamiento (entrenamiento_id, jugador_id) where eliminado_en is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias_entrenamiento');
    }
};
