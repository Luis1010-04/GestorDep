<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/**
 * Recorrido de seguridad de las rutas nuevas de H2 (RNF-01, DD-49, DD-50, HU-01): ajeno, rol equivocado, sin sesión, ids
 * inexistentes y UUID válidos que no son v7, con los 404 comparados byte a byte. Datos de partida de docs/hitos/f1-h2.md §7.0.
 */
const UUID_V4_ACADEMICO = '123e4567-e89b-42d3-a456-426614174000';

/**
 * Los recursos de D1 sobre los que se ataca: su materia, su inscripción I1, una calificación y una asistencia de I1.
 *
 * @return array<string, string>
 */
function seguridadAcademicaMundo(): array
{
    $m = mundoDeDocentes();

    return [
        ...$m,
        'calificacion' => mundoCalificar($m['i1'], 'final', '8.00'),
        'asistencia' => mundoAsistir($m['i1'], '2026-03-09', 'presente'),
    ];
}

/**
 * Cada ruta de H2 con un recurso en la ruta: método, plantilla con `{v}` en el identificador, el recurso de D1 que se
 * ataca y los cuerpos con que se intenta (válidos e inválidos).
 *
 * @param  array<string, string>  $m
 * @return array<string, array{string, string, string, list<array<string, mixed>>}>
 */
function seguridadRutasConRecurso(array $m): array
{
    return [
        'GET /materias/{materia}' => ['GET', '/api/v1/materias/{v}', $m['m1'], [[]]],
        'PUT /materias/{materia}/criterio' => ['PUT', '/api/v1/materias/{v}/criterio', $m['m1'], [['calificacion_minima' => '6.00'], ['calificacion_minima' => '10.01'], ['calificacion_minima' => 6], []]],
        'GET /materias/{materia}/inscripciones' => ['GET', '/api/v1/materias/{v}/inscripciones', $m['m1'], [[]]],
        'POST /materias/{materia}/inscripciones' => ['POST', '/api/v1/materias/{v}/inscripciones', $m['m1'], [['matricula' => 'M-4'], ['matricula' => 'M-5'], ['matricula' => 'NO-EXISTE'], []]],
        'GET /inscripciones/{inscripcion}/calificaciones' => ['GET', '/api/v1/inscripciones/{v}/calificaciones', $m['i1'], [[]]],
        'POST /inscripciones/{inscripcion}/calificaciones' => ['POST', '/api/v1/inscripciones/{v}/calificaciones', $m['i1'], [['tipo' => 'final', 'valor' => '9.00'], ['tipo' => 'final', 'valor' => '10.01'], ['tipo' => 'final', 'valor' => 9], []]],
        'PATCH /calificaciones/{calificacion}' => ['PATCH', '/api/v1/calificaciones/{v}', $m['calificacion'], [['valor' => '9.00'], ['valor' => '10.01'], ['tipo' => 'final'], []]],
        'GET /inscripciones/{inscripcion}/asistencias' => ['GET', '/api/v1/inscripciones/{v}/asistencias', $m['i1'], [[]]],
        'POST /inscripciones/{inscripcion}/asistencias' => ['POST', '/api/v1/inscripciones/{v}/asistencias', $m['i1'], [['estado' => 'falta'], ['fecha' => '2026-03-09', 'estado' => 'falta'], ['estado' => 'tarde'], []]],
        'PATCH /asistencias-clase/{asistencia}' => ['PATCH', '/api/v1/asistencias-clase/{v}', $m['asistencia'], [['estado' => 'falta'], ['estado' => 'tarde'], ['fecha' => '2026-03-01'], []]],
    ];
}

