<?php

/*
 * Ayudantes de las pruebas de alertas (HU-22, docs/hitos/f1-h6.md). Viven aquí, y no dentro de un archivo de prueba,
 * porque los usan varios archivos y en paralelo cada proceso carga solo los suyos (DA-104).
 */

const ALERTA_ACADEMICA = 'riesgo_baja_academica';
const ALERTA_DEPORTIVA = 'riesgo_baja_deportiva';
const ALERTA_SANCIONES = 'acumulacion_sanciones_deportivas';

use Database\Seeders\CriterioGeneralSeeder;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Tests\Ayudantes\NoNulo;

function alertasRuta(string $jugador): string
{
    return "/api/v1/jugadores/$jugador/alertas";
}

function estatusRuta(string $consulta = ''): string
{
    return '/api/v1/estatus-de-jugadores'.($consulta === '' ? '' : "?$consulta");
}

/**
 * `avisosMundo()` (docs/hitos/f1-h5.md §7.0) más el criterio general de aprobación, que el detalle de materias del docente
 * necesita (HU-09).
 *
 * @return array<string, string>
 */
function estatusMundo(): array
{
    $mundo = avisosMundo();
    (new CriterioGeneralSeeder)->run();

    return $mundo;
}

/**
 * Inserta una alerta directo en la base, con la conexión del propietario: las pruebas de lectura no dependen del
 * recálculo (T03).
 *
 * @param  array<string, mixed>  $causa
 */
function alertaInsertar(string $jugador, string $tipo, ?string $equipo = null, array $causa = ['dato' => 'x'], ?string $cerradaEn = null, string $activadaEn = '2026-11-15 08:00:00+00'): string
{
    return mundoInsertar('alertas', [
        'tipo' => $tipo, 'jugador_id' => $jugador, 'equipo_id' => $equipo, 'causa' => json_encode($causa),
        'activada_en' => $activadaEn, 'cerrada_en' => $cerradaEn,
    ]);
}

/*
 * Datos de partida del recálculo de alertas (T03, docs/hitos/f1-h6.md §7.1, §7.3 y §7.4). Se inserta directo con la
 * conexión del propietario, como los demás mundos.
 */

function alertasFijarReloj(string $momentoUtc): void
{
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable($momentoUtc, new DateTimeZone('UTC'))));
}

/**
 * `mundoDeDocentes()` (J1 en EA con el entrenador A, inscrito en M1; reloj fijo en 2026-03-10; parámetros sembrados) más
 * el segundo entrenador A2 de EA (insertado directo, DA-114), la temporada T-2026 (todo 2026), la T-2025, un lugar y un rival.
 *
 * @return array<string, string>
 */
