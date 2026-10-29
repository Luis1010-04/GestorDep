<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/**
 * Recorrido de seguridad de las rutas de H3 (RNF-01, RN-22, HU-01; docs/hitos/f1-h3.md v1.0.2 §6.1 y §7.6): un
 * entrenador ajeno, ids inexistentes y UUID válidos que no son v7, con los 404 comparados byte a byte; 403 al docente y
 * 401 sin sesión. Datos de partida de §7.0: E2 entrena B y ataca recursos de A.
 */
const UUID_V4_COMPETENCIA = '123e4567-e89b-42d3-a456-426614174000';

const UUID_V1_COMPETENCIA = 'a8098c1a-f86e-11da-bd1a-00112444be1e';

beforeEach(function () {
    sesionesPrepararBase();
});

/**
 * Los recursos de A sobre los que se ataca: un entrenamiento realizado con una asistencia de J1, un partido programado
 * con J1 convocado y un partido jugado.
 *
 * @return array<string, string>
 */
function seguridadCompetenciaMundo(): array
{
    $m = mundoDeCompetencia();
    $entrenamiento = mundoEntrenamiento($m['a'], $m['l1'], $m['e1'], '2026-09-10 23:00:00+00', '2026-09-10 23:59:00+00', ['estado' => 'realizado']);
    $partido = mundoInsertar('partidos', [
        'equipo_id' => $m['a'], 'temporada_id' => $m['t1'], 'rival_id' => $m['r1'], 'lugar_id' => $m['l1'],
        'inicia_en' => '2026-09-12 23:00:00+00', 'tipo' => 'amistoso', 'condicion' => 'local', 'estado' => 'programado',
    ]);

    return [
        ...$m,
        'entrenamiento' => $entrenamiento,
        'asistencia' => mundoInsertar('asistencias_entrenamiento', ['entrenamiento_id' => $entrenamiento, 'jugador_id' => $m['j1'], 'estado' => 'falta']),
        'partido' => $partido,
        'convocatoria' => mundoInsertar('convocatorias', ['partido_id' => $partido, 'jugador_id' => $m['j1']]),
        'defensa' => (string) DB::table('posiciones')->where('codigo', 'defensa')->value('id'),
    ];
}

/**
 * Cada ruta de §6.1 con un recurso de A en la ruta: método, plantilla con `{v}`, el recurso que se ataca y los cuerpos
 * con que se intenta (válidos, inválidos y vacíos).
 *
 * @param  array<string, string>  $m
 * @return array<string, array{string, string, string, list<array<string, mixed>>}>
 */
