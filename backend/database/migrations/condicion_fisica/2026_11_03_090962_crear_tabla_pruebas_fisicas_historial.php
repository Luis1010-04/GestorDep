<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('pruebas_fisicas', 'prueba_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('pruebas_fisicas_historial');
    }
};
