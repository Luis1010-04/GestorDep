<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La matrícula no distingue mayúsculas (RNF-03, docs/hitos/f1-h1.md, §7.6 fila 3): el índice único parcial
 * original (`matricula` donde `eliminado_en is null`) pasa a ser sobre `lower(matricula)`, con el mismo predicado.
 * La columna sigue siendo varchar(30); el dato se guarda como lo escribió el usuario.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('drop index uq_jugadores_matricula');
        DB::statement('create unique index uq_jugadores_matricula on jugadores (lower(matricula)) where eliminado_en is null');
    }

    public function down(): void
    {
        DB::statement('drop index uq_jugadores_matricula');
        DB::statement('create unique index uq_jugadores_matricula on jugadores (matricula) where eliminado_en is null');
    }
};
