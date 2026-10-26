<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('bajas_jugador', 'baja_jugador_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('bajas_jugador_historial');
    }
};
