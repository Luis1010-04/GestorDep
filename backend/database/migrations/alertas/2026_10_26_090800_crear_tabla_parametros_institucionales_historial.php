<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('parametros_institucionales', 'parametro_institucional_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('parametros_institucionales_historial');
    }
};
