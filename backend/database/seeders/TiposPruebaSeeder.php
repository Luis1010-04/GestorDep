<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;

class TiposPruebaSeeder extends Seeder
{
    /**
     * Los tres tipos de prueba de docs/hitos/f1-h5.md §5.2. [Propuesta] Unidades, sentido de mejora y rangos son valores
     * de fábrica por validar con la institución (DD-62, DA-16); la velocidad en segundos supone una distancia fija.
     *
     * Idempotente: se identifica cada tipo por `codigo`. Lo que ya existe no se toca, para respetar un ajuste posterior
     * en la base (RNF-09). Los seeders no escriben historial.
     */
    public function run(): void
    {
        $tipos = [
            'velocidad' => ['Velocidad', 's', 'menor', '1.00', '60.00'],
            'resistencia' => ['Resistencia', 'm', 'mayor', '100.00', '6000.00'],
            'tiempo_prueba' => ['Tiempo de prueba', 's', 'menor', '1.00', '3600.00'],
        ];

        foreach ($tipos as $codigo => [$nombre, $unidad, $mejorResultado, $minimo, $maximo]) {
            if (! DB::table('tipos_prueba')->where('codigo', $codigo)->exists()) {
                DB::table('tipos_prueba')->insert([
                    'id' => Identificador::generar()->valor(), 'codigo' => $codigo, 'nombre' => $nombre, 'unidad' => $unidad,
                    'mejor_resultado' => $mejorResultado, 'minimo' => $minimo, 'maximo' => $maximo,
                ]);
            }
        }
    }
}
