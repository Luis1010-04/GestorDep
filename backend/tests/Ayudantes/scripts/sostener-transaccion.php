<?php

/*
 * «La otra petición» de las pruebas de concurrencia de T06 (docs/reportes/f1-h7-plan-t06.md §5, DA-145).
 *
 * Corre en un proceso aparte, sin Laravel, con su propia conexión PDO:
 *   1. abre una transacción y ejecuta las sentencias recibidas;
 *   2. imprime `LISTO`;
 *   3. espera a que otra sesión quede bloqueada por ella (`pg_blocking_pids`), hasta el tiempo máximo;
 *   4. confirma e imprime `ESPERA=1` si vio una sesión bloqueada, o `ESPERA=0` si no.
 *
 * Entrada por variables de entorno: CONCURRENCIA_DSN, CONCURRENCIA_USUARIO, CONCURRENCIA_CLAVE, CONCURRENCIA_MAXIMO
 * (segundos) y CONCURRENCIA_SENTENCIAS (JSON: lista de [sql, lista de parámetros]).
 */

/** Lee una variable de entorno obligatoria; sin ella, termina con el código 2. */
function concurrenciaVariable(string $nombre): string
{
    $valor = getenv($nombre);
    if ($valor === false) {
        fwrite(STDERR, "Falta la variable de entorno $nombre\n");
        exit(2);
    }

    return $valor;
}

/** @return list<array{0: string, 1: list<scalar|null>}> */
function concurrenciaSentencias(string $json): array
{
    $datos = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    if (! is_array($datos)) {
        fwrite(STDERR, "CONCURRENCIA_SENTENCIAS no es una lista\n");
        exit(2);
    }

    $sentencias = [];
    foreach ($datos as $sentencia) {
        if (! is_array($sentencia) || ! is_string($sentencia[0] ?? null) || ! is_array($sentencia[1] ?? null)) {
            fwrite(STDERR, "Cada sentencia debe ser [sql, parámetros]\n");
            exit(2);
        }
        $parametros = [];
        foreach ($sentencia[1] as $parametro) {
            if (! is_scalar($parametro) && $parametro !== null) {
                fwrite(STDERR, "Los parámetros deben ser escalares\n");
                exit(2);
            }
            $parametros[] = $parametro;
        }
        $sentencias[] = [$sentencia[0], $parametros];
    }

    return $sentencias;
}

$dsn = concurrenciaVariable('CONCURRENCIA_DSN');
$usuario = concurrenciaVariable('CONCURRENCIA_USUARIO');
$clave = concurrenciaVariable('CONCURRENCIA_CLAVE');
$maximo = (float) concurrenciaVariable('CONCURRENCIA_MAXIMO');
$sentencias = concurrenciaSentencias(concurrenciaVariable('CONCURRENCIA_SENTENCIAS'));

$opciones = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
$conexion = new PDO($dsn, $usuario, $clave, $opciones);
$observador = new PDO($dsn, $usuario, $clave, $opciones);

$conexion->beginTransaction();
foreach ($sentencias as [$sql, $parametros]) {
    $conexion->prepare($sql)->execute($parametros);
}
$consultaPid = $conexion->query('select pg_backend_pid()');
$pid = $consultaPid === false ? 0 : (int) $consultaPid->fetchColumn();

fwrite(STDOUT, "LISTO\n");
fflush(STDOUT);

// Cada consulta del observador va en su propia transacción (autocommit), así que ve el estado de ese momento.
$bloqueada = $observador->prepare('select exists (select 1 from pg_stat_activity where pid <> ? and ? = any(pg_blocking_pids(pid)))');
$limite = microtime(true) + $maximo;
$huboEspera = false;
while (microtime(true) < $limite) {
    $bloqueada->execute([$pid, $pid]);
    if ((bool) $bloqueada->fetchColumn()) {
        $huboEspera = true;
        break;
    }
    usleep(20000);
}

$conexion->commit();
fwrite(STDOUT, 'ESPERA='.($huboEspera ? '1' : '0')."\n");
