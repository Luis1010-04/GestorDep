<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/**
 * Recorrido de seguridad de las rutas de H4 (RNF-01, RN-14; docs/hitos/f1-h4.md v1.0.6 §6.1 y §7.4): un entrenador
 * ajeno, ids inexistentes y UUID válidos que no son v7, con los 404 comparados byte a byte; 403 al docente y 401 sin
 * sesión. Datos de partida de §7.0: E1 entrena A, E2 entrena B, E3 entrena A y B; F1 está en A y en B.
 */
const UUID_V4_RENDIMIENTO = '123e4567-e89b-42d3-a456-426614174000';

const UUID_V1_RENDIMIENTO = 'a8098c1a-f86e-11da-bd1a-00112444be1e';

beforeEach(function () {
    sesionesPrepararBase();
});

/**
 * El mundo de §7.0 con un rendimiento de E1 (P1 en PJ1), un umbral de E1 para F1 en A y una plantilla propia de E1.
 *
 * @return array<string, string>
 */
function seguridadRendimientoMundo(TestCase $prueba): array
{
    $m = umbralMundo();
    $sesion = sesionesIniciar($prueba, 'e1@x.com');
    $rendimiento = (string) sesionesPeticion($prueba, 'POST', "/api/v1/partidos/{$m['pj1']}/rendimientos", capturaCuerpo($m['p1'], 'portero'), $sesion)->assertCreated()->json('datos.id');
    sesionesPeticion($prueba, 'PUT', "/api/v1/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral", ['calificacion_minima' => '7.00', 'ventana_partidos' => 3], $sesion)->assertOk();
    $plantilla = (string) sesionesPeticion($prueba, 'POST', '/api/v1/plantillas-evaluacion', [
        'nombre' => 'Propia de E1', 'posicion_id' => umbralPosicion('delantero'), 'calificacion_minima' => '7.50', 'ventana_partidos' => 4,
    ], $sesion)->assertCreated()->json('datos.id');

    return [...$m, 'rendimiento' => $rendimiento, 'plantilla' => $plantilla];
}

/**
 * Cada ruta de §6.1 con un recurso de A en la ruta: método, plantilla con `{v}`, el recurso que se ataca y los cuerpos
 * con que se intenta (válidos, inválidos y vacíos). En las rutas de jugador en equipo se ataca cada parámetro: el equipo
 * A (que E2 no entrena) con F1, y el equipo B (que E2 sí entrena) con D1, que no está en B.
 *
 * @param  array<string, string>  $m
 * @return array<string, array{string, string, string, list<array<string, mixed>>}>
 */
function seguridadRendimientoRutas(array $m): array
{
    $captura = capturaCuerpo($m['d1'], 'defensa');

    return [
        'GET /partidos/{partido}/rendimientos' => ['GET', '/api/v1/partidos/{v}/rendimientos', $m['pj1'], [[]]],
        'POST /partidos/{partido}/rendimientos' => ['POST', '/api/v1/partidos/{v}/rendimientos', $m['pj1'], [$captura, [...$captura, 'minutos' => 151], []]],
        'PATCH /rendimientos/{rendimiento}' => ['PATCH', '/api/v1/rendimientos/{v}', $m['rendimiento'], [['calificacion_general' => 1], ['calificacion_general' => 11], []]],
        'GET /equipos/{equipo}/.../umbral' => ['GET', "/api/v1/equipos/{v}/jugadores/{$m['f1']}/umbral", $m['a'], [[]]],
        'GET /equipos/.../jugadores/{jugador}/umbral' => ['GET', "/api/v1/equipos/{$m['b']}/jugadores/{v}/umbral", $m['d1'], [[]]],
        'PUT /equipos/{equipo}/.../umbral' => ['PUT', "/api/v1/equipos/{v}/jugadores/{$m['f1']}/umbral", $m['a'], [['calificacion_minima' => '9.00', 'ventana_partidos' => 1], ['calificacion_minima' => '0'], []]],
        'PUT /equipos/.../jugadores/{jugador}/umbral' => ['PUT', "/api/v1/equipos/{$m['b']}/jugadores/{v}/umbral", $m['d1'], [['plantilla_id' => $m['plantilla']], ['ventana_partidos' => 'x'], []]],
        'GET /equipos/{equipo}/.../desempeno' => ['GET', "/api/v1/equipos/{v}/jugadores/{$m['f1']}/desempeno", $m['a'], [[]]],
        'GET /equipos/.../jugadores/{jugador}/desempeno' => ['GET', "/api/v1/equipos/{$m['b']}/jugadores/{v}/desempeno", $m['d1'], [[]]],
    ];
}

