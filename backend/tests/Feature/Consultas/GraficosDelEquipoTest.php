<?php

use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/**
 * HU-25 c2 y c3 (docs/hitos/f1-h6.md §6.1 y §6.2, DA-97, DA-105): los gráficos de un equipo y de un jugador contra el
 * promedio de su equipo. Mundo de partida: `graficosMundo()`.
 */
test('HU-25 c2 (DA-97): el entrenador del equipo recibe la evolución del grupo: el promedio por partido de lo que él registró y el porcentaje de asistencia por mes', function () {
    /** @var TestCase $this */
    $m = graficosMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    $datos = sesionesPeticion($this, 'GET', graficosEquipoRuta($m['a']), idDeSesion: $sesion)->assertOk()->json('datos');

    expect(array_keys($datos))->toBe(['equipo'])
        ->and(array_keys($datos['equipo']))->toBe(['rendimiento_partidos', 'asistencia_entrenamientos'])
        ->and($datos['equipo']['rendimiento_partidos']['serie'])->toBe([['fecha' => '2026-10-10', 'valor' => '8.00'], ['fecha' => '2026-10-24', 'valor' => '7.00']])
        ->and($datos['equipo']['rendimiento_partidos']['actualizado_en'])->toBe('2026-11-10T10:00:00+00:00')
        ->and($datos['equipo']['rendimiento_partidos']['posiblemente_desactualizado'])->toBeFalse()
        // Octubre: F1 presente, falta y justificada, y J1 presente dos veces: 3 de 5; noviembre: F1 presente dos veces.
        ->and($datos['equipo']['asistencia_entrenamientos']['serie'])->toBe([['periodo' => '2026-10', 'valor' => '60.00'], ['periodo' => '2026-11', 'valor' => '100.00']]);
});

test('HU-25 c2 (RN-14, T07): el promedio del equipo excluye lo que registraron otros entrenadores del mismo equipo', function () {
    /** @var TestCase $this */
    $m = graficosMundo();

    $deE3 = sesionesPeticion($this, 'GET', graficosEquipoRuta($m['a']), idDeSesion: sesionesIniciar($this, 'e3@x.com'))->assertOk()->json('datos');

    // E3 solo registró a F1 en PA1 (5): su promedio de A es 5.00 y no incluye los 7, 8, 9 y 6 de E1.
    expect($deE3['equipo']['rendimiento_partidos']['serie'])->toBe([['fecha' => '2026-10-10', 'valor' => '5.00']]);
});

test('HU-25 c2: con filtro[jugador_id] llega también el jugador contra el promedio del equipo, solo con lo que registró el consultante en ese equipo', function () {
    /** @var TestCase $this */
    $m = graficosMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    $datos = sesionesPeticion($this, 'GET', graficosEquipoRuta($m['a'], "filtro[jugador_id]={$m['f1']}"), idDeSesion: $sesion)->assertOk()->json('datos');

    expect(array_keys($datos))->toBe(['equipo', 'jugador'])
        ->and(array_keys($datos['jugador']))->toBe(['jugador_id', 'rendimiento_partidos', 'asistencia_entrenamientos'])
        ->and($datos['jugador']['jugador_id'])->toBe($m['f1'])
        ->and($datos['jugador']['rendimiento_partidos']['serie'])->toBe([['fecha' => '2026-10-10', 'valor' => 7], ['fecha' => '2026-10-24', 'valor' => 8]])
        ->and($datos['jugador']['asistencia_entrenamientos']['serie'])->toBe([['periodo' => '2026-10', 'valor' => '33.33'], ['periodo' => '2026-11', 'valor' => '100.00']])
        ->and($datos['equipo']['rendimiento_partidos']['serie'])->toBe([['fecha' => '2026-10-10', 'valor' => '8.00'], ['fecha' => '2026-10-24', 'valor' => '7.00']]);
});

test('HU-25 c3 (RN-14): el jugador de dos equipos, visto desde un equipo, no mezcla lo del otro equipo', function () {
    /** @var TestCase $this */
    $m = graficosMundo();
    $sesion = sesionesIniciar($this, 'e3@x.com');

    $enB = sesionesPeticion($this, 'GET', graficosEquipoRuta($m['b'], "filtro[jugador_id]={$m['f1']}"), idDeSesion: $sesion)->assertOk()->json('datos');

    expect($enB['jugador']['rendimiento_partidos']['serie'])->toBe([['fecha' => '2026-10-17', 'valor' => 6]])
        ->and($enB['jugador']['asistencia_entrenamientos']['serie'])->toBe([['periodo' => '2026-10', 'valor' => '100.00']]);
});

test('RNF-01, HU-25: un equipo que el usuario no entrena o inexistente responde el mismo 404, y el docente, 403', function () {
    /** @var TestCase $this */
    $m = graficosMundo();
    $e2 = sesionesIniciar($this, 'e2@x.com');

    $ajeno = sesionesPeticion($this, 'GET', graficosEquipoRuta($m['a']), idDeSesion: $e2)->assertNotFound();
    $referencia = sesionesPeticion($this, 'GET', graficosEquipoRuta(Identificador::generar()->valor()), idDeSesion: $e2)->assertNotFound();

    expect($ajeno->getContent())->toBe($referencia->getContent());
    sesionesPeticion($this, 'GET', graficosEquipoRuta($m['a']), idDeSesion: sesionesIniciar($this, 'd1@x.com'))->assertForbidden();
});

test('RNF-01, RN-23: filtro[jugador_id] de un jugador que no es del equipo, que está en baja o que no existe responde 404', function () {
    /** @var TestCase $this */
    $m = graficosMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $referencia = sesionesPeticion($this, 'GET', graficosEquipoRuta($m['a'], 'filtro[jugador_id]='.Identificador::generar()->valor()), idDeSesion: $sesion)->assertNotFound();

    foreach ([$m['jb'], Identificador::generar()->valor()] as $jugador) {
        expect(sesionesPeticion($this, 'GET', graficosEquipoRuta($m['a'], "filtro[jugador_id]=$jugador"), idDeSesion: $sesion)->assertNotFound()->getContent())
            ->toBe($referencia->getContent());
    }
    // J1 solo está en A: visto desde B no es del equipo.
    sesionesPeticion($this, 'GET', graficosEquipoRuta($m['b'], "filtro[jugador_id]={$m['j1']}"), idDeSesion: sesionesIniciar($this, 'e3@x.com'))->assertNotFound();
});

test('P-05 (HU-25): un filtro[jugador_id] que no es un UUID responde 422 validacion_fallida con filtro_invalido', function () {
    /** @var TestCase $this */
    $m = graficosMundo();

    $respuesta = sesionesPeticion($this, 'GET', graficosEquipoRuta($m['a'], 'filtro[jugador_id]=123'), idDeSesion: sesionesIniciar($this, 'e1@x.com'))->assertUnprocessable();

    expect($respuesta->json('codigo'))->toBe('validacion_fallida')
        ->and($respuesta->json('errores.0.campo'))->toBe('filtro.jugador_id')
        ->and($respuesta->json('errores.0.codigo'))->toBe('filtro_invalido');
});

test('HU-25: sin sesión responde 401', function () {
    /** @var TestCase $this */
    $m = graficosMundo();

    sesionesPeticion($this, 'GET', graficosEquipoRuta($m['a']))->assertUnauthorized();
});
