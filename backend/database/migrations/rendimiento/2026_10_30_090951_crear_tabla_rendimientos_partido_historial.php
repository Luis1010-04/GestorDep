<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('rendimientos_partido', 'rendimiento_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('rendimientos_partido_historial');
    }
};
