<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/**
 * Vista consolidada de la convocatoria (HU-12 c2, DA-96; docs/hitos/f1-h6.md §6.1 y §6.2). Mundo de H4 con reloj en
 * 2026-10-01: E1 entrena A, E2 entrena B, E3 entrena A y B. El partido jugado PJ1 de A convoca a P1, D1, M1 y F1; el
 * programado PP1 de A convoca a P1.
 */

/** Los datos de la ruta de convocatoria de H3 y de la vista, página por página, para compararlas. */
function vistaConvRuta(string $partido, string $consulta = ''): string
{
    return "/api/v1/partidos/$partido/convocatoria/vista".($consulta === '' ? '' : "?$consulta");
}

function vistaConvRutaDeH3(string $partido, string $consulta = ''): string
{
    return "/api/v1/partidos/$partido/convocatoria".($consulta === '' ? '' : "?$consulta");
}

/**
 * @param  array<string, string>  $m
 * @return string el id de un partido más del equipo indicado, con la temporada, el rival y el lugar del mundo
 */
function vistaConvPartido(array $m, string $equipo, string $estado = 'programado', string $iniciaEn = '2026-10-20 23:00:00+00'): string
{
    return mundoInsertar('partidos', [
        'equipo_id' => $equipo, 'temporada_id' => (string) DB::table('temporadas')->value('id'), 'rival_id' => (string) DB::table('rivales')->value('id'),
        'lugar_id' => $m['lugar'], 'inicia_en' => $iniciaEn, 'tipo' => 'amistoso', 'condicion' => 'local', 'estado' => $estado,
    ]);
}

/**
 * @param  list<string>  $ids
 * @return list<string>
 */
function vistaConvOrdenados(array $ids): array
{
    sort($ids);

    return $ids;
}

/**
 * Ids de jugador, en orden, de una página de la ruta de H3 (`jugador_id`) o de la vista (`jugador.id`), y su meta.
 *
 * @return array{jugadores: list<string>, meta: array<string, int>}
 */
function vistaConvPagina(TestCase $prueba, string $ruta, string $correo, bool $esVista): array
{
    $respuesta = convocarPedir($prueba, $ruta, $correo)->assertOk();
    /** @var list<array<string, mixed>> $filas */
    $filas = $respuesta->json('datos');

    return [
        'jugadores' => array_map(fn (array $fila): string => $esVista ? (string) $fila['jugador']['id'] : (string) $fila['jugador_id'], $filas),
        'meta' => $respuesta->json('meta'),
    ];
}

test('HU-12 c2 (DA-96): la ruta devuelve la vista de cada convocado, idéntica a vista-para-convocar con el equipo del partido', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarEvaluar($m['p1'], $m['a'], $m['e1'], 'pasa', '8.00');
    convocarMedir($m['d1'], $m['a'], $m['e1'], '2026-09-20');

    $filas = convocarPedir($this, vistaConvRuta($m['pj1']))->assertOk()->json('datos');

    expect($filas)->toHaveCount(4);
    foreach ($filas as $fila) {
        $individual = convocarPedir($this, convocarRuta($fila['jugador']['id'], "filtro[equipo_id]={$m['a']}"))->assertOk()->json('datos');
        expect($fila)->toBe($individual);
    }
});

test('DA-96 (aditivo): GET /partidos/{partido}/convocatoria sigue devolviendo solo los seis campos de H3 y su meta', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    $respuesta = convocarPedir($this, vistaConvRutaDeH3($m['pj1']))->assertOk();

    expect($respuesta->json('datos.0'))->toHaveKeys(['id', 'partido_id', 'jugador_id', 'posicion_id', 'rol_alineacion', 'actualizado_en'])
        ->and(array_keys($respuesta->json('datos.0')))->toHaveCount(6)
        ->and(array_keys($respuesta->json('meta')))->toBe(['pagina', 'por_pagina', 'total', 'total_paginas'])
        ->and($respuesta->json('meta.total'))->toBe(4);
});

test('DA-96 (HU-12 c2): la vista de la convocatoria no trae campos de la convocatoria, solo las siete dimensiones', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    $fila = convocarPedir($this, vistaConvRuta($m['pj1']))->assertOk()->json('datos.0');

    expect(array_keys($fila))->toBe(['jugador', 'disponibilidad', 'rendimiento_reciente', 'situacion_academica', 'asistencia', 'estado_fisico', 'progreso']);
});

test('DA-96 (convenciones §1): la vista pagina con 15 por defecto, máximo 100, y meta como la ruta de H3', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    $respuesta = convocarPedir($this, vistaConvRuta($m['pj1']))->assertOk();

    expect($respuesta->json('meta'))->toBe(['pagina' => 1, 'por_pagina' => 15, 'total' => 4, 'total_paginas' => 1]);
    convocarPedir($this, vistaConvRuta($m['pj1'], 'por_pagina=101'))->assertUnprocessable();
    convocarPedir($this, vistaConvRuta($m['pj1'], 'pagina=0'))->assertUnprocessable();
});

