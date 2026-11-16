<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * HU-23, RN-13, RN-14, RN-23 y §7.6 (docs/hitos/f1-h6.md): el listado de estatus, una fila por rol (DA-105, T04 opción B).
 * Mundo de partida: `avisosMundo()` (E1 entrena A, E2 entrena B, E3 entrena A y B, DE es docente y entrena A; D1 da una
 * materia con F1 y JB; D2 no tiene alumnos; F1 está en A y B; J1, solo en A; JB está en baja).
 */

/**
 * @param  TestResponse<Response>  $respuesta
 * @return list<string> los identificadores de jugador de la página, en el orden recibido
 */
function estatusJugadores(TestResponse $respuesta): array
{
    /** @var list<array{jugador: array{id: string}}> $datos */
    $datos = $respuesta->json('datos');

    return array_map(fn (array $fila): string => $fila['jugador']['id'], $datos);
}

/**
 * Un objeto JSON de la fila, ya como arreglo.
 *
 * @param  array<string, mixed>  $fila
 * @return array<string, mixed>
 */
function estatusObjeto(array $fila, string $clave): array
{
    $valor = $fila[$clave] ?? null;
    if (! is_array($valor)) {
        throw new RuntimeException("«{$clave}» no es un objeto de la fila.");
    }

    /** @var array<string, mixed> $valor */
    return $valor;
}

/**
 * Una lista JSON de objetos de la fila, ya como arreglo.
 *
 * @param  array<string, mixed>  $fila
 * @return list<array<string, mixed>>
 */
function estatusLista(array $fila, string $clave): array
{
    $valor = $fila[$clave] ?? null;
    if (! is_array($valor)) {
        throw new RuntimeException("«{$clave}» no es una lista de la fila.");
    }

    /** @var list<array<string, mixed>> $valor */
    return $valor;
}

/**
 * @param  TestResponse<Response>  $respuesta
 * @return array<string, mixed> la fila de un jugador de la página
 */
function estatusFila(TestResponse $respuesta, string $jugador): array
{
    /** @var list<array<string, mixed>> $datos */
    $datos = $respuesta->json('datos');
    foreach ($datos as $fila) {
        /** @var array{id: string} $datosDelJugador */
        $datosDelJugador = $fila['jugador'];
        if ($datosDelJugador['id'] === $jugador) {
            return $fila;
        }
    }

    throw new RuntimeException("El jugador $jugador no está en la página.");
}

test('HU-23 c1 (RNF-01): cada usuario ve solo a sus jugadores asignados y nunca a uno dado de baja', function () {
    /** @var TestCase $this */
    $m = estatusMundo();

    $esperado = [
        'e1@x.com' => [$m['f1'], $m['j1']],
        'e2@x.com' => [$m['f1']],
        'e3@x.com' => [$m['f1'], $m['j1']],
        'd1@x.com' => [$m['f1']],
        'd2@x.com' => [],
        'de@x.com' => [$m['f1'], $m['j1']],
    ];

    foreach ($esperado as $correo => $jugadores) {
        $sesion = sesionesIniciar($this, $correo);
        $respuesta = sesionesPeticion($this, 'GET', estatusRuta(), idDeSesion: $sesion)->assertOk();

        expect(estatusJugadores($respuesta))->toBe($jugadores, $correo)
            ->and($respuesta->json('meta.total'))->toBe(count($jugadores), $correo);
    }
});

