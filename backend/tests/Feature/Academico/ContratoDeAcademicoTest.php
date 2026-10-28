<?php

use Database\Seeders\CriterioGeneralSeeder;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/** Las respuestas reales de las rutas de Académico cumplen docs/api/openapi.yaml (como en H1). */
beforeEach(function () {
    sesionesPrepararBase();
    sesionesCrearUsuario('d1@x.com', ['docente']);
    sesionesCrearUsuario('a@x.com', ['entrenador']);
});

test('HU-06: las respuestas de materias cumplen el contrato', function () {
    /** @var TestCase $this */
    (new CriterioGeneralSeeder)->run();
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    $asignatura = mundoInsertar('asignaturas', ['clave' => 'MAT-101', 'nombre' => 'Matemáticas', 'grado' => 1]);
    $periodo = mundoInsertar('periodos_escolares', ['nombre' => '2026-1', 'fecha_inicio' => '2026-01-12', 'fecha_fin' => '2026-06-30']);
    $cuerpo = ['asignatura_id' => $asignatura, 'periodo_id' => $periodo];

    $creada = sesionesPeticion($this, 'POST', '/api/v1/materias', $cuerpo, $sesion)->assertCreated();
    $id = $creada->json('datos.id');
    $respuestas = [
        ['/materias', 'post', $creada],
        ['/materias', 'post', sesionesPeticion($this, 'POST', '/api/v1/materias', $cuerpo, $sesion)->assertStatus(409)],
        ['/materias', 'post', sesionesPeticion($this, 'POST', '/api/v1/materias', ['asignatura_id' => Identificador::generar()->valor(), 'periodo_id' => $periodo], $sesion)->assertUnprocessable()],
        ['/materias', 'post', sesionesPeticion($this, 'POST', '/api/v1/materias', [], $sesion)->assertUnprocessable()],
        ['/materias', 'post', sesionesPeticion($this, 'POST', '/api/v1/materias', $cuerpo)->assertUnauthorized()],
        ['/materias', 'post', sesionesPeticion($this, 'POST', '/api/v1/materias', $cuerpo, sesionesIniciar($this, 'a@x.com'))->assertForbidden()],
        ['/materias', 'get', sesionesPeticion($this, 'GET', '/api/v1/materias', idDeSesion: $sesion)->assertOk()],
        ['/materias', 'get', sesionesPeticion($this, 'GET', '/api/v1/materias?filtro[periodo_id]=x', idDeSesion: $sesion)->assertUnprocessable()],
        ['/materias/{materia}', 'get', sesionesPeticion($this, 'GET', "/api/v1/materias/$id", idDeSesion: $sesion)->assertOk()],
        ['/materias/{materia}', 'get', sesionesPeticion($this, 'GET', '/api/v1/materias/'.Identificador::generar()->valor(), idDeSesion: $sesion)->assertNotFound()],
        ['/materias/{materia}/criterio', 'put', sesionesPeticion($this, 'PUT', "/api/v1/materias/$id/criterio", ['calificacion_minima' => '6.50'], $sesion)->assertOk()],
        ['/materias/{materia}/criterio', 'put', sesionesPeticion($this, 'PUT', "/api/v1/materias/$id/criterio", ['calificacion_minima' => '10.01'], $sesion)->assertUnprocessable()],
        ['/materias/{materia}/criterio', 'put', sesionesPeticion($this, 'PUT', "/api/v1/materias/$id/criterio", ['calificacion_minima' => 6], $sesion)->assertUnprocessable()],
        ['/materias/{materia}/criterio', 'put', sesionesPeticion($this, 'PUT', '/api/v1/materias/'.Identificador::generar()->valor().'/criterio', ['calificacion_minima' => '6.50'], $sesion)->assertNotFound()],
        ['/materias/{materia}/criterio', 'put', sesionesPeticion($this, 'PUT', "/api/v1/materias/$id/criterio", ['calificacion_minima' => '6.50'])->assertUnauthorized()],
        ['/materias/{materia}/criterio', 'put', sesionesPeticion($this, 'PUT', "/api/v1/materias/$id/criterio", ['calificacion_minima' => '6.50'], sesionesIniciar($this, 'a@x.com'))->assertForbidden()],
    ];

    foreach ($respuestas as [$ruta, $metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $ruta, $metodo))->toBeTrue("$metodo $ruta {$respuesta->getStatusCode()}");
    }
});

