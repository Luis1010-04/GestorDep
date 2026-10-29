<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_inscripciones');
            $table->uuid('materia_id');
            $table->uuid('jugador_id');
            $table->auditoriaBasica();

            $table->foreign('materia_id', 'fk_inscripciones_materia_id')->references('id')->on('materias')->restrictOnDelete();
            $table->foreign('jugador_id', 'fk_inscripciones_jugador_id')->references('id')->on('jugadores')->restrictOnDelete();
            $table->index('jugador_id', 'ix_inscripciones_jugador_id');
        });

        DB::statement('create unique index uq_inscripciones_materia_jugador on inscripciones (materia_id, jugador_id) where eliminado_en is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};
