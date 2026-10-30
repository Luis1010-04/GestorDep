<?php

use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/** Las respuestas reales de las rutas de asistencia a entrenamientos cumplen docs/api/openapi.yaml. */
beforeEach(function () {
    sesionesPrepararBase();
});

test('HU-11: las respuestas de asistencia a entrenamientos cumplen el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = mundoDeCompetencia();
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $ajena = sesionesIniciar($this, 'e2@x.com');
    $docente = sesionesIniciar($this, 'd@x.com');
    $realizado = mundoEntrenamiento($m['a'], $m['l1'], $m['e1'], '2026-09-10 23:00:00+00', '2026-09-10 23:59:00+00', ['estado' => 'realizado']);
    $programado = mundoEntrenamiento($m['a'], $m['l1'], $m['e1'], '2026-09-11 23:00:00+00', '2026-09-11 23:59:00+00');
    $lista = "/api/v1/entrenamientos/$realizado/asistencias";
    $cuerpo = ['registros' => [['jugador_id' => $m['j1'], 'estado' => 'presente'], ['jugador_id' => $m['j2'], 'estado' => 'falta']]];

    $creada = sesionesPeticion($this, 'POST', $lista, $cuerpo, $sesion)->assertCreated();
    $correccion = '/api/v1/asistencias-entrenamiento/'.$creada->json('datos.1.id');
    $respuestas = [
        ['/entrenamientos/{entrenamiento}/asistencias', 'post', $creada],
        ['/entrenamientos/{entrenamiento}/asistencias', 'post', sesionesPeticion($this, 'POST', $lista, $cuerpo, $sesion)->assertStatus(409)],
        ['/entrenamientos/{entrenamiento}/asistencias', 'post', sesionesPeticion($this, 'POST', "/api/v1/entrenamientos/$programado/asistencias", $cuerpo, $sesion)->assertStatus(409)],
        ['/entrenamientos/{entrenamiento}/asistencias', 'post', sesionesPeticion($this, 'POST', $lista, ['registros' => [['jugador_id' => $m['j5'], 'estado' => 'presente']]], $sesion)->assertUnprocessable()],
        ['/entrenamientos/{entrenamiento}/asistencias', 'post', sesionesPeticion($this, 'POST', $lista, ['registros' => []], $sesion)->assertUnprocessable()],
        ['/entrenamientos/{entrenamiento}/asistencias', 'post', sesionesPeticion($this, 'POST', $lista, $cuerpo, $ajena)->assertNotFound()],
        ['/entrenamientos/{entrenamiento}/asistencias', 'post', sesionesPeticion($this, 'POST', $lista, $cuerpo)->assertUnauthorized()],
        ['/entrenamientos/{entrenamiento}/asistencias', 'post', sesionesPeticion($this, 'POST', $lista, $cuerpo, $docente)->assertForbidden()],
        ['/entrenamientos/{entrenamiento}/asistencias', 'get', sesionesPeticion($this, 'GET', $lista, idDeSesion: $sesion)->assertOk()],
        ['/entrenamientos/{entrenamiento}/asistencias', 'get', sesionesPeticion($this, 'GET', $lista.'?pagina=0', idDeSesion: $sesion)->assertUnprocessable()],
        ['/entrenamientos/{entrenamiento}/asistencias', 'get', sesionesPeticion($this, 'GET', $lista, idDeSesion: $ajena)->assertNotFound()],
        ['/entrenamientos/{entrenamiento}/asistencias', 'get', sesionesPeticion($this, 'GET', $lista)->assertUnauthorized()],
        ['/entrenamientos/{entrenamiento}/asistencias', 'get', sesionesPeticion($this, 'GET', $lista, idDeSesion: $docente)->assertForbidden()],
        ['/asistencias-entrenamiento/{asistencia}', 'patch', sesionesPeticion($this, 'PATCH', $correccion, ['estado' => 'presente'], $sesion)->assertOk()],
        ['/asistencias-entrenamiento/{asistencia}', 'patch', sesionesPeticion($this, 'PATCH', $correccion, ['estado' => 'x'], $sesion)->assertUnprocessable()],
        ['/asistencias-entrenamiento/{asistencia}', 'patch', sesionesPeticion($this, 'PATCH', $correccion, ['estado' => 'presente'], $ajena)->assertNotFound()],
        ['/asistencias-entrenamiento/{asistencia}', 'patch', sesionesPeticion($this, 'PATCH', $correccion, ['estado' => 'presente'])->assertUnauthorized()],
        ['/asistencias-entrenamiento/{asistencia}', 'patch', sesionesPeticion($this, 'PATCH', $correccion, ['estado' => 'presente'], $docente)->assertForbidden()],
        ['/equipos/{equipo}/asistencia-entrenamientos', 'get', sesionesPeticion($this, 'GET', "/api/v1/equipos/{$m['a']}/asistencia-entrenamientos", idDeSesion: $sesion)->assertOk()],
        ['/equipos/{equipo}/asistencia-entrenamientos', 'get', sesionesPeticion($this, 'GET', "/api/v1/equipos/{$m['b']}/asistencia-entrenamientos", idDeSesion: $ajena)->assertOk()],
        ['/equipos/{equipo}/asistencia-entrenamientos', 'get', sesionesPeticion($this, 'GET', "/api/v1/equipos/{$m['a']}/asistencia-entrenamientos", idDeSesion: $ajena)->assertNotFound()],
        ['/equipos/{equipo}/asistencia-entrenamientos', 'get', sesionesPeticion($this, 'GET', "/api/v1/equipos/{$m['a']}/asistencia-entrenamientos")->assertUnauthorized()],
        ['/equipos/{equipo}/asistencia-entrenamientos', 'get', sesionesPeticion($this, 'GET', "/api/v1/equipos/{$m['a']}/asistencia-entrenamientos", idDeSesion: $docente)->assertForbidden()],
        ['/jugadores/{jugador}/asistencia-entrenamientos', 'get', sesionesPeticion($this, 'GET', "/api/v1/jugadores/{$m['j1']}/asistencia-entrenamientos", idDeSesion: $sesion)->assertOk()],
        ['/jugadores/{jugador}/asistencia-entrenamientos', 'get', sesionesPeticion($this, 'GET', "/api/v1/jugadores/{$m['j3']}/asistencia-entrenamientos", idDeSesion: $ajena)->assertOk()],
        ['/jugadores/{jugador}/asistencia-entrenamientos', 'get', sesionesPeticion($this, 'GET', '/api/v1/jugadores/'.Identificador::generar()->valor().'/asistencia-entrenamientos', idDeSesion: $sesion)->assertNotFound()],
        ['/jugadores/{jugador}/asistencia-entrenamientos', 'get', sesionesPeticion($this, 'GET', "/api/v1/jugadores/{$m['j1']}/asistencia-entrenamientos")->assertUnauthorized()],
    ];

    foreach ($respuestas as [$plantilla, $metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $plantilla, $metodo))->toBeTrue("$metodo $plantilla {$respuesta->getStatusCode()}");
    }
});
