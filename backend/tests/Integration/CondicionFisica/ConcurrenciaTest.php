<?php

use App\Exceptions\FormatoDeError;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\CondicionFisica\Aplicacion\MedicionConcurrente;
use Src\CondicionFisica\Aplicacion\PruebaConcurrente;
use Src\CondicionFisica\Dominio\MedicionCorporal;
use Src\CondicionFisica\Dominio\PruebaFisica;
use Src\CondicionFisica\Infraestructura\RepositorioDeMedicionesSql;
use Src\CondicionFisica\Infraestructura\RepositorioDePruebasFisicasSql;

/**
 * Concurrencia real (DA-74): dos transacciones en conexiones distintas a PostgreSQL. Las dos leen que no hay registro,
 * una confirma primero y la segunda choca con el índice único: recibe el error que se responde como 409, nunca un 500.
 */
afterEach(function () {
    DB::purge('concurrente');
});

function concurrenciaConexion(): ConnectionInterface
{
    config(['database.connections.concurrente' => config('database.connections.pgsql')]);

    return DB::connection('concurrente');
}

function concurrenciaEscritor(ConnectionInterface $conexion): EscritorDeHistorial
{
    return new EscritorDeHistorial($conexion, app(Reloj::class), app(UsuarioActual::class));
}

test('DA-74: dos pruebas físicas simultáneas del mismo tipo y periodo: la segunda recibe PruebaConcurrente, que se responde 409', function () {
    $m = pruebasMundo();
    $principal = DB::connection();
    $otra = concurrenciaConexion();
    $repositorioA = new RepositorioDePruebasFisicasSql($principal, concurrenciaEscritor($principal), app(UsuarioActual::class));
    $repositorioB = new RepositorioDePruebasFisicasSql($otra, concurrenciaEscritor($otra), app(UsuarioActual::class));
    [$f1, $e1, $velocidad] = [Identificador::desdeTexto($m['f1']), Identificador::desdeTexto($m['e1']), Identificador::desdeTexto($m['velocidad'])];
    $prueba = fn (string $resultado) => PruebaFisica::registrar(
        Identificador::generar(), $f1, Identificador::desdeTexto($m['a']), $e1, $velocidad, '2026-11-03', NumeroDecimal::desdeTexto($resultado),
        new DateTimeImmutable('2026-11-15 18:00:00+00:00'),
    );

    $otra->beginTransaction();
    $leidaPorB = $repositorioB->delPeriodoParaActualizar($f1, $velocidad, $e1, '2026-11');
    $principal->transaction(fn () => $repositorioA->guardarNueva($prueba('4.35')));

    $error = null;
    try {
        $repositorioB->guardarNueva($prueba('4.20'));
    } catch (PruebaConcurrente $e) {
        $error = $e;
    }
    $otra->rollBack();

    expect($leidaPorB)->toBeNull()
        ->and($error)->toBeInstanceOf(PruebaConcurrente::class)
        ->and(FormatoDeError::responder(NoNuloDeConcurrencia::exigir($error))->getStatusCode())->toBe(409)
        ->and(DB::table('pruebas_fisicas')->where('jugador_id', $m['f1'])->count())->toBe(1)
        ->and(DB::table('pruebas_fisicas')->where('jugador_id', $m['f1'])->value('resultado'))->toBe('4.35');
});

test('DA-74: dos mediciones simultáneas del mismo periodo: la segunda recibe MedicionConcurrente, que se responde 409 conflicto', function () {
    $m = medicionesMundo();
    $principal = DB::connection();
    $otra = concurrenciaConexion();
    $repositorioA = new RepositorioDeMedicionesSql($principal, concurrenciaEscritor($principal), app(UsuarioActual::class));
    $repositorioB = new RepositorioDeMedicionesSql($otra, concurrenciaEscritor($otra), app(UsuarioActual::class));
    [$f1, $e1] = [Identificador::desdeTexto($m['f1']), Identificador::desdeTexto($m['e1'])];
    $medicion = fn (string $peso) => MedicionCorporal::registrar(
        Identificador::generar(), $f1, Identificador::desdeTexto($m['a']), $e1, '2026-11-15', NumeroDecimal::desdeTexto($peso), NumeroDecimal::desdeTexto('175.0'),
        new DateTimeImmutable('2026-11-15 18:00:00+00:00'),
    );

    $otra->beginTransaction();
    $leidaPorB = $repositorioB->delPeriodoParaActualizar($f1, $e1, '2026-11');
    $principal->transaction(fn () => $repositorioA->guardarNueva($medicion('70.00')));

    $error = null;
    try {
        $repositorioB->guardarNueva($medicion('71.00'));
    } catch (MedicionConcurrente $e) {
        $error = $e;
    }
    $otra->rollBack();
    $respuesta = FormatoDeError::responder(NoNuloDeConcurrencia::exigir($error));

    expect($leidaPorB)->toBeNull()
        ->and($respuesta->getStatusCode())->toBe(409)
        ->and(json_decode((string) $respuesta->getContent(), true)['codigo'])->toBe('conflicto')
        ->and(DB::table('mediciones_corporales')->where('jugador_id', $m['f1'])->count())->toBe(1);
});

/** Exige que la excepción exista antes de pasarla al formateador. */
final class NoNuloDeConcurrencia
{
    public static function exigir(?Throwable $error): Throwable
    {
        return $error ?? throw new RuntimeException('la segunda escritura no chocó');
    }
}
