<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Parámetros de las reglas de cálculo (arquitectura §11, DD-42): se crea en H2 porque el riesgo académico la lee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametros_institucionales', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_parametros_institucionales');
            $table->string('clave', 100);
            $table->jsonb('valor');
            $table->text('descripcion');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->auditoriaBasica();
        });

        DB::statement('create unique index uq_parametros_institucionales_clave on parametros_institucionales (clave) where vigente_hasta is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('parametros_institucionales');
    }
};
