<?php

/*
 * Datos de partida de docs/hitos/f1-h4.md §7.0: E1 entrena A, E2 entrena B, E3 entrena A y B; D es docente. P1 portero,
 * D1 defensa, M1 medio y F1 delantero en A, F1 también en B; J2 en A sin convocar. PJ1 jugado de A con P1, D1, M1 y F1
 * convocados; PP1 programado de A con P1. Se inserta directo con la conexion del propietario.
 */

use Database\Seeders\MetricasSeeder;
use Database\Seeders\PlantillasDeFabricaSeeder;
use Database\Seeders\PosicionesSeeder;
use Illuminate\Support\Facades\DB;

/** @return array<string, string> */
function capturaMundo(): array
{
    (new PosicionesSeeder)->run();
    (new MetricasSeeder)->run();
    $e1 = sesionesCrearUsuario('e1@x.com', ['entrenador']);
    $e2 = sesionesCrearUsuario('e2@x.com', ['entrenador']);
    $e3 = sesionesCrearUsuario('e3@x.com', ['entrenador']);
    sesionesCrearUsuario('d@x.com', ['docente']);
    $categoria = mundoInsertar('categorias', ['nombre' => 'Sub-17']);
    $a = mundoEquipo('A', $categoria, $e1);
    $b = mundoEquipo('B', $categoria, $e2);
    mundoInsertar('entrenador_equipo', ['usuario_id' => $e3, 'equipo_id' => $a, 'desde' => '2026-01-01']);
    mundoInsertar('entrenador_equipo', ['usuario_id' => $e3, 'equipo_id' => $b, 'desde' => '2026-01-01']);
    $partido = [
        'temporada_id' => mundoInsertar('temporadas', ['nombre' => 'T1', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-12-15']),
        'rival_id' => mundoInsertar('rivales', ['nombre' => 'R1']),
        'lugar_id' => mundoInsertar('lugares', ['nombre' => 'Cancha norte']),
        'tipo' => 'amistoso', 'condicion' => 'local',
    ];
    $m = [
        'e1' => $e1, 'e2' => $e2, 'e3' => $e3, 'a' => $a, 'b' => $b,
        'p1' => mundoJugador('M-P1', 'Portero', [$a => 'portero'], nombres: 'P1'),
        'd1' => mundoJugador('M-D1', 'Defensa', [$a => 'defensa'], nombres: 'D1'),
        'm1' => mundoJugador('M-M1', 'Medio', [$a => 'medio'], nombres: 'M1'),
        'f1' => mundoJugador('M-F1', 'Delantero', [$a => 'delantero', $b => 'delantero'], nombres: 'F1'),
        'j2' => mundoJugador('M-J2', 'Dos', [$a => 'medio'], nombres: 'J2'),
        'pj1' => mundoInsertar('partidos', [...$partido, 'equipo_id' => $a, 'inicia_en' => '2026-09-12 23:00:00+00', 'estado' => 'jugado', 'goles_favor' => 2, 'goles_contra' => 1]),
        'pp1' => mundoInsertar('partidos', [...$partido, 'equipo_id' => $a, 'inicia_en' => '2026-09-26 23:00:00+00', 'estado' => 'programado']),
    ];
    foreach (['p1', 'd1', 'm1', 'f1'] as $jugador) {
        mundoInsertar('convocatorias', ['partido_id' => $m['pj1'], 'jugador_id' => $m[$jugador]]);
    }
    mundoInsertar('convocatorias', ['partido_id' => $m['pp1'], 'jugador_id' => $m['p1']]);

    return $m;
}

/** @return list<array{metrica_id: string, valor: int}> las métricas obligatorias de la posición, con valor 1 */
function capturaMetricasDe(string $posicion): array
{
    $codigos = [
        'portero' => ['goles', 'asistencias', 'atajadas', 'goles_recibidos'],
        'defensa' => ['goles', 'asistencias', 'recuperaciones', 'faltas_cometidas'],
        'medio' => ['goles', 'asistencias', 'pases_completados', 'balones_perdidos'],
        'delantero' => ['goles', 'asistencias', 'tiros_a_porteria'],
    ][$posicion];
    $ids = DB::table('metricas')->pluck('id', 'codigo');

    return array_map(fn (string $codigo) => ['metrica_id' => (string) $ids[$codigo], 'valor' => 1], $codigos);
}

function capturaMetrica(string $codigo): string
{
    return (string) DB::table('metricas')->where('codigo', $codigo)->value('id');
}

/** @return array<string, string> el mundo de §7.0 con las plantillas «Desarrollo» de fábrica sembradas */
function umbralMundo(): array
{
    $m = capturaMundo();
    (new PlantillasDeFabricaSeeder)->run();

    return $m;
}

function umbralPosicion(string $codigo): string
{
    return (string) DB::table('posiciones')->where('codigo', $codigo)->value('id');
}

function umbralPlantillaDeFabrica(string $posicion): string
{
    return (string) DB::table('plantillas_evaluacion')->whereNull('creada_por')->where('posicion_id', umbralPosicion($posicion))->value('id');
}

/**
 * @param  array<string, mixed>  $cambios
 * @return array<string, mixed>
 */
function capturaCuerpo(string $jugador, string $posicion, array $cambios = []): array
{
    return [
        'jugador_id' => $jugador, 'minutos' => 90, 'fue_titular' => true, 'tarjetas_amarillas' => 0, 'tarjetas_rojas' => 0,
        'calificacion_general' => 7, 'observacion' => 'Buen partido', 'metricas' => capturaMetricasDe($posicion), ...$cambios,
    ];
}
