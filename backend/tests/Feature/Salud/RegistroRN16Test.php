<?php

use Tests\Ayudantes\NoNulo;
use Tests\TestCase;

/**
 * Tabla de RN-16 (docs/hitos/f1-h5.md §7.3), filas 3 a 5 en secuencia, tras una confirmación hecha por la API. Las filas
 * 1 y 2, y las mismas filas 3 a 5 con un estado insertado, están en ConfirmacionYEstadoTest (ver f1-h5-t08.md §2).
 */
test('§7.3 filas 3 a 5 (RN-16): tras la confirmación de E2, E1 no modifica el estado, no ve la lesión y ve el estado sin el detalle', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['d1']);
    $e2 = sesionesIniciar($this, 'e2@x.com');
    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), $e2)->assertOk();
    $lesion = NoNulo::exigir(sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: $e2)->json('datos.lesion_id'), 'la lesión');
    $e1 = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['fecha_estimada_regreso' => '2026-12-10'], $e1)
        ->assertForbidden()
        ->assertJsonPath('codigo', 'no_es_registrante');
    sesionesPeticion($this, 'GET', lesionRuta($lesion), idDeSesion: $e1)->assertNotFound();
    $estado = sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: $e1)
        ->assertOk()
        ->assertJsonPath('datos.estado', 'lesionado')
        ->assertJsonPath('datos.fecha_estimada_regreso', '2026-11-30')
        ->assertJsonPath('datos.lesion_id', null);

    expect(array_keys($estado->json('datos')))->not->toContain('tipo')
        ->and(array_keys($estado->json('datos')))->not->toContain('zona')
        ->and(array_keys($estado->json('datos')))->not->toContain('causa');
});
