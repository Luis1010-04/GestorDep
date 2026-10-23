<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jugadores', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_jugadores');
            $table->string('nombres', 120);
            $table->string('apellidos', 120);
            $table->date('fecha_nacimiento');
            $table->string('matricula', 30);
            $table->string('estatus_academico', 20)->default('activo');
            $table->string('estatus_deportivo', 20)->default('activo');
            $table->auditoriaBasica();
        });

        DB::statement('create unique index uq_jugadores_matricula on jugadores (matricula) where eliminado_en is null');
        DB::statement("alter table jugadores add constraint ck_jugadores_estatus_academico check (estatus_academico in ('activo', 'baja'))");
        DB::statement("alter table jugadores add constraint ck_jugadores_estatus_deportivo check (estatus_deportivo in ('activo', 'baja'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('jugadores');
    }
};
