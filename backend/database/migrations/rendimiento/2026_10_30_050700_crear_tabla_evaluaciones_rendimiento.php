<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Evaluación pasa, no pasa o datos insuficientes (RN-08, HU-16), persistente y de solo inserción, sin auditoría.
 * `entrenador_id` no está en docs/arquitectura.md §7.6 (DA-04). `calificacion_minima` y `ventana_partidos` copian lo
 * aplicado al calcular; `umbral_id` y `plantilla_id` dicen de dónde salió (DA-05). `ck_evaluaciones_rendimiento_promedio`
 * (DA-06): el promedio es nulo si y solo si el resultado es `datos_insuficientes`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_rendimiento', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_evaluaciones_rendimiento');
            $table->uuid('jugador_id');
            $table->uuid('equipo_id');
            $table->uuid('entrenador_id');
            $table->uuid('umbral_id')->nullable();
            $table->uuid('plantilla_id')->nullable();
            $table->decimal('calificacion_minima', 4, 2);
            $table->smallInteger('ventana_partidos');
            $table->decimal('promedio', 4, 2)->nullable();
            $table->smallInteger('partidos_considerados');
            $table->string('resultado', 30);
            $table->timestampTz('calculado_en');

            $table->foreign('jugador_id', 'fk_evaluaciones_rendimiento_jugador_id')->references('id')->on('jugadores')->restrictOnDelete();
            $table->foreign('equipo_id', 'fk_evaluaciones_rendimiento_equipo_id')->references('id')->on('equipos')->restrictOnDelete();
            $table->foreign('entrenador_id', 'fk_evaluaciones_rendimiento_entrenador_id')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('umbral_id', 'fk_evaluaciones_rendimiento_umbral_id')->references('id')->on('umbrales_rendimiento')->restrictOnDelete();
            $table->foreign('plantilla_id', 'fk_evaluaciones_rendimiento_plantilla_id')->references('id')->on('plantillas_evaluacion')->restrictOnDelete();
        });

        DB::statement("alter table evaluaciones_rendimiento add constraint ck_evaluaciones_rendimiento_resultado check (resultado in ('pasa', 'no_pasa', 'datos_insuficientes'))");
        DB::statement("alter table evaluaciones_rendimiento add constraint ck_evaluaciones_rendimiento_promedio check ((promedio is null) = (resultado = 'datos_insuficientes'))");
        DB::statement('create index ix_evaluaciones_rendimiento_jugador_equipo_entrenador on evaluaciones_rendimiento (jugador_id, equipo_id, entrenador_id, calculado_en desc)');
        Schema::soloInsercion('evaluaciones_rendimiento');
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_rendimiento');
    }
};
