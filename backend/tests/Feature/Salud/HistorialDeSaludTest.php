<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/*
 * F1-H7-T03: historial de avisos, lesiones y estados (HU-26, RN-15; docs/hitos/f1-h7.md §5.3, §6, §7 y DA-125). Mundo de
 * docs/hitos/f1-h5.md §7.0 (avisosMundo): F de §7.1 es F1 (equipo A de E1 y equipo B de E2, inscrito en la materia de D1).
 * En los historiales de H5 no se entregan el autor ni los campos que DA-18 no deja revelar; `lesion_id` solo aparece para
 * quien registró la lesión (DA-125).
 */

const RUTAS_DE_HISTORIAL_DE_SALUD = ['avisos-lesion', 'lesiones', 'estados-disponibilidad'];

const CAMPOS_OCULTOS_POR_DA_18 = ['creado_por', 'confirmado_por', 'descartado_por', 'registrada_por'];

/** @return TestResponse<Response> */
function historialDeSaludPedir(TestCase $prueba, string $jugadorId, string $recurso, ?string $correo, string $consulta = ''): TestResponse
{
    return sesionesPeticion(
        $prueba,
        'GET',
        "/api/v1/jugadores/$jugadorId/historial/$recurso$consulta",
        [],
        $correo === null ? null : sesionesIniciar($prueba, $correo),
    );
}

/** @return list<array<string, mixed>> */
function historialDeSaludDatos(TestCase $prueba, string $jugadorId, string $recurso, string $correo): array
{
    /** @var list<array<string, mixed>> */
    return historialDeSaludPedir($prueba, $jugadorId, $recurso, $correo)->assertOk()->json('datos');
}

/** Crea un aviso por la API (escribe su historial) y devuelve su id. */
function historialDeSaludCrearAviso(TestCase $prueba, string $jugadorId, string $correo): string
{
    return (string) sesionesPeticion($prueba, 'POST', avisosRuta($jugadorId), ['nota' => 'Molestia en el tobillo', 'fecha' => '2026-11-14'], sesionesIniciar($prueba, $correo))
        ->assertCreated()->json('datos.id');
}

/** Confirma un aviso como lesionado por la API; quien confirma registra la lesión y sostiene el estado. Devuelve la lesión. */
function historialDeSaludConfirmar(TestCase $prueba, string $avisoId, string $correo): string
{
    sesionesPeticion($prueba, 'POST', avisoConfirmacionRuta($avisoId), confirmacionCuerpo(), sesionesIniciar($prueba, $correo))->assertOk();

    return (string) DB::table('avisos_lesion')->where('id', $avisoId)->value('lesion_id');
}

/**
 * Ninguna entrada trae `autor` ni los campos de DA-18 en sus datos: se omiten, no van en nulo (DA-125).
 *
 * @param  list<array<string, mixed>>  $entradas
 */
function historialDeSaludSinCamposDeDa18(array $entradas): void
{
    foreach ($entradas as $entrada) {
        expect($entrada)->not->toHaveKey('autor');
        foreach (['datos_anteriores', 'datos_nuevos'] as $lado) {
            /** @var array<string, mixed>|null $datos */
            $datos = $entrada[$lado];
            foreach (CAMPOS_OCULTOS_POR_DA_18 as $campo) {
                expect($datos ?? [])->not->toHaveKey($campo);
            }
        }
    }
}

