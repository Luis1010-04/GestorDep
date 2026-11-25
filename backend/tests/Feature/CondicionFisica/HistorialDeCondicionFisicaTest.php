<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/*
 * F1-H7-T04: historial de mediciones y pruebas físicas (HU-26, RN-14; docs/hitos/f1-h7.md §5.3, §6, §7 y DA-125). Mundo de
 * docs/hitos/f1-h5.md §7.0 (pruebasMundo): F de §7.1 es F1 (equipo A de E1 y equipo B de E2). Cada entrenador ve solo lo
 * que registró (RN-14). En los historiales de H5 no se entregan el autor ni `registrado_por` (DA-18, DA-125).
 */

const RUTAS_DE_HISTORIAL_FISICO = ['mediciones', 'pruebas-fisicas'];

/** @return TestResponse<Response> */
function historialFisicoPedir(TestCase $prueba, string $jugadorId, string $recurso, ?string $correo, string $consulta = ''): TestResponse
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
function historialFisicoDatos(TestCase $prueba, string $jugadorId, string $recurso, string $correo): array
{
    /** @var list<array<string, mixed>> */
    return historialFisicoPedir($prueba, $jugadorId, $recurso, $correo)->assertOk()->json('datos');
}

/**
 * Registra una medición por la API (escribe su historial) y devuelve su id.
 *
 * @param  array<string, mixed>  $cambios
 */
function historialFisicoMedir(TestCase $prueba, string $jugadorId, string $equipoId, string $correo, array $cambios = []): string
{
    return (string) sesionesPeticion($prueba, 'POST', medicionesRuta($jugadorId), medicionCuerpo($equipoId, $cambios), sesionesIniciar($prueba, $correo))
        ->assertSuccessful()->json('datos.id');
}

/**
 * Registra una prueba física por la API (escribe su historial) y devuelve su id.
 *
 * @param  array<string, mixed>  $cambios
 */
function historialFisicoProbar(TestCase $prueba, string $jugadorId, string $equipoId, string $tipo, string $correo, array $cambios = []): string
{
    return (string) sesionesPeticion($prueba, 'POST', pruebasRuta($jugadorId), pruebaCuerpo($equipoId, $tipo, $cambios), sesionesIniciar($prueba, $correo))
        ->assertSuccessful()->json('datos.id');
}

/**
 * Ninguna entrada trae `autor` ni `registrado_por` en sus datos: se omiten, no van en nulo (DA-125).
 *
 * @param  list<array<string, mixed>>  $entradas
 */
function historialFisicoSinCamposDeDa18(array $entradas): void
{
    foreach ($entradas as $entrada) {
        expect($entrada)->not->toHaveKey('autor')
            ->and($entrada['datos_anteriores'] ?? [])->not->toHaveKey('registrado_por')
            ->and($entrada['datos_nuevos'] ?? [])->not->toHaveKey('registrado_por');
    }
}

test('HU-26 c1 (DA-18): el historial de mediciones entrega fecha, valor anterior y nuevo, sin autor ni registrado_por', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $medicion = historialFisicoMedir($this, $m['f1'], $m['a'], 'e1@x.com');
    relojAdelantar('2026-11-15 18:00:07');
    historialFisicoMedir($this, $m['f1'], $m['a'], 'e1@x.com', ['peso_kg' => '71.00', 'es_correccion' => true, 'motivo' => 'Báscula mal calibrada']);

    $datos = historialFisicoDatos($this, $m['f1'], 'mediciones', 'e1@x.com');

    expect($datos)->toHaveCount(2)
        ->and($datos[0])->toMatchArray([
            'recurso' => 'mediciones', 'entidad_id' => $medicion, 'version' => 2, 'operacion' => 'actualizar', 'ocurrido_en' => '2026-11-15T18:00:07+00:00',
        ])
        ->and($datos[0]['datos_anteriores'])->toMatchArray(['peso_kg' => '70.00', 'estatura_cm' => '175.0', 'equipo_id' => $m['a']])
        ->and($datos[0]['datos_nuevos'])->toMatchArray(['peso_kg' => '71.00', 'estatura_cm' => '175.0', 'equipo_id' => $m['a']])
        ->and($datos[1])->toMatchArray([
            'entidad_id' => $medicion, 'version' => 1, 'operacion' => 'crear', 'ocurrido_en' => '2026-11-15T18:00:00+00:00', 'motivo' => null, 'datos_anteriores' => null,
        ])
        ->and($datos[1]['datos_nuevos'])->toMatchArray(['peso_kg' => '70.00', 'fecha' => '2026-11-15']);
    historialFisicoSinCamposDeDa18($datos);
});

