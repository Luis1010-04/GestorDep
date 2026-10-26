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

        if (! app()->environment('production')) {
            $this->call(UsuariosDemoSeeder::class);
        }
    }
}