test('HU-26 c1 (DA-18): el historial de avisos entrega fecha, valor anterior y nuevo, sin autor ni creado_por, confirmado_por ni descartado_por', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $confirmado = historialDeSaludCrearAviso($this, $m['f1'], 'e1@x.com');
    relojAdelantar('2026-11-15 18:00:01');
    historialDeSaludConfirmar($this, $confirmado, 'e1@x.com');
    relojAdelantar('2026-11-15 18:00:02');
    $descartado = historialDeSaludCrearAviso($this, $m['f1'], 'e1@x.com');
    relojAdelantar('2026-11-15 18:00:03');
    sesionesPeticion($this, 'POST', avisoDescarteRuta($descartado), ['nota_descarte' => 'No fue una lesión'], sesionesIniciar($this, 'e1@x.com'))->assertOk();

    $datos = historialDeSaludDatos($this, $m['f1'], 'avisos-lesion', 'e1@x.com');

    expect($datos)->toHaveCount(4)
        ->and(array_map(fn (array $e) => [$e['entidad_id'], $e['operacion'], $e['ocurrido_en']], $datos))->toBe([
            [$descartado, 'actualizar', '2026-11-15T18:00:03+00:00'],
            [$descartado, 'crear', '2026-11-15T18:00:02+00:00'],
            [$confirmado, 'actualizar', '2026-11-15T18:00:01+00:00'],
            [$confirmado, 'crear', '2026-11-15T18:00:00+00:00'],
        ])
        ->and($datos[0])->toMatchArray(['recurso' => 'avisos-lesion', 'version' => 2, 'motivo' => null])
        ->and($datos[0]['datos_anteriores'])->toMatchArray(['estado' => 'pendiente', 'nota_descarte' => null])
        ->and($datos[0]['datos_nuevos'])->toMatchArray(['estado' => 'descartado', 'nota_descarte' => 'No fue una lesión', 'nota' => 'Molestia en el tobillo'])
        ->and($datos[1]['datos_anteriores'])->toBeNull()
        ->and($datos[1]['datos_nuevos'])->toMatchArray(['estado' => 'pendiente', 'fecha_aviso' => '2026-11-14']);
    historialDeSaludSinCamposDeDa18($datos);
});

test('HU-26 c1 (§7.2 fila 4, DA-125): el historial de un aviso confirmado muestra el paso de pendiente a confirmado con su instante, sin autor ni confirmado_por', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = historialDeSaludCrearAviso($this, $m['f1'], 'd1@x.com');
    relojAdelantar('2026-11-15 18:00:05');
    $lesion = historialDeSaludConfirmar($this, $aviso, 'e1@x.com');

    foreach (['e1@x.com', 'd1@x.com'] as $correo) {
        $datos = historialDeSaludDatos($this, $m['f1'], 'avisos-lesion', $correo);

        expect($datos)->toHaveCount(2)
            ->and($datos[0])->toMatchArray(['entidad_id' => $aviso, 'operacion' => 'actualizar', 'ocurrido_en' => '2026-11-15T18:00:05+00:00'])
            ->and($datos[0]['datos_anteriores']['estado'])->toBe('pendiente')
            ->and($datos[0]['datos_nuevos']['estado'])->toBe('confirmado');
        historialDeSaludSinCamposDeDa18($datos);
    }

    // `lesion_id` de la confirmación: solo quien registró la lesión (E1, que confirmó) lo ve; D1, que creó el aviso, no.
    expect(historialDeSaludDatos($this, $m['f1'], 'avisos-lesion', 'e1@x.com')[0]['datos_nuevos']['lesion_id'])->toBe($lesion)
        ->and(historialDeSaludDatos($this, $m['f1'], 'avisos-lesion', 'd1@x.com')[0]['datos_nuevos'])->not->toHaveKey('lesion_id');
});

test('HU-26 c1 (DA-18): el historial de lesiones entrega fecha, valor anterior y nuevo, sin autor ni registrada_por', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = historialDeSaludConfirmar($this, historialDeSaludCrearAviso($this, $m['f1'], 'd1@x.com'), 'e1@x.com');
    relojAdelantar('2026-11-15 18:00:07');
    sesionesPeticion($this, 'PATCH', lesionRuta($lesion), ['zona' => 'Rodilla'], sesionesIniciar($this, 'e1@x.com'))->assertOk();

    $datos = historialDeSaludDatos($this, $m['f1'], 'lesiones', 'e1@x.com');

    expect($datos)->toHaveCount(2)
        ->and($datos[0])->toMatchArray([
            'recurso' => 'lesiones', 'entidad_id' => $lesion, 'version' => 2, 'operacion' => 'actualizar', 'ocurrido_en' => '2026-11-15T18:00:07+00:00',
        ])
        ->and($datos[0]['datos_anteriores']['zona'])->toBe('Tobillo')
        ->and($datos[0]['datos_nuevos']['zona'])->toBe('Rodilla')
        ->and($datos[1])->toMatchArray(['entidad_id' => $lesion, 'version' => 1, 'operacion' => 'crear', 'datos_anteriores' => null])
        ->and($datos[1]['datos_nuevos'])->toMatchArray(['tipo' => 'Esguince', 'zona' => 'Tobillo', 'causa' => 'partido']);
    historialDeSaludSinCamposDeDa18($datos);
});