test('HU-23 c3 (RN-13, DA-105): el entrenador recibe el resumen académico simplificado, sin materias, y el docente recibe el detalle de sus materias, sin el resumen', function () {
    /** @var TestCase $this */
    $m = estatusMundo();
    $e1 = sesionesIniciar($this, 'e1@x.com');
    $d1 = sesionesIniciar($this, 'd1@x.com');

    $filaDelEntrenador = estatusFila(sesionesPeticion($this, 'GET', estatusRuta(), idDeSesion: $e1)->assertOk(), $m['f1']);
    $filaDelDocente = estatusFila(sesionesPeticion($this, 'GET', estatusRuta(), idDeSesion: $d1)->assertOk(), $m['f1']);

    expect(array_keys($filaDelEntrenador))->toBe(['jugador', 'estado_oficial', 'aviso_pendiente', 'alertas', 'resumen_academico'])
        ->and(array_keys(estatusObjeto($filaDelEntrenador, 'resumen_academico')))
        ->toBe(['periodo', 'promedio', 'materias_reprobadas', 'materias_sin_calificar', 'dato_incompleto', 'porcentaje_asistencia', 'nivel_riesgo', 'calculado_en'])
        ->and(array_keys($filaDelDocente))->toBe(['jugador', 'estado_oficial', 'aviso_pendiente', 'alertas', 'materias'])
        ->and(estatusLista($filaDelDocente, 'materias'))->toHaveCount(1)
        ->and(estatusObjeto(estatusLista($filaDelDocente, 'materias')[0], 'asignatura'))->toBe(['clave' => 'MAT-101', 'nombre' => 'Matemáticas']);
});

test('HU-23: la fila trae los datos de identificación del jugador y nada de otros datos', function () {
    /** @var TestCase $this */
    $m = estatusMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    $fila = estatusFila(sesionesPeticion($this, 'GET', estatusRuta(), idDeSesion: $sesion)->assertOk(), $m['j1']);

    expect($fila['jugador'])->toBe(['id' => $m['j1'], 'nombres' => 'J1', 'apellidos' => 'Uno', 'matricula' => 'M-J1']);
});

test('HU-23 c4 (DA-108): el indicador de aviso pendiente es verdadero para los entrenadores del jugador y para quien creó el aviso', function () {
    /** @var TestCase $this */
    $m = estatusMundo();
    avisoInsertar($m['f1'], $m['d1']);
    avisoInsertar($m['j1'], $m['e1']);

    $indicador = function (string $correo, string $jugador): bool {
        $respuesta = sesionesPeticion($this, 'GET', estatusRuta(), idDeSesion: sesionesIniciar($this, $correo))->assertOk();

        return estatusFila($respuesta, $jugador)['aviso_pendiente'] === true;
    };

    expect($indicador('d1@x.com', $m['f1']))->toBeTrue()
        ->and($indicador('e1@x.com', $m['f1']))->toBeTrue()
        ->and($indicador('e2@x.com', $m['f1']))->toBeTrue()
        ->and($indicador('e1@x.com', $m['j1']))->toBeTrue()
        ->and($indicador('e3@x.com', $m['j1']))->toBeTrue();
});

test('HU-23 c4 (DA-108, RN-16): el docente no ve el aviso pendiente que creó otro, y un aviso ya confirmado o descartado no cuenta', function () {
    /** @var TestCase $this */
    $m = estatusMundo();
    avisoInsertar($m['f1'], $m['e1']);
    $d1 = sesionesIniciar($this, 'd1@x.com');
    $e1 = sesionesIniciar($this, 'e1@x.com');

    $delDocente = estatusFila(sesionesPeticion($this, 'GET', estatusRuta(), idDeSesion: $d1)->assertOk(), $m['f1']);
    DB::connection('propietario')->table('avisos_lesion')->update([
        'estado' => 'descartado', 'descartado_por' => $m['e1'], 'nota_descarte' => 'No aplica',
    ]);
    $descartado = estatusFila(sesionesPeticion($this, 'GET', estatusRuta(), idDeSesion: $e1)->assertOk(), $m['f1']);

    expect($delDocente['aviso_pendiente'])->toBeFalse()
        ->and($descartado['aviso_pendiente'])->toBeFalse();
});

