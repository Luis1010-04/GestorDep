<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;

class ParametrosInstitucionalesSeeder extends Seeder
{
    /**
     * Parámetro `riesgo_academico` (docs/hitos/f1-h2.md §5.3). [Propuesta] umbrales pendientes de validar con la
     * institución (RN-04). Idempotente: se identifica por `clave` vigente y no se sobrescribe si ya existe.
     * Los seeders no escriben historial.
     */
    public function run(): void
    {
        $existe = DB::table('parametros_institucionales')
            ->where('clave', 'riesgo_academico')
            ->whereNull('vigente_hasta')
            ->exists();

        if ($existe) {
            return;
        }

        DB::table('parametros_institucionales')->insert([
            'id' => Identificador::generar()->valor(),
            'clave' => 'riesgo_academico',
            'valor' => json_encode([
                'promedio_sin_riesgo_minimo' => '7.50',
                'promedio_observacion_minimo' => '7.00',
                'reprobadas_observacion' => 1,
                'reprobadas_riesgo' => 2,
            ], JSON_THROW_ON_ERROR),
            'descripcion' => 'Umbrales del riesgo académico (RN-04)',
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => null,
        ]);
    }
}