test('HU-26 c1 (DA-18): el historial de estados entrega fecha, valor anterior y nuevo, sin autor ni confirmado_por, con lesion_id para quien registró la lesión', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = historialDeSaludConfirmar($this, historialDeSaludCrearAviso($this, $m['f1'], 'd1@x.com'), 'e1@x.com');
    relojAdelantar('2026-11-15 18:00:09');
    sesionesPeticion($this, 'POST', altaRuta($m['f1']), [], sesionesIniciar($this, 'e1@x.com'))->assertOk();

    $datos = historialDeSaludDatos($this, $m['f1'], 'estados-disponibilidad', 'e1@x.com');
    $lesionado = array_values(array_filter($datos, fn (array $e) => $e['operacion'] === 'crear' && $e['datos_nuevos']['estado'] === 'lesionado'));
    $cierre = array_values(array_filter($datos, fn (array $e) => $e['operacion'] === 'actualizar'));

    expect($datos)->toHaveCount(3)
        ->and($datos[0]['recurso'])->toBe('estados-disponibilidad')
        ->and($lesionado)->toHaveCount(1)
        ->and($lesionado[0]['datos_nuevos']['lesion_id'])->toBe($lesion)
        ->and($cierre)->toHaveCount(1)
        ->and($cierre[0]['datos_anteriores']['vigente_hasta'])->toBeNull()
        ->and($cierre[0]['datos_nuevos']['vigente_hasta'])->toBe('2026-11-15T18:00:09+00:00')
        ->and($cierre[0]['datos_nuevos']['lesion_id'])->toBe($lesion);
    historialDeSaludSinCamposDeDa18($datos);
});

test('DA-125: el historial de estados no incluye lesion_id para quien no registró la lesión', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    historialDeSaludConfirmar($this, historialDeSaludCrearAviso($this, $m['f1'], 'd1@x.com'), 'e1@x.com');

    foreach (['e2@x.com', 'd1@x.com'] as $correo) {
        $datos = historialDeSaludDatos($this, $m['f1'], 'estados-disponibilidad', $correo);

        expect($datos)->toHaveCount(1)
            ->and($datos[0]['datos_nuevos']['estado'])->toBe('lesionado')
            ->and($datos[0]['datos_nuevos'])->not->toHaveKey('lesion_id');
        historialDeSaludSinCamposDeDa18($datos);
    }
});

test('HU-26 c3 (§7.1 fila 4): E1 ve el historial de la lesión que registró', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = historialDeSaludConfirmar($this, historialDeSaludCrearAviso($this, $m['f1'], 'd1@x.com'), 'e1@x.com');

    $datos = historialDeSaludDatos($this, $m['f1'], 'lesiones', 'e1@x.com');

    expect(array_column($datos, 'entidad_id'))->toBe([$lesion]);
});

test('HU-26 c3 (§7.1 fila 5): E2 recibe 200 sin la lesión registrada por E1', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    historialDeSaludConfirmar($this, historialDeSaludCrearAviso($this, $m['f1'], 'd1@x.com'), 'e1@x.com');

    historialDeSaludPedir($this, $m['f1'], 'lesiones', 'e2@x.com')
        ->assertOk()
        ->assertJsonPath('datos', [])
        ->assertJsonPath('meta.total', 0);
});

test('HU-26 c3 (§7.1 fila 6): D1 recibe en el historial de lesiones lo mismo que en la lectura de lesiones (403)', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    historialDeSaludConfirmar($this, historialDeSaludCrearAviso($this, $m['f1'], 'd1@x.com'), 'e1@x.com');

    $lectura = sesionesPeticion($this, 'GET', lesionesRuta($m['f1']), [], sesionesIniciar($this, 'd1@x.com'))->assertForbidden();
    $historial = historialDeSaludPedir($this, $m['f1'], 'lesiones', 'd1@x.com')->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');

    expect((string) $historial->getContent())->toBe((string) $lectura->getContent());
});

test('HU-26 c3 (§7.1 fila 8): D1 ve en el historial de avisos solo el aviso que creó', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $deD1 = historialDeSaludCrearAviso($this, $m['f1'], 'd1@x.com');
    historialDeSaludCrearAviso($this, $m['f1'], 'e1@x.com');

    $datos = historialDeSaludDatos($this, $m['f1'], 'avisos-lesion', 'd1@x.com');

    expect(array_column($datos, 'entidad_id'))->toBe([$deD1]);
});

