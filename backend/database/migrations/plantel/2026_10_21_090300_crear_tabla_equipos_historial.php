<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('equipos', 'equipo_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos_historial');
    }
};
