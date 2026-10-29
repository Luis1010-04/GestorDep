<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Situación académica por HTTP (HU-09, RN-04, RN-13, RN-14, DD-45, DD-48, RNF-01). Datos de partida de docs/hitos/f1-h2.md
 * §7.0; las capturas las hacen D1 (M1) y D2 (M2) por la API, y el escucha (cola `sync`) recalcula el resumen.
 */

/** @return TestResponse<Response> */
function situacionDe(TestCase $prueba, string $jugadorId, string $correo): TestResponse
{
    return sesionesPeticion($prueba, 'GET', "/api/v1/jugadores/$jugadorId/situacion-academica", idDeSesion: sesionesIniciar($prueba, $correo));
}

/** @param  array<string, mixed>  $cuerpo */
function situacionCapturar(TestCase $prueba, string $inscripcionId, array $cuerpo, string $correo = 'd1@x.com'): string
{
    return (string) sesionesPeticion($prueba, 'POST', "/api/v1/inscripciones/$inscripcionId/calificaciones", $cuerpo, sesionesIniciar($prueba, $correo))
        ->assertCreated()->json('datos.id');
}

function situacionCriterio(TestCase $prueba, string $materiaId, string $minimo, string $correo = 'd1@x.com'): void
{
    sesionesPeticion($prueba, 'PUT', "/api/v1/materias/$materiaId/criterio", ['calificacion_minima' => $minimo], sesionesIniciar($prueba, $correo))->assertOk();
}

/**
 * @param  array<string, mixed>  $cambios
 * @return array<string, mixed>
 */
function situacionResumenEsperado(array $cambios = []): array
{
    return [
        'promedio' => null, 'materias_reprobadas' => 0, 'materias_sin_calificar' => 0, 'dato_incompleto' => true,
        'porcentaje_asistencia' => null, 'nivel_riesgo' => null, ...$cambios,
    ];
}

test('HU-09 (§7.10 fila 1): A consulta a J1 inscrito sin calificaciones: periodo P1, promedio nulo, 1 sin calificar, dato incompleto', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    consolaArtisan('alertas:recalcular')->assertSuccessful();

    $respuesta = situacionDe($this, $m['j1'], 'a@x.com')->assertOk();

    expect($respuesta->json('datos'))->toBe([
        'periodo' => ['id' => $m['p1'], 'nombre' => '2026-1'],
        'promedio' => null,
        'materias_reprobadas' => 0,
        'materias_sin_calificar' => 1,
        'dato_incompleto' => true,
        'porcentaje_asistencia' => null,
        'nivel_riesgo' => null,
        'calculado_en' => '2026-03-10T12:00:00+00:00',
    ]);
});

test('HU-07 (§7.10 fila 2): D1 captura la final 8.00 de J1 y A ve 8.00, sin reprobadas ni pendientes, sin_riesgo', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    situacionCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '8.00']);

    expect(situacionDe($this, $m['j1'], 'a@x.com')->assertOk()->json('datos'))->toMatchArray(situacionResumenEsperado([
        'promedio' => '8.00', 'dato_incompleto' => false, 'nivel_riesgo' => 'sin_riesgo',
    ]));
});

test('HU-07 (§7.10 filas 3 y 4): D1 corrige la final a 7.20 (en_observacion) y a 6.50 (1 reprobada, en_riesgo)', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $final = situacionCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '8.00']);
    $sesion = sesionesIniciar($this, 'd1@x.com');

    sesionesPeticion($this, 'PATCH', "/api/v1/calificaciones/$final", ['valor' => '7.20'], $sesion)->assertOk();
    expect(situacionDe($this, $m['j1'], 'a@x.com')->json('datos'))->toMatchArray(situacionResumenEsperado([
        'promedio' => '7.20', 'dato_incompleto' => false, 'nivel_riesgo' => 'en_observacion',
    ]));

    sesionesPeticion($this, 'PATCH', "/api/v1/calificaciones/$final", ['valor' => '6.50'], $sesion)->assertOk();
    expect(situacionDe($this, $m['j1'], 'a@x.com')->json('datos'))->toMatchArray(situacionResumenEsperado([
        'promedio' => '6.50', 'materias_reprobadas' => 1, 'dato_incompleto' => false, 'nivel_riesgo' => 'en_riesgo',
    ]));
});

