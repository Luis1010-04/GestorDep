<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de métricas de rendimiento (HU-14). Se siembra (`MetricasSeeder`) y no se edita desde la API; `minimo` y
 * `maximo` acotan el valor que se captura (RNF-03, RNF-09).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metricas', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_metricas');
            $table->string('codigo', 50);
            $table->string('nombre', 100);
            $table->string('tipo_dato', 20);
            $table->decimal('minimo', 10, 2);
            $table->decimal('maximo', 10, 2);
            $table->boolean('activa')->default(true);
            $table->auditoriaBasica();
        });

        DB::statement('alter table metricas add constraint uq_metricas_codigo unique (codigo)');
        DB::statement("alter table metricas add constraint ck_metricas_tipo_dato check (tipo_dato in ('entero', 'decimal'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('metricas');
    }
};
