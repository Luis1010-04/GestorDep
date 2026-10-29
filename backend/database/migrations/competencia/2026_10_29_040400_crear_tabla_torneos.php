<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de los entrenadores (HU-28). El nombre es único dentro de su temporada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('torneos', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_torneos');
            $table->rawColumn('nombre', 'citext');
            $table->uuid('temporada_id');
            $table->auditoriaBasica();

            $table->foreign('temporada_id', 'fk_torneos_temporada_id')->references('id')->on('temporadas')->restrictOnDelete();
        });

        DB::statement('create unique index uq_torneos_nombre_temporada on torneos (nombre, temporada_id) where eliminado_en is null');
        DB::statement('alter table torneos add constraint ck_torneos_nombre check (char_length(nombre) <= 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('torneos');
    }
};
