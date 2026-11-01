<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('umbrales_rendimiento', 'umbral_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('umbrales_rendimiento_historial');
    }
};
