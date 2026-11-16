<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Aplicacion\EvaluacionesVigentesDelEquipo;
use Src\Compartido\Aplicacion\FaltasDeEntrenamiento;
use Src\Compartido\Aplicacion\JugadoresDeLaMateria;
use Src\Compartido\Aplicacion\MembresiasVigentes;
use Src\Compartido\Aplicacion\RiesgoAcademicoDelJugador;
use Src\Compartido\Aplicacion\TarjetasPorPartido;
use Src\Compartido\Aplicacion\TemporadaDeFecha;
use Src\Compartido\Dominio\Identificador;
use Tests\Ayudantes\NoNulo;
use Tests\TestCase;

/** Contrato de los puertos que Alertas lee de otros contextos (DA-112, DA-115, P-09), con PostgreSQL real. */
function puertoId(string $id): Identificador
{
    return Identificador::desdeTexto($id);
}

test('DA-112: TarjetasPorPartido devuelve las filas de todos los entrenadores del equipo', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    $partido = alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00');
    alertasTarjetas($partido, $m['j1'], $m['ea'], $m['a'], 1, 0);
    alertasTarjetas($partido, $m['j1'], $m['ea'], $m['a2'], 2, 1);

    $filas = app(TarjetasPorPartido::class)->de(puertoId($m['j1']), puertoId($m['ea']));

    $resumen = array_map(fn ($fila): array => [$fila->partidoId, $fila->amarillas, $fila->rojas], $filas);
    sort($resumen);
    expect($resumen)->toBe([[$partido, 1, 0], [$partido, 2, 1]]);
});

test('DA-112: TarjetasPorPartido no trae las filas eliminadas ni las de otro jugador', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    $partido = alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00');
    $eliminada = alertasTarjetas($partido, $m['j1'], $m['ea'], $m['a'], 1, 0);
    DB::connection('propietario')->table('rendimientos_partido')->where('id', $eliminada)->update(['eliminado_en' => '2026-03-02 00:00:00+00']);
    alertasTarjetas($partido, $m['j3'], $m['ea'], $m['a'], 2, 0);

    expect(app(TarjetasPorPartido::class)->de(puertoId($m['j1']), puertoId($m['ea'])))->toBe([]);
});

test('DA-112: FaltasDeEntrenamiento solo trae entrenamientos realizados y dentro de la ventana pedida', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasFalta($m, $m['j1'], '2026-03-01 18:00:00+00');
    alertasFalta($m, $m['j1'], '2026-03-02 18:00:00+00', 'falta_justificada');
    alertasFalta($m, $m['j1'], '2026-01-01 18:00:00+00');
    $programado = mundoEntrenamiento($m['ea'], $m['lugar'], $m['a'], '2026-03-03 18:00:00+00', '2026-03-03 19:00:00+00');
    mundoInsertar('asistencias_entrenamiento', ['entrenamiento_id' => $programado, 'jugador_id' => $m['j1'], 'estado' => 'falta']);
    alertasFalta($m, $m['j1'], '2026-03-04 18:00:00+00', 'presente');

    $faltas = app(FaltasDeEntrenamiento::class)->de(
        puertoId($m['j1']),
        puertoId($m['ea']),
        new DateTimeImmutable('2026-02-09', new DateTimeZone('America/Mexico_City')),
        new DateTimeImmutable('2026-03-10', new DateTimeZone('America/Mexico_City')),
    );

    $resumen = array_map(fn ($falta): array => [$falta->fecha->format('Y-m-d'), $falta->justificada], $faltas);
    sort($resumen);
    expect($resumen)->toBe([['2026-03-01', false], ['2026-03-02', true]]);
});

test('DA-112: TemporadaDeFecha devuelve la temporada que contiene la fecha, o nulo', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    $puerto = app(TemporadaDeFecha::class);

    expect($puerto->de(new DateTimeImmutable('2026-03-10'))?->valor())->toBe($m['t2026'])
        ->and($puerto->de(new DateTimeImmutable('2026-12-31'))?->valor())->toBe($m['t2026'])
        ->and($puerto->de(new DateTimeImmutable('2025-01-01'))?->valor())->toBe($m['t2025'])
        ->and($puerto->de(new DateTimeImmutable('2030-01-10')))->toBeNull();
});