function seguridadCompetenciaRutas(array $m): array
{
    $entrenamiento = ['inicia_en' => '2026-09-20T17:00:00-06:00', 'termina_en' => '2026-09-20T18:00:00-06:00', 'lugar_id' => $m['l1'], 'responsable_id' => $m['e2']];
    $partido = ['inicia_en' => '2026-09-26T17:00:00-06:00', 'lugar_id' => $m['l1'], 'rival_id' => $m['r1'], 'tipo' => 'amistoso', 'condicion' => 'local'];
    $alineacion = ['alineacion' => [['jugador_id' => $m['j1'], 'posicion_id' => $m['defensa'], 'rol_alineacion' => 'titular']]];

    return [
        'GET /equipos/{equipo}/entrenamientos' => ['GET', '/api/v1/equipos/{v}/entrenamientos', $m['a'], [[]]],
        'GET /equipos/{equipo}/entrenamientos?filtro' => ['GET', '/api/v1/equipos/{v}/entrenamientos?filtro[desde]=x', $m['a'], [[]]],
        'POST /equipos/{equipo}/entrenamientos' => ['POST', '/api/v1/equipos/{v}/entrenamientos', $m['a'], [$entrenamiento, [...$entrenamiento, 'termina_en' => 'x'], []]],
        'GET /entrenamientos/{entrenamiento}' => ['GET', '/api/v1/entrenamientos/{v}', $m['entrenamiento'], [[]]],
        'PATCH /entrenamientos/{entrenamiento}' => ['PATCH', '/api/v1/entrenamientos/{v}', $m['entrenamiento'], [['estado' => 'cancelado'], ['estado' => 'x', 'equipo_id' => 1], []]],
        'GET /entrenamientos/{entrenamiento}/asistencias' => ['GET', '/api/v1/entrenamientos/{v}/asistencias', $m['entrenamiento'], [[]]],
        'POST /entrenamientos/{entrenamiento}/asistencias' => ['POST', '/api/v1/entrenamientos/{v}/asistencias', $m['entrenamiento'], [['registros' => [['jugador_id' => $m['j2'], 'estado' => 'presente']]], ['registros' => 'x'], []]],
        'PATCH /asistencias-entrenamiento/{asistencia}' => ['PATCH', '/api/v1/asistencias-entrenamiento/{v}', $m['asistencia'], [['estado' => 'presente'], ['estado' => 'tarde', 'jugador_id' => 1], []]],
        'GET /equipos/{equipo}/asistencia-entrenamientos' => ['GET', '/api/v1/equipos/{v}/asistencia-entrenamientos', $m['a'], [[]]],
        'GET /jugadores/{jugador}/asistencia-entrenamientos' => ['GET', '/api/v1/jugadores/{v}/asistencia-entrenamientos', $m['j1'], [[]]],
        'GET /equipos/{equipo}/partidos' => ['GET', '/api/v1/equipos/{v}/partidos', $m['a'], [[]]],
        'GET /equipos/{equipo}/partidos?filtro' => ['GET', '/api/v1/equipos/{v}/partidos?filtro[estado]=x', $m['a'], [[]]],
        'POST /equipos/{equipo}/partidos' => ['POST', '/api/v1/equipos/{v}/partidos', $m['a'], [$partido, [...$partido, 'tipo' => 'liga', 'temporada_id' => 'x'], []]],
        'GET /partidos/{partido}' => ['GET', '/api/v1/partidos/{v}', $m['partido'], [[]]],
        'PATCH /partidos/{partido}' => ['PATCH', '/api/v1/partidos/{v}', $m['partido'], [['estado' => 'cancelado'], ['estado' => 'jugado', 'goles_favor' => 1], []]],
        'PUT /partidos/{partido}/resultado' => ['PUT', '/api/v1/partidos/{v}/resultado', $m['partido'], [['goles_favor' => 3, 'goles_contra' => 1], ['goles_favor' => 100, 'goles_contra' => 'x'], []]],
        'GET /partidos/{partido}/convocatoria' => ['GET', '/api/v1/partidos/{v}/convocatoria', $m['partido'], [[]]],
        'POST /partidos/{partido}/convocatoria' => ['POST', '/api/v1/partidos/{v}/convocatoria', $m['partido'], [['jugador_id' => $m['j2']], ['jugador_id' => 'x', 'rol_alineacion' => 1], []]],
        'DELETE /convocatorias/{convocatoria}' => ['DELETE', '/api/v1/convocatorias/{v}', $m['convocatoria'], [[]]],
        'PUT /partidos/{partido}/alineacion' => ['PUT', '/api/v1/partidos/{v}/alineacion', $m['partido'], [$alineacion, ['alineacion' => 'x', 'otro' => 1], []]],
    ];
}

/** Huella de las tablas de Competencia que un atacante no debe tocar. */
function seguridadCompetenciaHuella(): string
{
    $huella = [];
    foreach (['entrenamientos', 'asistencias_entrenamiento', 'partidos', 'convocatorias'] as $tabla) {
        $huella[$tabla] = DB::table($tabla)->orderBy('id')->get()->map(fn ($fila) => (array) $fila)->all();
    }
    foreach (['entrenamientos_historial', 'asistencias_entrenamiento_historial', 'partidos_historial', 'convocatorias_historial'] as $tabla) {
        $huella[$tabla] = DB::table($tabla)->count();
    }

    return md5((string) json_encode($huella));
}