test('HU-26 c1 (DA-18): el historial de pruebas físicas entrega fecha, valor anterior y nuevo, sin autor ni registrado_por', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $prueba = historialFisicoProbar($this, $m['f1'], $m['a'], $m['velocidad'], 'e1@x.com');
    relojAdelantar('2026-11-15 18:00:07');
    historialFisicoProbar($this, $m['f1'], $m['a'], $m['velocidad'], 'e1@x.com', ['resultado' => '4.20', 'es_correccion' => true, 'motivo' => 'Cronómetro mal iniciado']);

    $datos = historialFisicoDatos($this, $m['f1'], 'pruebas-fisicas', 'e1@x.com');

    expect($datos)->toHaveCount(2)
        ->and($datos[0])->toMatchArray([
            'recurso' => 'pruebas-fisicas', 'entidad_id' => $prueba, 'version' => 2, 'operacion' => 'actualizar', 'ocurrido_en' => '2026-11-15T18:00:07+00:00',
            'motivo' => 'Cronómetro mal iniciado',
        ])
        ->and($datos[0]['datos_anteriores'])->toMatchArray(['resultado' => '4.35', 'tipo_prueba_id' => $m['velocidad']])
        ->and($datos[0]['datos_nuevos'])->toMatchArray(['resultado' => '4.20', 'tipo_prueba_id' => $m['velocidad']])
        ->and($datos[1])->toMatchArray(['entidad_id' => $prueba, 'version' => 1, 'operacion' => 'crear', 'motivo' => null, 'datos_anteriores' => null])
        ->and($datos[1]['datos_nuevos'])->toMatchArray(['resultado' => '4.35', 'fecha' => '2026-11-03']);
    historialFisicoSinCamposDeDa18($datos);
});

test('HU-26 c3 (§7.1 fila 7): E2 no recibe las mediciones de F registradas por E1', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    historialFisicoMedir($this, $m['f1'], $m['a'], 'e1@x.com');

    historialFisicoPedir($this, $m['f1'], 'mediciones', 'e2@x.com')
        ->assertOk()
        ->assertJsonPath('datos', [])
        ->assertJsonPath('meta.total', 0);

    // Con una medición propia, E2 recibe solo la suya.
    $deE2 = historialFisicoMedir($this, $m['f1'], $m['b'], 'e2@x.com');
    expect(array_column(historialFisicoDatos($this, $m['f1'], 'mediciones', 'e2@x.com'), 'entidad_id'))->toBe([$deE2]);
});

test('RN-14: el segundo entrenador no ve en el historial las mediciones ni las pruebas que no registró', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $medicion = historialFisicoMedir($this, $m['f1'], $m['a'], 'e1@x.com');
    $prueba = historialFisicoProbar($this, $m['f1'], $m['a'], $m['velocidad'], 'e1@x.com');

    expect(array_column(historialFisicoDatos($this, $m['f1'], 'mediciones', 'e1@x.com'), 'entidad_id'))->toBe([$medicion])
        ->and(array_column(historialFisicoDatos($this, $m['f1'], 'pruebas-fisicas', 'e1@x.com'), 'entidad_id'))->toBe([$prueba]);
    // E2 (otro equipo de F), E3 (entrena los dos equipos de F) y DE (docente y entrenador de A) no las registraron.
    foreach (['e2@x.com', 'e3@x.com', 'de@x.com'] as $correo) {
        foreach (RUTAS_DE_HISTORIAL_FISICO as $recurso) {
            historialFisicoPedir($this, $m['f1'], $recurso, $correo)
                ->assertOk()
                ->assertJsonPath('datos', [])
                ->assertJsonPath('meta.total', 0);
        }
    }
});

test('HU-26 c1 (§7.2 fila 2): una medición corregida con motivo lleva el motivo en la entrada actualizar', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    historialFisicoMedir($this, $m['f1'], $m['a'], 'e1@x.com');
    relojAdelantar('2026-11-15 18:00:07');
    historialFisicoMedir($this, $m['f1'], $m['a'], 'e1@x.com', ['peso_kg' => '71.00', 'es_correccion' => true, 'motivo' => 'Báscula mal calibrada']);

    $actualizar = array_values(array_filter(
        historialFisicoDatos($this, $m['f1'], 'mediciones', 'e1@x.com'),
        fn (array $entrada) => $entrada['operacion'] === 'actualizar',
    ));

    expect($actualizar)->toHaveCount(1)->and($actualizar[0]['motivo'])->toBe('Báscula mal calibrada');
});