test('RNF-01: D2 sobre cada recurso de D1, con cualquier cuerpo, recibe un 404 byte a byte idéntico al de un id inexistente o no v7', function () {
    /** @var TestCase $this */
    $m = seguridadAcademicaMundo();
    $sesion = sesionesIniciar($this, 'd2@x.com');
    $referencia = sesionesPeticion($this, 'GET', '/api/v1/materias/'.Identificador::generar()->valor(), idDeSesion: $sesion)->assertNotFound();
    expect($referencia->json())->toBe(['codigo' => 'recurso_no_encontrado', 'mensaje' => 'El recurso solicitado no existe.', 'errores' => []]);
    $antes = DB::table('calificaciones')->count() + DB::table('asistencias_clase')->count() + DB::table('inscripciones')->count() + DB::table('criterios_aprobacion')->count();

    foreach (seguridadRutasConRecurso($m) as $nombre => [$metodo, $plantilla, $recurso, $cuerpos]) {
        foreach ($cuerpos as $cuerpo) {
            foreach ([$recurso, Identificador::generar()->valor(), UUID_V4_ACADEMICO] as $id) {
                $respuesta = sesionesPeticion($this, $metodo, str_replace('{v}', $id, $plantilla), $cuerpo, $sesion);
                expect($respuesta->getStatusCode())->toBe(404, "$nombre con $id")
                    ->and($respuesta->getContent())->toBe($referencia->getContent(), "$nombre con $id");
            }
        }
    }

    $despues = DB::table('calificaciones')->count() + DB::table('asistencias_clase')->count() + DB::table('inscripciones')->count() + DB::table('criterios_aprobacion')->count();
    expect($despues)->toBe($antes);
});

/** Filas que una petición atacante no debe crear. */
function seguridadFilasAcademicas(): int
{
    return DB::table('calificaciones')->count() + DB::table('asistencias_clase')->count() + DB::table('inscripciones')->count() + DB::table('criterios_aprobacion')->count();
}

/**
 * Ataca con `$sesion` una ruta de `seguridadRutasConRecurso($m)`, con cada uno de sus cuerpos, y exige el 404 byte a byte
 * idéntico al de una materia inexistente, sin filas nuevas.
 *
 * @param  array<string, string>  $m
 */
function seguridadAtacarConCadaCuerpo(TestCase $prueba, string $sesion, array $m, string $ruta): void
{
    $referencia = sesionesPeticion($prueba, 'GET', '/api/v1/materias/'.Identificador::generar()->valor(), idDeSesion: $sesion)->assertNotFound();
    $antes = seguridadFilasAcademicas();
    [$metodo, $plantilla, $recurso, $cuerpos] = seguridadRutasConRecurso($m)[$ruta];

    foreach ($cuerpos as $cuerpo) {
        $respuesta = sesionesPeticion($prueba, $metodo, str_replace('{v}', $recurso, $plantilla), $cuerpo, $sesion);
        expect($respuesta->getStatusCode())->toBe(404, "$ruta con ".json_encode($cuerpo))
            ->and($respuesta->getContent())->toBe($referencia->getContent());
    }
    expect(seguridadFilasAcademicas())->toBe($antes);
}

dataset('rutas de una inscripción de H2', [
    'GET /inscripciones/{inscripcion}/calificaciones',
    'POST /inscripciones/{inscripcion}/calificaciones',
    'PATCH /calificaciones/{calificacion}',
    'GET /inscripciones/{inscripcion}/asistencias',
    'POST /inscripciones/{inscripcion}/asistencias',
    'PATCH /asistencias-clase/{asistencia}',
]);

test('RNF-01 (§0.5.4): D2, docente de J3 en M2, recibe un 404 byte a byte idéntico sobre I3a (J3 en M1, de D1)', function (string $ruta) {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $deI3a = [
        ...$m,
        'i1' => $m['i3a'],
        'calificacion' => mundoCalificar($m['i3a'], 'final', '8.00'),
        'asistencia' => mundoAsistir($m['i3a'], '2026-03-09', 'presente'),
    ];
    $sesion = sesionesIniciar($this, 'd2@x.com');
    // J3 está en el alcance de D2 (por I3b): solo la pertenencia de la materia separa a D2 de I3a.
    sesionesPeticion($this, 'GET', "/api/v1/jugadores/{$m['j3']}", idDeSesion: $sesion)->assertOk();

    seguridadAtacarConCadaCuerpo($this, $sesion, $deI3a, $ruta);

    expect(DB::table('calificaciones')->where('id', $deI3a['calificacion'])->value('valor'))->toBe('8.00')
        ->and(DB::table('asistencias_clase')->where('id', $deI3a['asistencia'])->value('estado'))->toBe('presente');
})->with('rutas de una inscripción de H2');

