<?php

use Database\Seeders\CriterioGeneralSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/**
 * Recorrido de seguridad de H6 (docs/hitos/f1-h6.md §8 T08; RN-13, RN-14, RN-23, RNF-01) sobre las siete rutas de §6.1. Es la
 * fuente única de las filas de baja y de docente de las siete rutas; las filas de §7.6 y los demás casos ya están en las
 * pruebas de T03 a T07 y no se repiten. Queda fuera, por decisión pendiente (P-15), el agregado de `GET /equipos/{equipo}/graficos`
 * sin `filtro[jugador_id]` frente a los jugadores en baja.
 *
 * Mundo de H4 con reloj en 2026-10-01: E1 entrena A, E2 entrena B, E3 entrena A y B; D es docente. Se agregan DE (docente y
 * entrenador de A), la materia de D con F1 y JB, la materia de DE con OB, JB (en A, en baja), OB (solo en B) y el partido PB de B.
 */
const UUID_V4_DEL_RECORRIDO_H6 = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

/** @return array<string, string> */
function recorridoH6Mundo(): array
{
    $m = convocarMundo();
    (new CriterioGeneralSeeder)->run();
    $d = (string) DB::table('usuarios')->where('correo', 'd@x.com')->value('id');
    $de = sesionesCrearUsuario('de@x.com', ['docente', 'entrenador']);
    mundoInsertar('entrenador_equipo', ['usuario_id' => $de, 'equipo_id' => $m['a'], 'desde' => '2026-01-01']);

    $periodo = mundoInsertar('periodos_escolares', ['nombre' => '2026-2', 'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-12-18']);
    $matematicas = mundoInsertar('asignaturas', ['clave' => 'MAT-101', 'nombre' => 'Matemáticas', 'grado' => 1]);
    $fisica = mundoInsertar('asignaturas', ['clave' => 'FIS-101', 'nombre' => 'Física', 'grado' => 1]);
    $materiaDeD = mundoInsertar('materias', ['asignatura_id' => $matematicas, 'periodo_id' => $periodo, 'docente_id' => $d]);
    $materiaDeDe = mundoInsertar('materias', ['asignatura_id' => $fisica, 'periodo_id' => $periodo, 'docente_id' => $de]);

    $enBaja = mundoJugador('M-JB', 'Zapata', [$m['a'] => 'medio'], enBaja: true, nombres: 'JB');
    $soloDeB = mundoJugador('M-OB', 'Otro', [$m['b'] => 'medio'], nombres: 'OB');
    mundoInscribir($materiaDeD, $m['f1']);
    mundoInscribir($materiaDeD, $enBaja);
    mundoInscribir($materiaDeDe, $soloDeB);
    mundoInsertar('convocatorias', ['partido_id' => $m['pj1'], 'jugador_id' => $enBaja]);

    return [...$m, 'materia_de' => $materiaDeDe, 'd' => $d, 'de' => $de, 'jb' => $enBaja, 'ob' => $soloDeB, 'pb' => recorridoH6Partido($m['b'], $m['lugar'])];
}

/** Un partido programado del equipo indicado, con la temporada, el rival y el lugar del mundo. */
function recorridoH6Partido(string $equipo, string $lugar): string
{
    return mundoInsertar('partidos', [
        'equipo_id' => $equipo, 'temporada_id' => (string) DB::table('temporadas')->value('id'), 'rival_id' => (string) DB::table('rivales')->value('id'),
        'lugar_id' => $lugar, 'inicia_en' => '2026-10-20 23:00:00+00', 'tipo' => 'amistoso', 'condicion' => 'local', 'estado' => 'programado',
    ]);
}

/**
 * El cuerpo de cualquier 404 de H6 (esquema `Error` de docs/api/openapi.yaml; mensaje de backend/lang/es/errores.php).
 *
 * @return array<string, mixed>
 */
function recorridoH6Cuerpo404(): array
{
    return ['codigo' => 'recurso_no_encontrado', 'mensaje' => __('errores.recurso_no_encontrado'), 'errores' => []];
}