test('Orden 404 antes de 422: jugador inexistente con por_pagina=0 responde 404', function (string $recurso) {
    /** @var TestCase $this */
    pruebasMundo();

    historialFisicoPedir($this, Identificador::generar()->valor(), $recurso, 'e1@x.com', '?por_pagina=0')
        ->assertNotFound()
        ->assertJsonPath('codigo', 'recurso_no_encontrado');
})->with(RUTAS_DE_HISTORIAL_FISICO);

test('HU-26: el historial de condición física cumple el contrato de OpenAPI (200, 401, 403, 404 y 422)', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    historialFisicoMedir($this, $m['f1'], $m['a'], 'e1@x.com');
    historialFisicoProbar($this, $m['f1'], $m['a'], $m['velocidad'], 'e1@x.com');
    $inexistente = Identificador::generar()->valor();

    foreach (RUTAS_DE_HISTORIAL_FISICO as $recurso) {
        $ruta = "/jugadores/{jugador}/historial/$recurso";
        $respuestas = [
            historialFisicoPedir($this, $m['f1'], $recurso, 'e1@x.com')->assertOk(),
            historialFisicoPedir($this, $m['f1'], $recurso, null)->assertUnauthorized(),
            historialFisicoPedir($this, $m['f1'], $recurso, 'd1@x.com')->assertForbidden(),
            historialFisicoPedir($this, $inexistente, $recurso, 'e1@x.com')->assertNotFound(),
            historialFisicoPedir($this, $m['f1'], $recurso, 'e1@x.com', '?por_pagina=0')->assertUnprocessable(),
        ];
        expect($respuestas[0]->json('datos'))->not->toBe([]);
        foreach ($respuestas as $respuesta) {
            expect($validador->validate($respuesta->baseResponse, $ruta, 'get'))->toBeTrue("GET $ruta {$respuesta->getStatusCode()}");
        }
    }
});

/**
 * Marca la fila con borrado lógico e inserta su entrada `eliminar` (D-5: ningún caso de uso de H5 borra; F1-H7-T06b).
 *
 * @param  array<string, mixed>  $anteriores
 */
function historialFisicoEliminar(string $tabla, string $columna, string $id, string $usuario, array $anteriores): void
{
    DB::connection('propietario')->table($tabla)->where('id', $id)->update(['eliminado_en' => '2026-11-15 17:00:00+00', 'eliminado_por' => $usuario]);
    DB::connection('propietario')->table("{$tabla}_historial")->insert([
        'id' => Identificador::generar()->valor(), $columna => $id, 'version' => 1, 'operacion' => 'eliminar',
        'datos_anteriores' => json_encode($anteriores), 'datos_nuevos' => null,
        'usuario_id' => $usuario, 'ocurrido_en' => '2026-11-15 17:00:00+00', 'motivo' => null,
    ]);
}

test('HU-26 c2 (DA-18): una medición con borrado lógico no aparece en la lectura pero sí en el historial', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    $medicion = medicionInsertar($m['f1'], $m['a'], $m['e1']);
    historialFisicoEliminar('mediciones_corporales', 'medicion_id', $medicion, $m['e1'], ['peso_kg' => '70.00', 'registrado_por' => $m['e1']]);

    $lectura = sesionesPeticion($this, 'GET', medicionesRuta($m['f1']), [], sesionesIniciar($this, 'e1@x.com'))->assertOk()->json('datos');
    $historial = historialFisicoDatos($this, $m['f1'], 'mediciones', 'e1@x.com');

    expect(array_column($lectura, 'id'))->not->toContain($medicion)
        ->and($historial)->toHaveCount(1)
        ->and($historial[0])->toMatchArray(['entidad_id' => $medicion, 'operacion' => 'eliminar', 'datos_nuevos' => null]);
    historialFisicoSinCamposDeDa18($historial);
});

test('HU-26 c2 (DA-18): una prueba física con borrado lógico no aparece en la lectura pero sí en el historial', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $prueba = pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad']);
    historialFisicoEliminar('pruebas_fisicas', 'prueba_id', $prueba, $m['e1'], ['resultado' => '4.35', 'registrado_por' => $m['e1']]);

    $lectura = sesionesPeticion($this, 'GET', pruebasRuta($m['f1']), [], sesionesIniciar($this, 'e1@x.com'))->assertOk()->json('datos');
    $historial = historialFisicoDatos($this, $m['f1'], 'pruebas-fisicas', 'e1@x.com');

    expect(array_column($lectura, 'id'))->not->toContain($prueba)
        ->and($historial)->toHaveCount(1)
        ->and($historial[0])->toMatchArray(['entidad_id' => $prueba, 'operacion' => 'eliminar', 'datos_nuevos' => null]);
    historialFisicoSinCamposDeDa18($historial);
});