test('RNF-01 (§0.5.4): DE, docente de J1 en M3, recibe un 404 byte a byte idéntico sobre I1 (J1 en M1, de D1)', function (string $ruta) {
    /** @var TestCase $this */
    $m = mundoAgregarEquipoYMateriaDeDocenteEntrenador(mundoDeDocentes());
    $deI1 = [
        ...$m,
        'calificacion' => mundoCalificar($m['i1'], 'final', '8.00'),
        'asistencia' => mundoAsistir($m['i1'], '2026-03-09', 'presente'),
    ];
    $sesion = sesionesIniciar($this, 'de@x.com');
    // DE es docente de J1 (por M3) y ve su propia inscripción: solo la pertenencia de la materia lo separa de I1.
    sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i1m3']}/calificaciones", idDeSesion: $sesion)->assertOk();

    seguridadAtacarConCadaCuerpo($this, $sesion, $deI1, $ruta);

    expect(DB::table('calificaciones')->where('id', $deI1['calificacion'])->value('valor'))->toBe('8.00')
        ->and(DB::table('asistencias_clase')->where('id', $deI1['asistencia'])->value('estado'))->toBe('presente');
})->with('rutas de una inscripción de H2');

test('RNF-01 (§0.5.4): DELETE de un catálogo con un id inexistente o que no es v7 recibe un 404 byte a byte idéntico y no borra nada', function (string $catalogo) {
    /** @var TestCase $this */
    $m = seguridadAcademicaMundo();
    $sesion = sesionesIniciar($this, 'd2@x.com');
    $referencia = sesionesPeticion($this, 'GET', '/api/v1/materias/'.Identificador::generar()->valor(), idDeSesion: $sesion)->assertNotFound();
    $tabla = str_replace('-', '_', $catalogo);
    $antes = DB::table($tabla)->whereNull('eliminado_en')->count();

    foreach ([Identificador::generar()->valor(), UUID_V4_ACADEMICO] as $id) {
        $respuesta = sesionesPeticion($this, 'DELETE', "/api/v1/$catalogo/$id", idDeSesion: $sesion);
        expect($respuesta->getStatusCode())->toBe(404, "DELETE /$catalogo/$id")
            ->and($respuesta->getContent())->toBe($referencia->getContent());
    }
    expect(DB::table($tabla)->whereNull('eliminado_en')->count())->toBe($antes);
})->with(['periodos-escolares', 'asignaturas']);

test('RNF-01: la situación académica de un jugador ajeno es un 404 byte a byte idéntico para cada perfil', function () {
    /** @var TestCase $this */
    $m = seguridadAcademicaMundo();

    foreach (['d1@x.com' => [$m['j2'], $m['j4']], 'd2@x.com' => [$m['j1'], $m['j4']], 'a@x.com' => [$m['j2'], $m['j3'], $m['j4']], 'de@x.com' => [$m['j1'], $m['j4']]] as $correo => $ajenos) {
        $sesion = sesionesIniciar($this, $correo);
        $referencia = sesionesPeticion($this, 'GET', '/api/v1/jugadores/'.Identificador::generar()->valor().'/situacion-academica', idDeSesion: $sesion)->assertNotFound();
        foreach ([...$ajenos, UUID_V4_ACADEMICO] as $jugador) {
            $respuesta = sesionesPeticion($this, 'GET', "/api/v1/jugadores/$jugador/situacion-academica", idDeSesion: $sesion);
            expect($respuesta->getStatusCode())->toBe(404, "$correo sobre $jugador")
                ->and($respuesta->getContent())->toBe($referencia->getContent());
        }
    }
});

