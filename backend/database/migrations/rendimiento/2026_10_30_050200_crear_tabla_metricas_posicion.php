<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Métricas que se capturan en cada posición (HU-14), sin auditoría. Se siembra (`MetricasSeeder`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metricas_posicion', function (Blueprint $table) {
            $table->uuid('metrica_id');
            $table->uuid('posicion_id');
            $table->boolean('obligatoria');
            $table->smallInteger('orden');
            $table->primary(['metrica_id', 'posicion_id'], 'pk_metricas_posicion');

            $table->foreign('metrica_id', 'fk_metricas_posicion_metrica_id')->references('id')->on('metricas')->restrictOnDelete();
            $table->foreign('posicion_id', 'fk_metricas_posicion_posicion_id')->references('id')->on('posiciones')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metricas_posicion');
    }
};
