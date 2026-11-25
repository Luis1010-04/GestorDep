<?php

/*
 * A6-10 (docs/hitos/f1-h7.md v0.6 §8, T06b): en las cuatro lecturas de H6 que validaban los parámetros antes de mirar el
 * alcance, un recurso ajeno responde 404 antes que el 422, como en las demás rutas (DD-50). Mundo de
 * `convocarMundo()`: E1 entrena A, E2 entrena B; P1 está solo en A y PP1 es un partido programado de A.
 * El dueño del recurso sigue recibiendo el 422: la validación no cambia, solo su orden.
 */

use Tests\TestCase;

/** @return array{0: int, 1: int} el código para E2 (ajeno) y para E1 (dueño) */
function noEncontradoCodigos(TestCase $prueba, string $ruta): array
{
    return [convocarPedir($prueba, $ruta, 'e2@x.com')->getStatusCode(), convocarPedir($prueba, $ruta, 'e1@x.com')->getStatusCode()];
}

test('RNF-01 (A6-10): con un recurso ajeno y un parámetro inválido, GET /jugadores/{jugador}/vista-para-convocar responde 404 y no 422', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    expect(noEncontradoCodigos($this, convocarRuta($m['p1'], 'filtro[equipo_id]=no-es-uuid')))->toBe([404, 422]);
});

test('RNF-01 (A6-10): con un recurso ajeno y un parámetro inválido, GET /equipos/{equipo}/jugadores/para-convocar responde 404 y no 422', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    expect(noEncontradoCodigos($this, convocarRutaDelEquipo($m['a'], 'pagina=0')))->toBe([404, 422]);
});

test('RNF-01 (A6-10): con un recurso ajeno y un parámetro inválido, GET /partidos/{partido}/convocatoria/vista responde 404 y no 422', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    expect(noEncontradoCodigos($this, "/api/v1/partidos/{$m['pp1']}/convocatoria/vista?pagina=0"))->toBe([404, 422]);
});

test('RNF-01 (A6-10): con un recurso ajeno y un parámetro inválido, GET /equipos/{equipo}/graficos responde 404 y no 422', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    expect(noEncontradoCodigos($this, "/api/v1/equipos/{$m['a']}/graficos?filtro[jugador_id]=no-es-uuid"))->toBe([404, 422]);
});
