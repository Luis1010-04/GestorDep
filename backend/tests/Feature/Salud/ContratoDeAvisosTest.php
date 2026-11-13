<?php

use Illuminate\Support\Facades\DB;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/** Las respuestas reales de las rutas de avisos cumplen docs/api/openapi.yaml. */
test('HU-19 y HU-30: las respuestas de avisos cumplen el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = avisosMundo();
    $entrenador = sesionesIniciar($this, 'e1@x.com');
    $ajeno = sesionesIniciar($this, 'e2@x.com');
    $docente = sesionesIniciar($this, 'd1@x.com');
    $lista = avisosRuta($m['f1']);
    $listaDeBaja = avisosRuta($m['jb']);
    $listaAjena = avisosRuta($m['j1']);
    $cuerpo = ['nota' => 'Molestia', 'fecha' => '2026-11-14'];
    $descarte = ['nota_descarte' => 'No aplica'];

    $creado = sesionesPeticion($this, 'POST', $lista, $cuerpo, $entrenador)->assertCreated();
    $aviso = avisoDescarteRuta($creado->json('datos.id'));
    $inexistente = avisoDescarteRuta(Identificador::generar()->valor());
    $plantillaLista = '/jugadores/{jugador}/avisos-lesion';
    $plantillaDescarte = '/avisos-lesion/{aviso}/descarte';

    $respuestas = [
        [$plantillaLista, 'post', $creado],
        [$plantillaLista, 'post', sesionesPeticion($this, 'POST', $lista, [...$cuerpo, 'reportado_por_jugador' => true], $docente)->assertCreated()],
        [$plantillaLista, 'post', sesionesPeticion($this, 'POST', $lista, [], $entrenador)->assertUnprocessable()],
        [$plantillaLista, 'post', sesionesPeticion($this, 'POST', $listaDeBaja, $cuerpo, $entrenador)->assertConflict()],
        [$plantillaLista, 'post', sesionesPeticion($this, 'POST', $listaAjena, $cuerpo, $ajeno)->assertNotFound()],
        [$plantillaLista, 'post', sesionesPeticion($this, 'POST', $lista, $cuerpo)->assertUnauthorized()],
        [$plantillaLista, 'get', sesionesPeticion($this, 'GET', $lista, idDeSesion: $entrenador)->assertOk()],
        [$plantillaLista, 'get', sesionesPeticion($this, 'GET', $lista, idDeSesion: $docente)->assertOk()],
        [$plantillaLista, 'get', sesionesPeticion($this, 'GET', $lista.'?pagina=0', idDeSesion: $entrenador)->assertUnprocessable()],
        [$plantillaLista, 'get', sesionesPeticion($this, 'GET', $listaAjena, idDeSesion: $ajeno)->assertNotFound()],
        [$plantillaLista, 'get', sesionesPeticion($this, 'GET', $lista)->assertUnauthorized()],
        [$plantillaDescarte, 'post', sesionesPeticion($this, 'POST', $aviso, [], $entrenador)->assertUnprocessable()],
        [$plantillaDescarte, 'post', sesionesPeticion($this, 'POST', $aviso, $descarte)->assertUnauthorized()],
        [$plantillaDescarte, 'post', sesionesPeticion($this, 'POST', $aviso, $descarte, $docente)->assertForbidden()],
        [$plantillaDescarte, 'post', sesionesPeticion($this, 'POST', $inexistente, $descarte, $entrenador)->assertNotFound()],
        [$plantillaDescarte, 'post', sesionesPeticion($this, 'POST', $aviso, $descarte, $entrenador)->assertOk()],
        [$plantillaDescarte, 'post', sesionesPeticion($this, 'POST', $aviso, $descarte, $entrenador)->assertConflict()],
    ];

    foreach ($respuestas as [$plantilla, $metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $plantilla, $metodo))->toBeTrue("$metodo $plantilla {$respuesta->getStatusCode()}");
    }
});

test('DA-79: el 409 conflicto del descarte cumple el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e1']);
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $propietario = DB::connection('propietario');
    // Disparador solo de esta prueba: simula el choque con el índice único (aviso_id, version) de dos escrituras a la vez.
    $propietario->statement("create function public.prueba_t09_choque_descarte() returns trigger language plpgsql as \$\$ begin raise exception 'choque simulado' using errcode = 'unique_violation'; end \$\$");
    $propietario->statement("create trigger prueba_t09_choque_descarte before insert on avisos_lesion_historial for each row when (new.operacion = 'actualizar') execute function public.prueba_t09_choque_descarte()");
    try {
        $respuesta = sesionesPeticion($this, 'POST', avisoDescarteRuta($aviso), ['nota_descarte' => 'No aplica'], $sesion);
    } finally {
        $propietario->statement('drop trigger prueba_t09_choque_descarte on avisos_lesion_historial');
        $propietario->statement('drop function public.prueba_t09_choque_descarte()');
    }

    $respuesta->assertConflict()->assertJsonPath('codigo', 'conflicto');
    expect($validador->validate($respuesta->baseResponse, '/avisos-lesion/{aviso}/descarte', 'post'))->toBeTrue();
});
