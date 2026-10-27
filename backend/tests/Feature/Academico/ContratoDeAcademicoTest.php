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
    ];

    foreach ($respuestas as [$ruta, $metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $ruta, $metodo))->toBeTrue("$metodo $ruta {$respuesta->getStatusCode()}");
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