test('HU-23 c3 (RN-13, RN-15): el estado oficial es el mismo para todos, y solo quien lo registró recibe lesion_id', function () {
    /** @var TestCase $this */
    $m = estatusMundo();
    $estado = estadoInsertar($m['f1'], $m['e2']);
    $porUsuario = [];
    foreach (['e2@x.com', 'e1@x.com', 'd1@x.com'] as $correo) {
        $respuesta = sesionesPeticion($this, 'GET', estatusRuta(), idDeSesion: sesionesIniciar($this, $correo))->assertOk();
        $porUsuario[$correo] = estatusObjeto(estatusFila($respuesta, $m['f1']), 'estado_oficial');
    }

    expect($porUsuario['e2@x.com']['estado'])->toBe('lesionado')
        ->and($porUsuario['e2@x.com']['lesion_id'])->toBe($estado['lesion'])
        ->and($porUsuario['e1@x.com']['estado'])->toBe('lesionado')
        ->and($porUsuario['e1@x.com']['fecha_estimada_regreso'])->toBe('2026-11-30')
        ->and($porUsuario['e1@x.com']['lesion_id'])->toBeNull()
        ->and($porUsuario['d1@x.com']['lesion_id'])->toBeNull();
});

test('HU-23 c3: un jugador sin ningún estado registrado sale como apto implícito', function () {
    /** @var TestCase $this */
    $m = estatusMundo();

    $fila = estatusFila(sesionesPeticion($this, 'GET', estatusRuta(), idDeSesion: sesionesIniciar($this, 'e1@x.com'))->assertOk(), $m['j1']);

    expect(estatusObjeto($fila, 'estado_oficial')['estado'])->toBe('apto')
        ->and(estatusObjeto($fila, 'estado_oficial')['es_implicito'])->toBeTrue();
});

test('HU-22, HU-23 (DA-89, DA-90): la fila trae las alertas abiertas que el usuario puede ver, y solo esas', function () {
    /** @var TestCase $this */
    $m = estatusMundo();
    alertaInsertar($m['f1'], ALERTA_ACADEMICA);
    alertaInsertar($m['f1'], ALERTA_DEPORTIVA, $m['a']);
    alertaInsertar($m['f1'], ALERTA_SANCIONES, $m['b']);
    alertaInsertar($m['j1'], ALERTA_DEPORTIVA, $m['a'], cerradaEn: '2026-11-14 08:00:00+00');
    $tipos = fn (string $correo, string $jugador): array => array_map(
        fn (array $alerta): string => (string) $alerta['tipo'],
        estatusLista(estatusFila(sesionesPeticion($this, 'GET', estatusRuta(), idDeSesion: sesionesIniciar($this, $correo))->assertOk(), $jugador), 'alertas'),
    );

    expect($tipos('e1@x.com', $m['f1']))->toBe([ALERTA_ACADEMICA, ALERTA_DEPORTIVA])
        ->and($tipos('e2@x.com', $m['f1']))->toBe([ALERTA_ACADEMICA, ALERTA_SANCIONES])
        ->and($tipos('e3@x.com', $m['f1']))->toBe([ALERTA_ACADEMICA, ALERTA_DEPORTIVA, ALERTA_SANCIONES])
        ->and($tipos('d1@x.com', $m['f1']))->toBe([ALERTA_ACADEMICA])
        ->and($tipos('e1@x.com', $m['j1']))->toBe([]);
});

test('HU-23 c2: filtro[equipo_id] deja solo a los jugadores del equipo, y solo de un equipo del usuario', function () {
    /** @var TestCase $this */
    $m = estatusMundo();
    $e1 = sesionesIniciar($this, 'e1@x.com');
    $d1 = sesionesIniciar($this, 'd1@x.com');
    $e2 = sesionesIniciar($this, 'e2@x.com');

    expect(estatusJugadores(sesionesPeticion($this, 'GET', estatusRuta("filtro[equipo_id]={$m['a']}"), idDeSesion: $e1)->assertOk()))->toBe([$m['f1'], $m['j1']])
        ->and(estatusJugadores(sesionesPeticion($this, 'GET', estatusRuta("filtro[equipo_id]={$m['b']}"), idDeSesion: $e1)->assertOk()))->toBe([])
        ->and(estatusJugadores(sesionesPeticion($this, 'GET', estatusRuta("filtro[equipo_id]={$m['b']}"), idDeSesion: $e2)->assertOk()))->toBe([$m['f1']])
        ->and(estatusJugadores(sesionesPeticion($this, 'GET', estatusRuta("filtro[equipo_id]={$m['a']}"), idDeSesion: $d1)->assertOk()))->toBe([]);
});

