<?php

use Database\Seeders\TiposPruebaSeeder;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/**
 * Vista para convocar de un jugador (HU-24, DD-15, DA-94, DA-98, DA-111, DA-119; docs/hitos/f1-h6.md §6.2 y §7.7). Mundo de
 * H4 con reloj en 2026-10-01 (hoy de negocio en México): E1 entrena A, E2 entrena B, E3 entrena A y B; D es docente.
 */
test('HU-24 (§7.7): una última medición de hace 31 días marca estado_fisico como posiblemente desactualizado', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarMedir($m['f1'], $m['a'], $m['e1'], '2026-08-31');

    $estado = convocarPedir($this, convocarRuta($m['f1']))->assertOk()->json('datos.estado_fisico');

    expect($estado['posiblemente_desactualizado'])->toBeTrue()
        ->and($estado['actualizado_en'])->toBe('2026-08-31T18:00:00+00:00');
});

test('HU-24 (§7.7): una última medición de hace 30 días no la marca', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarMedir($m['f1'], $m['a'], $m['e1'], '2026-09-01');

    expect(convocarPedir($this, convocarRuta($m['f1']))->assertOk()->json('datos.estado_fisico.posiblemente_desactualizado'))->toBeFalse();
});

test('RNF-09 (DA-98): los días de desactualización salen de la tabla de parámetros; cambiarlos cambia la marca', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarMedir($m['f1'], $m['a'], $m['e1'], '2026-09-20');
    expect(convocarPedir($this, convocarRuta($m['f1']))->json('datos.estado_fisico.posiblemente_desactualizado'))->toBeFalse();

    DB::connection('propietario')->table('parametros_institucionales')->where('clave', 'desactualizacion')->update(['valor' => json_encode(['dias' => 10])]);

    expect(convocarPedir($this, convocarRuta($m['f1']))->json('datos.estado_fisico.posiblemente_desactualizado'))->toBeTrue();
});

test('HU-24 (§7.7): sin evaluación de rendimiento, rendimiento_reciente trae promedio nulo y resultado datos_insuficientes', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    $respuesta = convocarPedir($this, convocarRuta($m['j2']))->assertOk();

    expect($respuesta->json('datos.rendimiento_reciente'))->toBe(['promedio' => null, 'resultado' => 'datos_insuficientes', 'actualizado_en' => null, 'posiblemente_desactualizado' => false])
        ->and($respuesta->json('datos.progreso'))->toBe(['primero' => null, 'ultimo' => null, 'actualizado_en' => null, 'posiblemente_desactualizado' => false])
        ->and($respuesta->json('datos.estado_fisico'))->toBe(['ultima_medicion' => null, 'pruebas' => [], 'actualizado_en' => null, 'posiblemente_desactualizado' => false]);
});

test('DA-106 (plan §4): el promedio de rendimiento_reciente es el de las últimas N calificaciones capturadas por la API de H4', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarCapturar($this, $m['a'], $m['f1'], 'delantero', [3, 6, 7, 8, 9, 7]);
    $ultimas = DB::table('rendimientos_partido as rendimiento')->join('partidos as partido', 'partido.id', '=', 'rendimiento.partido_id')
        ->where('rendimiento.jugador_id', $m['f1'])->where('rendimiento.registrado_por', $m['e1'])
        ->orderByDesc('partido.inicia_en')->limit(5)->pluck('rendimiento.calificacion_general')->all();
    $esperado = number_format(array_sum($ultimas) / count($ultimas), 2, '.', '');

    $vista = convocarPedir($this, convocarRuta($m['f1']))->assertOk()->json('datos');

    expect($ultimas)->toHaveCount(5)
        ->and($esperado)->toBe('7.40')
        ->and($vista['rendimiento_reciente']['promedio'])->toBe($esperado)
        ->and($vista['rendimiento_reciente']['resultado'])->toBe('pasa')
        ->and($vista['progreso']['primero']['valor'])->toBe(3)
        ->and($vista['progreso']['ultimo']['valor'])->toBe(7);
});

