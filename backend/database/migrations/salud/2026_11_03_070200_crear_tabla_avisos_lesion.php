<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aviso de molestia o lesión (HU-19, RN-16). `confirmado_por` es quién confirmó este aviso y no cambia después (DA-19).
 * `creado_por` es la columna de `auditoriaBasica()`, obligatoria aquí porque DD-58 depende de ese dato. La tabla se acaba
 * de crear, así que no tiene filas sin él; además, `set not null` falla si encontrara alguna.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avisos_lesion', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_avisos_lesion');
            $table->uuid('jugador_id');
            $table->boolean('reportado_por_jugador')->default(false);
            $table->string('nota', 500);
            $table->string('estado', 20);
            $table->uuid('lesion_id')->nullable();
            $table->date('fecha_aviso');
            $table->uuid('confirmado_por')->nullable();
            $table->uuid('descartado_por')->nullable();
            $table->string('nota_descarte', 500)->nullable();
            $table->auditoriaBasica();

            $table->foreign('jugador_id', 'fk_avisos_lesion_jugador_id')->references('id')->on('jugadores')->restrictOnDelete();
            $table->foreign('lesion_id', 'fk_avisos_lesion_lesion_id')->references('id')->on('lesiones')->restrictOnDelete();
            $table->foreign('confirmado_por', 'fk_avisos_lesion_confirmado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('descartado_por', 'fk_avisos_lesion_descartado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->index(['jugador_id', 'estado'], 'ix_avisos_lesion_jugador_estado');
        });

        DB::statement('alter table avisos_lesion alter column creado_por set not null');
        DB::statement("alter table avisos_lesion add constraint ck_avisos_lesion_estado check (estado in ('pendiente', 'confirmado', 'descartado'))");
        DB::statement("alter table avisos_lesion add constraint ck_avisos_lesion_confirmado_por check ((confirmado_por is not null) = (estado = 'confirmado'))");
        DB::statement("alter table avisos_lesion add constraint ck_avisos_lesion_descarte check ((descartado_por is not null) = (estado = 'descartado') and (nota_descarte is not null) = (estado = 'descartado'))");
        DB::statement("alter table avisos_lesion add constraint ck_avisos_lesion_lesion_id check (lesion_id is null or estado = 'confirmado')");
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos_lesion');
    }
};