test('HU-01: un entrenador recibe 403 sin_permiso en cada ruta docente de H2, también con ids ajenos o inexistentes', function () {
    /** @var TestCase $this */
    $m = seguridadAcademicaMundo();
    $sesion = sesionesIniciar($this, 'a@x.com');
    $referencia = sesionesPeticion($this, 'GET', '/api/v1/materias', idDeSesion: $sesion)->assertForbidden();
    expect($referencia->json('codigo'))->toBe('sin_permiso');

    $sinRecurso = [
        ['GET', '/api/v1/periodos-escolares', []], ['POST', '/api/v1/periodos-escolares', ['nombre' => 'X', 'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-12-18']],
        ['GET', '/api/v1/asignaturas', []], ['POST', '/api/v1/asignaturas', ['clave' => 'X', 'nombre' => 'X', 'grado' => 1]],
        ['POST', '/api/v1/materias', ['asignatura_id' => $m['s1'], 'periodo_id' => $m['p0']]],
        ['DELETE', '/api/v1/periodos-escolares/'.$m['p0'], []], ['DELETE', '/api/v1/asignaturas/'.$m['s2'], []],
    ];
    foreach ($sinRecurso as [$metodo, $ruta, $cuerpo]) {
        expect(sesionesPeticion($this, $metodo, $ruta, $cuerpo, $sesion)->getContent())->toBe($referencia->getContent(), "$metodo $ruta");
    }
    foreach (seguridadRutasConRecurso($m) as $nombre => [$metodo, $plantilla, $recurso, $cuerpos]) {
        foreach ([$recurso, Identificador::generar()->valor()] as $id) {
            $respuesta = sesionesPeticion($this, $metodo, str_replace('{v}', $id, $plantilla), $cuerpos[0], $sesion);
            expect($respuesta->getStatusCode())->toBe(403, $nombre)->and($respuesta->getContent())->toBe($referencia->getContent());
        }
    }
    expect(DB::table('periodos_escolares')->whereNull('eliminado_en')->count())->toBe(3)
        ->and(DB::table('calificaciones')->where('id', $m['calificacion'])->value('valor'))->toBe('8.00');
});

test('HU-01: sin sesión, cada ruta nueva de H2 responde 401', function () {
    /** @var TestCase $this */
    $m = seguridadAcademicaMundo();
    $rutas = [
        ['GET', '/api/v1/periodos-escolares'], ['POST', '/api/v1/periodos-escolares'], ['DELETE', '/api/v1/periodos-escolares/'.$m['p0']],
        ['GET', '/api/v1/asignaturas'], ['POST', '/api/v1/asignaturas'], ['DELETE', '/api/v1/asignaturas/'.$m['s1']],
        ['GET', '/api/v1/materias'], ['POST', '/api/v1/materias'], ['GET', "/api/v1/jugadores/{$m['j1']}/situacion-academica"],
    ];
    foreach (seguridadRutasConRecurso($m) as [$metodo, $plantilla, $recurso]) {
        $rutas[] = [$metodo, str_replace('{v}', $recurso, $plantilla)];
    }

    foreach ($rutas as [$metodo, $ruta]) {
        sesionesPeticion($this, $metodo, $ruta)->assertUnauthorized()->assertJsonPath('codigo', 'no_autenticado');
    }
});

test('RNF-01: un docente no ve en la situación académica nada de otro docente, aunque compartan alumno', function () {
    /** @var TestCase $this */
    $m = seguridadAcademicaMundo();
    // Dos parciales de D2 cuya media (7.50) es lo único que el resumen común deja ver.
    mundoCalificar($m['i3b'], 'parcial', '5.55', 1);
    mundoCalificar($m['i3b'], 'parcial', '9.45', 2);
    consolaArtisan('alertas:recalcular')->assertSuccessful();

    $respuesta = sesionesPeticion($this, 'GET', "/api/v1/jugadores/{$m['j3']}/situacion-academica", idDeSesion: sesionesIniciar($this, 'd1@x.com'))->assertOk();
    $cuerpo = (string) $respuesta->getContent();

    expect($respuesta->json('datos.promedio'))->toBe('7.50')
        ->and($cuerpo)->not->toContain('5.55')->and($cuerpo)->not->toContain('9.45')->and($cuerpo)->not->toContain($m['m2'])->and($cuerpo)->not->toContain($m['i3b'])->and($cuerpo)->not->toContain('FIS-101');
});