test('RN-14 (§7.7, DD-17): con dos entrenadores en dos equipos, cada uno ve solo sus evaluaciones, mediciones y asistencia', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarEvaluar($m['f1'], $m['a'], $m['e1'], 'no_pasa', '5.00');
    convocarEvaluar($m['f1'], $m['a'], $m['e3'], 'pasa', '8.00', '2026-09-10 12:00:00+00');
    convocarEvaluar($m['f1'], $m['b'], $m['e3'], 'pasa', '9.00', '2026-09-25 12:00:00+00');
    convocarMedir($m['f1'], $m['a'], $m['e1'], '2026-09-20', '70.00');
    convocarMedir($m['f1'], $m['a'], $m['e3'], '2026-09-21', '80.00');
    convocarAsistir($m, $m['a'], $m['f1'], 'presente', $m['e1']);
    convocarAsistir($m, $m['b'], $m['f1'], 'falta', $m['e2'], '2026-09-11 18:00:00+00');

    $deE1 = convocarPedir($this, convocarRuta($m['f1']), 'e1@x.com')->assertOk()->json('datos');
    $deE3 = convocarPedir($this, convocarRuta($m['f1']), 'e3@x.com')->assertOk()->json('datos');

    expect($deE1['rendimiento_reciente']['promedio'])->toBe('5.00')
        ->and($deE1['rendimiento_reciente']['resultado'])->toBe('no_pasa')
        ->and($deE1['estado_fisico']['ultima_medicion']['peso_kg'])->toBe('70.00')
        ->and($deE1['asistencia']['equipo']['porcentaje'])->toBe('100.00')
        ->and($deE1['asistencia']['de_mis_capturas']['porcentaje'])->toBe('100.00')
        ->and($deE3['rendimiento_reciente']['promedio'])->toBe('9.00')
        ->and($deE3['estado_fisico']['ultima_medicion']['peso_kg'])->toBe('80.00')
        ->and($deE3['asistencia']['equipo']['porcentaje'])->toBe('50.00')
        ->and($deE3['asistencia']['de_mis_capturas'])->toBe(['porcentaje' => null, 'actualizado_en' => null, 'posiblemente_desactualizado' => false]);
});

test('DA-119: filtro[equipo_id] fija el equipo de rendimiento_reciente; sin él se usa el de la evaluación más reciente del consultante', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarEvaluar($m['f1'], $m['a'], $m['e3'], 'pasa', '8.00', '2026-09-10 12:00:00+00');
    convocarEvaluar($m['f1'], $m['b'], $m['e3'], 'pasa', '9.00', '2026-09-25 12:00:00+00');

    $porOmision = convocarPedir($this, convocarRuta($m['f1']), 'e3@x.com')->assertOk()->json('datos.rendimiento_reciente.promedio');
    $enA = convocarPedir($this, convocarRuta($m['f1'], "filtro[equipo_id]={$m['a']}"), 'e3@x.com')->assertOk()->json('datos.rendimiento_reciente.promedio');
    $enB = convocarPedir($this, convocarRuta($m['f1'], "filtro[equipo_id]={$m['b']}"), 'e3@x.com')->assertOk()->json('datos.rendimiento_reciente.promedio');

    expect($porOmision)->toBe('9.00')->and($enA)->toBe('8.00')->and($enB)->toBe('9.00');
});

test('DA-119 (RNF-01): un equipo que el consultante no entrena, o donde el jugador no está, responde el mismo 404', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    $ajeno = convocarPedir($this, convocarRuta($m['f1'], "filtro[equipo_id]={$m['b']}"), 'e1@x.com')->assertNotFound();
    $sinMembresia = convocarPedir($this, convocarRuta($m['p1'], "filtro[equipo_id]={$m['b']}"), 'e3@x.com')->assertNotFound();
    $inexistente = convocarPedir($this, convocarRuta($m['f1'], 'filtro[equipo_id]='.Identificador::generar()->valor()), 'e1@x.com')->assertNotFound();

    expect($sinMembresia->getContent())->toBe($ajeno->getContent())
        ->and($inexistente->getContent())->toBe($ajeno->getContent());
});

test('DA-119: un filtro[equipo_id] que no es UUID responde 422 con filtro_invalido', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    $respuesta = convocarPedir($this, convocarRuta($m['f1'], 'filtro[equipo_id]=abc'))->assertUnprocessable();

    expect($respuesta->json('codigo'))->toBe('validacion_fallida')
        ->and($respuesta->json('errores.0.codigo'))->toBe('filtro_invalido');
});

