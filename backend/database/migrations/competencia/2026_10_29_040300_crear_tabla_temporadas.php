<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de los entrenadores (HU-28, DD-07). El traslape entre temporadas lo rechaza la aplicación (409).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporadas', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_temporadas');
            $table->rawColumn('nombre', 'citext');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->auditoriaBasica();
        });

        DB::statement('create unique index uq_temporadas_nombre on temporadas (nombre) where eliminado_en is null');
        DB::statement('alter table temporadas add constraint ck_temporadas_nombre check (char_length(nombre) <= 100)');
        DB::statement('alter table temporadas add constraint ck_temporadas_fechas check (fecha_fin > fecha_inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('temporadas');
    }
};
