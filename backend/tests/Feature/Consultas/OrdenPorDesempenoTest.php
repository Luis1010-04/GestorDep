<?php

use Tests\TestCase;

/**
 * Orden por desempeño en `GET /equipos/{equipo}/jugadores/para-convocar` (HU-24, RN-18, DD-12, DA-95, DA-118): con los
 * cinco jugadores de A del mundo de H4. En orden de entrada (apellidos, nombres, id) son D1 «Defensa», F1 «Delantero», J2
 * «Dos», M1 «Medio» y P1 «Portero». El orden usa el promedio de las últimas N calificaciones de partido (DA-106), el que
 * guarda la evaluación más reciente de E1 en A.
 */

/**
 * @param  array<string, string>  $m
 * @return list<string> los identificadores de jugador de la lista, en orden
 */
function ordenIds(TestCase $prueba, array $m, string $consulta = '', string $correo = 'e1@x.com'): array
{
    /** @var list<array{jugador: array{id: string}}> $filas */
    $filas = convocarPedir($prueba, convocarRutaDelEquipo($m['a'], $consulta), $correo)->assertOk()->json('datos');

    return array_map(fn (array $fila): string => $fila['jugador']['id'], $filas);
}

test('RN-18 (§7.5 fila 1, por la ruta): P1 8.0, D1 6.5 y M1 7.2 se ordenan P1, M1, D1, y los sin evaluación al final', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarEvaluar($m['p1'], $m['a'], $m['e1'], 'pasa', '8.00');
    convocarEvaluar($m['d1'], $m['a'], $m['e1'], 'pasa', '6.50');
    convocarEvaluar($m['m1'], $m['a'], $m['e1'], 'pasa', '7.20');

    expect(ordenIds($this, $m))->toBe([$m['p1'], $m['m1'], $m['d1'], $m['f1'], $m['j2']]);
});

test('RN-18 (§7.5 fila 2, por la ruta): P1 7.0, D1 datos insuficientes y M1 5.0 se ordenan P1, M1, D1', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarEvaluar($m['p1'], $m['a'], $m['e1'], 'pasa', '7.00');
    convocarEvaluar($m['d1'], $m['a'], $m['e1'], 'datos_insuficientes', null);
    convocarEvaluar($m['m1'], $m['a'], $m['e1'], 'no_pasa', '5.00');

    expect(ordenIds($this, $m))->toBe([$m['p1'], $m['m1'], $m['d1'], $m['f1'], $m['j2']]);
});

test('RN-18 (§7.5 fila 3, por la ruta): P1 7.0 y D1 7.0 conservan el orden de entrada (apellidos), D1 y luego P1', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarEvaluar($m['p1'], $m['a'], $m['e1'], 'pasa', '7.00');
    convocarEvaluar($m['d1'], $m['a'], $m['e1'], 'pasa', '7.00');

    expect(ordenIds($this, $m))->toBe([$m['d1'], $m['p1'], $m['f1'], $m['j2'], $m['m1']]);
});

test('RN-18 (DA-106): el orden usa el promedio de las últimas N calificaciones de partido, el de la evaluación más reciente, no el de las anteriores', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarEvaluar($m['p1'], $m['a'], $m['e1'], 'pasa', '9.00', '2026-09-01 12:00:00+00');
    convocarEvaluar($m['p1'], $m['a'], $m['e1'], 'no_pasa', '4.00', '2026-09-20 12:00:00+00');
    convocarEvaluar($m['d1'], $m['a'], $m['e1'], 'pasa', '6.00');

    expect(ordenIds($this, $m))->toBe([$m['d1'], $m['p1'], $m['f1'], $m['j2'], $m['m1']]);
});

test('RN-14 (DA-95): el orden usa solo las evaluaciones del entrenador que consulta, no las de otro entrenador del equipo', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarEvaluar($m['p1'], $m['a'], $m['e3'], 'pasa', '9.50');
    convocarEvaluar($m['d1'], $m['a'], $m['e1'], 'pasa', '6.00');

    expect(ordenIds($this, $m))->toBe([$m['d1'], $m['f1'], $m['j2'], $m['m1'], $m['p1']]);
});

test('DA-95: el orden se calcula sobre todos los jugadores y después se pagina', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarEvaluar($m['p1'], $m['a'], $m['e1'], 'pasa', '8.00');
    convocarEvaluar($m['m1'], $m['a'], $m['e1'], 'pasa', '7.20');
    convocarEvaluar($m['d1'], $m['a'], $m['e1'], 'pasa', '6.50');

    $segunda = convocarPedir($this, convocarRutaDelEquipo($m['a'], 'por_pagina=2&pagina=2'))->assertOk();

    /** @var list<array{jugador: array{id: string}}> $filas */
    $filas = $segunda->json('datos');
    expect(array_map(fn (array $fila): string => $fila['jugador']['id'], $filas))->toBe([$m['d1'], $m['f1']])
        ->and($segunda->json('meta.total'))->toBe(5)
        ->and($segunda->json('meta.pagina'))->toBe(2);
});

test('RNF-01 (RN-23): la lista del equipo no incluye jugadores en baja', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    $baja = mundoJugador('M-BAJA', 'Zapata', [$m['a'] => 'medio'], enBaja: true);

    expect(ordenIds($this, $m))->not->toContain($baja)
        ->and(convocarPedir($this, convocarRutaDelEquipo($m['a']))->json('meta.total'))->toBe(5);
});

test('RNF-01: un entrenador que no entrena el equipo, un equipo inexistente y un id que no es v7 responden el mismo 404', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    $ajeno = convocarPedir($this, convocarRutaDelEquipo($m['b']))->assertNotFound();
    $inexistente = convocarPedir($this, convocarRutaDelEquipo('0198a2f4-0000-4000-8000-000000000000'))->assertNotFound();
    $noV7 = convocarPedir($this, convocarRutaDelEquipo('00000000-0000-0000-0000-000000000000'))->assertNotFound();

    expect($inexistente->getContent())->toBe($ajeno->getContent())
        ->and($noV7->getContent())->toBe($ajeno->getContent());
});

test('HU-24: el docente recibe 403 y sin sesión 401 en la lista del equipo', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    convocarPedir($this, convocarRutaDelEquipo($m['a']), 'd@x.com')->assertForbidden();
    sesionesPeticion($this, 'GET', convocarRutaDelEquipo($m['a']))->assertUnauthorized();
});

test('RNF-01 (DA-119): un parámetro de paginación inválido responde 422', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    convocarPedir($this, convocarRutaDelEquipo($m['a'], 'por_pagina=500'))->assertUnprocessable();
});