test('HU-26 c3 (§7.1 fila 9, D-2): E2 no recibe las entradas de un aviso con nota de E1 que no confirmó, y las que recibe no muestran autor', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $deE1 = historialDeSaludCrearAviso($this, $m['f1'], 'e1@x.com');
    $deE2 = historialDeSaludCrearAviso($this, $m['f1'], 'e2@x.com');
    // E2 ve el aviso de E1 en la lectura, pero sin su nota.
    $lectura = sesionesPeticion($this, 'GET', avisosRuta($m['f1']), [], sesionesIniciar($this, 'e2@x.com'))->assertOk()->json('datos');
    $avisoDeE1 = array_values(array_filter($lectura, fn (array $aviso) => $aviso['id'] === $deE1));
    expect($avisoDeE1)->toHaveCount(1)->and($avisoDeE1[0]['nota'])->toBeNull();

    $datos = historialDeSaludDatos($this, $m['f1'], 'avisos-lesion', 'e2@x.com');

    expect(array_column($datos, 'entidad_id'))->toBe([$deE2]);
    historialDeSaludSinCamposDeDa18($datos);
});

test('HU-26 c3 (§7.1 fila 10): E1, E2 y D1 reciben 200 en el historial de estados de F', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    historialDeSaludConfirmar($this, historialDeSaludCrearAviso($this, $m['f1'], 'd1@x.com'), 'e1@x.com');

    foreach (['e1@x.com', 'e2@x.com', 'd1@x.com'] as $correo) {
        expect(historialDeSaludDatos($this, $m['f1'], 'estados-disponibilidad', $correo))->toHaveCount(1);
    }
});

test('RN-15: el historial de una lesión solo lo ve quien la registró', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = historialDeSaludConfirmar($this, historialDeSaludCrearAviso($this, $m['f1'], 'd1@x.com'), 'e1@x.com');

    expect(array_column(historialDeSaludDatos($this, $m['f1'], 'lesiones', 'e1@x.com'), 'entidad_id'))->toBe([$lesion]);
    // E2 (otro equipo de F), E3 (entrena los dos equipos de F) y DE (docente y entrenador de A) no la registraron.
    foreach (['e2@x.com', 'e3@x.com', 'de@x.com'] as $correo) {
        expect(historialDeSaludDatos($this, $m['f1'], 'lesiones', $correo))->toBe([]);
    }
});

test('D-2: el historial de un aviso no se entrega a quien ve el aviso pero no su nota', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = historialDeSaludCrearAviso($this, $m['f1'], 'd1@x.com');
    relojAdelantar('2026-11-15 18:00:01');
    // E2 descarta el aviso de D1: ve su nota de descarte, pero no la nota del aviso, así que no ve la fila completa.
    sesionesPeticion($this, 'POST', avisoDescarteRuta($aviso), ['nota_descarte' => 'No fue una lesión'], sesionesIniciar($this, 'e2@x.com'))->assertOk();

    expect(historialDeSaludDatos($this, $m['f1'], 'avisos-lesion', 'e2@x.com'))->toBe([])
        ->and(historialDeSaludDatos($this, $m['f1'], 'avisos-lesion', 'e3@x.com'))->toBe([])
        ->and(array_column(historialDeSaludDatos($this, $m['f1'], 'avisos-lesion', 'd1@x.com'), 'entidad_id'))->toBe([$aviso, $aviso]);
});

test('AB-30: una lesión sin fecha_alta aparece en el historial', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = historialDeSaludConfirmar($this, historialDeSaludCrearAviso($this, $m['f1'], 'd1@x.com'), 'e1@x.com');
    expect(DB::table('lesiones')->where('id', $lesion)->value('fecha_alta'))->toBeNull();

    $datos = historialDeSaludDatos($this, $m['f1'], 'lesiones', 'e1@x.com');

    expect($datos)->toHaveCount(1)
        ->and($datos[0]['entidad_id'])->toBe($lesion)
        ->and($datos[0]['datos_nuevos'])->toHaveKey('fecha_alta')
        ->and($datos[0]['datos_nuevos']['fecha_alta'])->toBeNull();
});

test('Orden 404 antes de 422: jugador inexistente con por_pagina=0 responde 404', function (string $recurso) {
    /** @var TestCase $this */
    avisosMundo();

    historialDeSaludPedir($this, Identificador::generar()->valor(), $recurso, 'e1@x.com', '?por_pagina=0')
        ->assertNotFound()
        ->assertJsonPath('codigo', 'recurso_no_encontrado');
})->with(RUTAS_DE_HISTORIAL_DE_SALUD);

