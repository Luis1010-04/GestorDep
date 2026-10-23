<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('jugador_equipo', 'jugador_equipo_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('jugador_equipo_historial');
    }
};
