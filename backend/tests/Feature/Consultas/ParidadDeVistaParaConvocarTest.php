<?php

use Src\Compartido\Aplicacion\PorcentajeDeAsistenciaAEntrenamientos;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/**
 * Paridad de la vista para convocar (T05) con T04 y T07: la misma lectura de los mismos datos da el mismo resultado en las
 * tres rutas. Mundo de H4 con reloj en 2026-10-01 (hoy de negocio en México); E1 entrena A.
 */

/**
 * Compara la vista de un jugador con el listado de estatus de T04 (estado oficial) y con los gráficos de T07 (marca de
 * desactualizada de las mediciones, que es la misma captura que `estado_fisico`).
 *
 * @param  array<string, string>  $m
 * @return array{vista: array<string, mixed>, estatus: array<string, mixed>, graficos: array<string, mixed>}
 */
function paridadLeer(TestCase $prueba, array $m, string $jugador): array
{
    /** @var array<string, mixed> $vista */
    $vista = convocarPedir($prueba, convocarRuta($jugador))->assertOk()->json('datos');
    /** @var list<array<string, mixed>> $filas */
    $filas = convocarPedir($prueba, "/api/v1/estatus-de-jugadores?filtro[equipo_id]={$m['a']}&por_pagina=100")->assertOk()->json('datos');
    /** @var array<string, mixed> $graficos */
    $graficos = convocarPedir($prueba, "/api/v1/jugadores/$jugador/graficos")->assertOk()->json('datos');
    $fila = array_values(array_filter($filas, fn (array $fila): bool => $fila['jugador']['id'] === $jugador))[0] ?? null;
    expect($fila)->not->toBeNull();

    return ['vista' => $vista, 'estatus' => $fila, 'graficos' => $graficos];
}

test('DA-98: con una medición de hace 31 días, la vista y los gráficos de T07 la marcan igual, y la vista y el listado de T04 dan el mismo estado oficial', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarMedir($m['f1'], $m['a'], $m['e1'], '2026-08-31');
    estadoInsertar($m['f1'], $m['e1'], 'lesionado');

    $lectura = paridadLeer($this, $m, $m['f1']);

    expect($lectura['vista']['estado_fisico']['posiblemente_desactualizado'])->toBeTrue()
        ->and($lectura['graficos']['mediciones']['peso_kg']['posiblemente_desactualizado'])->toBe($lectura['vista']['estado_fisico']['posiblemente_desactualizado'])
        ->and($lectura['graficos']['mediciones']['peso_kg']['actualizado_en'])->toBe($lectura['vista']['estado_fisico']['actualizado_en'])
        ->and($lectura['estatus']['estado_oficial']['estado'])->toBe($lectura['vista']['disponibilidad']['estado'])
        ->and($lectura['estatus']['estado_oficial']['fecha_estimada_regreso'])->toBe($lectura['vista']['disponibilidad']['fecha_estimada_regreso'])
        ->and($lectura['estatus']['estado_oficial']['actualizado_en'])->toBe($lectura['vista']['disponibilidad']['actualizado_en']);
});

test('DA-98: con una medición de hace 30 días, la vista y los gráficos de T07 no la marcan, y el estado oficial coincide', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarMedir($m['f1'], $m['a'], $m['e1'], '2026-09-01');
    estadoInsertar($m['f1'], $m['e1'], 'lesionado');

    $lectura = paridadLeer($this, $m, $m['f1']);

    expect($lectura['vista']['estado_fisico']['posiblemente_desactualizado'])->toBeFalse()
        ->and($lectura['graficos']['mediciones']['peso_kg']['posiblemente_desactualizado'])->toBe($lectura['vista']['estado_fisico']['posiblemente_desactualizado'])
        ->and($lectura['graficos']['mediciones']['peso_kg']['actualizado_en'])->toBe($lectura['vista']['estado_fisico']['actualizado_en'])
        ->and($lectura['estatus']['estado_oficial']['estado'])->toBe($lectura['vista']['disponibilidad']['estado'])
        ->and($lectura['estatus']['estado_oficial']['actualizado_en'])->toBe($lectura['vista']['disponibilidad']['actualizado_en']);
});

