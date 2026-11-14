<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('avisos_lesion', 'aviso_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos_lesion_historial');
    }
};
