<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_usuarios');
            $table->string('nombre', 150);
            $table->rawColumn('correo', 'citext');
            $table->string('contrasena_hash', 255);
            $table->boolean('activo')->default(true);
            $table->smallInteger('intentos_fallidos')->default(0);
            $table->timestampTz('bloqueado_hasta')->nullable();
            $table->auditoriaBasica();
        });

        DB::statement('create unique index uq_usuarios_correo on usuarios (correo) where eliminado_en is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
