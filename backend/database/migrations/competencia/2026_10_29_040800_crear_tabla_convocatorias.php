<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `posicion_id` y `rol_alineacion` son nulos mientras no haya alineación (DA-03).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convocatorias', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_convocatorias');
            $table->uuid('partido_id');
            $table->uuid('jugador_id');
            $table->uuid('posicion_id')->nullable();
            $table->string('rol_alineacion', 20)->nullable();
            $table->auditoriaBasica();

            $table->foreign('partido_id', 'fk_convocatorias_partido_id')->references('id')->on('partidos')->restrictOnDelete();
            $table->foreign('jugador_id', 'fk_convocatorias_jugador_id')->references('id')->on('jugadores')->restrictOnDelete();
            $table->foreign('posicion_id', 'fk_convocatorias_posicion_id')->references('id')->on('posiciones')->restrictOnDelete();
            $table->index('jugador_id', 'ix_convocatorias_jugador_id');
        });

        DB::statement("alter table convocatorias add constraint ck_convocatorias_rol_alineacion check (rol_alineacion in ('titular', 'suplente'))");
        DB::statement('create unique index uq_convocatorias_partido_jugador on convocatorias (partido_id, jugador_id) where eliminado_en is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('convocatorias');
    }
};