test('RNF-01 (RN-23): un jugador sin alcance, en baja o inexistente responde el mismo 404, byte a byte', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    $baja = mundoJugador('M-BAJA', 'Zapata', [$m['a'] => 'medio'], enBaja: true);

    $sinAlcance = convocarPedir($this, convocarRuta($m['p1']), 'e2@x.com')->assertNotFound();
    $enBaja = convocarPedir($this, convocarRuta($baja), 'e1@x.com')->assertNotFound();
    $inexistente = convocarPedir($this, convocarRuta(Identificador::generar()->valor()), 'e1@x.com')->assertNotFound();
    $noV7 = convocarPedir($this, convocarRuta('00000000-0000-0000-0000-000000000000'), 'e1@x.com')->assertNotFound();

    expect($enBaja->getContent())->toBe($sinAlcance->getContent())
        ->and($inexistente->getContent())->toBe($sinAlcance->getContent())
        ->and($noV7->getContent())->toBe($sinAlcance->getContent());
});

test('HU-24: el docente recibe 403 y sin sesión 401', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    convocarPedir($this, convocarRuta($m['f1']), 'd@x.com')->assertForbidden();
    sesionesPeticion($this, 'GET', convocarRuta($m['f1']))->assertUnauthorized();
});

test('HU-24 (DA-94): disponibilidad trae el estado oficial, la fecha de regreso y el aviso pendiente, sin el detalle de la lesión', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    estadoInsertar($m['f1'], $m['e1'], 'lesionado');
    avisoInsertar($m['f1'], $m['e3']);

    $vista = convocarPedir($this, convocarRuta($m['f1']))->assertOk()->json('datos');

    expect($vista['disponibilidad'])->toBe([
        'estado' => 'lesionado', 'fecha_estimada_regreso' => '2026-11-30', 'aviso_pendiente' => true,
        'actualizado_en' => '2026-11-10T10:00:00+00:00', 'posiblemente_desactualizado' => false,
    ]);
});

test('HU-24 (DA-94): sin estado guardado, disponibilidad es apto, sin fecha ni aviso y sin marca', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    expect(convocarPedir($this, convocarRuta($m['p1']))->json('datos.disponibilidad'))->toBe([
        'estado' => 'apto', 'fecha_estimada_regreso' => null, 'aviso_pendiente' => false, 'actualizado_en' => null, 'posiblemente_desactualizado' => false,
    ]);
});

test('HU-24 (DA-94, RN-13): situacion_academica trae el resumen simplificado del periodo vigente, sin materias', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    $periodo = mundoInsertar('periodos_escolares', ['nombre' => '2026-2', 'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-12-18']);
    mundoInsertar('resumenes_academicos', [
        'jugador_id' => $m['f1'], 'periodo_id' => $periodo, 'promedio' => '6.50', 'materias_reprobadas' => 1, 'materias_sin_calificar' => 0,
        'porcentaje_asistencia' => '90.00', 'nivel_riesgo' => 'en_riesgo', 'calculado_en' => '2026-09-30 12:00:00+00',
    ]);

    $situacion = convocarPedir($this, convocarRuta($m['f1']))->assertOk()->json('datos.situacion_academica');

    expect($situacion)->toBe([
        'periodo' => ['id' => $periodo, 'nombre' => '2026-2'], 'promedio' => '6.50', 'materias_reprobadas' => 1, 'materias_sin_calificar' => 0,
        'dato_incompleto' => false, 'porcentaje_asistencia' => '90.00', 'nivel_riesgo' => 'en_riesgo', 'calculado_en' => '2026-09-30T12:00:00+00:00',
        'actualizado_en' => '2026-09-30T12:00:00+00:00', 'posiblemente_desactualizado' => false,
    ]);
});

test('HU-24 (DA-94): sin periodo vigente, situacion_academica trae periodo nulo y el dato incompleto', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    expect(convocarPedir($this, convocarRuta($m['p1']))->json('datos.situacion_academica'))->toBe([
        'periodo' => null, 'promedio' => null, 'materias_reprobadas' => 0, 'materias_sin_calificar' => 0, 'dato_incompleto' => true,
        'porcentaje_asistencia' => null, 'nivel_riesgo' => null, 'calculado_en' => null, 'actualizado_en' => null, 'posiblemente_desactualizado' => false,
    ]);
});

