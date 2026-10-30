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
     *
     * PT-08: las conexiones de prueba se abren con `synchronous_commit = off`. Cada commit regresa sin esperar a que
     * el WAL llegue al disco, que en el volumen de Docker era el costo dominante de la suite. La visibilidad entre
     * sesiones, los candados, los permisos y las reversiones no cambian; solo la durabilidad ante una caída del
     * servidor, que a una base de pruebas no le importa. Se purgan para que ninguna conexión abierta durante el
     * arranque conserve el valor anterior.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['session.lottery' => [0, 100]]);

        foreach (['pgsql', 'propietario'] as $conexion) {
            config(["database.connections.{$conexion}.synchronous_commit" => 'off']);
            DB::purge($conexion);
        }
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
