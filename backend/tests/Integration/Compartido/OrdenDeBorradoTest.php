<?php

use Illuminate\Support\Facades\DB;

/** @return array<int, stdClass> */
function ordenFk(string ...$pares): array
{
    $dependencias = [];
    foreach ($pares as $par) {
        [$hija, $padre] = explode('>', $par);
        $dependencia = new stdClass;
        $dependencia->hija = $hija;
        $dependencia->padre = $padre;
        $dependencias[] = $dependencia;
    }

    return $dependencias;
}

test('F1-H2-FIX: el borrado va con las tablas hijas antes que sus padres', function () {
    $orden = esquemaOrdenDeBorrado(['usuarios', 'materias', 'inscripciones', 'asignaturas'], ordenFk(
        'materias>usuarios', 'materias>asignaturas', 'inscripciones>materias',
    ));

    expect($orden)->not->toBeNull()->toHaveCount(4);
    assert($orden !== null);
    foreach ([['materias', 'usuarios'], ['materias', 'asignaturas'], ['inscripciones', 'materias']] as [$hija, $padre]) {
        expect((int) array_search($hija, $orden, true))->toBeLessThan((int) array_search($padre, $orden, true));
    }
});

test('F1-H2-FIX: una clave foránea hacia una tabla sin filas o hacia sí misma no bloquea el orden', function () {
    expect(esquemaOrdenDeBorrado(['categorias'], ordenFk('categorias>categorias', 'equipos>categorias')))->toBe(['categorias']);
});

test('F1-H2-FIX: un ciclo de claves foráneas devuelve nulo para volver a TRUNCATE', function () {
    expect(esquemaOrdenDeBorrado(['a', 'b'], ordenFk('a>b', 'b>a')))->toBeNull();
});

test('F1-H2-FIX: el vaciado por DELETE cubre las claves foráneas reales de la base', function () {
    esquemaReconstruir();
    $tablas = array_values(array_map(fn (stdClass $fila): string => $fila->tablename, DB::connection('propietario')->select(
        "select tablename from pg_tables where schemaname = 'public' and tablename <> 'migrations'",
    )));
    $fks = DB::connection('propietario')->select(
        "select conrelid::regclass::text as hija, confrelid::regclass::text as padre from pg_constraint where contype = 'f' and connamespace = 'public'::regnamespace",
    );

    expect(esquemaOrdenDeBorrado($tablas, $fks))->not->toBeNull()->toHaveCount(count($tablas));
});
