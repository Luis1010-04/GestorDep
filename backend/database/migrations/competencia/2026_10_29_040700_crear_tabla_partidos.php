<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La categoría se toma del equipo (DD-18). `ck_partidos_resultado`: los goles existen si y solo si el partido
 * está jugado, y nunca uno sin el otro (DA-02). UQ (`id`, `equipo_id`) para las claves foráneas compuestas de H4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partidos', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_partidos');
            $table->uuid('equipo_id');
            $table->uuid('temporada_id');
            $table->uuid('torneo_id')->nullable();
            $table->uuid('rival_id');
            $table->uuid('lugar_id');
            $table->timestampTz('inicia_en');
            $table->string('tipo', 20);
            $table->string('condicion', 20);
            $table->string('estado', 20);
            $table->smallInteger('goles_favor')->nullable();
            $table->smallInteger('goles_contra')->nullable();
            $table->auditoriaBasica();

            $table->foreign('equipo_id', 'fk_partidos_equipo_id')->references('id')->on('equipos')->restrictOnDelete();
            $table->foreign('temporada_id', 'fk_partidos_temporada_id')->references('id')->on('temporadas')->restrictOnDelete();
            $table->foreign('torneo_id', 'fk_partidos_torneo_id')->references('id')->on('torneos')->restrictOnDelete();
            $table->foreign('rival_id', 'fk_partidos_rival_id')->references('id')->on('rivales')->restrictOnDelete();
            $table->foreign('lugar_id', 'fk_partidos_lugar_id')->references('id')->on('lugares')->restrictOnDelete();
            $table->index(['equipo_id', 'inicia_en'], 'ix_partidos_equipo_inicia_en');
            $table->unique(['id', 'equipo_id'], 'uq_partidos_id_equipo');
        });

        DB::statement("alter table partidos add constraint ck_partidos_tipo check (tipo in ('amistoso', 'oficial', 'interno', 'torneo'))");
        DB::statement("alter table partidos add constraint ck_partidos_condicion check (condicion in ('local', 'visitante'))");
        DB::statement("alter table partidos add constraint ck_partidos_estado check (estado in ('programado', 'jugado', 'cancelado'))");
        DB::statement('alter table partidos add constraint ck_partidos_goles check (goles_favor between 0 and 99 and goles_contra between 0 and 99)');
        DB::statement("alter table partidos add constraint ck_partidos_resultado check ((goles_favor is null) = (goles_contra is null) and (goles_favor is not null) = (estado = 'jugado'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('partidos');
    }
};
