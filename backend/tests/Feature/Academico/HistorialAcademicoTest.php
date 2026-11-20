<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/*
 * F1-H7-T02: historial de calificaciones y de asistencia a clase (HU-26, RN-17; docs/hitos/f1-h7.md §5.3, §6, §7 y
 * DA-124). Mundo de docs/hitos/f1-h2.md §7.0 (MundoDeDocentes): F de §7.1 es J1 (M1 de D1, equipo EA de A).
 */

const RUTAS_DE_HISTORIAL_ACADEMICO = ['calificaciones', 'asistencias-clase'];

/** @return TestResponse<Response> */
function historialAcademicoPedir(TestCase $prueba, string $jugadorId, string $recurso, ?string $correo, string $consulta = ''): TestResponse
{
    return sesionesPeticion(
        $prueba,
        'GET',
        "/api/v1/jugadores/$jugadorId/historial/$recurso$consulta",
        [],
        $correo === null ? null : sesionesIniciar($prueba, $correo),
    );
}

function historialAcademicoReloj(string $instante): void
{
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable($instante, new DateTimeZone('UTC'))));
}

test('HU-26 c1: el historial de una calificación muestra autor (con nombre), fecha, valor anterior y nuevo', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    $id = (string) sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i1']}/calificaciones", ['tipo' => 'final', 'valor' => '8.00'], $sesion)
        ->assertCreated()->json('datos.id');
    historialAcademicoReloj('2026-03-10 12:05:00');
    sesionesPeticion($this, 'PATCH', "/api/v1/calificaciones/$id", ['valor' => '9.00'], $sesion)->assertOk();

    $datos = historialAcademicoPedir($this, $m['j1'], 'calificaciones', 'd1@x.com')->assertOk()->json('datos');

    expect($datos)->toHaveCount(2)
        ->and($datos[0])->toMatchArray([
            'recurso' => 'calificaciones', 'entidad_id' => $id, 'version' => 2, 'operacion' => 'actualizar',
            'autor' => ['id' => $m['d1'], 'nombre' => 'D1'], 'ocurrido_en' => '2026-03-10T12:05:00+00:00', 'motivo' => null,
        ])
        ->and($datos[0]['datos_anteriores']['valor'])->toBe('8.00')
        ->and($datos[0]['datos_nuevos']['valor'])->toBe('9.00')
        ->and($datos[1])->toMatchArray([
            'entidad_id' => $id, 'version' => 1, 'operacion' => 'crear', 'datos_anteriores' => null,
            'autor' => ['id' => $m['d1'], 'nombre' => 'D1'], 'ocurrido_en' => '2026-03-10T12:00:00+00:00',
        ])
        ->and($datos[1]['datos_nuevos']['valor'])->toBe('8.00');
});

test('HU-26 c1: el historial de una asistencia a clase muestra autor (con nombre), fecha, valor anterior y nuevo', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    $id = (string) sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i1']}/asistencias", ['fecha' => '2026-03-09', 'estado' => 'presente'], $sesion)
        ->assertCreated()->json('datos.id');
    historialAcademicoReloj('2026-03-10 12:05:00');
    sesionesPeticion($this, 'PATCH', "/api/v1/asistencias-clase/$id", ['estado' => 'falta'], $sesion)->assertOk();

    $datos = historialAcademicoPedir($this, $m['j1'], 'asistencias-clase', 'd1@x.com')->assertOk()->json('datos');

    expect($datos)->toHaveCount(2)
        ->and($datos[0])->toMatchArray([
            'recurso' => 'asistencias-clase', 'entidad_id' => $id, 'version' => 2, 'operacion' => 'actualizar',
            'autor' => ['id' => $m['d1'], 'nombre' => 'D1'], 'ocurrido_en' => '2026-03-10T12:05:00+00:00',
        ])
        ->and($datos[0]['datos_anteriores']['estado'])->toBe('presente')
        ->and($datos[0]['datos_nuevos']['estado'])->toBe('falta')
        ->and($datos[1])->toMatchArray(['version' => 1, 'operacion' => 'crear', 'datos_anteriores' => null]);
});