test('DA-112: EvaluacionesVigentesDelEquipo devuelve la última evaluación de cada entrenador', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasEvaluacion($m['j1'], $m['ea'], $m['a'], 'no_pasa', '5.00', '2026-03-01 12:00:00+00');
    alertasEvaluacion($m['j1'], $m['ea'], $m['a'], 'pasa', '8.00', '2026-03-09 12:00:00+00');
    alertasEvaluacion($m['j1'], $m['ea'], $m['a2'], 'datos_insuficientes', null, '2026-03-05 12:00:00+00');

    $filas = app(EvaluacionesVigentesDelEquipo::class)->de(puertoId($m['j1']), puertoId($m['ea']));

    $resumen = array_map(fn ($fila): array => [$fila->entrenadorId, $fila->resultado, $fila->promedio], $filas);
    sort($resumen);
    $esperado = [[$m['a'], 'pasa', '8.00'], [$m['a2'], 'datos_insuficientes', null]];
    sort($esperado);
    expect($resumen)->toBe($esperado);
});

test('P-09: RiesgoAcademicoDelJugador calcula nivel, promedio y materias reprobadas sin leer resumenes_academicos', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasNivel($m, 'en_riesgo');

    $riesgo = NoNulo::exigir(app(RiesgoAcademicoDelJugador::class)->de(puertoId($m['j1'])), 'el riesgo académico');

    expect(DB::table('resumenes_academicos')->count())->toBe(0)
        ->and($riesgo->nivel)->toBe('en_riesgo')
        ->and($riesgo->promedio)->toBe('6.00')
        ->and($riesgo->materiasReprobadas)->toBe(1);
});

test('P-09: RiesgoAcademicoDelJugador devuelve nulo sin calificaciones y sin inscripciones', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    $puerto = app(RiesgoAcademicoDelJugador::class);

    expect($puerto->de(puertoId($m['j1'])))->toBeNull()
        ->and($puerto->de(puertoId($m['j4'])))->toBeNull();
});

test('P-09: RiesgoAcademicoDelJugador devuelve nulo cuando hoy no cae en ningún periodo', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasNivel($m, 'en_riesgo');
    alertasFijarReloj('2030-01-10 12:00:00');

    expect(app(RiesgoAcademicoDelJugador::class)->de(puertoId($m['j1'])))->toBeNull();
});

test('DA-115: JugadoresDeLaMateria devuelve los jugadores inscritos en la materia', function () {
    /** @var TestCase $this */
    $m = alertasMundo();

    $jugadores = array_map(fn (Identificador $jugador): string => $jugador->valor(), app(JugadoresDeLaMateria::class)->de(puertoId($m['m1'])));
    sort($jugadores);
    $esperados = [$m['j1'], $m['j3'], $m['j5']];
    sort($esperados);
    expect($jugadores)->toBe($esperados);
});

test('DA-115: MembresiasVigentes excluye a los jugadores en baja y trae una fila por membresía vigente', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    mundoMembresia($m['j5'], $m['ea']);
    mundoMembresia($m['j2'], $m['ea'], 'medio', '2026-02-01');

    $todas = array_map(fn ($membresia): array => [$membresia->jugadorId, $membresia->equipoId], app(MembresiasVigentes::class)->todas());

    expect($todas)->toContain([$m['j1'], $m['ea']])
        ->and($todas)->toContain([$m['j4'], null])
        ->and($todas)->toContain([$m['j2'], null])
        ->and(array_filter($todas, fn (array $fila): bool => $fila[0] === $m['j5']))->toBe([]);
});

test('DA-115: MembresiasVigentes del jugador devuelve sus membresías, y nada si está en baja', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    mundoMembresia($m['j5'], $m['ea']);
    $puerto = app(MembresiasVigentes::class);

    $deJ1 = array_map(fn ($membresia): array => [$membresia->jugadorId, $membresia->equipoId], $puerto->delJugador(puertoId($m['j1'])));

    expect($deJ1)->toBe([[$m['j1'], $m['ea']]])
        ->and($puerto->delJugador(puertoId($m['j5'])))->toBe([]);
});
