<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El empalme de lugar se advierte y no se impide (HU-10): no hay restricción de exclusión. El índice
 * (`lugar_id`, `inicia_en`) sirve para buscarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrenamientos', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_entrenamientos');
            $table->uuid('equipo_id');
            $table->uuid('lugar_id');
            $table->timestampTz('inicia_en');
            $table->timestampTz('termina_en');
            $table->uuid('responsable_id');
            $table->string('estado', 20);
            $table->auditoriaBasica();

            $table->foreign('equipo_id', 'fk_entrenamientos_equipo_id')->references('id')->on('equipos')->restrictOnDelete();
            $table->foreign('lugar_id', 'fk_entrenamientos_lugar_id')->references('id')->on('lugares')->restrictOnDelete();
            $table->foreign('responsable_id', 'fk_entrenamientos_responsable_id')->references('id')->on('usuarios')->restrictOnDelete();
            $table->index(['equipo_id', 'inicia_en'], 'ix_entrenamientos_equipo_inicia_en');
            $table->index(['lugar_id', 'inicia_en'], 'ix_entrenamientos_lugar_inicia_en');
        });

        DB::statement('alter table entrenamientos add constraint ck_entrenamientos_horario check (termina_en > inicia_en)');
        DB::statement("alter table entrenamientos add constraint ck_entrenamientos_estado check (estado in ('programado', 'realizado', 'cancelado'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('entrenamientos');
    }
};
