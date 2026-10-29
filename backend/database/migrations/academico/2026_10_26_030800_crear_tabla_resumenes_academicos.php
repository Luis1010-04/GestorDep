<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla derivada (arquitectura §7.1): se reconstruye desde lo capturado, por eso no lleva auditoría, borrado lógico
 * ni historial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resumenes_academicos', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_resumenes_academicos');
            $table->uuid('jugador_id');
            $table->uuid('periodo_id');
            $table->decimal('promedio', 4, 2)->nullable();
            $table->smallInteger('materias_reprobadas')->default(0);
            $table->smallInteger('materias_sin_calificar')->default(0);
            $table->decimal('porcentaje_asistencia', 5, 2)->nullable();
            $table->string('nivel_riesgo', 20)->nullable();
            $table->timestampTz('calculado_en');

            $table->foreign('jugador_id', 'fk_resumenes_academicos_jugador_id')->references('id')->on('jugadores')->restrictOnDelete();
            $table->foreign('periodo_id', 'fk_resumenes_academicos_periodo_id')->references('id')->on('periodos_escolares')->restrictOnDelete();
            $table->unique(['jugador_id', 'periodo_id'], 'uq_resumenes_academicos_jugador_periodo');
            $table->index('nivel_riesgo', 'ix_resumenes_academicos_nivel_riesgo');
        });

        DB::statement("alter table resumenes_academicos add constraint ck_resumenes_academicos_nivel_riesgo check (nivel_riesgo in ('sin_riesgo', 'en_observacion', 'en_riesgo'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('resumenes_academicos');
    }
};
