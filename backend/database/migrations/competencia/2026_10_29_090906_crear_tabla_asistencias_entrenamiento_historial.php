<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('asistencias_entrenamiento', 'asistencia_entrenamiento_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias_entrenamiento_historial');
    }
};