test('HU-06: las respuestas de inscripciones cumplen el contrato', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    $ruta = '/api/v1/materias/'.$m['m1'].'/inscripciones';
    mundoJugador('M-9', 'Zamora', enBaja: true);

    $respuestas = [
        ['post', sesionesPeticion($this, 'POST', $ruta, ['matricula' => 'M-4'], $sesion)->assertCreated()],
        ['post', sesionesPeticion($this, 'POST', $ruta, ['matricula' => 'M-4'], $sesion)->assertStatus(409)],
        ['post', sesionesPeticion($this, 'POST', $ruta, ['matricula' => 'M-9'], $sesion)->assertStatus(409)],
        ['post', sesionesPeticion($this, 'POST', $ruta, ['matricula' => 'NO-EXISTE'], $sesion)->assertNotFound()],
        ['post', sesionesPeticion($this, 'POST', '/api/v1/materias/'.$m['m2'].'/inscripciones', ['matricula' => 'M-4'], $sesion)->assertNotFound()],
        ['post', sesionesPeticion($this, 'POST', $ruta, [], $sesion)->assertUnprocessable()],
        ['post', sesionesPeticion($this, 'POST', $ruta, ['matricula' => 'M-4'])->assertUnauthorized()],
        ['post', sesionesPeticion($this, 'POST', $ruta, ['matricula' => 'M-4'], sesionesIniciar($this, 'a@x.com'))->assertForbidden()],
        ['get', sesionesPeticion($this, 'GET', $ruta, idDeSesion: $sesion)->assertOk()],
        ['get', sesionesPeticion($this, 'GET', $ruta.'?pagina=0', idDeSesion: $sesion)->assertUnprocessable()],
        ['get', sesionesPeticion($this, 'GET', '/api/v1/materias/'.$m['m2'].'/inscripciones', idDeSesion: $sesion)->assertNotFound()],
    ];

    foreach ($respuestas as [$metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, '/materias/{materia}/inscripciones', $metodo))->toBeTrue("$metodo {$respuesta->getStatusCode()}");
    }
});

test('HU-07: las respuestas de calificaciones cumplen el contrato', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    $ruta = '/api/v1/inscripciones/'.$m['i1'].'/calificaciones';
    $inexistente = Identificador::generar()->valor();

    $creada = sesionesPeticion($this, 'POST', $ruta, ['tipo' => 'parcial', 'numero_parcial' => 1, 'valor' => '8.50'], $sesion)->assertCreated();
    $final = sesionesPeticion($this, 'POST', $ruta, ['tipo' => 'final', 'valor' => '8.00'], $sesion)->assertCreated();
    $id = $creada->json('datos.id');
    $listado = [
        ['post', $creada],
        ['post', $final],
        ['post', sesionesPeticion($this, 'POST', $ruta, ['tipo' => 'final', 'valor' => '9.00'], $sesion)->assertStatus(409)],
        ['post', sesionesPeticion($this, 'POST', '/api/v1/inscripciones/'.$m['i5'].'/calificaciones', ['tipo' => 'final', 'valor' => '9.00'], $sesion)->assertStatus(409)],
        ['post', sesionesPeticion($this, 'POST', $ruta, ['tipo' => 'final', 'valor' => '10.01'], $sesion)->assertUnprocessable()],
        ['post', sesionesPeticion($this, 'POST', $ruta, ['tipo' => 'parcial', 'valor' => 8.5], $sesion)->assertUnprocessable()],
        ['post', sesionesPeticion($this, 'POST', "/api/v1/inscripciones/$inexistente/calificaciones", ['tipo' => 'final', 'valor' => '9.00'], $sesion)->assertNotFound()],
        ['post', sesionesPeticion($this, 'POST', $ruta, ['tipo' => 'final', 'valor' => '9.00'])->assertUnauthorized()],
        ['post', sesionesPeticion($this, 'POST', $ruta, ['tipo' => 'final', 'valor' => '9.00'], sesionesIniciar($this, 'a@x.com'))->assertForbidden()],
        ['get', sesionesPeticion($this, 'GET', $ruta, idDeSesion: $sesion)->assertOk()],
        ['get', sesionesPeticion($this, 'GET', $ruta.'?pagina=0', idDeSesion: $sesion)->assertUnprocessable()],
        ['get', sesionesPeticion($this, 'GET', "/api/v1/inscripciones/$inexistente/calificaciones", idDeSesion: $sesion)->assertNotFound()],
    ];
    foreach ($listado as [$metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, '/inscripciones/{inscripcion}/calificaciones', $metodo))->toBeTrue("$metodo {$respuesta->getStatusCode()}");
    }

    $correccion = [
        sesionesPeticion($this, 'PATCH', "/api/v1/calificaciones/$id", ['valor' => '9.00'], $sesion)->assertOk(),
        sesionesPeticion($this, 'PATCH', "/api/v1/calificaciones/$id", ['valor' => '10.01'], $sesion)->assertUnprocessable(),
        sesionesPeticion($this, 'PATCH', "/api/v1/calificaciones/$id", ['valor' => '9.00', 'tipo' => 'final'], $sesion)->assertUnprocessable(),
        sesionesPeticion($this, 'PATCH', "/api/v1/calificaciones/$inexistente", ['valor' => '9.00'], $sesion)->assertNotFound(),
        sesionesPeticion($this, 'PATCH', "/api/v1/calificaciones/$id", ['valor' => '9.00'])->assertUnauthorized(),
        sesionesPeticion($this, 'PATCH', "/api/v1/calificaciones/$id", ['valor' => '9.00'], sesionesIniciar($this, 'a@x.com'))->assertForbidden(),
    ];
    $previa = mundoCalificar($m['i5'], 'final', '7.00');
    $correccion[] = sesionesPeticion($this, 'PATCH', "/api/v1/calificaciones/$previa", ['valor' => '9.00'], $sesion)->assertStatus(409);
    foreach ($correccion as $respuesta) {
        expect($validador->validate($respuesta->baseResponse, '/calificaciones/{calificacion}', 'patch'))->toBeTrue("patch {$respuesta->getStatusCode()}");
    }
});

