<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;

class RangosMedicionesCorporalesSeeder extends Seeder
{
    /**
     * Los rangos de peso y estatura de docs/hitos/f1-h5.md §5.2. [Propuesta] Son valores de fábrica por validar con la
     * institución (DD-62, DA-16). Los rangos son cerrados: el mínimo y el máximo son válidos.
     *
     * Idempotente: se identifica cada rango por `codigo`. Lo que ya existe no se toca, para respetar un ajuste posterior
     * en la base (RNF-09). Los seeders no escriben historial.
     */
    public function run(): void
    {
        $rangos = [
            'peso_kg' => ['Peso', 'kg', '20.00', '200.00'],
            'estatura_cm' => ['Estatura', 'cm', '100.00', '230.00'],
        ];

        foreach ($rangos as $codigo => [$nombre, $unidad, $minimo, $maximo]) {
            if (! DB::table('rangos_mediciones_corporales')->where('codigo', $codigo)->exists()) {
                DB::table('rangos_mediciones_corporales')->insert([
                    'id' => Identificador::generar()->valor(), 'codigo' => $codigo, 'nombre' => $nombre, 'unidad' => $unidad,
                    'minimo' => $minimo, 'maximo' => $maximo,
                ]);
            }
        }
    }
}
