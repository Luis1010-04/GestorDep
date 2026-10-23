<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_categorias');
            $table->rawColumn('nombre', 'citext');
            $table->boolean('activo')->default(true);
            $table->auditoriaBasica();
        });

        DB::statement('create unique index uq_categorias_nombre on categorias (nombre) where eliminado_en is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
