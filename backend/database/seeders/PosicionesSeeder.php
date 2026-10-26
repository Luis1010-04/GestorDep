<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;

class PosicionesSeeder extends Seeder
{
    /**
     * Posiciones de la Fase 1 (docs/hitos/f1-h1.md §5.6). Idempotente: se identifica cada posicion por `codigo`.
     */
    public function run(): void
    {
        $posiciones = ['portero' => 'Portero', 'defensa' => 'Defensa', 'medio' => 'Medio', 'delantero' => 'Delantero'];

        foreach ($posiciones as $codigo => $nombre) {
            $existe = DB::table('posiciones')->where('codigo', $codigo)->exists();

            if ($existe) {
                DB::table('posiciones')->where('codigo', $codigo)->update(['nombre' => $nombre]);

                continue;
            }

            DB::table('posiciones')->insert(['id' => Identificador::generar()->valor(), 'codigo' => $codigo, 'nombre' => $nombre]);
        }
    }
}
