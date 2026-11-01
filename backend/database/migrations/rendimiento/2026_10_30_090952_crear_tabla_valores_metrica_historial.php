<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('valores_metrica', 'valor_metrica_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('valores_metrica_historial');
    }
};
