<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Detalle de una lesión, visible solo para quien la registró (RN-15). `fecha_estimada_regreso` guarda la que se registró
 * al confirmar y no cambia después: la vigente vive en `estados_disponibilidad` (DA-02).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesiones', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_lesiones');
            $table->uuid('jugador_id');
            $table->uuid('registrada_por');
            $table->string('tipo', 100);
            $table->string('zona', 100);
            $table->string('causa', 20);
            $table->date('fecha_inicio');
            $table->date('fecha_estimada_regreso');
            $table->date('fecha_alta')->nullable();
            $table->string('nota', 500)->nullable();
            $table->auditoriaBasica();

            $table->foreign('jugador_id', 'fk_lesiones_jugador_id')->references('id')->on('jugadores')->restrictOnDelete();
            $table->foreign('registrada_por', 'fk_lesiones_registrada_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->index(['jugador_id', 'registrada_por'], 'ix_lesiones_jugador_registrada_por');
        });

        DB::statement("alter table lesiones add constraint ck_lesiones_causa check (causa in ('entrenamiento', 'partido', 'otra'))");
        DB::statement('alter table lesiones add constraint ck_lesiones_fecha_estimada_regreso check (fecha_estimada_regreso >= fecha_inicio)');
        DB::statement('alter table lesiones add constraint ck_lesiones_fecha_alta check (fecha_alta is null or fecha_alta >= fecha_inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('lesiones');
    }
};
