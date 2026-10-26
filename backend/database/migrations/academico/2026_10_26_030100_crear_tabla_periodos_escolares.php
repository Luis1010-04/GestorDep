<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de los docentes (HU-28). `citext` no admite longitud: el límite de 100 caracteres va en un CHECK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos_escolares', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_periodos_escolares');
            $table->rawColumn('nombre', 'citext');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->auditoriaBasica();
        });

        DB::statement('create unique index uq_periodos_escolares_nombre on periodos_escolares (nombre) where eliminado_en is null');
        DB::statement('alter table periodos_escolares add constraint ck_periodos_escolares_nombre check (char_length(nombre) <= 100)');
        DB::statement('alter table periodos_escolares add constraint ck_periodos_escolares_fechas check (fecha_fin > fecha_inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_escolares');
    }
};
