<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Los seeders de datos de fabrica se agregan por hito (docs/arquitectura.md, seccion 8.4).
     */
    public function run(): void
    {
        $this->call(RolesSeeder::class);
        $this->call(PosicionesSeeder::class);
        $this->call(CriterioGeneralSeeder::class);
        $this->call(ParametrosInstitucionalesSeeder::class);
        $this->call(MetricasSeeder::class);
        $this->call(PlantillasDeFabricaSeeder::class);
        $this->call(TiposPruebaSeeder::class);
        $this->call(RangosMedicionesCorporalesSeeder::class);

        // Solo desarrollo y pruebas (docs/arquitectura.md, tabla de seeders): lista blanca de entornos (A6-16). El seeder se
        // sigue negando por su cuenta a correr con `production`.
        if (app()->environment(['local', 'testing'])) {
            $this->call(UsuariosDemoSeeder::class);
        }
    }
}
