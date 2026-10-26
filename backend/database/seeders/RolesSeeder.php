<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;

class RolesSeeder extends Seeder
{
    /**
     * Roles de la Fase 1 (docs/hitos/f1-h1.md §5.6). Idempotente: se identifica cada rol por `codigo`.
     */
    public function run(): void
    {
        foreach (['docente' => 'Docente', 'entrenador' => 'Entrenador'] as $codigo => $nombre) {
            $existe = DB::table('roles')->where('codigo', $codigo)->exists();

            if ($existe) {
                DB::table('roles')->where('codigo', $codigo)->update(['nombre' => $nombre]);

                continue;
            }

            DB::table('roles')->insert(['id' => Identificador::generar()->valor(), 'codigo' => $codigo, 'nombre' => $nombre]);
        }
    }
}
