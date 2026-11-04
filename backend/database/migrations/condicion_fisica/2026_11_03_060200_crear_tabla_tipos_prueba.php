<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de pruebas físicas (HU-18). Se siembra (`TiposPruebaSeeder`) y no se edita desde la API. `minimo` y `maximo`
 * acotan el resultado (RNF-03, RNF-09, DA-16). Lleva auditoría básica y no tiene historial, como `metricas`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_prueba', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_tipos_prueba');
            $table->string('codigo', 50);
            $table->string('nombre', 100);
            $table->string('unidad', 20);
            $table->string('mejor_resultado', 10);
            $table->decimal('minimo', 8, 2);
            $table->decimal('maximo', 8, 2);
            $table->auditoriaBasica();
        });

        DB::statement('alter table tipos_prueba add constraint uq_tipos_prueba_codigo unique (codigo)');
        DB::statement("alter table tipos_prueba add constraint ck_tipos_prueba_mejor_resultado check (mejor_resultado in ('mayor', 'menor'))");
        DB::statement('alter table tipos_prueba add constraint ck_tipos_prueba_rango check (minimo > 0 and maximo > minimo)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_prueba');
    }
};
