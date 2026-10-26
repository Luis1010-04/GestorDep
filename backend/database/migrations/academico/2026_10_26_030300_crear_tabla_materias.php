<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materias', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_materias');
            $table->uuid('asignatura_id');
            $table->uuid('periodo_id');
            $table->uuid('docente_id');
            $table->auditoriaBasica();

            $table->foreign('asignatura_id', 'fk_materias_asignatura_id')->references('id')->on('asignaturas')->restrictOnDelete();
            $table->foreign('periodo_id', 'fk_materias_periodo_id')->references('id')->on('periodos_escolares')->restrictOnDelete();
            $table->foreign('docente_id', 'fk_materias_docente_id')->references('id')->on('usuarios')->restrictOnDelete();
            $table->index('docente_id', 'ix_materias_docente_id');
        });

        DB::statement('create unique index uq_materias_asignatura_periodo_docente on materias (asignatura_id, periodo_id, docente_id) where eliminado_en is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('materias');
    }
};