test('HU-23 c2: filtro[materia_id] deja solo a los inscritos en una materia del docente', function () {
    /** @var TestCase $this */
    $m = estatusMundo();
    $d1 = sesionesIniciar($this, 'd1@x.com');
    $e1 = sesionesIniciar($this, 'e1@x.com');
    $d2 = sesionesIniciar($this, 'd2@x.com');

    expect(estatusJugadores(sesionesPeticion($this, 'GET', estatusRuta("filtro[materia_id]={$m['materia']}"), idDeSesion: $d1)->assertOk()))->toBe([$m['f1']])
        ->and(estatusJugadores(sesionesPeticion($this, 'GET', estatusRuta("filtro[materia_id]={$m['materia']}"), idDeSesion: $d2)->assertOk()))->toBe([])
        ->and(estatusJugadores(sesionesPeticion($this, 'GET', estatusRuta("filtro[materia_id]={$m['materia']}"), idDeSesion: $e1)->assertOk()))->toBe([]);
});

test('HU-23 c2, §7.6 fila 9: filtro[tipo_alerta] deja solo a quienes tienen esa alerta abierta y visible, y el docente no ve la deportiva', function () {
    /** @var TestCase $this */
    $m = estatusMundo();
    alertaInsertar($m['f1'], ALERTA_ACADEMICA);
    alertaInsertar($m['f1'], ALERTA_DEPORTIVA, $m['a']);
    alertaInsertar($m['j1'], ALERTA_SANCIONES, $m['a']);
    alertaInsertar($m['j1'], ALERTA_DEPORTIVA, $m['a'], cerradaEn: '2026-11-14 08:00:00+00');
    $e1 = sesionesIniciar($this, 'e1@x.com');
    $d1 = sesionesIniciar($this, 'd1@x.com');
    $e2 = sesionesIniciar($this, 'e2@x.com');

    expect(estatusJugadores(sesionesPeticion($this, 'GET', estatusRuta('filtro[tipo_alerta]='.ALERTA_DEPORTIVA), idDeSesion: $e1)->assertOk()))->toBe([$m['f1']])
        ->and(estatusJugadores(sesionesPeticion($this, 'GET', estatusRuta('filtro[tipo_alerta]='.ALERTA_SANCIONES), idDeSesion: $e1)->assertOk()))->toBe([$m['j1']])
        ->and(estatusJugadores(sesionesPeticion($this, 'GET', estatusRuta('filtro[tipo_alerta]='.ALERTA_ACADEMICA), idDeSesion: $d1)->assertOk()))->toBe([$m['f1']])
        ->and(estatusJugadores(sesionesPeticion($this, 'GET', estatusRuta('filtro[tipo_alerta]='.ALERTA_DEPORTIVA), idDeSesion: $d1)->assertOk()))->toBe([])
        ->and(estatusJugadores(sesionesPeticion($this, 'GET', estatusRuta('filtro[tipo_alerta]='.ALERTA_DEPORTIVA), idDeSesion: $e2)->assertOk()))->toBe([]);
});

test('HU-23 c2: los filtros se combinan con AND', function () {
    /** @var TestCase $this */
    $m = estatusMundo();
    alertaInsertar($m['f1'], ALERTA_DEPORTIVA, $m['a']);
    alertaInsertar($m['j1'], ALERTA_SANCIONES, $m['a']);
    $sesion = sesionesIniciar($this, 'e3@x.com');
    $listar = fn (string $consulta): array => estatusJugadores(sesionesPeticion($this, 'GET', estatusRuta($consulta), idDeSesion: $sesion)->assertOk());

    // F1 está en A y en B y tiene la deportiva; J1 solo está en A y tiene las sanciones.
    expect($listar("filtro[equipo_id]={$m['a']}&filtro[tipo_alerta]=".ALERTA_DEPORTIVA))->toBe([$m['f1']])
        ->and($listar("filtro[equipo_id]={$m['a']}&filtro[tipo_alerta]=".ALERTA_SANCIONES))->toBe([$m['j1']])
        ->and($listar("filtro[equipo_id]={$m['b']}&filtro[tipo_alerta]=".ALERTA_SANCIONES))->toBe([])
        ->and($listar("filtro[equipo_id]={$m['b']}"))->toBe([$m['f1']]);
});

