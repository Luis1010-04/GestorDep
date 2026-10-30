<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de los entrenadores (HU-28). `citext` no admite longitud: el límite de 100 caracteres va en un CHECK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lugares', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_lugares');
            $table->rawColumn('nombre', 'citext');
            $table->auditoriaBasica();
        });

        DB::statement('create unique index uq_lugares_nombre on lugares (nombre) where eliminado_en is null');
        DB::statement('alter table lugares add constraint ck_lugares_nombre check (char_length(nombre) <= 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('lugares');
    }
};