/** Huella de las tablas de Rendimiento que un atacante no debe tocar. */
function seguridadRendimientoHuella(): string
{
    $huella = [];
    foreach (['rendimientos_partido', 'valores_metrica', 'plantillas_evaluacion', 'umbrales_rendimiento', 'evaluaciones_rendimiento'] as $tabla) {
        $huella[$tabla] = DB::table($tabla)->orderBy('id')->get()->map(fn ($fila) => (array) $fila)->all();
    }
    foreach (['rendimientos_partido_historial', 'valores_metrica_historial', 'plantillas_evaluacion_historial', 'umbrales_rendimiento_historial'] as $tabla) {
        $huella[$tabla] = DB::table($tabla)->count();
    }

    return md5((string) json_encode($huella));
}

/**
 * Las 10 rutas de §6.1, con recursos reales de A en la ruta.
 *
 * @param  array<string, string>  $m
 * @return list<array{string, string}>
 */
function seguridadRendimientoTodasLasRutas(array $m): array
{
    return [
        ['GET', '/metricas'],
        ['GET', '/plantillas-evaluacion'], ['POST', '/plantillas-evaluacion'],
        ['GET', '/partidos/pendientes-de-rendimiento'],
        ['GET', "/partidos/{$m['pj1']}/rendimientos"], ['POST', "/partidos/{$m['pj1']}/rendimientos"],
        ['PATCH', "/rendimientos/{$m['rendimiento']}"],
        ['GET', "/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral"], ['PUT', "/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral"],
        ['GET', "/equipos/{$m['a']}/jugadores/{$m['f1']}/desempeno"],
    ];
}

test('RNF-01 (§7.4 filas 4 y 5): E2 sobre cada recurso de A, con cualquier cuerpo, recibe un 404 byte a byte idéntico al de un id inexistente o no v7, sin cambios', function () {
    /** @var TestCase $this */
    $m = seguridadRendimientoMundo($this);
    $sesion = sesionesIniciar($this, 'e2@x.com');
    $referencia = sesionesPeticion($this, 'GET', '/api/v1/partidos/'.Identificador::generar()->valor().'/rendimientos', idDeSesion: $sesion)->assertNotFound();
    expect($referencia->json())->toBe(['codigo' => 'recurso_no_encontrado', 'mensaje' => 'El recurso solicitado no existe.', 'errores' => []]);
    $antes = seguridadRendimientoHuella();

    foreach (seguridadRendimientoRutas($m) as $nombre => [$metodo, $plantilla, $recurso, $cuerpos]) {
        foreach ($cuerpos as $cuerpo) {
            foreach (['ajeno' => $recurso, 'inexistente' => Identificador::generar()->valor(), 'v4' => UUID_V4_RENDIMIENTO, 'v1' => UUID_V1_RENDIMIENTO] as $caso => $id) {
                $respuesta = sesionesPeticion($this, $metodo, str_replace('{v}', $id, $plantilla), $cuerpo, $sesion);
                expect($respuesta->getStatusCode())->toBe(404, "$nombre, $caso, ".json_encode($cuerpo))
                    ->and($respuesta->getContent())->toBe($referencia->getContent(), "$nombre, $caso");
            }
        }
    }

    expect(seguridadRendimientoHuella())->toBe($antes);
});

