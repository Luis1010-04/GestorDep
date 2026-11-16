<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;

class ParametrosInstitucionalesSeeder extends Seeder
{
    /**
     * Parámetros de las reglas de cálculo (RNF-09). Cada clave se siembra por separado: es idempotente, se identifica
     * por `clave` vigente y no sobrescribe una que ya exista. Los seeders no escriben historial.
     *
     * - `riesgo_academico` (docs/hitos/f1-h2.md §5.3): [Propuesta] umbrales pendientes de validar con la institución
     *   (RN-04).
     * - `sanciones_deportivas` y `desactualizacion` (docs/hitos/f1-h6.md, DA-100): [Propuesta] pendientes de validar
     *   con la institución (AB-13).
     * - `alerta_academica` (DA-100): el nivel que activa la alerta lo fija RN-21.
     */
    public function run(): void
    {
        foreach ($this->parametros() as $clave => $parametro) {
            $existe = DB::table('parametros_institucionales')
                ->where('clave', $clave)
                ->whereNull('vigente_hasta')
                ->exists();

            if ($existe) {
                continue;
            }

            DB::table('parametros_institucionales')->insert([
                'id' => Identificador::generar()->valor(),
                'clave' => $clave,
                'valor' => json_encode($parametro['valor'], JSON_THROW_ON_ERROR),
                'descripcion' => $parametro['descripcion'],
                'vigente_desde' => '2026-01-01',
                'vigente_hasta' => null,
            ]);
        }
    }

    /**
     * @return array<string, array{valor: array<string, int|string>, descripcion: string}>
     */
    private function parametros(): array
    {
        return [
            'riesgo_academico' => [
                'valor' => [
                    'promedio_sin_riesgo_minimo' => '7.50',
                    'promedio_observacion_minimo' => '7.00',
                    'reprobadas_observacion' => 1,
                    'reprobadas_riesgo' => 2,
                ],
                'descripcion' => 'Umbrales del riesgo académico (RN-04)',
            ],
            'sanciones_deportivas' => [
                'valor' => [
                    'tarjetas_amarillas' => 3,
                    'tarjetas_rojas' => 1,
                    'faltas_umbral' => 3,
                    'faltas_ventana_dias' => 30,
                ],
                'descripcion' => 'Acumulación de sanciones deportivas (RN-09)',
            ],
            'alerta_academica' => [
                'valor' => ['nivel' => 'en_riesgo'],
                'descripcion' => 'Nivel de riesgo que activa la alerta académica (RN-21)',
            ],
            'desactualizacion' => [
                'valor' => ['dias' => 30],
                'descripcion' => 'Días sin captura para marcar un dato como posiblemente desactualizado (RNF-04, DD-15)',
            ],
        ];
    }
}
