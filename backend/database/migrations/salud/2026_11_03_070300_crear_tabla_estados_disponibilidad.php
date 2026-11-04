<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Estado oficial de disponibilidad, compartido por los entrenadores y docentes del jugador (HU-21, RN-14). Un solo estado
 * vigente por jugador. `confirmado_por` es quién sostiene el estado y solo él puede modificarlo (RN-16, DA-19); la fecha
 * estimada de regreso de esta tabla es la que decide la vigencia (DA-02).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_disponibilidad', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_estados_disponibilidad');
            $table->uuid('jugador_id');
            $table->string('estado', 20);
            $table->uuid('lesion_id')->nullable();
            $table->date('fecha_estimada_regreso')->nullable();
            $table->uuid('confirmado_por');
            $table->timestampTz('vigente_desde');
            $table->timestampTz('vigente_hasta')->nullable();
            $table->auditoriaBasica();

            $table->foreign('jugador_id', 'fk_estados_disponibilidad_jugador_id')->references('id')->on('jugadores')->restrictOnDelete();
            $table->foreign('lesion_id', 'fk_estados_disponibilidad_lesion_id')->references('id')->on('lesiones')->restrictOnDelete();
            $table->foreign('confirmado_por', 'fk_estados_disponibilidad_confirmado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });

        DB::statement("alter table estados_disponibilidad add constraint ck_estados_disponibilidad_estado check (estado in ('apto', 'con_molestia', 'lesionado'))");
        DB::statement("alter table estados_disponibilidad add constraint ck_estados_disponibilidad_apto check ((lesion_id is null) = (estado = 'apto') and (fecha_estimada_regreso is null) = (estado = 'apto'))");
        DB::statement('alter table estados_disponibilidad add constraint ck_estados_disponibilidad_vigencia check (vigente_hasta is null or vigente_hasta > vigente_desde)');
        DB::statement('create unique index uq_estados_disponibilidad_vigente on estados_disponibilidad (jugador_id) where vigente_hasta is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_disponibilidad');
    }
};
