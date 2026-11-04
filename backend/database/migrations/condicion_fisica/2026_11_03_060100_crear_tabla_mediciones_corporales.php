<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Peso y estatura de un jugador por periodo (HU-17). El periodo se deriva de `fecha` (DA-03) y el IMC nunca se captura:
 * es una columna generada con el mismo redondeo que el dominio (DA-05). `registrado_por` decide quién la ve (DD-59), y
 * la unicidad no incluye el equipo (DA-13). Con estatura 0 la columna generada falla por división entre cero antes de
 * evaluar el `CHECK`: los dos son respaldo, porque la solicitud valida el rango antes de llegar a la base (T05).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mediciones_corporales', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_mediciones_corporales');
            $table->uuid('jugador_id');
            $table->uuid('equipo_id');
            $table->uuid('registrado_por');
            $table->date('fecha');
            $table->char('periodo', 7);
            $table->decimal('peso_kg', 5, 2);
            $table->decimal('estatura_cm', 5, 1);
            $table->auditoriaBasica();

            $table->foreign('jugador_id', 'fk_mediciones_corporales_jugador_id')->references('id')->on('jugadores')->restrictOnDelete();
            $table->foreign('equipo_id', 'fk_mediciones_corporales_equipo_id')->references('id')->on('equipos')->restrictOnDelete();
            $table->foreign('registrado_por', 'fk_mediciones_corporales_registrado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });

        DB::statement('alter table mediciones_corporales add column imc numeric(5,2) generated always as (round(peso_kg / power(estatura_cm / 100, 2), 2)) stored');
        DB::statement('alter table mediciones_corporales add constraint ck_mediciones_corporales_valores_positivos check (peso_kg > 0 and estatura_cm > 0)');
        DB::statement("alter table mediciones_corporales add constraint ck_mediciones_corporales_periodo check (periodo = to_char(fecha, 'YYYY-MM'))");
        DB::statement('create unique index uq_mediciones_corporales_jugador_registrado_periodo on mediciones_corporales (jugador_id, registrado_por, periodo) where eliminado_en is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('mediciones_corporales');
    }
};