test('RN-14 (§7.1 fila 13): E3, que entrena A, intenta corregir el rendimiento de E1 con cualquier cuerpo y recibe el 404 de uno inexistente, sin cambios', function () {
    /** @var TestCase $this */
    $m = seguridadRendimientoMundo($this);
    $sesion = sesionesIniciar($this, 'e3@x.com');
    $antes = seguridadRendimientoHuella();

    foreach ([['calificacion_general' => 1], ['calificacion_general' => 11], ['jugador_id' => $m['f1']], []] as $cuerpo) {
        expect(sesionesPeticion($this, 'PATCH', "/api/v1/rendimientos/{$m['rendimiento']}", $cuerpo, $sesion)->assertNotFound()->getContent())
            ->toBe(sesionesPeticion($this, 'PATCH', '/api/v1/rendimientos/'.Identificador::generar()->valor(), $cuerpo, $sesion)->getContent(), (string) json_encode($cuerpo));
    }
    expect(seguridadRendimientoHuella())->toBe($antes);
});

test('RN-14 (§7.4 fila 3): E3 no ve el rendimiento, el umbral, la plantilla ni el desempeño que registró E1', function () {
    /** @var TestCase $this */
    $m = seguridadRendimientoMundo($this);
    $sesion = sesionesIniciar($this, 'e3@x.com');

    sesionesPeticion($this, 'GET', "/api/v1/partidos/{$m['pj1']}/rendimientos", idDeSesion: $sesion)->assertOk()->assertJsonPath('datos', [])->assertJsonPath('meta.total', 0);
    sesionesPeticion($this, 'GET', "/api/v1/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral", idDeSesion: $sesion)->assertOk()->assertJsonPath('datos.origen', 'plantilla');
    sesionesPeticion($this, 'GET', "/api/v1/equipos/{$m['a']}/jugadores/{$m['p1']}/desempeno", idDeSesion: $sesion)->assertOk()->assertJsonPath('datos.partidos_considerados', 0);
    $plantillas = sesionesPeticion($this, 'GET', '/api/v1/plantillas-evaluacion', idDeSesion: $sesion)->assertOk();
    expect(array_column($plantillas->json('datos'), 'id'))->not->toContain($m['plantilla'])
        ->and(array_column($plantillas->json('datos'), 'es_de_fabrica'))->each->toBeTrue();
    sesionesPeticion($this, 'PUT', "/api/v1/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral", ['plantilla_id' => $m['plantilla']], $sesion)
        ->assertUnprocessable()->assertJsonPath('codigo', 'plantilla_inexistente');
});

test('RNF-01 (§7.4 fila 4): E1 con un UUID válido que no es v7 en cada parámetro de ruta recibe 404, nunca 500', function () {
    /** @var TestCase $this */
    $m = seguridadRendimientoMundo($this);
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $referencia = sesionesPeticion($this, 'PATCH', '/api/v1/rendimientos/'.Identificador::generar()->valor(), ['calificacion_general' => 8], $sesion)->assertNotFound()->getContent();
    $rutas = [
        ['PATCH', '/rendimientos/{v}'], ['GET', '/partidos/{v}/rendimientos'], ['POST', '/partidos/{v}/rendimientos'],
        ['GET', "/equipos/{v}/jugadores/{$m['f1']}/umbral"], ['PUT', "/equipos/{v}/jugadores/{$m['f1']}/umbral"], ['GET', "/equipos/{v}/jugadores/{$m['f1']}/desempeno"],
        ['GET', "/equipos/{$m['a']}/jugadores/{v}/umbral"], ['PUT', "/equipos/{$m['a']}/jugadores/{v}/umbral"], ['GET', "/equipos/{$m['a']}/jugadores/{v}/desempeno"],
    ];

    foreach ($rutas as [$metodo, $ruta]) {
        foreach ([UUID_V4_RENDIMIENTO, UUID_V1_RENDIMIENTO] as $id) {
            $respuesta = sesionesPeticion($this, $metodo, '/api/v1'.str_replace('{v}', $id, $ruta), ['calificacion_general' => 8], $sesion);
            expect($respuesta->getStatusCode())->toBe(404, "$metodo $ruta $id")->and($respuesta->getContent())->toBe($referencia);
        }
    }
});