test('RN-03 (§7.10 fila 5): con final 7.50, un criterio propio de 8.00 deja 1 reprobada; con final 8.00, 0', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $final = situacionCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '7.50']);
    expect(situacionDe($this, $m['j1'], 'a@x.com')->json('datos.materias_reprobadas'))->toBe(0);

    situacionCriterio($this, $m['m1'], '8.00');
    expect(situacionDe($this, $m['j1'], 'a@x.com')->json('datos'))->toMatchArray(['promedio' => '7.50', 'materias_reprobadas' => 1, 'nivel_riesgo' => 'en_observacion']);

    sesionesPeticion($this, 'PATCH', "/api/v1/calificaciones/$final", ['valor' => '8.00'], sesionesIniciar($this, 'd1@x.com'))->assertOk();
    expect(situacionDe($this, $m['j1'], 'a@x.com')->json('datos'))->toMatchArray(['promedio' => '8.00', 'materias_reprobadas' => 0, 'nivel_riesgo' => 'sin_riesgo']);
});

test('RN-03 (§7.10 fila 6): con final 6.50, un criterio propio de 6.00 deja 0 reprobadas (antes 1)', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    situacionCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '6.50']);
    expect(situacionDe($this, $m['j1'], 'a@x.com')->json('datos.materias_reprobadas'))->toBe(1);

    situacionCriterio($this, $m['m1'], '6.00');

    // El nivel sigue en_riesgo: el promedio 6.50 está bajo 7.00 (RN-04), con independencia de las reprobadas.
    expect(situacionDe($this, $m['j1'], 'a@x.com')->json('datos'))->toMatchArray(['promedio' => '6.50', 'materias_reprobadas' => 0, 'nivel_riesgo' => 'en_riesgo']);
});

test('RN-04 (§7.10 fila 7): J3 con 9.00 en M1 (D1) y 6.00 en M2 (D2): 7.50, 1 reprobada, en_observacion, riesgo único', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    mundoMembresia($m['j3'], $m['ea']);
    situacionCapturar($this, $m['i3a'], ['tipo' => 'final', 'valor' => '9.00']);
    situacionCapturar($this, $m['i3b'], ['tipo' => 'final', 'valor' => '6.00'], 'd2@x.com');

    expect(situacionDe($this, $m['j3'], 'a@x.com')->assertOk()->json('datos'))->toMatchArray(situacionResumenEsperado([
        'promedio' => '7.50', 'materias_reprobadas' => 1, 'dato_incompleto' => false, 'nivel_riesgo' => 'en_observacion',
    ]));
});

test('RN-05 (§7.10 fila 8): J1 con 7 presentes, 1 falta justificada y 2 faltas tiene 80.00 de asistencia', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    foreach ([...array_fill(0, 7, 'presente'), 'falta_justificada', 'falta', 'falta'] as $i => $estado) {
        sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i1']}/asistencias", ['fecha' => sprintf('2026-02-%02d', $i + 1), 'estado' => $estado], $sesion)->assertCreated();
    }

    expect(situacionDe($this, $m['j1'], 'a@x.com')->json('datos.porcentaje_asistencia'))->toBe('80.00');
});