test('RNF-01 (§7.6 filas 1, 2, 3, 6 y 8): E2 sobre cada recurso de A, con cualquier cuerpo, recibe un 404 byte a byte idéntico al de un id inexistente o no v7, sin cambios', function () {
    /** @var TestCase $this */
    $m = seguridadCompetenciaMundo();
    $sesion = sesionesIniciar($this, 'e2@x.com');
    $referencia = sesionesPeticion($this, 'GET', '/api/v1/partidos/'.Identificador::generar()->valor(), idDeSesion: $sesion)->assertNotFound();
    expect($referencia->json())->toBe(['codigo' => 'recurso_no_encontrado', 'mensaje' => 'El recurso solicitado no existe.', 'errores' => []]);
    $antes = seguridadCompetenciaHuella();

    foreach (seguridadCompetenciaRutas($m) as $nombre => [$metodo, $plantilla, $recurso, $cuerpos]) {
        foreach ($cuerpos as $cuerpo) {
            foreach (['ajeno' => $recurso, 'inexistente' => Identificador::generar()->valor(), 'v4' => UUID_V4_COMPETENCIA, 'v1' => UUID_V1_COMPETENCIA] as $caso => $id) {
                $respuesta = sesionesPeticion($this, $metodo, str_replace('{v}', $id, $plantilla), $cuerpo, $sesion);
                expect($respuesta->getStatusCode())->toBe(404, "$nombre, $caso, ".json_encode($cuerpo))
                    ->and($respuesta->getContent())->toBe($referencia->getContent(), "$nombre, $caso");
            }
        }
    }

    expect(seguridadCompetenciaHuella())->toBe($antes);
});

test('RNF-01: los catálogos responden el mismo 404 a un id inexistente y a un UUID válido que no es v7, nunca 500', function (string $ruta) {
    /** @var TestCase $this */
    seguridadCompetenciaMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $referencia = sesionesPeticion($this, 'DELETE', "/api/v1/$ruta/".Identificador::generar()->valor(), idDeSesion: $sesion)->assertNotFound();

    foreach ([UUID_V4_COMPETENCIA, UUID_V1_COMPETENCIA] as $id) {
        expect(sesionesPeticion($this, 'DELETE', "/api/v1/$ruta/$id", idDeSesion: $sesion)->getContent())->toBe($referencia->getContent(), "$ruta $id");
    }
})->with(['lugares', 'rivales', 'temporadas', 'torneos']);

/**
 * Las 29 rutas de entrenador de §6.1 (todas menos `GET /jugadores/{jugador}/asistencia-entrenamientos`), con recursos
 * reales de A en la ruta.
 *
 * @param  array<string, string>  $m
 * @return list<array{string, string}>
 */
function seguridadCompetenciaRutasDeEntrenador(array $m): array
{
    return [
        ['GET', '/lugares'], ['POST', '/lugares'], ['DELETE', "/lugares/{$m['l1']}"],
        ['GET', '/rivales'], ['POST', '/rivales'], ['DELETE', "/rivales/{$m['r1']}"],
        ['GET', '/temporadas'], ['POST', '/temporadas'], ['DELETE', "/temporadas/{$m['t1']}"],
        ['GET', '/torneos'], ['POST', '/torneos'], ['DELETE', "/torneos/{$m['to1']}"],
        ['GET', "/equipos/{$m['a']}/entrenamientos"], ['POST', "/equipos/{$m['a']}/entrenamientos"],
        ['GET', "/entrenamientos/{$m['entrenamiento']}"], ['PATCH', "/entrenamientos/{$m['entrenamiento']}"],
        ['GET', "/entrenamientos/{$m['entrenamiento']}/asistencias"], ['POST', "/entrenamientos/{$m['entrenamiento']}/asistencias"],
        ['PATCH', "/asistencias-entrenamiento/{$m['asistencia']}"], ['GET', "/equipos/{$m['a']}/asistencia-entrenamientos"],
        ['GET', "/equipos/{$m['a']}/partidos"], ['POST', "/equipos/{$m['a']}/partidos"],
        ['GET', "/partidos/{$m['partido']}"], ['PATCH', "/partidos/{$m['partido']}"],
        ['PUT', "/partidos/{$m['partido']}/resultado"],
        ['GET', "/partidos/{$m['partido']}/convocatoria"], ['POST', "/partidos/{$m['partido']}/convocatoria"],
        ['DELETE', "/convocatorias/{$m['convocatoria']}"],
        ['PUT', "/partidos/{$m['partido']}/alineacion"],
    ];
}