test('HU-26 c3 (§7.1 fila 1): D1 ve el historial de F solo de sus materias', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    // J3 está inscrito en M1 (D1) y en M2 (D2): cada docente captura en su inscripción.
    $deD1 = (string) sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i3a']}/calificaciones", ['tipo' => 'final', 'valor' => '8.00'], sesionesIniciar($this, 'd1@x.com'))
        ->assertCreated()->json('datos.id');
    sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i3b']}/calificaciones", ['tipo' => 'final', 'valor' => '6.00'], sesionesIniciar($this, 'd2@x.com'))
        ->assertCreated();

    $datos = historialAcademicoPedir($this, $m['j3'], 'calificaciones', 'd1@x.com')->assertOk()->json('datos');

    expect(array_column($datos, 'entidad_id'))->toBe([$deD1]);
});

test('HU-26 c3 (§7.1 fila 2, DA-124): un docente sin ninguna materia con F recibe 404 en las dos rutas, igual que la lectura de su inscripción', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    // Control: la ruta existe y D1 sí ve a F.
    historialAcademicoPedir($this, $m['j1'], 'calificaciones', 'd1@x.com')->assertOk();

    $lectura = sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i1']}/calificaciones", [], sesionesIniciar($this, 'd2@x.com'))->assertNotFound();
    foreach (RUTAS_DE_HISTORIAL_ACADEMICO as $recurso) {
        $historial = historialAcademicoPedir($this, $m['j1'], $recurso, 'd2@x.com')
            ->assertNotFound()
            ->assertJsonPath('codigo', 'recurso_no_encontrado');
        expect((string) $historial->getContent())->toBe((string) $lectura->getContent());
    }
});

test('HU-26 c3 (§7.1 fila 3): E1 recibe lo mismo que en la ruta de lectura de calificaciones (403)', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i1']}/calificaciones", [], sesionesIniciar($this, 'a@x.com'))->assertForbidden();
    foreach (RUTAS_DE_HISTORIAL_ACADEMICO as $recurso) {
        historialAcademicoPedir($this, $m['j1'], $recurso, 'a@x.com')->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    }
});

test('HU-26 c3 (§7.1 fila 11, DA-124): E3, entrenador sin rol docente y sin F, recibe 403 sin_permiso en las dos rutas, igual que la lectura de calificaciones y de asistencia', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    sesionesCrearUsuario('e3@x.com', ['entrenador']);

    sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i1']}/calificaciones", [], sesionesIniciar($this, 'e3@x.com'))->assertForbidden();
    sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i1']}/asistencias", [], sesionesIniciar($this, 'e3@x.com'))->assertForbidden();
    foreach (RUTAS_DE_HISTORIAL_ACADEMICO as $recurso) {
        historialAcademicoPedir($this, $m['j1'], $recurso, 'e3@x.com')->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    }
});

test('HU-26 c3 (DA-124): un usuario con los dos roles que entrena a F pero no tiene ninguna materia con F recibe 404 en las dos rutas', function () {
    /** @var TestCase $this */
    $m = mundoAgregarEquipoYMateriaDeDocenteEntrenador(mundoDeDocentes());
    // Control: la ruta existe y DE sí ve a J1, inscrito en su materia M3.
    historialAcademicoPedir($this, $m['j1'], 'calificaciones', 'de@x.com')->assertOk();

    // DE entrena a J4 (equipo ED) y no tiene ninguna materia con J4.
    foreach (RUTAS_DE_HISTORIAL_ACADEMICO as $recurso) {
        historialAcademicoPedir($this, $m['j4'], $recurso, 'de@x.com')->assertNotFound();
    }
});

test('HU-26 c3 (§7.1 fila 12): sin sesión, las dos rutas responden 401', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    foreach (RUTAS_DE_HISTORIAL_ACADEMICO as $recurso) {
        historialAcademicoPedir($this, $m['j1'], $recurso, null)->assertUnauthorized()->assertJsonPath('codigo', 'no_autenticado');
    }
});

