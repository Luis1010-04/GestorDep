<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('torneos', 'torneo_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('torneos_historial');
    }
};
