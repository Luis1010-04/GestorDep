<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Umbral propio de un jugador en un equipo, de un entrenador (HU-15, RN-08). `entrenador_id` no está en
 * docs/arquitectura.md §7.6 (DA-04). Cambiarlo cierra el vigente (`vigente_hasta`) y abre otro; el índice deja un solo
 * umbral vigente por jugador, equipo y entrenador. Las vigencias son instantes (DA-09).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('umbrales_rendimiento', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_umbrales_rendimiento');
            $table->uuid('jugador_id');
            $table->uuid('equipo_id');
            $table->uuid('entrenador_id');
            $table->uuid('plantilla_id')->nullable();
            $table->decimal('calificacion_minima', 4, 2);
            $table->smallInteger('ventana_partidos');
            $table->timestampTz('vigente_desde');
            $table->timestampTz('vigente_hasta')->nullable();
            $table->auditoriaBasica();

            $table->foreign('jugador_id', 'fk_umbrales_rendimiento_jugador_id')->references('id')->on('jugadores')->restrictOnDelete();
            $table->foreign('equipo_id', 'fk_umbrales_rendimiento_equipo_id')->references('id')->on('equipos')->restrictOnDelete();
            $table->foreign('entrenador_id', 'fk_umbrales_rendimiento_entrenador_id')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('plantilla_id', 'fk_umbrales_rendimiento_plantilla_id')->references('id')->on('plantillas_evaluacion')->restrictOnDelete();
        });

        DB::statement('alter table umbrales_rendimiento add constraint ck_umbrales_rendimiento_calificacion_minima check (calificacion_minima between 1 and 10)');
        DB::statement('alter table umbrales_rendimiento add constraint ck_umbrales_rendimiento_ventana_partidos check (ventana_partidos > 0)');
        DB::statement('create unique index uq_umbrales_rendimiento_vigente on umbrales_rendimiento (jugador_id, equipo_id, entrenador_id) where vigente_hasta is null and eliminado_en is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('umbrales_rendimiento');
    }
};