test('HU-26 c2: una calificación con borrado lógico no aparece en la lectura pero sí en el historial', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    // D-5: ningún caso de uso borra una calificación; la prueba crea la fila eliminada y su entrada `eliminar`.
    $id = mundoCalificar($m['i1'], 'final', '7.00');
    DB::connection('propietario')->table('calificaciones')->where('id', $id)->update(['eliminado_en' => '2026-03-10 11:00:00+00', 'eliminado_por' => $m['d1']]);
    DB::connection('propietario')->table('calificaciones_historial')->insert([
        'id' => Identificador::generar()->valor(), 'calificacion_id' => $id, 'version' => 1, 'operacion' => 'eliminar',
        'datos_anteriores' => json_encode(['valor' => '7.00']), 'datos_nuevos' => null,
        'usuario_id' => $m['d1'], 'ocurrido_en' => '2026-03-10 11:00:00+00', 'motivo' => null,
    ]);
    $sesion = sesionesIniciar($this, 'd1@x.com');

    $lectura = sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i1']}/calificaciones", [], $sesion)->assertOk()->json('datos');
    $historial = historialAcademicoPedir($this, $m['j1'], 'calificaciones', 'd1@x.com')->assertOk()->json('datos');

    expect(array_column($lectura, 'id'))->not->toContain($id)
        ->and($historial)->toHaveCount(1)
        ->and($historial[0])->toMatchArray(['entidad_id' => $id, 'operacion' => 'eliminar', 'datos_nuevos' => null, 'datos_anteriores' => ['valor' => '7.00']]);
});

test('HU-26: el historial académico cumple el contrato de OpenAPI (200, 401, 404 y 422)', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i1']}/calificaciones", ['tipo' => 'final', 'valor' => '8.00'], $sesion)->assertCreated();
    sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i1']}/asistencias", ['fecha' => '2026-03-09', 'estado' => 'presente'], $sesion)->assertCreated();

    foreach (RUTAS_DE_HISTORIAL_ACADEMICO as $recurso) {
        $ruta = "/jugadores/{jugador}/historial/$recurso";
        $respuestas = [
            historialAcademicoPedir($this, $m['j1'], $recurso, 'd1@x.com')->assertOk(),
            historialAcademicoPedir($this, $m['j1'], $recurso, null)->assertUnauthorized(),
            historialAcademicoPedir($this, $m['j1'], $recurso, 'd2@x.com')->assertNotFound(),
            historialAcademicoPedir($this, $m['j1'], $recurso, 'd1@x.com', '?por_pagina=0')->assertUnprocessable(),
        ];
        foreach ($respuestas as $respuesta) {
            expect($validador->validate($respuesta->baseResponse, $ruta, 'get'))->toBeTrue("GET $ruta {$respuesta->getStatusCode()}");
        }
    }
});

test('RN-17: el historial de un jugador en baja sigue visible para el docente con materia', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    $id = (string) sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i1']}/calificaciones", ['tipo' => 'final', 'valor' => '8.00'], $sesion)
        ->assertCreated()->json('datos.id');
    // J1 pasa a baja después de la captura; su inscripción en M1 se conserva (DD-52).
    DB::connection('propietario')->table('jugadores')->where('id', $m['j1'])->update(['estatus_academico' => 'baja', 'estatus_deportivo' => 'baja']);

    foreach (RUTAS_DE_HISTORIAL_ACADEMICO as $recurso) {
        historialAcademicoPedir($this, $m['j1'], $recurso, 'd1@x.com')->assertOk();
    }
    $datos = historialAcademicoPedir($this, $m['j1'], 'calificaciones', 'd1@x.com')->json('datos');

    expect($datos)->toHaveCount(1)
        ->and($datos[0])->toMatchArray(['entidad_id' => $id, 'operacion' => 'crear', 'autor' => ['id' => $m['d1'], 'nombre' => 'D1']]);
});

test('HU-26 c1: un cambio hecho sin sesión entrega `autor` nulo', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    // DD-35: sin sesión (por ejemplo, desde la consola) el historial se escribe con `usuario_id` nulo.
    $id = mundoCalificar($m['i1'], 'final', '7.00');
    app(EscritorDeHistorial::class)->registrar('calificaciones', 'calificacion_id', Identificador::desdeTexto($id), 'crear', null, ['valor' => '7.00'], fn () => null);
    expect(DB::table('calificaciones_historial')->where('calificacion_id', $id)->value('usuario_id'))->toBeNull();

    $datos = historialAcademicoPedir($this, $m['j1'], 'calificaciones', 'd1@x.com')->assertOk()->json('datos');

    expect($datos)->toHaveCount(1)
        ->and($datos[0])->toHaveKey('autor')
        ->and($datos[0]['autor'])->toBeNull()
        ->and($datos[0])->toMatchArray(['entidad_id' => $id, 'operacion' => 'crear', 'ocurrido_en' => '2026-03-10T12:00:00+00:00']);
});
