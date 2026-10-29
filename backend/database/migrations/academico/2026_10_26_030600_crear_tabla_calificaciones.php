<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `criterio_id` guarda el criterio vigente al capturar (DD-47). El índice único usa `NULLS NOT DISTINCT` para que
 * dos finales de la misma inscripción (`numero_parcial` nulo) también choquen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_calificaciones');
            $table->uuid('inscripcion_id');
            $table->string('tipo', 20);
            $table->smallInteger('numero_parcial')->nullable();
            $table->decimal('valor', 4, 2);
            $table->uuid('criterio_id');
            $table->date('fecha');
            $table->auditoriaBasica();

            $table->foreign('inscripcion_id', 'fk_calificaciones_inscripcion_id')->references('id')->on('inscripciones')->restrictOnDelete();
            $table->foreign('criterio_id', 'fk_calificaciones_criterio_id')->references('id')->on('criterios_aprobacion')->restrictOnDelete();
        });

        DB::statement("alter table calificaciones add constraint ck_calificaciones_tipo check (tipo in ('parcial', 'final'))");
        DB::statement('alter table calificaciones add constraint ck_calificaciones_valor check (valor between 0 and 10)');
        DB::statement("alter table calificaciones add constraint ck_calificaciones_numero_parcial check ((tipo = 'final' and numero_parcial is null) or (tipo = 'parcial' and numero_parcial is not null and numero_parcial between 1 and 10))");
        DB::statement('create unique index uq_calificaciones_inscripcion_tipo_parcial on calificaciones (inscripcion_id, tipo, numero_parcial) nulls not distinct where eliminado_en is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};
