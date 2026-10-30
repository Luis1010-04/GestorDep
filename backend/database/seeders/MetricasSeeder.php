<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;

class MetricasSeeder extends Seeder
{
    /**
     * Las nueve métricas de docs/arquitectura.md §8.4 y su relación con cada posición (HU-14): goles y asistencias en las
     * cuatro, más las propias de cada posición; todas obligatorias. Rangos de fábrica por validar (AB-01, DA-07): enteros
     * de 0 a 99, salvo pases completados (0 a 300).
     *
     * Idempotente: se identifica cada métrica por `codigo` y cada relación por su clave. Lo que ya existe no se toca,
     * para respetar un ajuste posterior en la base (RNF-09, DA-08). Los seeders no escriben historial.
     */
    public function run(): void
    {
        $metricas = [
            'goles' => ['Goles', '99'],
            'asistencias' => ['Asistencias', '99'],
            'tiros_a_porteria' => ['Tiros a portería', '99'],
            'atajadas' => ['Atajadas', '99'],
            'goles_recibidos' => ['Goles recibidos', '99'],
            'recuperaciones' => ['Recuperaciones', '99'],
            'faltas_cometidas' => ['Faltas cometidas', '99'],
            'pases_completados' => ['Pases completados', '300'],
            'balones_perdidos' => ['Balones perdidos', '99'],
        ];
        $porPosicion = [
            'portero' => ['goles', 'asistencias', 'atajadas', 'goles_recibidos'],
            'defensa' => ['goles', 'asistencias', 'recuperaciones', 'faltas_cometidas'],
            'medio' => ['goles', 'asistencias', 'pases_completados', 'balones_perdidos'],
            'delantero' => ['goles', 'asistencias', 'tiros_a_porteria'],
        ];

        foreach ($metricas as $codigo => [$nombre, $maximo]) {
            if (! DB::table('metricas')->where('codigo', $codigo)->exists()) {
                DB::table('metricas')->insert([
                    'id' => Identificador::generar()->valor(), 'codigo' => $codigo, 'nombre' => $nombre,
                    'tipo_dato' => 'entero', 'minimo' => '0', 'maximo' => $maximo, 'activa' => true,
                ]);
            }
        }

        $idsDeMetrica = DB::table('metricas')->pluck('id', 'codigo');
        $idsDePosicion = DB::table('posiciones')->pluck('id', 'codigo');

        foreach ($porPosicion as $posicion => $codigos) {
            foreach ($codigos as $indice => $codigo) {
                $clave = ['metrica_id' => $idsDeMetrica[$codigo], 'posicion_id' => $idsDePosicion[$posicion]];

                if (! DB::table('metricas_posicion')->where($clave)->exists()) {
                    DB::table('metricas_posicion')->insert([...$clave, 'obligatoria' => true, 'orden' => $indice + 1]);
                }
            }
        }
    }
}
