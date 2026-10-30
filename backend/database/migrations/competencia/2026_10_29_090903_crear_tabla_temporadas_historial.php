<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('temporadas', 'temporada_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('temporadas_historial');
    }
};