test('P-05 (HU-23 c2): un filtro con un valor que no corresponde responde 422 validacion_fallida con filtro_invalido en errores[].codigo', function (string $consulta, string $campo) {
    /** @var TestCase $this */
    estatusMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    $respuesta = sesionesPeticion($this, 'GET', estatusRuta($consulta), idDeSesion: $sesion)->assertUnprocessable();

    expect($respuesta->json('codigo'))->toBe('validacion_fallida')
        ->and($respuesta->json('errores.0.campo'))->toBe($campo)
        ->and($respuesta->json('errores.0.codigo'))->toBe('filtro_invalido')
        ->and(is_string($respuesta->json('errores.0.mensaje')) && $respuesta->json('errores.0.mensaje') !== '')->toBeTrue();
})->with([
    'tipo de alerta desconocido' => ['filtro[tipo_alerta]=bajo_promedio', 'filtro.tipo_alerta'],
    'equipo que no es un UUID' => ['filtro[equipo_id]=no-es-un-uuid', 'filtro.equipo_id'],
    'materia que no es un UUID' => ['filtro[materia_id]=123', 'filtro.materia_id'],
]);

test('P-05: un id válido pero ajeno o inexistente devuelve la lista vacía, no un error, también si es un UUID de otra versión', function () {
    /** @var TestCase $this */
    estatusMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    foreach (['0194d1c2-7c1a-7a3e-9c1e-0a1b2c3d4e5f', '3f2504e0-4f89-41d3-9a0c-0305e82c3301'] as $id) {
        $respuesta = sesionesPeticion($this, 'GET', estatusRuta("filtro[equipo_id]=$id&filtro[materia_id]=$id"), idDeSesion: $sesion)->assertOk();

        expect($respuesta->json('datos'))->toBe([])->and($respuesta->json('meta.total'))->toBe(0);
    }
});

test('HU-23: la paginación usa pagina y por_pagina, ordena por apellidos y nombres, y no repite jugadores entre páginas', function () {
    /** @var TestCase $this */
    $m = estatusMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    $primera = sesionesPeticion($this, 'GET', estatusRuta('por_pagina=1&pagina=1'), idDeSesion: $sesion)->assertOk();
    $segunda = sesionesPeticion($this, 'GET', estatusRuta('por_pagina=1&pagina=2'), idDeSesion: $sesion)->assertOk();

    expect(estatusJugadores($primera))->toBe([$m['f1']])
        ->and(estatusJugadores($segunda))->toBe([$m['j1']])
        ->and($primera->json('meta'))->toBe(['pagina' => 1, 'por_pagina' => 1, 'total' => 2, 'total_paginas' => 2])
        ->and(sesionesPeticion($this, 'GET', estatusRuta('por_pagina=101'), idDeSesion: $sesion)->assertUnprocessable()->json('codigo'))->toBe('validacion_fallida');
});

test('RN-23 (DA-99): un jugador dado de baja desaparece del listado de todos, con su alerta abierta en la base', function () {
    /** @var TestCase $this */
    $m = estatusMundo();
    alertaInsertar($m['jb'], ALERTA_DEPORTIVA, $m['a']);
    alertaInsertar($m['jb'], ALERTA_ACADEMICA);

    foreach (['e1@x.com', 'd1@x.com'] as $correo) {
        $respuesta = sesionesPeticion($this, 'GET', estatusRuta('filtro[tipo_alerta]='.ALERTA_ACADEMICA), idDeSesion: sesionesIniciar($this, $correo))->assertOk();

        expect(estatusJugadores($respuesta))->not->toContain($m['jb']);
    }

    expect(DB::table('alertas')->where('jugador_id', $m['jb'])->whereNull('cerrada_en')->count())->toBe(2);
});

test('HU-23: sin sesión responde 401', function () {
    /** @var TestCase $this */
    estatusMundo();

    sesionesPeticion($this, 'GET', estatusRuta())->assertUnauthorized();
});