test('RNF-01 (§7.4 fila 1): un docente con sesión recibe 403 sin_permiso en las 10 rutas de H4, sin cambios', function () {
    /** @var TestCase $this */
    $m = seguridadRendimientoMundo($this);
    $sesion = sesionesIniciar($this, 'd@x.com');
    $rutas = seguridadRendimientoTodasLasRutas($m);
    $antes = seguridadRendimientoHuella();

    expect($rutas)->toHaveCount(10);
    foreach ($rutas as [$metodo, $ruta]) {
        sesionesPeticion($this, $metodo, '/api/v1'.$ruta, ['calificacion_general' => 1, 'nombre' => 'X'], $sesion)
            ->assertForbidden()
            ->assertExactJson(['codigo' => 'sin_permiso', 'mensaje' => __('errores.sin_permiso'), 'errores' => []]);
    }
    expect(seguridadRendimientoHuella())->toBe($antes);
});

test('RNF-01 (§7.4 fila 2): sin sesión, las 10 rutas de H4 responden 401 no_autenticado, sin cambios', function () {
    /** @var TestCase $this */
    $m = seguridadRendimientoMundo($this);
    $rutas = seguridadRendimientoTodasLasRutas($m);
    $antes = seguridadRendimientoHuella();

    expect($rutas)->toHaveCount(10);
    foreach ($rutas as [$metodo, $ruta]) {
        sesionesPeticion($this, $metodo, '/api/v1'.$ruta, ['calificacion_general' => 1])
            ->assertUnauthorized()
            ->assertExactJson(['codigo' => 'no_autenticado', 'mensaje' => __('errores.no_autenticado'), 'errores' => []]);
    }
    expect(seguridadRendimientoHuella())->toBe($antes);
});

/** El 404 de referencia: un PATCH a un rendimiento inexistente. */
function seguridadRendimientoReferencia(TestCase $prueba, string $sesion): string
{
    return (string) sesionesPeticion($prueba, 'PATCH', '/api/v1/rendimientos/'.Identificador::generar()->valor(), ['calificacion_general' => 8], $sesion)->assertNotFound()->getContent();
}

test('RN-14 (T05, mutación M02): quien registró un rendimiento y deja de entrenar ese equipo ya no lo corrige ni lista, aunque vea al jugador por otro equipo', function () {
    /** @var TestCase $this */
    $m = seguridadRendimientoMundo($this);
    $sesion = sesionesIniciar($this, 'e3@x.com');
    $id = (string) sesionesPeticion($this, 'POST', "/api/v1/partidos/{$m['pj1']}/rendimientos", capturaCuerpo($m['f1'], 'delantero'), $sesion)->assertCreated()->json('datos.id');
    DB::connection('propietario')->table('entrenador_equipo')->where('usuario_id', $m['e3'])->where('equipo_id', $m['a'])->update(['hasta' => '2026-09-30']);
    $antes = seguridadRendimientoHuella();

    expect(sesionesPeticion($this, 'PATCH', "/api/v1/rendimientos/{$id}", ['calificacion_general' => 8], $sesion)->assertNotFound()->getContent())
        ->toBe(seguridadRendimientoReferencia($this, $sesion));
    sesionesPeticion($this, 'GET', "/api/v1/partidos/{$m['pj1']}/rendimientos", idDeSesion: $sesion)->assertNotFound();
    sesionesPeticion($this, 'GET', "/api/v1/equipos/{$m['b']}/jugadores/{$m['f1']}/umbral", idDeSesion: $sesion)->assertOk();
    expect(seguridadRendimientoHuella())->toBe($antes);
});

test('RNF-01 (T05, mutaciones M03 y M06): si el jugador deja el equipo, quien registró su rendimiento ya no lo corrige ni lo ve en la lista del partido', function () {
    /** @var TestCase $this */
    $m = seguridadRendimientoMundo($this);
    $sesion = sesionesIniciar($this, 'e1@x.com');
    sesionesPeticion($this, 'GET', "/api/v1/partidos/{$m['pj1']}/rendimientos", idDeSesion: $sesion)->assertOk()->assertJsonPath('meta.total', 1);
    DB::connection('propietario')->table('jugador_equipo')->where('jugador_id', $m['p1'])->where('equipo_id', $m['a'])->update(['hasta' => '2026-09-30']);
    $antes = seguridadRendimientoHuella();

    expect(sesionesPeticion($this, 'PATCH', "/api/v1/rendimientos/{$m['rendimiento']}", ['calificacion_general' => 8], $sesion)->assertNotFound()->getContent())
        ->toBe(seguridadRendimientoReferencia($this, $sesion));
    sesionesPeticion($this, 'GET', "/api/v1/partidos/{$m['pj1']}/rendimientos", idDeSesion: $sesion)->assertOk()->assertJsonPath('datos', [])->assertJsonPath('meta.total', 0);
    expect(seguridadRendimientoHuella())->toBe($antes);
});

