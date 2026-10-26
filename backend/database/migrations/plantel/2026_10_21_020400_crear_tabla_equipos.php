<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * [Decision por defecto] `nombre` es varchar(120), como los nombres de jugadores (docs/hitos/f1-h1.md, T07).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_equipos');
            $table->string('nombre', 120);
            $table->uuid('categoria_id');
            $table->boolean('activo')->default(true);
            $table->auditoriaBasica();

            $table->foreign('categoria_id', 'fk_equipos_categoria_id')->references('id')->on('categorias')->restrictOnDelete();
        });

        DB::statement('create unique index uq_equipos_nombre_categoria on equipos (nombre, categoria_id) where eliminado_en is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