test('RNF-01 (DA-119, P-05): el literal que usa el recorrido es un UUID v4 y no puede ser un identificador del sistema (v7)', function () {
    expect(Str::isUuid(UUID_V4_DEL_RECORRIDO_H6))->toBeTrue()
        ->and(UUID_V4_DEL_RECORRIDO_H6[14])->toBe('4')
        ->and(UUID_V4_DEL_RECORRIDO_H6[19])->toBeIn(['8', '9', 'a', 'b'])
        ->and(fn () => Identificador::desdeTexto(UUID_V4_DEL_RECORRIDO_H6))->toThrow(InvalidArgumentException::class);
});

test('RNF-01: sin sesión, las siete rutas de H6 responden 401 con el mismo cuerpo', function () {
    /** @var TestCase $this */
    $m = recorridoH6Mundo();
    $rutas = [
        "/api/v1/jugadores/{$m['f1']}/alertas",
        '/api/v1/estatus-de-jugadores',
        "/api/v1/jugadores/{$m['f1']}/vista-para-convocar",
        "/api/v1/equipos/{$m['a']}/jugadores/para-convocar",
        "/api/v1/partidos/{$m['pj1']}/convocatoria/vista",
        "/api/v1/jugadores/{$m['f1']}/graficos",
        "/api/v1/equipos/{$m['a']}/graficos",
    ];

    $cuerpos = array_map(
        fn (string $ruta) => sesionesPeticion($this, 'GET', $ruta)->assertUnauthorized()->assertJsonPath('codigo', 'no_autenticado')->getContent(),
        $rutas,
    );

    expect($cuerpos)->toHaveCount(7)->and(array_unique($cuerpos))->toHaveCount(1);
});

test('RNF-01: un recurso ajeno, inexistente o con UUID v4 responde el mismo 404, con el cuerpo de error, en cada ruta por recurso', function () {
    /** @var TestCase $this */
    $m = recorridoH6Mundo();
    $rutas = [
        'alertas' => ['jugador', fn (string $id): string => "/api/v1/jugadores/$id/alertas"],
        'vista-para-convocar' => ['jugador', fn (string $id): string => "/api/v1/jugadores/$id/vista-para-convocar"],
        'graficos del jugador' => ['jugador', fn (string $id): string => "/api/v1/jugadores/$id/graficos"],
        'para-convocar' => ['equipo', fn (string $id): string => "/api/v1/equipos/$id/jugadores/para-convocar"],
        'graficos del equipo' => ['equipo', fn (string $id): string => "/api/v1/equipos/$id/graficos"],
        'convocatoria/vista' => ['partido', fn (string $id): string => "/api/v1/partidos/$id/convocatoria/vista"],
    ];
    $ajeno = ['jugador' => $m['ob'], 'equipo' => $m['b'], 'partido' => $m['pb']];

    foreach ($rutas as $nombre => [$tipo, $ruta]) {
        $casos = ['ajeno' => $ajeno[$tipo], 'inexistente' => Identificador::generar()->valor(), 'uuid v4' => UUID_V4_DEL_RECORRIDO_H6];

        foreach ($casos as $caso => $id) {
            $respuesta = convocarPedir($this, $ruta($id), 'e1@x.com')->assertNotFound();

            expect($respuesta->json())->toBe(recorridoH6Cuerpo404(), "$nombre, $caso");
        }
    }
});

test('P-05 (RNF-01): en el listado de estatus, un filtro con un UUID v4 es un id válido que no es de nadie y da la lista vacía, no un error', function () {
    /** @var TestCase $this */
    recorridoH6Mundo();

    foreach (['filtro[equipo_id]', 'filtro[materia_id]'] as $filtro) {
        $respuesta = convocarPedir($this, '/api/v1/estatus-de-jugadores?'.$filtro.'='.UUID_V4_DEL_RECORRIDO_H6, 'e1@x.com')->assertOk();

        expect($respuesta->json('datos'))->toBe([], $filtro)->and($respuesta->json('meta.total'))->toBe(0, $filtro);
    }
});

test('DA-119 (RNF-01): en la vista para convocar, un filtro[equipo_id] con un UUID v4 responde 404, como un equipo ajeno', function () {
    /** @var TestCase $this */
    $m = recorridoH6Mundo();

    $v4 = convocarPedir($this, convocarRuta($m['f1'], 'filtro[equipo_id]='.UUID_V4_DEL_RECORRIDO_H6), 'e1@x.com')->assertNotFound();
    $ajeno = convocarPedir($this, convocarRuta($m['f1'], "filtro[equipo_id]={$m['b']}"), 'e1@x.com')->assertNotFound();

    expect($v4->json())->toBe(recorridoH6Cuerpo404())->and($ajeno->json())->toBe(recorridoH6Cuerpo404());
});