test('RNF-01 (§7.6 fila 4): un docente con sesión recibe 403 sin_permiso en las 29 rutas de entrenador, sin cambios', function () {
    /** @var TestCase $this */
    $m = seguridadCompetenciaMundo();
    $sesion = sesionesIniciar($this, 'd@x.com');
    $rutas = seguridadCompetenciaRutasDeEntrenador($m);
    $antes = seguridadCompetenciaHuella();
    $catalogos = DB::table('lugares')->count() + DB::table('rivales')->count() + DB::table('temporadas')->count() + DB::table('torneos')->count();

    expect($rutas)->toHaveCount(29);
    foreach ($rutas as [$metodo, $ruta]) {
        sesionesPeticion($this, $metodo, '/api/v1'.$ruta, ['nombre' => 'X', 'estado' => 'cancelado'], $sesion)
            ->assertForbidden()
            ->assertExactJson(['codigo' => 'sin_permiso', 'mensaje' => __('errores.sin_permiso'), 'errores' => []]);
    }
    expect(seguridadCompetenciaHuella())->toBe($antes)
        ->and(DB::table('lugares')->count() + DB::table('rivales')->count() + DB::table('temporadas')->count() + DB::table('torneos')->count())->toBe($catalogos);
});

test('RNF-01 (DA-20): el docente recibe en el porcentaje de asistencia de un jugador el mismo 404 que con un jugador inexistente', function () {
    /** @var TestCase $this */
    $m = seguridadCompetenciaMundo();
    $sesion = sesionesIniciar($this, 'd@x.com');

    $propio = sesionesPeticion($this, 'GET', "/api/v1/jugadores/{$m['j1']}/asistencia-entrenamientos", idDeSesion: $sesion)->assertNotFound();
    $inexistente = sesionesPeticion($this, 'GET', '/api/v1/jugadores/'.Identificador::generar()->valor().'/asistencia-entrenamientos', idDeSesion: $sesion)->assertNotFound();

    expect($propio->getContent())->toBe($inexistente->getContent());
});

test('RNF-01 (§7.6 fila 5): sin sesión, las 30 rutas de H3 responden 401 no_autenticado', function () {
    /** @var TestCase $this */
    $m = seguridadCompetenciaMundo();
    $rutas = [...seguridadCompetenciaRutasDeEntrenador($m), ['GET', "/jugadores/{$m['j1']}/asistencia-entrenamientos"]];

    expect($rutas)->toHaveCount(30);
    foreach ($rutas as [$metodo, $ruta]) {
        sesionesPeticion($this, $metodo, '/api/v1'.$ruta, ['nombre' => 'X'])
            ->assertUnauthorized()
            ->assertExactJson(['codigo' => 'no_autenticado', 'mensaje' => __('errores.no_autenticado'), 'errores' => []]);
    }
});

test('RNF-01 (§7.6 fila 6): un entrenamiento de A consultado por E1 con un UUID v4 da 404, nunca 500', function () {
    /** @var TestCase $this */
    seguridadCompetenciaMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    $v4 = sesionesPeticion($this, 'GET', '/api/v1/entrenamientos/'.UUID_V4_COMPETENCIA, idDeSesion: $sesion);

    expect($v4->getStatusCode())->toBe(404)
        ->and($v4->getContent())->toBe(sesionesPeticion($this, 'GET', '/api/v1/entrenamientos/'.Identificador::generar()->valor(), idDeSesion: $sesion)->getContent());
});

test('RN-22 (§7.6 fila 7): E3, que entrena A y B, consulta un partido de A y recibe 200', function () {
    /** @var TestCase $this */
    $m = seguridadCompetenciaMundo();

    sesionesPeticion($this, 'GET', "/api/v1/partidos/{$m['partido']}", idDeSesion: sesionesIniciar($this, 'e3@x.com'))
        ->assertOk()->assertJsonPath('datos.id', $m['partido'])->assertJsonPath('datos.equipo_id', $m['a']);
});

test('RNF-01 (§7.6 fila 1): E2 consulta un entrenamiento de A y recibe el 404 de uno inexistente', function () {
    /** @var TestCase $this */
    $m = seguridadCompetenciaMundo();
    $sesion = sesionesIniciar($this, 'e2@x.com');

    expect(sesionesPeticion($this, 'GET', "/api/v1/entrenamientos/{$m['entrenamiento']}", idDeSesion: $sesion)->assertNotFound()->getContent())
        ->toBe(sesionesPeticion($this, 'GET', '/api/v1/entrenamientos/'.Identificador::generar()->valor(), idDeSesion: $sesion)->getContent());
});