test('DA-96 (paridad con H3): página por página, la vista y la ruta de convocatoria dan los mismos jugadores, el mismo orden y el mismo meta', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    mundoInsertar('convocatorias', ['partido_id' => $m['pj1'], 'jugador_id' => $m['j2']]);

    foreach (['por_pagina=2&pagina=1', 'por_pagina=2&pagina=2', 'por_pagina=2&pagina=3', 'por_pagina=2&pagina=4', ''] as $consulta) {
        $deH3 = vistaConvPagina($this, vistaConvRutaDeH3($m['pj1'], $consulta), 'e1@x.com', false);
        $deLaVista = vistaConvPagina($this, vistaConvRuta($m['pj1'], $consulta), 'e1@x.com', true);

        expect($deLaVista['jugadores'])->toBe($deH3['jugadores'], "página «{$consulta}»")
            ->and($deLaVista['meta'])->toBe($deH3['meta'], "meta «{$consulta}»");
    }
    expect(vistaConvPagina($this, vistaConvRuta($m['pj1'], 'por_pagina=2&pagina=3'), 'e1@x.com', true)['jugadores'])->toHaveCount(1);
});

test('DA-96: la vista no filtra por el estado del partido, igual que H3', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    $cancelado = vistaConvPartido($m, $m['a'], 'cancelado', '2026-10-25 23:00:00+00');
    mundoInsertar('convocatorias', ['partido_id' => $cancelado, 'jugador_id' => $m['m1']]);

    foreach ([$m['pp1'] => [$m['p1']], $m['pj1'] => [$m['p1'], $m['d1'], $m['m1'], $m['f1']], $cancelado => [$m['m1']]] as $partido => $jugadores) {
        $deLaVista = vistaConvPagina($this, vistaConvRuta((string) $partido), 'e1@x.com', true)['jugadores'];

        expect(vistaConvOrdenados($deLaVista))->toBe(vistaConvOrdenados($jugadores))
            ->and($deLaVista)->toBe(vistaConvPagina($this, vistaConvRutaDeH3((string) $partido), 'e1@x.com', false)['jugadores']);
    }
});

test('HU-29 (DA-29, paridad con H3): un convocado dado de baja se omite de datos y de meta.total, igual que en H3', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    DB::connection('propietario')->table('jugadores')->where('id', $m['d1'])->update(['estatus_deportivo' => 'baja', 'estatus_academico' => 'baja']);

    $deH3 = vistaConvPagina($this, vistaConvRutaDeH3($m['pj1']), 'e1@x.com', false);
    $deLaVista = vistaConvPagina($this, vistaConvRuta($m['pj1']), 'e1@x.com', true);

    expect(vistaConvOrdenados($deLaVista['jugadores']))->toBe(vistaConvOrdenados([$m['p1'], $m['m1'], $m['f1']]))
        ->and($deLaVista['jugadores'])->toBe($deH3['jugadores'])
        ->and($deLaVista['meta']['total'])->toBe(3)
        ->and($deLaVista['meta'])->toBe($deH3['meta']);
});

test('RNF-01 (paridad con H3): un convocado sin membresía vigente en ningún equipo del usuario se omite, sin 404, y no cuenta en meta.total', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    DB::connection('propietario')->table('jugador_equipo')->where('jugador_id', $m['m1'])->where('equipo_id', $m['a'])->update(['hasta' => '2026-09-01']);

    $deH3 = vistaConvPagina($this, vistaConvRutaDeH3($m['pj1']), 'e1@x.com', false);
    $deLaVista = vistaConvPagina($this, vistaConvRuta($m['pj1']), 'e1@x.com', true);

    expect(vistaConvOrdenados($deLaVista['jugadores']))->toBe(vistaConvOrdenados([$m['p1'], $m['d1'], $m['f1']]))
        ->and($deLaVista['jugadores'])->toBe($deH3['jugadores'])
        ->and($deLaVista['meta']['total'])->toBe(3)
        ->and($deLaVista['meta'])->toBe($deH3['meta']);
});

test('DA-96 (matiz de alcance heredado de H3): un convocado que dejó el equipo del partido pero sigue en otro equipo del usuario aparece, con el rendimiento del equipo del partido', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    $deB = vistaConvPartido($m, $m['b']);
    mundoInsertar('convocatorias', ['partido_id' => $deB, 'jugador_id' => $m['f1']]);
    DB::connection('propietario')->table('jugador_equipo')->where('jugador_id', $m['f1'])->where('equipo_id', $m['b'])->update(['hasta' => '2026-09-01']);
    convocarEvaluar($m['f1'], $m['a'], $m['e3'], 'pasa', '8.00', '2026-09-25 12:00:00+00');
    convocarEvaluar($m['f1'], $m['b'], $m['e3'], 'pasa', '9.00', '2026-09-10 12:00:00+00');

    $deLaVista = convocarPedir($this, vistaConvRuta($deB), 'e3@x.com')->assertOk();
    $deH3 = vistaConvPagina($this, vistaConvRutaDeH3($deB), 'e3@x.com', false);

    expect($deH3['jugadores'])->toBe([$m['f1']])
        ->and($deLaVista->json('datos.0.jugador.id'))->toBe($m['f1'])
        ->and($deLaVista->json('datos.0.rendimiento_reciente.promedio'))->toBe('9.00');
    // E2 entrena solo B: para él F1 ya no está en su alcance y se omite, como en H3.
    expect(convocarPedir($this, vistaConvRuta($deB), 'e2@x.com')->assertOk()->json('datos'))->toBe([])
        ->and(vistaConvPagina($this, vistaConvRutaDeH3($deB), 'e2@x.com', false)['jugadores'])->toBe([]);
});

