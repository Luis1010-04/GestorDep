<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Plantillas de evaluación por posición (RN-07, HU-15): las de fábrica (`creada_por` nulo) y las propias de un
 * entrenador. `ck_plantillas_evaluacion_origen` (DA-06): `creada_por` es nulo si y solo si es de fábrica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantillas_evaluacion', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_plantillas_evaluacion');
            $table->string('nombre', 100);
            $table->uuid('posicion_id');
            $table->uuid('creada_por')->nullable();
            $table->decimal('calificacion_minima', 4, 2);
            $table->smallInteger('ventana_partidos');
            $table->boolean('es_de_fabrica')->default(false);
            $table->auditoriaBasica();

            $table->foreign('posicion_id', 'fk_plantillas_evaluacion_posicion_id')->references('id')->on('posiciones')->restrictOnDelete();
            $table->foreign('creada_por', 'fk_plantillas_evaluacion_creada_por')->references('id')->on('usuarios')->restrictOnDelete();
        });

        DB::statement('alter table plantillas_evaluacion add constraint ck_plantillas_evaluacion_calificacion_minima check (calificacion_minima between 1 and 10)');
        DB::statement('alter table plantillas_evaluacion add constraint ck_plantillas_evaluacion_ventana_partidos check (ventana_partidos > 0)');
        DB::statement('alter table plantillas_evaluacion add constraint ck_plantillas_evaluacion_origen check ((creada_por is null) = es_de_fabrica)');
        DB::statement('create unique index uq_plantillas_evaluacion_nombre_posicion on plantillas_evaluacion (nombre, posicion_id) where eliminado_en is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('plantillas_evaluacion');
    }
};
