<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Cada prueba levanta su propia aplicación y sus conexiones sólo se liberan cuando el recolector las alcanza:
     * en la suite completa se acumulan hasta agotar `max_connections` de PostgreSQL. Se cierran al terminar la prueba.
     */
    protected function tearDown(): void
    {
        foreach (array_keys(DB::getConnections()) as $conexion) {
            DB::disconnect($conexion);
        }

        parent::tearDown();
    }
}
