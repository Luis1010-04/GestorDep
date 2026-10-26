<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('materias', 'materia_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('materias_historial');
    }
};
