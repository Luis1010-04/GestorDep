<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * HU-22, RN-13, RN-14, RN-23 y §7.6 (docs/hitos/f1-h6.md): las alertas abiertas de un jugador que cada usuario puede ver.
 * Mundo de partida: `avisosMundo()` (E1 entrena A, E2 entrena B, E3 entrena A y B; D1 da una materia con F1 y JB; F1 está en
 * A y B; JB, en baja; hoy es 2026-11-15, dentro del periodo vigente).
 */
/**
 * @param  TestResponse<Response>  $respuesta
 * @return list<string> los tipos de la lista, en el orden recibido
 */
function alertasTipos(TestResponse $respuesta): array
{
    /** @var list<array{tipo: string}> $datos */
    $datos = $respuesta->json('datos');

    return array_map(fn (array $alerta): string => $alerta['tipo'], $datos);
}

test('§7.6 fila 1 (HU-22, DA-89): el docente con materia del jugador recibe 200 con la alerta académica', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $alerta = alertaInsertar($m['f1'], ALERTA_ACADEMICA, causa: ['nivel_riesgo' => 'en_riesgo', 'promedio' => '6.50', 'materias_reprobadas' => 1]);
    $sesion = sesionesIniciar($this, 'd1@x.com');

    $respuesta = sesionesPeticion($this, 'GET', alertasRuta($m['f1']), idDeSesion: $sesion)->assertOk();

    // `jsonb` no conserva el orden de las claves de `causa`; en un objeto JSON el orden no importa, así que se compara sin él.
    expect($respuesta->json('datos'))->toEqual([[
        'id' => $alerta, 'tipo' => ALERTA_ACADEMICA, 'jugador_id' => $m['f1'], 'equipo_id' => null,
        'causa' => ['nivel_riesgo' => 'en_riesgo', 'promedio' => '6.50', 'materias_reprobadas' => 1],
        'activada_en' => '2026-11-15T08:00:00+00:00',
    ]])->and(array_keys($respuesta->json('datos.0')))->toBe(['id', 'tipo', 'jugador_id', 'equipo_id', 'causa', 'activada_en']);
});

test('§7.6 fila 2 (DD-08, DA-90): el docente con materia del jugador recibe 200 y la lista no incluye la alerta deportiva ni la de sanciones', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    alertaInsertar($m['f1'], ALERTA_ACADEMICA);
    alertaInsertar($m['f1'], ALERTA_DEPORTIVA, $m['a']);
    alertaInsertar($m['f1'], ALERTA_SANCIONES, $m['a']);
    $sesion = sesionesIniciar($this, 'd1@x.com');

    expect(alertasTipos(sesionesPeticion($this, 'GET', alertasRuta($m['f1']), idDeSesion: $sesion)->assertOk()))->toBe([ALERTA_ACADEMICA]);
});

test('§7.6 fila 3 (DA-90): el entrenador del equipo de la alerta recibe 200 y la lista incluye la deportiva y la de sanciones', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    alertaInsertar($m['f1'], ALERTA_DEPORTIVA, $m['a'], activadaEn: '2026-11-15 08:00:00+00');
    alertaInsertar($m['f1'], ALERTA_SANCIONES, $m['a'], activadaEn: '2026-11-15 09:00:00+00');
    $sesion = sesionesIniciar($this, 'e1@x.com');

    expect(alertasTipos(sesionesPeticion($this, 'GET', alertasRuta($m['f1']), idDeSesion: $sesion)->assertOk()))->toBe([ALERTA_DEPORTIVA, ALERTA_SANCIONES]);
});

test('§7.6 fila 4 (RN-14): el entrenador de otro equipo del jugador recibe 200 y la lista no incluye la alerta deportiva del primero', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    alertaInsertar($m['f1'], ALERTA_DEPORTIVA, $m['a']);
    alertaInsertar($m['f1'], ALERTA_SANCIONES, $m['a']);
    alertaInsertar($m['f1'], ALERTA_DEPORTIVA, $m['b']);
    $sesion = sesionesIniciar($this, 'e2@x.com');

    $respuesta = sesionesPeticion($this, 'GET', alertasRuta($m['f1']), idDeSesion: $sesion)->assertOk();

    expect(alertasTipos($respuesta))->toBe([ALERTA_DEPORTIVA])
        ->and($respuesta->json('datos.0.equipo_id'))->toBe($m['b']);
});

