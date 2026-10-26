<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('entrenador_equipo', 'entrenador_equipo_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('entrenador_equipo_historial');
    }
};
