<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;

class CriterioGeneralSeeder extends Seeder
{
    /**
     * Criterio de aprobación general (RN-01): 7.00, vigente desde una fecha fija anterior a cualquier prueba.
     * [Propuesta] pendiente de validar con la institución. Idempotente: si ya hay un criterio general vigente
     * no se toca, para respetar un ajuste posterior. Los seeders no escriben historial.
     */
    public function run(): void
    {
        $existe = DB::table('criterios_aprobacion')
            ->where('ambito', 'general')
            ->whereNull('vigente_hasta')
            ->whereNull('eliminado_en')
            ->exists();

        if ($existe) {
            return;
        }

        DB::table('criterios_aprobacion')->insert([
            'id' => Identificador::generar()->valor(),
            'ambito' => 'general',
            'materia_id' => null,
            'calificacion_minima' => '7.00',
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => null,
        ]);
    }
}