test('DA-98: sin evaluación ni mediciones, la vista y los gráficos de T07 no marcan nada y el estado oficial implícito coincide', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    $lectura = paridadLeer($this, $m, $m['j2']);

    expect($lectura['vista']['estado_fisico']['posiblemente_desactualizado'])->toBeFalse()
        ->and($lectura['vista']['rendimiento_reciente'])->toBe(['promedio' => null, 'resultado' => 'datos_insuficientes', 'actualizado_en' => null, 'posiblemente_desactualizado' => false])
        ->and($lectura['graficos']['mediciones']['peso_kg']['posiblemente_desactualizado'])->toBeFalse()
        ->and($lectura['graficos']['mediciones']['peso_kg']['actualizado_en'])->toBeNull()
        ->and($lectura['graficos']['rendimiento_partidos']['posiblemente_desactualizado'])->toBeFalse()
        ->and($lectura['estatus']['estado_oficial']['estado'])->toBe($lectura['vista']['disponibilidad']['estado'])
        ->and($lectura['estatus']['estado_oficial']['es_implicito'])->toBeTrue()
        ->and($lectura['vista']['disponibilidad']['actualizado_en'])->toBeNull();
});

test('DD-51: con los mismos datos, el porcentaje de asistencia de la vista coincide con el del puerto de T07, incluida la falta justificada en el denominador', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarAsistir($m, $m['a'], $m['f1'], 'presente', $m['e1'], '2026-09-10 18:00:00+00');
    convocarAsistir($m, $m['a'], $m['f1'], 'presente', $m['e1'], '2026-09-11 18:00:00+00');
    convocarAsistir($m, $m['a'], $m['f1'], 'falta', $m['e1'], '2026-09-12 18:00:00+00');
    convocarAsistir($m, $m['a'], $m['f1'], 'falta_justificada', $m['e1'], '2026-09-13 18:00:00+00');
    convocarAsistir($m, $m['a'], $m['f1'], 'presente', $m['e1'], '2026-09-14 18:00:00+00');

    $delPuerto = app(PorcentajeDeAsistenciaAEntrenamientos::class)->delJugadorEnEquipos(Identificador::desdeTexto($m['f1']), [Identificador::desdeTexto($m['a'])]);
    $vista = convocarPedir($this, convocarRuta($m['f1']))->assertOk()->json('datos.asistencia');

    // Todos los registros son de septiembre, así que el porcentaje del mes del puerto es el total que calcula la vista.
    expect(array_keys($delPuerto->porcentajes))->toBe(['2026-09'])
        ->and($delPuerto->porcentajes['2026-09']->comoCadena(2))->toBe('60.00')
        ->and($vista['equipo']['porcentaje'])->toBe($delPuerto->porcentajes['2026-09']->comoCadena(2))
        ->and($vista['de_mis_capturas']['porcentaje'])->toBe($vista['equipo']['porcentaje'])
        ->and($vista['equipo']['actualizado_en'])->toBe($delPuerto->actualizadoEn?->format(DateTimeInterface::ATOM));
});

test('DD-51: sin registros de asistencia, la vista y el puerto de T07 no dan porcentaje', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    $delPuerto = app(PorcentajeDeAsistenciaAEntrenamientos::class)->delJugadorEnEquipos(Identificador::desdeTexto($m['f1']), [Identificador::desdeTexto($m['a'])]);
    $vista = convocarPedir($this, convocarRuta($m['f1']))->assertOk()->json('datos.asistencia');

    expect($delPuerto->porcentajes)->toBe([])
        ->and($vista['equipo']['porcentaje'])->toBeNull();
});
