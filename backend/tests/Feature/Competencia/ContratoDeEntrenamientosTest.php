<?php

use Osteel\OpenApi\Testing\ValidatorBuilder;
use Tests\TestCase;

/** Las respuestas reales de las rutas de entrenamientos cumplen docs/api/openapi.yaml. */
beforeEach(function () {
    sesionesPrepararBase();
});

test('HU-10: las respuestas de entrenamientos cumplen el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = mundoDeCompetencia();
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $ajena = sesionesIniciar($this, 'e2@x.com');
    $docente = sesionesIniciar($this, 'd@x.com');
    $coleccion = "/api/v1/equipos/{$m['a']}/entrenamientos";
    $cuerpo = ['inicia_en' => '2026-09-10T17:00:00-06:00', 'termina_en' => '2026-09-10T18:00:00-06:00', 'lugar_id' => $m['l1'], 'responsable_id' => $m['e1']];

    $creado = sesionesPeticion($this, 'POST', $coleccion, $cuerpo, $sesion)->assertCreated();
    $id = $creado->json('datos.id');
    $detalle = "/api/v1/entrenamientos/$id";
    $respuestas = [
        ['/equipos/{equipo}/entrenamientos', 'post', $creado],
        ['/equipos/{equipo}/entrenamientos', 'post', sesionesPeticion($this, 'POST', $coleccion, $cuerpo, $sesion)->assertCreated()->assertJsonPath('advertencias.0.codigo', 'empalme_de_lugar')],
        ['/equipos/{equipo}/entrenamientos', 'post', sesionesPeticion($this, 'POST', $coleccion, [...$cuerpo, 'responsable_id' => $m['e2']], $sesion)->assertUnprocessable()],
        ['/equipos/{equipo}/entrenamientos', 'post', sesionesPeticion($this, 'POST', $coleccion, [], $sesion)->assertUnprocessable()],
        ['/equipos/{equipo}/entrenamientos', 'post', sesionesPeticion($this, 'POST', $coleccion, $cuerpo, $ajena)->assertNotFound()],
        ['/equipos/{equipo}/entrenamientos', 'post', sesionesPeticion($this, 'POST', $coleccion, $cuerpo)->assertUnauthorized()],
        ['/equipos/{equipo}/entrenamientos', 'post', sesionesPeticion($this, 'POST', $coleccion, $cuerpo, $docente)->assertForbidden()],
        ['/equipos/{equipo}/entrenamientos', 'get', sesionesPeticion($this, 'GET', $coleccion.'?filtro[desde]=2026-09-10', idDeSesion: $sesion)->assertOk()],
        ['/equipos/{equipo}/entrenamientos', 'get', sesionesPeticion($this, 'GET', $coleccion.'?filtro[desde]=x', idDeSesion: $sesion)->assertUnprocessable()],
        ['/equipos/{equipo}/entrenamientos', 'get', sesionesPeticion($this, 'GET', $coleccion, idDeSesion: $ajena)->assertNotFound()],
        ['/equipos/{equipo}/entrenamientos', 'get', sesionesPeticion($this, 'GET', $coleccion)->assertUnauthorized()],
        ['/equipos/{equipo}/entrenamientos', 'get', sesionesPeticion($this, 'GET', $coleccion, idDeSesion: $docente)->assertForbidden()],
        ['/entrenamientos/{entrenamiento}', 'get', sesionesPeticion($this, 'GET', $detalle, idDeSesion: $sesion)->assertOk()],
        ['/entrenamientos/{entrenamiento}', 'get', sesionesPeticion($this, 'GET', $detalle, idDeSesion: $ajena)->assertNotFound()],
        ['/entrenamientos/{entrenamiento}', 'get', sesionesPeticion($this, 'GET', $detalle)->assertUnauthorized()],
        ['/entrenamientos/{entrenamiento}', 'get', sesionesPeticion($this, 'GET', $detalle, idDeSesion: $docente)->assertForbidden()],
        ['/entrenamientos/{entrenamiento}', 'patch', sesionesPeticion($this, 'PATCH', $detalle, ['termina_en' => '2026-09-11T00:30:00Z'], $sesion)->assertOk()],
        ['/entrenamientos/{entrenamiento}', 'patch', sesionesPeticion($this, 'PATCH', $detalle, [], $sesion)->assertUnprocessable()],
        ['/entrenamientos/{entrenamiento}', 'patch', sesionesPeticion($this, 'PATCH', $detalle, ['estado' => 'cancelado'], $sesion)->assertOk()],
        ['/entrenamientos/{entrenamiento}', 'patch', sesionesPeticion($this, 'PATCH', $detalle, ['estado' => 'realizado'], $sesion)->assertStatus(409)],
        ['/entrenamientos/{entrenamiento}', 'patch', sesionesPeticion($this, 'PATCH', $detalle, ['lugar_id' => $m['l1']], $sesion)->assertStatus(409)],
        ['/entrenamientos/{entrenamiento}', 'patch', sesionesPeticion($this, 'PATCH', $detalle, ['estado' => 'cancelado'], $ajena)->assertNotFound()],
        ['/entrenamientos/{entrenamiento}', 'patch', sesionesPeticion($this, 'PATCH', $detalle, ['estado' => 'cancelado'])->assertUnauthorized()],
        ['/entrenamientos/{entrenamiento}', 'patch', sesionesPeticion($this, 'PATCH', $detalle, ['estado' => 'cancelado'], $docente)->assertForbidden()],
    ];

    foreach ($respuestas as [$plantilla, $metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $plantilla, $metodo))->toBeTrue("$metodo $plantilla {$respuesta->getStatusCode()}");
    }
});