test('D-2 y DA-25: quien confirmó un aviso y solo da clase al jugador no ve la entrada de ese aviso', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    // DE (docente y entrenador de A) confirma el aviso de E1 mientras entrena a F1.
    $aviso = historialDeSaludCrearAviso($this, $m['f1'], 'e1@x.com');
    historialDeSaludConfirmar($this, $aviso, 'de@x.com');
    expect(array_column(historialDeSaludDatos($this, $m['f1'], 'avisos-lesion', 'de@x.com'), 'entidad_id'))->toBe([$aviso, $aviso]);

    // DE deja de entrenar A y pasa a dar clase a F1: lo sigue viendo, solo como docente.
    DB::connection('propietario')->table('entrenador_equipo')->where('usuario_id', $m['de'])->update(['hasta' => '2026-11-15']);
    mundoInscribir(mundoInsertar('materias', ['asignatura_id' => $m['asignatura'], 'periodo_id' => $m['periodo'], 'docente_id' => $m['de']]), $m['f1']);
    $lectura = sesionesPeticion($this, 'GET', avisosRuta($m['f1']), [], sesionesIniciar($this, 'de@x.com'))->assertOk()->json('datos');
    expect(array_column($lectura, 'id'))->not->toContain($aviso);

    historialDeSaludPedir($this, $m['f1'], 'avisos-lesion', 'de@x.com')
        ->assertOk()
        ->assertJsonPath('datos', [])
        ->assertJsonPath('meta.total', 0);
});

test('DA-125: un estado apto sin lesión no incluye lesion_id', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    historialDeSaludConfirmar($this, historialDeSaludCrearAviso($this, $m['f1'], 'd1@x.com'), 'e1@x.com');
    relojAdelantar('2026-11-15 18:00:09');
    sesionesPeticion($this, 'POST', altaRuta($m['f1']), [], sesionesIniciar($this, 'e1@x.com'))->assertOk();
    // El historial guarda `lesion_id` en nulo para el estado apto.
    $guardado = DB::table('estados_disponibilidad_historial')->where('operacion', 'crear')->pluck('datos_nuevos')
        ->map(fn (string $json) => json_decode($json, true))
        ->filter(fn (array $datos) => $datos['estado'] === 'apto')
        ->values();
    expect($guardado)->toHaveCount(1)->and($guardado[0])->toHaveKey('lesion_id')->and($guardado[0]['lesion_id'])->toBeNull();

    // E1 registró la lesión del estado anterior; aun así, el estado apto no lleva `lesion_id`.
    $datos = historialDeSaludDatos($this, $m['f1'], 'estados-disponibilidad', 'e1@x.com');
    $apto = array_values(array_filter($datos, fn (array $e) => $e['operacion'] === 'crear' && $e['datos_nuevos']['estado'] === 'apto'));

    expect($apto)->toHaveCount(1)
        ->and($apto[0]['datos_anteriores'])->toBeNull()
        ->and($apto[0]['datos_nuevos'])->not->toHaveKey('lesion_id');
});

test('HU-26: el historial de salud cumple el contrato de OpenAPI (200, 401, 403, 404 y 422)', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    sesionesCrearUsuario('s@x.com', []);
    historialDeSaludConfirmar($this, historialDeSaludCrearAviso($this, $m['f1'], 'd1@x.com'), 'e1@x.com');
    $inexistente = Identificador::generar()->valor();

    foreach (RUTAS_DE_HISTORIAL_DE_SALUD as $recurso) {
        $ruta = "/jugadores/{jugador}/historial/$recurso";
        $sinRol = $recurso === 'lesiones' ? 'd1@x.com' : 's@x.com';
        $respuestas = [
            historialDeSaludPedir($this, $m['f1'], $recurso, 'e1@x.com')->assertOk(),
            historialDeSaludPedir($this, $m['f1'], $recurso, null)->assertUnauthorized(),
            historialDeSaludPedir($this, $m['f1'], $recurso, $sinRol)->assertForbidden(),
            historialDeSaludPedir($this, $inexistente, $recurso, 'e1@x.com')->assertNotFound(),
            historialDeSaludPedir($this, $m['f1'], $recurso, 'e1@x.com', '?por_pagina=0')->assertUnprocessable(),
        ];
        expect($respuestas[0]->json('datos'))->not->toBe([]);
        foreach ($respuestas as $respuesta) {
            expect($validador->validate($respuesta->baseResponse, $ruta, 'get'))->toBeTrue("GET $ruta {$respuesta->getStatusCode()}");
        }
    }
});