test('RN-23 (DA-99, RNF-01): un jugador en baja responde 404 en las rutas por jugador y se omite, con su total, en las tres listas; la base lo conserva', function () {
    /** @var TestCase $this */
    $m = recorridoH6Mundo();

    foreach (["/api/v1/jugadores/{$m['jb']}/alertas", convocarRuta($m['jb']), "/api/v1/jugadores/{$m['jb']}/graficos"] as $ruta) {
        expect(convocarPedir($this, $ruta, 'e1@x.com')->assertNotFound()->json())->toBe(recorridoH6Cuerpo404(), $ruta);
    }

    $listas = [
        'estatus' => '/api/v1/estatus-de-jugadores?por_pagina=100',
        'para-convocar' => convocarRutaDelEquipo($m['a'], 'por_pagina=100'),
        'convocatoria/vista' => "/api/v1/partidos/{$m['pj1']}/convocatoria/vista?por_pagina=100",
    ];
    foreach ($listas as $nombre => $ruta) {
        $respuesta = convocarPedir($this, $ruta, 'e1@x.com')->assertOk();
        /** @var list<array{jugador: array{id: string}}> $filas */
        $filas = $respuesta->json('datos');
        $ids = array_map(fn (array $fila): string => $fila['jugador']['id'], $filas);

        expect($ids)->not->toContain($m['jb'], $nombre)
            ->and($ids)->toContain($m['f1'])
            ->and($respuesta->json('meta.total'))->toBe(count($ids), $nombre);
    }

    expect(DB::table('jugadores')->where('id', $m['jb'])->exists())->toBeTrue()
        ->and(DB::table('convocatorias')->where('jugador_id', $m['jb'])->exists())->toBeTrue();
});

test('RN-23 (RN-13): para el docente, un jugador en baja responde 404 en las rutas por jugador y se omite del listado de estatus', function () {
    /** @var TestCase $this */
    $m = recorridoH6Mundo();

    foreach (["/api/v1/jugadores/{$m['jb']}/alertas", "/api/v1/jugadores/{$m['jb']}/graficos"] as $ruta) {
        expect(convocarPedir($this, $ruta, 'd@x.com')->assertNotFound()->json())->toBe(recorridoH6Cuerpo404(), $ruta);
    }

    /** @var list<array{jugador: array{id: string}}> $filas */
    $filas = convocarPedir($this, '/api/v1/estatus-de-jugadores?por_pagina=100', 'd@x.com')->assertOk()->json('datos');
    $ids = array_map(fn (array $fila): string => $fila['jugador']['id'], $filas);

    expect($ids)->toBe([$m['f1']]);
});

test('RN-13 (HU-01): el docente recibe 403 en las cuatro rutas de entrenador y 200 en las tres compartidas, con su alcance', function () {
    /** @var TestCase $this */
    $m = recorridoH6Mundo();

    foreach ([convocarRuta($m['f1']), convocarRutaDelEquipo($m['a']), "/api/v1/partidos/{$m['pj1']}/convocatoria/vista", "/api/v1/equipos/{$m['a']}/graficos"] as $ruta) {
        convocarPedir($this, $ruta, 'd@x.com')->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    }
    foreach (["/api/v1/jugadores/{$m['f1']}/alertas", '/api/v1/estatus-de-jugadores', "/api/v1/jugadores/{$m['f1']}/graficos"] as $ruta) {
        convocarPedir($this, $ruta, 'd@x.com')->assertOk();
    }
    // Su alcance es el de sus materias: un jugador que no es de ellas responde 404 en las rutas compartidas.
    foreach (["/api/v1/jugadores/{$m['p1']}/alertas", "/api/v1/jugadores/{$m['p1']}/graficos"] as $ruta) {
        expect(convocarPedir($this, $ruta, 'd@x.com')->assertNotFound()->json())->toBe(recorridoH6Cuerpo404(), $ruta);
    }
});

