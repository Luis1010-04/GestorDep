<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('partidos', 'partido_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('partidos_historial');
    }
};