test('HU-07: las respuestas de asistencias cumplen el contrato', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    $ruta = '/api/v1/inscripciones/'.$m['i1'].'/asistencias';
    $inexistente = Identificador::generar()->valor();

    $creada = sesionesPeticion($this, 'POST', $ruta, ['fecha' => '2026-03-09', 'estado' => 'falta'], $sesion)->assertCreated();
    $id = $creada->json('datos.id');
    $listado = [
        ['post', $creada],
        ['post', sesionesPeticion($this, 'POST', $ruta, ['fecha' => '2026-03-09', 'estado' => 'presente'], $sesion)->assertStatus(409)],
        ['post', sesionesPeticion($this, 'POST', '/api/v1/inscripciones/'.$m['i5'].'/asistencias', ['estado' => 'presente'], $sesion)->assertStatus(409)],
        ['post', sesionesPeticion($this, 'POST', $ruta, ['estado' => 'tarde'], $sesion)->assertUnprocessable()],
        ['post', sesionesPeticion($this, 'POST', "/api/v1/inscripciones/$inexistente/asistencias", ['estado' => 'presente'], $sesion)->assertNotFound()],
        ['post', sesionesPeticion($this, 'POST', $ruta, ['estado' => 'presente'])->assertUnauthorized()],
        ['post', sesionesPeticion($this, 'POST', $ruta, ['estado' => 'presente'], sesionesIniciar($this, 'a@x.com'))->assertForbidden()],
        ['get', sesionesPeticion($this, 'GET', $ruta, idDeSesion: $sesion)->assertOk()],
        ['get', sesionesPeticion($this, 'GET', $ruta.'?pagina=0', idDeSesion: $sesion)->assertUnprocessable()],
        ['get', sesionesPeticion($this, 'GET', "/api/v1/inscripciones/$inexistente/asistencias", idDeSesion: $sesion)->assertNotFound()],
    ];
    foreach ($listado as [$metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, '/inscripciones/{inscripcion}/asistencias', $metodo))->toBeTrue("$metodo {$respuesta->getStatusCode()}");
    }

    $previa = mundoAsistir($m['i5'], '2026-03-02', 'falta');
    $correccion = [
        sesionesPeticion($this, 'PATCH', "/api/v1/asistencias-clase/$id", ['estado' => 'falta_justificada'], $sesion)->assertOk(),
        sesionesPeticion($this, 'PATCH', "/api/v1/asistencias-clase/$id", ['estado' => 'presente', 'fecha' => '2026-03-08'], $sesion)->assertUnprocessable(),
        sesionesPeticion($this, 'PATCH', "/api/v1/asistencias-clase/$inexistente", ['estado' => 'presente'], $sesion)->assertNotFound(),
        sesionesPeticion($this, 'PATCH', "/api/v1/asistencias-clase/$previa", ['estado' => 'presente'], $sesion)->assertStatus(409),
        sesionesPeticion($this, 'PATCH', "/api/v1/asistencias-clase/$id", ['estado' => 'presente'])->assertUnauthorized(),
        sesionesPeticion($this, 'PATCH', "/api/v1/asistencias-clase/$id", ['estado' => 'presente'], sesionesIniciar($this, 'a@x.com'))->assertForbidden(),
    ];
    foreach ($correccion as $respuesta) {
        expect($validador->validate($respuesta->baseResponse, '/asistencias-clase/{asistencia}', 'patch'))->toBeTrue("patch {$respuesta->getStatusCode()}");
    }
});

