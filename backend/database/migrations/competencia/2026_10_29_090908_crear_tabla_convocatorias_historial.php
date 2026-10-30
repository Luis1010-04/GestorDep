<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('convocatorias', 'convocatoria_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('convocatorias_historial');
    }
};
