<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rendimiento de un jugador en un partido jugado (HU-14). La clave foránea compuesta (`partido_id`, `equipo_id`)
 * obliga a que el equipo sea el del partido; `posicion_id` es la posición con la que jugó, para que las estadísticas
 * pasadas no cambien; `registrado_por` decide quién lo ve (RN-14). Un rendimiento por partido, jugador y registrante
 * (v1.0.8): el de un entrenador no impide ni revela el de otro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rendimientos_partido', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_rendimientos_partido');
            $table->uuid('partido_id');
            $table->uuid('jugador_id');
            $table->uuid('equipo_id');
            $table->uuid('registrado_por');
            $table->uuid('posicion_id');
            $table->smallInteger('minutos');
            $table->boolean('fue_titular');
            $table->smallInteger('tarjetas_amarillas');
            $table->smallInteger('tarjetas_rojas');
            $table->smallInteger('calificacion_general');
            $table->string('observacion', 500)->nullable();
            $table->auditoriaBasica();

            $table->foreign(['partido_id', 'equipo_id'], 'fk_rendimientos_partido_partido_equipo')->references(['id', 'equipo_id'])->on('partidos')->restrictOnDelete();
            $table->foreign('jugador_id', 'fk_rendimientos_partido_jugador_id')->references('id')->on('jugadores')->restrictOnDelete();
            $table->foreign('registrado_por', 'fk_rendimientos_partido_registrado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('posicion_id', 'fk_rendimientos_partido_posicion_id')->references('id')->on('posiciones')->restrictOnDelete();
            $table->index(['jugador_id', 'partido_id'], 'ix_rendimientos_partido_jugador_partido');
            $table->index('registrado_por', 'ix_rendimientos_partido_registrado_por');
        });

        DB::statement('alter table rendimientos_partido add constraint ck_rendimientos_partido_minutos check (minutos between 0 and 150)');
        DB::statement('alter table rendimientos_partido add constraint ck_rendimientos_partido_calificacion_general check (calificacion_general between 1 and 10)');
        DB::statement('alter table rendimientos_partido add constraint ck_rendimientos_partido_tarjetas check (tarjetas_amarillas between 0 and 2 and tarjetas_rojas between 0 and 1)');
        DB::statement('create unique index uq_rendimientos_partido_partido_jugador_registrado_por on rendimientos_partido (partido_id, jugador_id, registrado_por) where eliminado_en is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('rendimientos_partido');
    }
};