test('§7.6 fila 7 (DA-89, HU-22): el entrenador del jugador recibe 200 y la lista incluye la alerta académica', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    alertaInsertar($m['f1'], ALERTA_ACADEMICA);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    expect(alertasTipos(sesionesPeticion($this, 'GET', alertasRuta($m['f1']), idDeSesion: $sesion)->assertOk()))->toBe([ALERTA_ACADEMICA]);
});

test('§7.6 fila 5 (DA-102): otro entrenador del mismo equipo de la alerta recibe 200 y la lista la incluye, sin importar quién registró los datos', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    alertaInsertar($m['f1'], ALERTA_DEPORTIVA, $m['a']);
    $e1 = sesionesIniciar($this, 'e1@x.com');
    $e3 = sesionesIniciar($this, 'e3@x.com');

    expect(alertasTipos(sesionesPeticion($this, 'GET', alertasRuta($m['f1']), idDeSesion: $e1)->assertOk()))->toBe([ALERTA_DEPORTIVA])
        ->and(alertasTipos(sesionesPeticion($this, 'GET', alertasRuta($m['f1']), idDeSesion: $e3)->assertOk()))->toBe([ALERTA_DEPORTIVA]);
});

test('§7.6 fila 8 (RN-23, DA-99): un jugador dado de baja con alerta abierta responde 404 a todos, y la alerta sigue en la base', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    alertaInsertar($m['jb'], ALERTA_ACADEMICA);
    alertaInsertar($m['jb'], ALERTA_DEPORTIVA, $m['a']);
    $e1 = sesionesIniciar($this, 'e1@x.com');
    $d1 = sesionesIniciar($this, 'd1@x.com');

    $paraEntrenador = sesionesPeticion($this, 'GET', alertasRuta($m['jb']), idDeSesion: $e1)->assertNotFound();
    $paraDocente = sesionesPeticion($this, 'GET', alertasRuta($m['jb']), idDeSesion: $d1)->assertNotFound();

    expect($paraEntrenador->getContent())->toBe($paraDocente->getContent())
        ->and(DB::table('alertas')->where('jugador_id', $m['jb'])->whereNull('cerrada_en')->count())->toBe(2);
});

test('RNF-01 (HU-22): un jugador fuera del alcance responde el mismo 404 que uno inexistente, sin revelar nada', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    alertaInsertar($m['j1'], ALERTA_DEPORTIVA, $m['a']);
    $inexistente = Identificador::generar()->valor();

    foreach (['e2@x.com', 'd1@x.com', 'd2@x.com'] as $correo) {
        $sesion = sesionesIniciar($this, $correo);
        $ajeno = sesionesPeticion($this, 'GET', alertasRuta($m['j1']), idDeSesion: $sesion)->assertNotFound();
        $referencia = sesionesPeticion($this, 'GET', alertasRuta($inexistente), idDeSesion: $sesion)->assertNotFound();

        expect($ajeno->getContent())->toBe($referencia->getContent());
    }
});

test('HU-22: una alerta cerrada no aparece, aunque quede en la base, y el jugador sin alertas recibe una lista vacía', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    alertaInsertar($m['f1'], ALERTA_DEPORTIVA, $m['a'], cerradaEn: '2026-11-14 08:00:00+00');
    $sesion = sesionesIniciar($this, 'e1@x.com');

    $respuesta = sesionesPeticion($this, 'GET', alertasRuta($m['f1']), idDeSesion: $sesion)->assertOk();

    expect($respuesta->json('datos'))->toBe([])
        ->and(DB::table('alertas')->count())->toBe(1);
});