test('RN-14 (DD-17): con dos entrenadores en el equipo del partido, cada uno ve en la vista solo sus evaluaciones', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarEvaluar($m['f1'], $m['a'], $m['e1'], 'no_pasa', '5.00');
    convocarEvaluar($m['f1'], $m['a'], $m['e3'], 'pasa', '8.00');

    $promedio = function (string $correo) use ($m): ?string {
        /** @var list<array{jugador: array{id: string}, rendimiento_reciente: array{promedio: string|null}}> $filas */
        $filas = convocarPedir($this, vistaConvRuta($m['pj1']), $correo)->assertOk()->json('datos');

        foreach ($filas as $fila) {
            if ($fila['jugador']['id'] === $m['f1']) {
                return $fila['rendimiento_reciente']['promedio'];
            }
        }

        return null;
    };

    expect($promedio('e1@x.com'))->toBe('5.00')
        ->and($promedio('e3@x.com'))->toBe('8.00');
});

test('DA-96: una convocatoria vacía da una lista vacía con total 0', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    $vacio = vistaConvPartido($m, $m['a']);

    $respuesta = convocarPedir($this, vistaConvRuta($vacio))->assertOk();

    expect($respuesta->json('datos'))->toBe([])
        ->and($respuesta->json('meta'))->toBe(['pagina' => 1, 'por_pagina' => 15, 'total' => 0, 'total_paginas' => 1]);
});

test('RNF-01: un partido inexistente, con id que no es v7, eliminado o de un equipo que el usuario no entrena responde el mismo 404, byte a byte', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    $eliminado = vistaConvPartido($m, $m['a'], 'programado', '2026-10-27 23:00:00+00');
    DB::connection('propietario')->table('partidos')->where('id', $eliminado)->update(['eliminado_en' => '2026-10-01 00:00:00+00']);
    $deB = vistaConvPartido($m, $m['b']);

    $ajeno = convocarPedir($this, vistaConvRuta($deB))->assertNotFound();
    $inexistente = convocarPedir($this, vistaConvRuta(Identificador::generar()->valor()))->assertNotFound();
    $noV7 = convocarPedir($this, vistaConvRuta('00000000-0000-0000-0000-000000000000'))->assertNotFound();
    $borrado = convocarPedir($this, vistaConvRuta($eliminado))->assertNotFound();

    // El cuerpo es exactamente {codigo, mensaje, errores}: el esquema `Error` de docs/api/openapi.yaml (líneas 7516 a 7532). El
    // mensaje sale de backend/lang/es/errores.php, línea 7 (clave `recurso_no_encontrado`), y la prueba lo pide con `__()` para
    // que un cambio en lang/es se refleje aquí.
    foreach (['ajeno' => $ajeno, 'inexistente' => $inexistente, 'no v7' => $noV7, 'eliminado' => $borrado] as $caso => $respuesta) {
        expect($respuesta->getStatusCode())->toBe(404, $caso)
            ->and($respuesta->json())->toBe(['codigo' => 'recurso_no_encontrado', 'mensaje' => __('errores.recurso_no_encontrado'), 'errores' => []], $caso);
    }

    expect($inexistente->getContent())->toBe($ajeno->getContent())
        ->and($noV7->getContent())->toBe($ajeno->getContent())
        ->and($borrado->getContent())->toBe($ajeno->getContent())
        ->and(convocarPedir($this, vistaConvRutaDeH3($deB))->assertNotFound()->getContent())->toBe($ajeno->getContent());
});

test('HU-12 c2: el docente recibe 403 y sin sesión 401', function () {
    /** @var TestCase $this */
    $m = convocarMundo();

    convocarPedir($this, vistaConvRuta($m['pj1']), 'd@x.com')->assertForbidden();
    sesionesPeticion($this, 'GET', vistaConvRuta($m['pj1']))->assertUnauthorized();
});

test('DA-96: la vista de la convocatoria no escribe nada en la base', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    convocarEvaluar($m['p1'], $m['a'], $m['e1'], 'pasa', '8.00');
    $contar = fn (): array => [DB::table('convocatorias')->count(), DB::table('evaluaciones_rendimiento')->count(), DB::table('alertas')->count()];
    $antes = $contar();

    convocarPedir($this, vistaConvRuta($m['pj1']))->assertOk();

    expect($contar())->toBe($antes);
});
