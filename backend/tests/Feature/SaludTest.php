<?php

namespace Tests\Feature;

use Tests\TestCase;

class SaludTest extends TestCase
{
    public function test_f1_h0_t03_salud_responde_200_con_aplicacion_y_base_de_datos_en_ok(): void
    {
        $respuesta = $this->getJson('/api/v1/salud');

        $respuesta->assertOk()->assertExactJson([
            'datos' => [
                'aplicacion' => 'ok',
                'base_de_datos' => 'ok',
            ],
        ]);
    }

    public function test_f1_h0_t03_la_aplicacion_usa_utc_idioma_es_y_postgresql(): void
    {
        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame('es', config('app.locale'));
        $this->assertSame('America/Mexico_City', config('app.zona_presentacion'));
        $this->assertSame('pgsql', config('database.default'));
    }
}
