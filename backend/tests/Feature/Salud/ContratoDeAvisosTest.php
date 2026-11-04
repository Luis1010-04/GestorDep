<?php

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