test('RN-13 (§7.10 fila 9): la respuesta del entrenador no contiene materias ni calificaciones individuales', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    situacionCapturar($this, $m['i1'], ['tipo' => 'parcial', 'numero_parcial' => 1, 'valor' => '8.25']);

    $respuesta = situacionDe($this, $m['j1'], 'a@x.com')->assertOk();

    expect(array_keys($respuesta->json('datos')))->toBe(['periodo', 'promedio', 'materias_reprobadas', 'materias_sin_calificar', 'dato_incompleto', 'porcentaje_asistencia', 'nivel_riesgo', 'calculado_en'])
        ->and((string) $respuesta->getContent())->not->toContain('materias"')
        ->and((string) $respuesta->getContent())->not->toContain('calificaciones')
        ->and((string) $respuesta->getContent())->not->toContain('MAT-101')
        ->and((string) $respuesta->getContent())->not->toContain($m['m1']);
});

test('DD-48 (§7.10 fila 10): D1 consulta a J3 y recibe el resumen con ambos docentes y materias con solo M1, con criterio y aprobada', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $parcial = situacionCapturar($this, $m['i3a'], ['tipo' => 'parcial', 'numero_parcial' => 1, 'valor' => '9.00']);
    $final = situacionCapturar($this, $m['i3a'], ['tipo' => 'final', 'valor' => '9.00']);
    situacionCapturar($this, $m['i3b'], ['tipo' => 'final', 'valor' => '6.00'], 'd2@x.com');
    situacionCriterio($this, $m['m1'], '6.50');

    $respuesta = situacionDe($this, $m['j3'], 'd1@x.com')->assertOk();

    expect($respuesta->json('datos'))->toMatchArray(['promedio' => '7.50', 'materias_reprobadas' => 1, 'nivel_riesgo' => 'en_observacion'])
        ->and($respuesta->json('datos.materias'))->toHaveCount(1)
        ->and($respuesta->json('datos.materias.0'))->toMatchArray([
            'materia_id' => $m['m1'],
            'asignatura' => ['clave' => 'MAT-101', 'nombre' => 'Matemáticas'],
            'calificacion' => '9.00',
            'criterio' => ['ambito' => 'materia', 'calificacion_minima' => '6.50'],
            'aprobada' => true,
        ])
        ->and(array_keys($respuesta->json('datos.materias.0')))->toBe(['materia_id', 'asignatura', 'calificacion', 'criterio', 'aprobada', 'calificaciones'])
        ->and(array_column($respuesta->json('datos.materias.0.calificaciones'), 'id'))->toBe([$parcial, $final])
        ->and($respuesta->json('datos.materias.0.calificaciones.0.criterio'))->toBe(['ambito' => 'general', 'calificacion_minima' => '7.00']);
    // Nada de M2 (de D2): ni la materia, ni la asignatura, ni la calificación 6.00.
    expect((string) $respuesta->getContent())->not->toContain($m['m2'])
        ->and((string) $respuesta->getContent())->not->toContain('FIS-101')
        ->and((string) $respuesta->getContent())->not->toContain('"6.00"');
});

test('DD-48: la materia del docente sin calificaciones sale con calificación y aprobada nulas y el criterio general', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    consolaArtisan('alertas:recalcular')->assertSuccessful();

    expect(situacionDe($this, $m['j1'], 'd1@x.com')->json('datos.materias'))->toBe([[
        'materia_id' => $m['m1'],
        'asignatura' => ['clave' => 'MAT-101', 'nombre' => 'Matemáticas'],
        'calificacion' => null,
        'criterio' => ['ambito' => 'general', 'calificacion_minima' => '7.00'],
        'aprobada' => null,
        'calificaciones' => [],
    ]]);
});

test('RN-02: la calificación de la materia del docente es la media de los parciales sin final, y reprueba con el criterio vigente', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    situacionCapturar($this, $m['i1'], ['tipo' => 'parcial', 'numero_parcial' => 1, 'valor' => '7.00']);
    situacionCapturar($this, $m['i1'], ['tipo' => 'parcial', 'numero_parcial' => 2, 'valor' => '6.99']);

    expect(situacionDe($this, $m['j1'], 'd1@x.com')->json('datos.materias.0'))->toMatchArray(['calificacion' => '7.00', 'aprobada' => true]);

    situacionCriterio($this, $m['m1'], '7.01');
    expect(situacionDe($this, $m['j1'], 'd1@x.com')->json('datos.materias.0'))->toMatchArray([
        'calificacion' => '7.00', 'criterio' => ['ambito' => 'materia', 'calificacion_minima' => '7.01'], 'aprobada' => false,
    ]);
});

