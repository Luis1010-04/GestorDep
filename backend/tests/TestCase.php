<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * PT-05: `config/session.php` barre las sesiones vencidas con una lotería de 2 en 100 en cada petición. Eso borra
     * filas de `sessions` al azar y volvía intermitente una prueba de H1 (`ConsultaDeJugadoresTest`, fila 313). En las
     * pruebas la lotería queda en 0 de 100: el barrido nunca ocurre solo.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['session.lottery' => [0, 100]]);
    }

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
