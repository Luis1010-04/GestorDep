<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Resultado de una prueba física (HU-18). Una por jugador, tipo, registrante y periodo (DA-04), sin contar el equipo
 * (DA-13). El periodo se deriva de `fecha` (DA-03) y `registrado_por` decide quién la ve (DD-59).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pruebas_fisicas', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_pruebas_fisicas');
            $table->uuid('jugador_id');
            $table->uuid('equipo_id');
            $table->uuid('registrado_por');
            $table->uuid('tipo_prueba_id');
            $table->date('fecha');
            $table->char('periodo', 7);
            $table->decimal('resultado', 8, 2);
            $table->auditoriaBasica();

            $table->foreign('jugador_id', 'fk_pruebas_fisicas_jugador_id')->references('id')->on('jugadores')->restrictOnDelete();
            $table->foreign('equipo_id', 'fk_pruebas_fisicas_equipo_id')->references('id')->on('equipos')->restrictOnDelete();
            $table->foreign('registrado_por', 'fk_pruebas_fisicas_registrado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('tipo_prueba_id', 'fk_pruebas_fisicas_tipo_prueba_id')->references('id')->on('tipos_prueba')->restrictOnDelete();
            $table->index(['jugador_id', 'tipo_prueba_id', 'fecha'], 'ix_pruebas_fisicas_jugador_tipo_fecha');
        });

        DB::statement('alter table pruebas_fisicas add constraint ck_pruebas_fisicas_resultado check (resultado > 0)');
        DB::statement("alter table pruebas_fisicas add constraint ck_pruebas_fisicas_periodo check (periodo = to_char(fecha, 'YYYY-MM'))");
        DB::statement('create unique index uq_pruebas_fisicas_jugador_tipo_registrado_periodo on pruebas_fisicas (jugador_id, tipo_prueba_id, registrado_por, periodo) where eliminado_en is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('pruebas_fisicas');
    }
};