test('RN-13, RN-14: un usuario con los dos roles recibe por cada rol lo que le toca, sin duplicados, y 404 en las rutas de entrenador para un jugador que solo enseña', function () {
    /** @var TestCase $this */
    $m = recorridoH6Mundo();

    // OB es de B y DE no entrena B: solo se lo ve como docente (materia de DE).
    convocarPedir($this, "/api/v1/jugadores/{$m['ob']}/alertas", 'de@x.com')->assertOk();
    convocarPedir($this, "/api/v1/jugadores/{$m['ob']}/graficos", 'de@x.com')->assertOk();
    expect(convocarPedir($this, convocarRuta($m['ob']), 'de@x.com')->assertNotFound()->json())->toBe(recorridoH6Cuerpo404());
    // P1 es de A, que DE entrena: lo ve como entrenador.
    convocarPedir($this, convocarRuta($m['p1']), 'de@x.com')->assertOk();

    /** @var list<array{jugador: array{id: string}}> $filas */
    $filas = convocarPedir($this, '/api/v1/estatus-de-jugadores?por_pagina=100', 'de@x.com')->assertOk()->json('datos');
    $ids = array_map(fn (array $fila): string => $fila['jugador']['id'], $filas);

    expect($ids)->toContain($m['ob'], $m['p1'])
        ->and($ids)->not->toContain($m['jb'])
        ->and(array_unique($ids))->toBe($ids);
});

/**
 * Ids de jugador, en orden, de una página de la ruta de convocatoria de H3 (`jugador_id`) o de la vista (`jugador.id`), y su meta.
 *
 * @return array{jugadores: list<string>, meta: array<string, int>}
 */
function recorridoH6Pagina(TestCase $prueba, string $ruta, string $correo, bool $esVista): array
{
    $respuesta = convocarPedir($prueba, $ruta, $correo)->assertOk();
    /** @var list<array<string, mixed>> $filas */
    $filas = $respuesta->json('datos');

    return [
        'jugadores' => array_map(fn (array $fila): string => $esVista ? (string) $fila['jugador']['id'] : (string) $fila['jugador_id'], $filas),
        'meta' => $respuesta->json('meta'),
    ];
}

test('DA-96 (paridad con H3): un usuario docente y entrenador no ve en la vista de la convocatoria a un convocado que solo enseña, y la vista y la ruta de H3 coinciden página por página', function () {
    // La exclusión de un convocado que el usuario solo enseña sigue el comportamiento de H3 (que usa solo la rama de entrenador del
    // alcance); no es un requisito propio de la vista. DA-96 se cita solo como identificador de la paridad con la ruta de H3, que no
    // cambia; el texto de DA-96 no dice nada sobre estos convocados (es la decisión 5 de T06).
    /** @var TestCase $this */
    $m = recorridoH6Mundo();
    $salio = mundoJugador('M-SAL', 'Salido', [$m['a'] => 'medio'], nombres: 'SAL');
    DB::connection('propietario')->table('jugador_equipo')->where('jugador_id', $salio)->where('equipo_id', $m['a'])->update(['hasta' => '2026-09-01']);
    mundoInscribir($m['materia_de'], $salio);
    mundoInsertar('convocatorias', ['partido_id' => $m['pj1'], 'jugador_id' => $salio]);

    // Control: DE sí ve a SAL como docente, y no como entrenador.
    convocarPedir($this, "/api/v1/jugadores/$salio/alertas", 'de@x.com')->assertOk();
    convocarPedir($this, convocarRuta($salio), 'de@x.com')->assertNotFound();

    foreach (['por_pagina=2&pagina=1', 'por_pagina=2&pagina=2', 'por_pagina=2&pagina=3', ''] as $consulta) {
        $deH3 = recorridoH6Pagina($this, "/api/v1/partidos/{$m['pj1']}/convocatoria".($consulta === '' ? '' : "?$consulta"), 'de@x.com', false);
        $deLaVista = recorridoH6Pagina($this, "/api/v1/partidos/{$m['pj1']}/convocatoria/vista".($consulta === '' ? '' : "?$consulta"), 'de@x.com', true);

        expect($deLaVista['jugadores'])->toBe($deH3['jugadores'], "página «{$consulta}»")
            ->and($deLaVista['jugadores'])->not->toContain($salio)
            ->and($deLaVista['meta'])->toBe($deH3['meta'], "meta «{$consulta}»");
    }
});