test('HU-28: las respuestas de asignaturas cumplen el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    $cuerpo = ['clave' => 'MAT-101', 'nombre' => 'Matemáticas', 'grado' => 1];

    $creada = sesionesPeticion($this, 'POST', '/api/v1/asignaturas', $cuerpo, $sesion)->assertCreated();
    $respuestas = [
        ['/asignaturas', 'post', $creada],
        ['/asignaturas', 'post', sesionesPeticion($this, 'POST', '/api/v1/asignaturas', $cuerpo, $sesion)->assertStatus(409)],
        ['/asignaturas', 'post', sesionesPeticion($this, 'POST', '/api/v1/asignaturas', [...$cuerpo, 'clave' => 'X', 'grado' => 13], $sesion)->assertUnprocessable()],
        ['/asignaturas', 'post', sesionesPeticion($this, 'POST', '/api/v1/asignaturas', $cuerpo)->assertUnauthorized()],
        ['/asignaturas', 'post', sesionesPeticion($this, 'POST', '/api/v1/asignaturas', $cuerpo, sesionesIniciar($this, 'a@x.com'))->assertForbidden()],
        ['/asignaturas', 'get', sesionesPeticion($this, 'GET', '/api/v1/asignaturas', idDeSesion: $sesion)->assertOk()],
        ['/asignaturas', 'get', sesionesPeticion($this, 'GET', '/api/v1/asignaturas?orden=x', idDeSesion: $sesion)->assertUnprocessable()],
        ['/asignaturas/{id}', 'delete', sesionesPeticion($this, 'DELETE', '/api/v1/asignaturas/'.$creada->json('datos.id'), idDeSesion: $sesion)->assertNoContent()],
        ['/asignaturas/{id}', 'delete', sesionesPeticion($this, 'DELETE', '/api/v1/asignaturas/'.Identificador::generar()->valor(), idDeSesion: $sesion)->assertNotFound()],
    ];

    foreach ($respuestas as [$ruta, $metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $ruta, $metodo))->toBeTrue("$metodo $ruta {$respuesta->getStatusCode()}");
    }
});

test('HU-28: las respuestas de periodos escolares cumplen el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    $cuerpo = ['nombre' => '2026-2', 'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-12-18'];

    $creado = sesionesPeticion($this, 'POST', '/api/v1/periodos-escolares', $cuerpo, $sesion)->assertCreated();
    $respuestas = [
        ['/periodos-escolares', 'post', $creado],
        ['/periodos-escolares', 'post', sesionesPeticion($this, 'POST', '/api/v1/periodos-escolares', $cuerpo, $sesion)->assertStatus(409)],
        ['/periodos-escolares', 'post', sesionesPeticion($this, 'POST', '/api/v1/periodos-escolares', [...$cuerpo, 'nombre' => 'X', 'fecha_fin' => '2026-08-03'], $sesion)->assertUnprocessable()],
        ['/periodos-escolares', 'post', sesionesPeticion($this, 'POST', '/api/v1/periodos-escolares', [], $sesion)->assertUnprocessable()],
        ['/periodos-escolares', 'post', sesionesPeticion($this, 'POST', '/api/v1/periodos-escolares', $cuerpo)->assertUnauthorized()],
        ['/periodos-escolares', 'post', sesionesPeticion($this, 'POST', '/api/v1/periodos-escolares', $cuerpo, sesionesIniciar($this, 'a@x.com'))->assertForbidden()],
        ['/periodos-escolares', 'get', sesionesPeticion($this, 'GET', '/api/v1/periodos-escolares', idDeSesion: $sesion)->assertOk()],
        ['/periodos-escolares', 'get', sesionesPeticion($this, 'GET', '/api/v1/periodos-escolares?orden=x', idDeSesion: $sesion)->assertUnprocessable()],
        ['/periodos-escolares/{id}', 'delete', sesionesPeticion($this, 'DELETE', '/api/v1/periodos-escolares/'.$creado->json('datos.id'), idDeSesion: $sesion)->assertNoContent()],
        ['/periodos-escolares/{id}', 'delete', sesionesPeticion($this, 'DELETE', '/api/v1/periodos-escolares/'.Identificador::generar()->valor(), idDeSesion: $sesion)->assertNotFound()],
    ];

    foreach ($respuestas as [$ruta, $metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $ruta, $metodo))->toBeTrue("$metodo $ruta {$respuesta->getStatusCode()}");
    }
});
