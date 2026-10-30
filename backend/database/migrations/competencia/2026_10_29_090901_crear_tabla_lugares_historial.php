<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('lugares', 'lugar_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('lugares_historial');
    }
};
