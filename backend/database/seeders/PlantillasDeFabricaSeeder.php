<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;

class PlantillasDeFabricaSeeder extends Seeder
{
    /**
     * Una plantilla de fábrica «Desarrollo» por posición (RN-07): calificación mínima 6.00 con ventana de 5 partidos.
     * [Propuesta] por validar con la institución (AB-01); el personal encargado la ajusta en la base, no en el código.
     *
     * Idempotente: se identifica por nombre y posición entre las de fábrica no eliminadas. Si ya existe no se toca,
     * para respetar un ajuste posterior (RNF-09, DA-08). Los seeders no escriben historial.
     */
    public function run(): void
    {
        foreach (DB::table('posiciones')->pluck('id') as $posicionId) {
            $existe = DB::table('plantillas_evaluacion')
                ->where('nombre', 'Desarrollo')
                ->where('posicion_id', $posicionId)
                ->where('es_de_fabrica', true)
                ->whereNull('eliminado_en')
                ->exists();

            if ($existe) {
                continue;
            }

            DB::table('plantillas_evaluacion')->insert([
                'id' => Identificador::generar()->valor(),
                'nombre' => 'Desarrollo',
                'posicion_id' => $posicionId,
                'creada_por' => null,
                'calificacion_minima' => '6.00',
                'ventana_partidos' => 5,
                'es_de_fabrica' => true,
            ]);
        }
    }
}