test('RNF-01 (§7.6 fila 2): E2 intenta registrar asistencia en un entrenamiento de A y recibe el 404 de uno inexistente, sin filas nuevas', function () {
    /** @var TestCase $this */
    $m = seguridadCompetenciaMundo();
    $sesion = sesionesIniciar($this, 'e2@x.com');
    $cuerpo = ['registros' => [['jugador_id' => $m['j3'], 'estado' => 'presente']]];
    $antes = seguridadCompetenciaHuella();

    expect(sesionesPeticion($this, 'POST', "/api/v1/entrenamientos/{$m['entrenamiento']}/asistencias", $cuerpo, $sesion)->assertNotFound()->getContent())
        ->toBe(sesionesPeticion($this, 'POST', '/api/v1/entrenamientos/'.Identificador::generar()->valor().'/asistencias', $cuerpo, $sesion)->getContent())
        ->and(seguridadCompetenciaHuella())->toBe($antes);
});

test('RNF-01 (§7.6 fila 3): E2 intenta convocar a J1 a un partido de A y recibe el 404 de uno inexistente, sin filas nuevas', function () {
    /** @var TestCase $this */
    $m = seguridadCompetenciaMundo();
    $sesion = sesionesIniciar($this, 'e2@x.com');
    $antes = seguridadCompetenciaHuella();

    expect(sesionesPeticion($this, 'POST', "/api/v1/partidos/{$m['partido']}/convocatoria", ['jugador_id' => $m['j1']], $sesion)->assertNotFound()->getContent())
        ->toBe(sesionesPeticion($this, 'POST', '/api/v1/partidos/'.Identificador::generar()->valor().'/convocatoria', ['jugador_id' => $m['j1']], $sesion)->getContent())
        ->and(seguridadCompetenciaHuella())->toBe($antes);
});

test('RNF-01 (§7.6 fila 8): la asistencia de un equipo ajeno da 404 byte a byte por cualquier método, con cuerpo válido e inválido, sin filas nuevas', function () {
    /** @var TestCase $this */
    $m = seguridadCompetenciaMundo();
    $sesion = sesionesIniciar($this, 'e2@x.com');
    $otro = Identificador::generar()->valor();
    $antes = seguridadCompetenciaHuella();
    $pares = [
        ['GET', "/entrenamientos/{$m['entrenamiento']}/asistencias", "/entrenamientos/$otro/asistencias", []],
        ['POST', "/entrenamientos/{$m['entrenamiento']}/asistencias", "/entrenamientos/$otro/asistencias", ['registros' => [['jugador_id' => $m['j3'], 'estado' => 'presente']]]],
        ['POST', "/entrenamientos/{$m['entrenamiento']}/asistencias", "/entrenamientos/$otro/asistencias", ['registros' => 'x']],
        ['PATCH', "/asistencias-entrenamiento/{$m['asistencia']}", "/asistencias-entrenamiento/$otro", ['estado' => 'presente']],
        ['PATCH', "/asistencias-entrenamiento/{$m['asistencia']}", "/asistencias-entrenamiento/$otro", ['estado' => 'tarde', 'otro' => 1]],
        ['GET', "/equipos/{$m['a']}/asistencia-entrenamientos", "/equipos/$otro/asistencia-entrenamientos", []],
        ['GET', "/jugadores/{$m['j1']}/asistencia-entrenamientos", "/jugadores/$otro/asistencia-entrenamientos", []],
    ];

    foreach ($pares as [$metodo, $ajena, $inexistente, $cuerpo]) {
        expect(sesionesPeticion($this, $metodo, '/api/v1'.$ajena, $cuerpo, $sesion)->assertNotFound()->getContent())
            ->toBe(sesionesPeticion($this, $metodo, '/api/v1'.$inexistente, $cuerpo, $sesion)->assertNotFound()->getContent(), "$metodo $ajena");
    }
    expect(seguridadCompetenciaHuella())->toBe($antes);
});
