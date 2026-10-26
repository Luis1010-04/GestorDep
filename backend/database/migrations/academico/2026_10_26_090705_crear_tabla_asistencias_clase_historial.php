<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('asistencias_clase', 'asistencia_clase_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias_clase_historial');
    }
};
