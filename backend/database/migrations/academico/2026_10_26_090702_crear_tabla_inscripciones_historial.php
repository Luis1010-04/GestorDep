<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('inscripciones', 'inscripcion_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones_historial');
    }
};
