<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias_clase', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_asistencias_clase');
            $table->uuid('inscripcion_id');
            $table->date('fecha');
            $table->string('estado', 20);
            $table->auditoriaBasica();

            $table->foreign('inscripcion_id', 'fk_asistencias_clase_inscripcion_id')->references('id')->on('inscripciones')->restrictOnDelete();
        });

        DB::statement("alter table asistencias_clase add constraint ck_asistencias_clase_estado check (estado in ('presente', 'falta', 'falta_justificada'))");
        DB::statement('create unique index uq_asistencias_clase_inscripcion_fecha on asistencias_clase (inscripcion_id, fecha) where eliminado_en is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias_clase');
    }
};