test('DA-15: las materias del docente son solo las del periodo vigente', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $pasada = mundoInsertar('materias', ['asignatura_id' => $m['s2'], 'periodo_id' => $m['p0'], 'docente_id' => $m['d1']]);
    mundoInscribir($pasada, $m['j1']);

    expect(array_column(situacionDe($this, $m['j1'], 'd1@x.com')->json('datos.materias'), 'materia_id'))->toBe([$m['m1']]);
});

test('RNF-01 (§7.10 fila 11): D2 consulta a J1, no inscrito en sus materias, y recibe 404', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $inexistente = situacionDe($this, Identificador::generar()->valor(), 'd2@x.com')->assertNotFound();

    $respuesta = situacionDe($this, $m['j1'], 'd2@x.com')->assertNotFound();

    expect($respuesta->getContent())->toBe($inexistente->getContent());
});

test('RNF-01 (§7.10 fila 12): D1 consulta a J2 y recibe 404', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $inexistente = situacionDe($this, Identificador::generar()->valor(), 'd1@x.com')->assertNotFound();

    expect(situacionDe($this, $m['j2'], 'd1@x.com')->assertNotFound()->getContent())->toBe($inexistente->getContent());
});

test('RNF-01 (§7.10 fila 13): A consulta a J2 (no es suyo) y un id inexistente y recibe el mismo 404', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    $ajeno = situacionDe($this, $m['j2'], 'a@x.com')->assertNotFound();
    $inexistente = situacionDe($this, Identificador::generar()->valor(), 'a@x.com')->assertNotFound();
    $noV7 = situacionDe($this, '123e4567-e89b-42d3-a456-426614174000', 'a@x.com')->assertNotFound();

    expect($ajeno->getContent())->toBe($inexistente->getContent())
        ->and($noV7->getContent())->toBe($inexistente->getContent())
        ->and($inexistente->json())->toBe(['codigo' => 'recurso_no_encontrado', 'mensaje' => 'El recurso solicitado no existe.', 'errores' => []]);
});

test('RN-14 (§7.10 fila 14): DE ve de J1 el resumen y M3 (como docente) y de J4 solo el resumen (como entrenador)', function () {
    /** @var TestCase $this */
    $m = mundoAgregarEquipoYMateriaDeDocenteEntrenador(mundoDeDocentes());
    situacionCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '8.00']);
    situacionCapturar($this, $m['i1m3'], ['tipo' => 'final', 'valor' => '9.00'], 'de@x.com');
    consolaArtisan('alertas:recalcular')->assertSuccessful();

    $j1 = situacionDe($this, $m['j1'], 'de@x.com')->assertOk();
    expect($j1->json('datos'))->toMatchArray(['promedio' => '8.50', 'materias_sin_calificar' => 0])
        ->and(array_column($j1->json('datos.materias'), 'materia_id'))->toBe([$m['m3']])
        ->and($j1->json('datos.materias.0.calificacion'))->toBe('9.00');
    expect((string) $j1->getContent())->not->toContain($m['m1']);

    $j4 = situacionDe($this, $m['j4'], 'de@x.com')->assertOk();
    expect(array_keys($j4->json('datos')))->not->toContain('materias')
        ->and($j4->json('datos.periodo'))->toBe(['id' => $m['p1'], 'nombre' => '2026-1']);
});

