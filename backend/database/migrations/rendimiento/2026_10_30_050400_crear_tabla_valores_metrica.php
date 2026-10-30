<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Valor de cada métrica en un rendimiento. La aplicación valida que la métrica sea de la posición y que el valor
 * esté entre `metricas.minimo` y `metricas.maximo`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valores_metrica', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_valores_metrica');
            $table->uuid('rendimiento_id');
            $table->uuid('metrica_id');
            $table->decimal('valor', 10, 2);
            $table->auditoriaBasica();

            $table->foreign('rendimiento_id', 'fk_valores_metrica_rendimiento_id')->references('id')->on('rendimientos_partido')->restrictOnDelete();
            $table->foreign('metrica_id', 'fk_valores_metrica_metrica_id')->references('id')->on('metricas')->restrictOnDelete();
        });

        DB::statement('create unique index uq_valores_metrica_rendimiento_metrica on valores_metrica (rendimiento_id, metrica_id) where eliminado_en is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('valores_metrica');
    }
};