test('RNF-01 (T05, mutación M09): un convocado que ya no está en el alcance del entrenador no deja el partido como pendiente', function () {
    /** @var TestCase $this */
    $m = seguridadRendimientoMundo($this);
    $sesion = sesionesIniciar($this, 'e1@x.com');
    foreach (['d1' => 'defensa', 'm1' => 'medio'] as $jugador => $posicion) {
        sesionesPeticion($this, 'POST', "/api/v1/partidos/{$m['pj1']}/rendimientos", capturaCuerpo($m[$jugador], $posicion), $sesion)->assertCreated();
    }
    $pendientes = fn () => array_column(sesionesPeticion($this, 'GET', '/api/v1/partidos/pendientes-de-rendimiento', idDeSesion: $sesion)->assertOk()->json('datos'), 'partido_id');
    expect($pendientes())->toContain($m['pj1']);

    // F1 deja A y sigue en B, que E1 no entrena: su falta de rendimiento ya no es un pendiente de E1.
    DB::connection('propietario')->table('jugador_equipo')->where('jugador_id', $m['f1'])->where('equipo_id', $m['a'])->update(['hasta' => '2026-09-30']);

    expect($pendientes())->not->toContain($m['pj1']);
});

test('RNF-01 (T05, mutación M12): E3 ve a un jugador de B, pero su umbral y su desempeño en A dan el 404 de uno inexistente', function () {
    /** @var TestCase $this */
    $m = seguridadRendimientoMundo($this);
    $soloB = mundoJugador('M-SB', 'Solo B', [$m['b'] => 'medio'], nombres: 'SB');
    $sesion = sesionesIniciar($this, 'e3@x.com');
    $referencia = sesionesPeticion($this, 'GET', "/api/v1/equipos/{$m['a']}/jugadores/".Identificador::generar()->valor().'/umbral', idDeSesion: $sesion)->assertNotFound()->getContent();
    $antes = seguridadRendimientoHuella();

    foreach ([['GET', 'umbral'], ['PUT', 'umbral'], ['GET', 'desempeno']] as [$metodo, $recurso]) {
        expect(sesionesPeticion($this, $metodo, "/api/v1/equipos/{$m['a']}/jugadores/{$soloB}/{$recurso}", [], $sesion)->assertNotFound()->getContent())
            ->toBe($referencia, "$metodo $recurso");
    }
    sesionesPeticion($this, 'GET', "/api/v1/equipos/{$m['b']}/jugadores/{$soloB}/umbral", idDeSesion: $sesion)->assertOk();
    expect(seguridadRendimientoHuella())->toBe($antes);
});

test('RNF-01 (DA-26 de H3): un filtro de posición con UUID v4 o v1 da una lista vacía, y los pendientes de E2 no muestran partidos de A', function () {
    /** @var TestCase $this */
    $m = seguridadRendimientoMundo($this);
    $sesion = sesionesIniciar($this, 'e2@x.com');

    foreach ([UUID_V4_RENDIMIENTO, UUID_V1_RENDIMIENTO] as $id) {
        sesionesPeticion($this, 'GET', "/api/v1/plantillas-evaluacion?filtro[posicion_id]=$id", idDeSesion: $sesion)->assertOk()->assertJsonPath('datos', []);
    }
    $pendientes = sesionesPeticion($this, 'GET', '/api/v1/partidos/pendientes-de-rendimiento', idDeSesion: $sesion)->assertOk();
    expect(array_column($pendientes->json('datos'), 'equipo_id'))->not->toContain($m['a']);
});
