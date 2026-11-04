<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('mediciones_corporales', 'medicion_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('mediciones_corporales_historial');
    }
};