test('HU-24 (DA-94): con periodo vigente pero sin resumen calculado, el dato queda incompleto', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    $periodo = mundoInsertar('periodos_escolares', ['nombre' => '2026-2', 'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-12-18']);

    $situacion = convocarPedir($this, convocarRuta($m['p1']))->json('datos.situacion_academica');

    expect($situacion['periodo'])->toBe(['id' => $periodo, 'nombre' => '2026-2'])
        ->and($situacion['dato_incompleto'])->toBeTrue()
        ->and($situacion['calculado_en'])->toBeNull();
});

test('HU-24 (DA-94): estado_fisico trae la última medición y la última prueba de cada tipo que registró el consultante', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    (new TiposPruebaSeeder)->run();
    $velocidad = (string) DB::table('tipos_prueba')->where('codigo', 'velocidad')->value('id');
    convocarMedir($m['f1'], $m['a'], $m['e1'], '2026-09-20', '71.00');
    pruebaInsertar($m['f1'], $m['a'], $m['e1'], $velocidad, '2026-08-10', ['resultado' => '5.10', 'creado_en' => '2026-08-10 18:00:00+00', 'actualizado_en' => '2026-08-10 18:00:00+00']);
    $reciente = pruebaInsertar($m['f1'], $m['a'], $m['e1'], $velocidad, '2026-09-25', ['resultado' => '4.90', 'creado_en' => '2026-09-25 18:00:00+00', 'actualizado_en' => '2026-09-25 18:00:00+00']);
    pruebaInsertar($m['f1'], $m['a'], $m['e3'], $velocidad, '2026-09-29', ['resultado' => '3.00']);

    $estado = convocarPedir($this, convocarRuta($m['f1']))->assertOk()->json('datos.estado_fisico');

    expect($estado['ultima_medicion']['peso_kg'])->toBe('71.00')
        ->and($estado['pruebas'])->toHaveCount(1)
        ->and($estado['pruebas'][0]['id'])->toBe($reciente)
        ->and($estado['pruebas'][0]['resultado'])->toBe('4.90')
        ->and($estado['actualizado_en'])->toBe('2026-09-25T18:00:00+00:00');
});

test('DA-111: asistencia.equipo cuenta la asistencia de los equipos del consultante; de_mis_capturas, solo lo que capturó él', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarAsistir($m, $m['a'], $m['f1'], 'presente', $m['e1'], '2026-09-10 18:00:00+00');
    convocarAsistir($m, $m['a'], $m['f1'], 'falta', $m['e3'], '2026-09-11 18:00:00+00');
    convocarAsistir($m, $m['a'], $m['f1'], 'falta_justificada', $m['e3'], '2026-09-12 18:00:00+00');
    convocarAsistir($m, $m['a'], $m['f1'], 'presente', $m['e3'], '2026-09-13 18:00:00+00');

    $asistencia = convocarPedir($this, convocarRuta($m['f1']), 'e1@x.com')->assertOk()->json('datos.asistencia');

    expect($asistencia['equipo']['porcentaje'])->toBe('50.00')
        ->and($asistencia['equipo']['actualizado_en'])->toBe('2026-09-13T18:00:00+00:00')
        ->and($asistencia['de_mis_capturas']['porcentaje'])->toBe('100.00')
        ->and($asistencia['de_mis_capturas']['actualizado_en'])->toBe('2026-09-10T18:00:00+00:00');
});

test('HU-24 (DA-94): el jugador de la vista lleva id, nombres, apellidos y matrícula', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    expect(convocarPedir($this, convocarRuta($m['f1']))->assertOk()->json('datos.jugador'))->toBe([
        'id' => $m['f1'], 'nombres' => 'F1', 'apellidos' => 'Delantero', 'matricula' => 'M-F1',
    ]);
});

test('RNF-01: la vista no escribe nada en la base', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarEvaluar($m['f1'], $m['a'], $m['e1'], 'pasa', '8.00');
    $antes = [DB::table('evaluaciones_rendimiento')->count(), DB::table('alertas')->count(), DB::table('resumenes_academicos')->count()];

    convocarPedir($this, convocarRuta($m['f1']))->assertOk();
    convocarPedir($this, convocarRutaDelEquipo($m['a']))->assertOk();

    expect([DB::table('evaluaciones_rendimiento')->count(), DB::table('alertas')->count(), DB::table('resumenes_academicos')->count()])->toBe($antes);
});
