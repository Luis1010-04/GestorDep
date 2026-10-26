<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La retícula (RN-12). `citext` no admite longitud: el límite de 20 caracteres de la clave va en un CHECK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaturas', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_asignaturas');
            $table->rawColumn('clave', 'citext');
            $table->string('nombre', 150);
            $table->smallInteger('grado');
            $table->auditoriaBasica();
        });

        DB::statement('create unique index uq_asignaturas_clave on asignaturas (clave) where eliminado_en is null');
        DB::statement('alter table asignaturas add constraint ck_asignaturas_clave check (char_length(clave) <= 20)');
        DB::statement('alter table asignaturas add constraint ck_asignaturas_grado check (grado between 1 and 12)');
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaturas');
    }
};
