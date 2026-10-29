<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;

/*
 * El reinicio rápido desactiva, dentro de una transacción, los disparadores de solo inserción de las tablas que vacía
 * (tests/Ayudantes/EsquemaDePrueba.php). Estas pruebas demuestran que no quedan desactivados, ni tras un reinicio
 * correcto ni tras uno que falla, y que la fila 12 de docs/hitos/f1-h2.md §7.11 sigue detectando UPDATE y DELETE
 * sobre filas de historial escritas después de un reinicio que borró otras anteriores.
 */

function disparadoresDesactivados(): int
{
    return (int) DB::connection('propietario')->scalar(
        "select count(*) from pg_trigger t join pg_class c on c.oid = t.tgrelid
          where not t.tgisinternal and c.relnamespace = 'public'::regnamespace and t.tgenabled <> 'O'",
    );
}

/** @return array{materia: string, jugador: string} */
function disparadoresSembrarHistorial(): array
{
    $m = mundoDeDocentes();
    $propietario = DB::connection('propietario');
    $propietario->table('materias_historial')->insert([
        'id' => Identificador::generar()->valor(), 'materia_id' => $m['m1'], 'version' => 1, 'operacion' => 'crear',
        'datos_nuevos' => json_encode(['dato' => 'x']), 'usuario_id' => null, 'ocurrido_en' => '2026-03-15 10:30:00+00',
    ]);
    $propietario->table('jugadores_historial')->insert([
        'id' => Identificador::generar()->valor(), 'jugador_id' => $m['j1'], 'version' => 1, 'operacion' => 'crear',
        'datos_nuevos' => json_encode(['dato' => 'x']), 'usuario_id' => null, 'ocurrido_en' => '2026-03-15 10:30:00+00',
    ]);

    return ['materia' => 'materias_historial', 'jugador' => 'jugadores_historial'];
}

function disparadoresRechazo(callable $operacion): bool
{
    try {
        $operacion();
    } catch (QueryException) {
        return true;
    }

    return false;
}

test('RNF-02 (§7.11 fila 12): tras un reinicio que borró historial, los disparadores están activos y UPDATE y DELETE se siguen rechazando', function () {
    esquemaReconstruir();
    disparadoresSembrarHistorial();
    expect(DB::table('materias_historial')->count())->toBe(1);

    expect(esquemaReiniciar())->toBe('rapido')
        ->and(DB::table('materias_historial')->count())->toBe(0)
        ->and(DB::table('jugadores_historial')->count())->toBe(0)
        ->and(disparadoresDesactivados())->toBe(0);

    foreach (disparadoresSembrarHistorial() as $tabla) {
        expect(disparadoresRechazo(fn () => DB::table($tabla)->update(['motivo' => 'x'])))->toBeTrue("UPDATE de la aplicación sobre {$tabla}")
            ->and(disparadoresRechazo(fn () => DB::table($tabla)->delete()))->toBeTrue("DELETE de la aplicación sobre {$tabla}")
            ->and(disparadoresRechazo(fn () => DB::connection('propietario')->table($tabla)->update(['motivo' => 'x'])))->toBeTrue("UPDATE del propietario sobre {$tabla}")
            ->and(disparadoresRechazo(fn () => DB::connection('propietario')->table($tabla)->delete()))->toBeTrue("DELETE del propietario sobre {$tabla}")
            ->and(DB::table($tabla)->count())->toBe(1);
    }
});

test('RNF-02: si el vaciado falla a la mitad, los disparadores quedan activos y no se pierde ninguna fila', function () {
    esquemaReconstruir();
    disparadoresSembrarHistorial();

    $fallo = null;
    try {
        esquemaBorrarFilas(DB::connection('propietario'), ['materias_historial', 'jugadores_historial', 'tabla_que_no_existe']);
    } catch (Throwable $e) {
        $fallo = $e;
    }

    expect($fallo)->not->toBeNull()
        ->and(disparadoresDesactivados())->toBe(0)
        ->and(DB::table('materias_historial')->count())->toBe(1)
        ->and(DB::table('jugadores_historial')->count())->toBe(1)
        ->and(disparadoresRechazo(fn () => DB::connection('propietario')->table('materias_historial')->delete()))->toBeTrue();
});

test('RNF-02: ningún reinicio deja una tabla de historial sin su disparador de solo inserción', function () {
    esquemaReconstruir();
    $historiales = array_map(
        fn (stdClass $fila): string => $fila->tablename,
        DB::connection('propietario')->select("select tablename from pg_tables where schemaname = 'public' and tablename like '%\\_historial' order by tablename"),
    );
    $conDisparador = array_map(
        fn (stdClass $fila): string => $fila->tabla,
        DB::connection('propietario')->select(
            "select c.relname as tabla from pg_trigger t join pg_class c on c.oid = t.tgrelid
              where not t.tgisinternal and t.tgenabled = 'O' and c.relname like '%\\_historial' order by c.relname",
        ),
    );

    expect($historiales)->not->toBeEmpty()->and($conDisparador)->toBe($historiales);
});