test('DD-45 (§7.10 fila 15): sin periodo vigente, A recibe 200 con periodo nulo, nulos, contadores en 0, dato incompleto y calculado_en nulo', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    situacionCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '8.00']);
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-07-15 12:00:00', new DateTimeZone('UTC'))));

    expect(situacionDe($this, $m['j1'], 'a@x.com')->assertOk()->json('datos'))->toBe([
        'periodo' => null, ...situacionResumenEsperado(), 'calculado_en' => null,
    ]);
    // El docente recibe además sus materias, vacías: no hay periodo vigente (DA-15).
    expect(situacionDe($this, $m['j1'], 'd1@x.com')->json('datos.materias'))->toBe([]);
});

test('DD-45 (§7.10 fila 16): con P2 vigente y alertas:recalcular, la situación pasa a P2 con dato incompleto y nulos', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    situacionCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '8.00']);
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-08-03 12:00:00', new DateTimeZone('UTC'))));

    $antes = situacionDe($this, $m['j1'], 'a@x.com')->json('datos');
    expect($antes)->toMatchArray(['periodo' => ['id' => $m['p2'], 'nombre' => '2026-2'], 'calculado_en' => null, 'dato_incompleto' => true]);

    consolaArtisan('alertas:recalcular')->assertSuccessful();

    expect(situacionDe($this, $m['j1'], 'a@x.com')->json('datos'))->toBe([
        'periodo' => ['id' => $m['p2'], 'nombre' => '2026-2'], ...situacionResumenEsperado(), 'calculado_en' => '2026-08-03T12:00:00+00:00',
    ]);
});

test('DD-52 (§7.10 fila 20): J5 en baja, inscrito y con calificaciones previas, sigue respondiendo 200 a quien ya lo tenía', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    mundoMembresia($m['j5'], $m['ea']);
    mundoCalificar($m['i5'], 'final', '6.00');
    consolaArtisan('alertas:recalcular')->assertSuccessful();

    expect(situacionDe($this, $m['j5'], 'a@x.com')->assertOk()->json('datos'))->toMatchArray(['promedio' => '6.00', 'materias_reprobadas' => 1, 'nivel_riesgo' => 'en_riesgo']);
    expect(situacionDe($this, $m['j5'], 'd1@x.com')->assertOk()->json('datos.materias.0.calificacion'))->toBe('6.00');
    situacionDe($this, $m['j5'], 'd2@x.com')->assertNotFound();
});

test('DD-51 (§7.10 fila 21): promedio y porcentaje de asistencia son cadenas con dos decimales', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    situacionCapturar($this, $m['i1'], ['tipo' => 'parcial', 'numero_parcial' => 1, 'valor' => '8']);
    situacionCapturar($this, $m['i1'], ['tipo' => 'parcial', 'numero_parcial' => 2, 'valor' => '7']);
    sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i1']}/asistencias", ['estado' => 'presente'], sesionesIniciar($this, 'd1@x.com'))->assertCreated();

    $cuerpo = (string) situacionDe($this, $m['j1'], 'a@x.com')->getContent();

    expect($cuerpo)->toContain('"promedio":"7.50"')->and($cuerpo)->toContain('"porcentaje_asistencia":"100.00"');
});

test('RNF-04 (§7.10 fila 22): calculado_en es el instante del reloj truncado a segundos, en UTC', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-03-10 18:45:30.900', new DateTimeZone('UTC'))));

    situacionCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '8.00']);

    expect(situacionDe($this, $m['j1'], 'a@x.com')->json('datos.calculado_en'))->toBe('2026-03-10T18:45:30+00:00');
});

test('HU-09: con periodo vigente y sin fila de resumen, responde el periodo con nulos y calculado_en nulo', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    expect(situacionDe($this, $m['j1'], 'a@x.com')->assertOk()->json('datos'))->toBe([
        'periodo' => ['id' => $m['p1'], 'nombre' => '2026-1'], ...situacionResumenEsperado(), 'calculado_en' => null,
    ]);
    expect(DB::table('resumenes_academicos')->count())->toBe(0);
});

test('HU-01: sin sesión la situación académica responde 401', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    sesionesPeticion($this, 'GET', "/api/v1/jugadores/{$m['j1']}/situacion-academica")->assertUnauthorized();
});
