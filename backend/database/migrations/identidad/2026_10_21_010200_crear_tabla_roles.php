<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_roles');
            $table->string('codigo', 20);
            $table->string('nombre', 100);
            $table->auditoriaBasica();
        });

        DB::statement('alter table roles add constraint uq_roles_codigo unique (codigo)');
        DB::statement("alter table roles add constraint ck_roles_codigo check (codigo in ('docente', 'entrenador'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
