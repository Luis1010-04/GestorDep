<?php

/*
 * Pruebas de concurrencia de T06 (docs/reportes/f1-h7-plan-t06.md §5 y §6, DA-145). Las pruebas no corren dentro de una
 * transacción: cada proceso usa su propia base y la vacía con TRUNCATE (tests/TestCase.php), así que otro proceso ve los
 * datos de la prueba. `scripts/sostener-transaccion.php` hace de «la otra petición»: sostiene una transacción sin
 * confirmar mientras la prueba hace su petición real, y confirma en cuanto ve que la petición espera su candado (o al
 * vencer el tiempo máximo, si la petición no espera).
 */

use Illuminate\Support\Facades\Process;

/**
 * Ejecuta `$accion` mientras otro proceso sostiene una transacción con `$sentencias`.
 *
 * @template T
 *
 * @param  list<array{0: string, 1: list<scalar|null>}>  $sentencias  lo que hace «la otra petición» dentro de su transacción
 * @param  callable(): T  $accion  la petición de la prueba
 * @return array{resultado: T, hubo_espera: bool}
 */
function concurrenciaMientrasOtraTransaccion(array $sentencias, callable $accion, float $maximo = 3.0): array
{
    /** @var array{host: string, port: int|string, database: string, username: string, password: string} $conexion */
    $conexion = config('database.connections.pgsql');

    $proceso = Process::env([
        'CONCURRENCIA_DSN' => "pgsql:host={$conexion['host']};port={$conexion['port']};dbname={$conexion['database']}",
        'CONCURRENCIA_USUARIO' => $conexion['username'],
        'CONCURRENCIA_CLAVE' => $conexion['password'],
        'CONCURRENCIA_MAXIMO' => (string) $maximo,
        'CONCURRENCIA_SENTENCIAS' => json_encode($sentencias, JSON_THROW_ON_ERROR),
    ])->timeout(60)->start([PHP_BINARY, __DIR__.'/scripts/sostener-transaccion.php']);

    $proceso->waitUntil(fn (string $tipo, string $salida): bool => str_contains($salida, 'LISTO'));
    if (! str_contains($proceso->output(), 'LISTO')) {
        $resultadoDelProceso = $proceso->wait();
        throw new RuntimeException('La otra transacción no llegó a LISTO: '.$resultadoDelProceso->errorOutput());
    }

    $resultado = $accion();

    $final = $proceso->wait();
    if (! $final->successful()) {
        throw new RuntimeException('La otra transacción falló: '.$final->errorOutput());
    }

    return ['resultado' => $resultado, 'hubo_espera' => str_contains($final->output(), 'ESPERA=1')];
}

/**
 * Las sentencias de la baja (`RepositorioDeJugadoresEloquent::darDeBaja`): bloquea la fila del jugador y cambia los dos
 * estatus, sin confirmar.
 *
 * @return list<array{0: string, 1: list<scalar|null>}>
 */
function concurrenciaBajaDe(string $jugador): array
{
    return [
        ['select id from jugadores where id = ? and eliminado_en is null for update', [$jugador]],
        ["update jugadores set estatus_academico = 'baja', estatus_deportivo = 'baja' where id = ?", [$jugador]],
    ];
}
