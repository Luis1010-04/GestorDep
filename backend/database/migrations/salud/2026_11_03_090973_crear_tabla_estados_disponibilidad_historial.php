<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('estados_disponibilidad', 'estado_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_disponibilidad_historial');
    }
};
