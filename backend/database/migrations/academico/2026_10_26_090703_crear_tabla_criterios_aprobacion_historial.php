<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('criterios_aprobacion', 'criterio_aprobacion_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('criterios_aprobacion_historial');
    }
};