test('DA-89: la alerta académica la ve el docente con una materia del jugador en el periodo vigente; con una de otro periodo recibe la lista vacía', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    alertaInsertar($m['f1'], ALERTA_ACADEMICA);
    $anterior = mundoInsertar('periodos_escolares', ['nombre' => '2026-1', 'fecha_inicio' => '2026-01-12', 'fecha_fin' => '2026-06-30']);
    $materiaAnterior = mundoInsertar('materias', ['asignatura_id' => $m['asignatura'], 'periodo_id' => $anterior, 'docente_id' => $m['d2']]);
    mundoInscribir($materiaAnterior, $m['f1']);
    $d2 = sesionesIniciar($this, 'd2@x.com');

    expect(sesionesPeticion($this, 'GET', alertasRuta($m['f1']), idDeSesion: $d2)->assertOk()->json('datos'))->toBe([]);
});

test('RN-13: un usuario con los dos roles ve lo que le toca por cada uno, sin duplicados', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $materiaDeDe = mundoInsertar('materias', ['asignatura_id' => $m['asignatura'], 'periodo_id' => $m['periodo'], 'docente_id' => $m['de']]);
    mundoInscribir($materiaDeDe, $m['f1']);
    alertaInsertar($m['f1'], ALERTA_ACADEMICA, activadaEn: '2026-11-15 08:00:00+00');
    alertaInsertar($m['f1'], ALERTA_DEPORTIVA, $m['a'], activadaEn: '2026-11-15 09:00:00+00');
    alertaInsertar($m['f1'], ALERTA_DEPORTIVA, $m['b'], activadaEn: '2026-11-15 10:00:00+00');
    $sesion = sesionesIniciar($this, 'de@x.com');

    $respuesta = sesionesPeticion($this, 'GET', alertasRuta($m['f1']), idDeSesion: $sesion)->assertOk();

    expect(alertasTipos($respuesta))->toBe([ALERTA_ACADEMICA, ALERTA_DEPORTIVA])
        ->and($respuesta->json('datos.1.equipo_id'))->toBe($m['a']);
});

test('HU-22: sin sesión responde 401; con un id que no es un UUID, 404', function () {
    /** @var TestCase $this */
    $m = avisosMundo();

    sesionesPeticion($this, 'GET', alertasRuta($m['f1']))->assertUnauthorized();
    sesionesPeticion($this, 'GET', '/api/v1/jugadores/no-es-un-uuid/alertas', idDeSesion: sesionesIniciar($this, 'e1@x.com'))->assertNotFound();
});

test('DA-90 (visibilidad): el entrenador de A y de B deja de ver la alerta deportiva de A cuando el jugador ya dejó A, y sigue viendo la de B', function () {
    // Esta prueba fija el contrato de VisibilidadDeAlertasSql, no un requisito: la visibilidad de una alerta deportiva pide una
    // membresía vigente del jugador en el equipo de la alerta. Ni RN-14 ni DA-90 dicen qué pasa con un jugador que ya dejó el equipo;
    // DA-90 solo dice que las ven los entrenadores del equipo al que pertenece la alerta. No afirma si la alerta queda abierta o
    // cerrada: eso es del recálculo (P-16).
    /** @var TestCase $this */
    $m = avisosMundo();
    alertaInsertar($m['f1'], ALERTA_DEPORTIVA, $m['a']);
    alertaInsertar($m['f1'], ALERTA_DEPORTIVA, $m['b']);
    $e3 = sesionesIniciar($this, 'e3@x.com');
    $equipos = function () use ($m, $e3): array {
        /** @var list<array{equipo_id: string}> $alertas */
        $alertas = sesionesPeticion($this, 'GET', alertasRuta($m['f1']), idDeSesion: $e3)->assertOk()->json('datos');
        $ids = array_map(fn (array $alerta): string => $alerta['equipo_id'], $alertas);
        sort($ids);

        return $ids;
    };

    $conAmbas = $equipos();
    DB::connection('propietario')->table('jugador_equipo')->where('jugador_id', $m['f1'])->where('equipo_id', $m['a'])->update(['hasta' => '2026-11-01']);
    $trasDejarA = $equipos();

    $esperado = [$m['a'], $m['b']];
    sort($esperado);

    expect($conAmbas)->toBe($esperado)
        ->and($trasDejarA)->toBe([$m['b']]);
});
