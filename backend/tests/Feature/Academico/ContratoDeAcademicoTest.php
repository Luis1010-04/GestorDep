<?php

use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/** Las respuestas reales de las rutas de Académico cumplen docs/api/openapi.yaml (como en H1). */
beforeEach(function () {
    sesionesPrepararBase();
    sesionesCrearUsuario('d1@x.com', ['docente']);
    sesionesCrearUsuario('a@x.com', ['entrenador']);
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
