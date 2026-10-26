<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posiciones', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_posiciones');
            $table->string('codigo', 20);
            $table->string('nombre', 100);
            $table->auditoriaBasica();
        });

        DB::statement('alter table posiciones add constraint uq_posiciones_codigo unique (codigo)');
        DB::statement("alter table posiciones add constraint ck_posiciones_codigo check (codigo in ('portero', 'defensa', 'medio', 'delantero'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('posiciones');
    }
};