function alertasMundo(): array
{
    $m = mundoDeDocentes();
    $a2 = sesionesCrearUsuario('a2@x.com', ['entrenador']);
    mundoInsertar('entrenador_equipo', ['usuario_id' => $a2, 'equipo_id' => $m['ea'], 'desde' => '2026-01-01']);

    return [
        ...$m,
        'a2' => $a2,
        't2026' => mundoInsertar('temporadas', ['nombre' => 'T-2026', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-12-31']),
        't2025' => mundoInsertar('temporadas', ['nombre' => 'T-2025', 'fecha_inicio' => '2025-01-01', 'fecha_fin' => '2025-12-31']),
        'lugar' => mundoInsertar('lugares', ['nombre' => 'Cancha']),
        'rival' => mundoInsertar('rivales', ['nombre' => 'Rival']),
    ];
}

/** @param  array<string, string>  $m */
function alertasPartido(array $m, string $temporada, string $iniciaEn): string
{
    return mundoInsertar('partidos', [
        'equipo_id' => $m['ea'], 'temporada_id' => $temporada, 'rival_id' => $m['rival'], 'lugar_id' => $m['lugar'],
        'inicia_en' => $iniciaEn, 'tipo' => 'amistoso', 'condicion' => 'local', 'estado' => 'jugado', 'goles_favor' => 1, 'goles_contra' => 0,
    ]);
}

/** Una fila de `rendimientos_partido` con las tarjetas que registró un entrenador (DA-102, DA-103). */
function alertasTarjetas(string $partido, string $jugador, string $equipo, string $entrenador, int $amarillas, int $rojas): string
{
    return mundoInsertar('rendimientos_partido', [
        'partido_id' => $partido, 'jugador_id' => $jugador, 'equipo_id' => $equipo, 'registrado_por' => $entrenador,
        'posicion_id' => DB::table('posiciones')->where('codigo', 'medio')->value('id'), 'minutos' => 90, 'fue_titular' => true,
        'tarjetas_amarillas' => $amarillas, 'tarjetas_rojas' => $rojas, 'calificacion_general' => 7,
    ]);
}

/**
 * Un entrenamiento realizado con la asistencia del jugador (`falta` o `falta_justificada`).
 *
 * @param  array<string, string>  $m
 */
function alertasFalta(array $m, string $jugador, string $iniciaEn, string $estado = 'falta'): string
{
    $entrenamiento = mundoEntrenamiento($m['ea'], $m['lugar'], $m['a'], $iniciaEn, date('Y-m-d H:i:sP', strtotime($iniciaEn) + 3600), ['estado' => 'realizado']);

    return mundoInsertar('asistencias_entrenamiento', ['entrenamiento_id' => $entrenamiento, 'jugador_id' => $jugador, 'estado' => $estado]);
}

/** Una fila de `evaluaciones_rendimiento` (solo inserción): lo último que calculó un entrenador (DA-92). */
function alertasEvaluacion(string $jugador, string $equipo, string $entrenador, string $resultado, ?string $promedio, string $calculadoEn = '2026-03-09 12:00:00+00'): string
{
    return mundoInsertar('evaluaciones_rendimiento', [
        'jugador_id' => $jugador, 'equipo_id' => $equipo, 'entrenador_id' => $entrenador, 'calificacion_minima' => '6.00',
        'ventana_partidos' => 5, 'promedio' => $promedio, 'partidos_considerados' => $resultado === 'datos_insuficientes' ? 2 : 5,
        'resultado' => $resultado, 'calculado_en' => $calculadoEn,
    ]);
}

/**
 * J1 con una calificación final: 6.00 da en_riesgo con los umbrales de fábrica de `riesgo_academico` (DA-100).
 *
 * @param  array<string, string>  $m
 * @param  'en_riesgo'|'en_observacion'|'sin_riesgo'  $nivel
 */
function alertasNivel(array $m, string $nivel): void
{
    $valor = match ($nivel) {
        'en_riesgo' => '6.00', 'en_observacion' => '7.25', 'sin_riesgo' => '9.00',
    };
    $actualizadas = DB::connection('propietario')->table('calificaciones')->where('inscripcion_id', $m['i1'])->update(['valor' => $valor]);
    if ($actualizadas === 0) {
        mundoCalificar($m['i1'], 'final', $valor);
    }
}

function alertasRecalcular(): void
{
    consolaArtisan('alertas:recalcular')->assertExitCode(0);
}

/** @return list<stdClass> las alertas del jugador, de la más antigua a la más reciente */
function alertasFilas(string $jugador, ?string $tipo = null): array
{
    $consulta = DB::connection('propietario')->table('alertas')->where('jugador_id', $jugador);

    return array_values($consulta->when($tipo !== null, fn ($q) => $q->where('tipo', $tipo))->orderBy('activada_en')->orderBy('id')->get()->all());
}

function alertasAbiertas(string $jugador, string $tipo): int
{
    return DB::connection('propietario')->table('alertas')->where('jugador_id', $jugador)->where('tipo', $tipo)->whereNull('cerrada_en')->count();
}

/** La única alerta del jugador de ese tipo (abierta o cerrada); falla si hay otro número. */
function alertasUnica(string $jugador, string $tipo): stdClass
{
    $filas = alertasFilas($jugador, $tipo);
    expect($filas)->toHaveCount(1);

    return NoNulo::exigir($filas[0] ?? null, 'la alerta');
}

/** @return array<string, mixed> */
function alertasCausa(stdClass $alerta): array
{
    /** @var array<string, mixed> $causa */
    $causa = json_decode((string) $alerta->causa, true, flags: JSON_THROW_ON_ERROR);

    return $causa;
}

/** @return list<array<string, mixed>> toda la tabla `alertas`, para comparar corridas */
function alertasInstantanea(): array
{
    return array_values(array_map(fn (stdClass $fila): array => (array) $fila, DB::connection('propietario')->table('alertas')->orderBy('id')->get()->all()));
}
