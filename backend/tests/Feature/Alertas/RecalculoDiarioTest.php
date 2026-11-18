<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Aplicacion\EvaluacionesVigentesDelEquipo;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/** El comando diario `alertas:recalcular` (HU-22, ADR-07, DA-87, DA-88, DA-113): idempotencia, cierre, baja, causa y programación. */

/** @param  array<string, string>  $m */
function diarioConTodasLasAlertas(array $m): void
{
    alertasNivel($m, 'en_riesgo');
    alertasEvaluacion($m['j1'], $m['ea'], $m['a'], 'no_pasa', '5.00');
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00'), $m['j1'], $m['ea'], $m['a'], 0, 1);
}

/** @return list<Event> */
function diarioEventosDe(string $comando): array
{
    return array_values(array_filter(
        app(Schedule::class)->events(),
        fn ($evento) => str_contains((string) $evento->command, $comando),
    ));
}

test('RN-21, RN-09, RN-10 (ADR-07): alertas:recalcular dos veces da la misma tabla, con los mismos id y fechas y sin duplicados', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    diarioConTodasLasAlertas($m);

    alertasRecalcular();
    $primera = alertasInstantanea();
    alertasRecalcular();

    expect($primera)->toHaveCount(3)
        ->and(alertasInstantanea())->toBe($primera);
});

test('DA-87: una alerta cerrada no se reabre; si la condición vuelve se crea una nueva', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasNivel($m, 'en_riesgo');
    alertasRecalcular();
    alertasNivel($m, 'sin_riesgo');
    alertasRecalcular();
    $cerrada = alertasUnica($m['j1'], ALERTA_ACADEMICA);
    expect($cerrada->cerrada_en)->not->toBeNull();

    alertasNivel($m, 'en_riesgo');
    alertasRecalcular();

    $filas = alertasFilas($m['j1'], ALERTA_ACADEMICA);
    expect($filas)->toHaveCount(2)
        ->and(alertasAbiertas($m['j1'], ALERTA_ACADEMICA))->toBe(1)
        ->and(collect($filas)->firstWhere('id', $cerrada->id)?->cerrada_en)->toBe($cerrada->cerrada_en);
});

test('DA-87: alertas:recalcular no toca activada_en ni cerrada_en de las alertas ya cerradas', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasNivel($m, 'sin_riesgo');
    alertaInsertar($m['j1'], ALERTA_ACADEMICA, null, ['dato' => 'x'], '2026-02-01 08:00:00+00');
    $antes = alertasInstantanea();

    alertasRecalcular();

    expect(alertasInstantanea())->toBe($antes);
});

test('P-08c (DA-99): un jugador en baja no recibe alertas nuevas y las abiertas se conservan', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    mundoMembresia($m['j5'], $m['ea']);
    mundoCalificar($m['i5'], 'final', '5.00');
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00'), $m['j5'], $m['ea'], $m['a'], 0, 1);
    alertaInsertar($m['j5'], ALERTA_ACADEMICA);
    $antes = alertasInstantanea();

    alertasRecalcular();

    expect(alertasInstantanea())->toBe($antes);
});

test('DA-91 (P-08b): sin temporada que contenga la fecha de cálculo no se cuentan tarjetas, pero 3 faltas sí activan', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00'), $m['j1'], $m['ea'], $m['a'], 0, 1);
    foreach (['2030-01-02', '2030-01-05', '2030-01-09'] as $dia) {
        alertasFalta($m, $m['j1'], "$dia 18:00:00+00");
    }
    alertasFijarReloj('2030-01-10 12:00:00');

    alertasRecalcular();

    expect(alertasCausa(alertasUnica($m['j1'], ALERTA_SANCIONES)))->toEqual(['tarjetas_amarillas' => 0, 'tarjetas_rojas' => 0, 'faltas_en_ventana' => 3]);
});

test('DA-91 (P-08b): sin temporada y sin faltas suficientes, no se activa la acumulación', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00'), $m['j1'], $m['ea'], $m['a'], 0, 1);
    alertasFijarReloj('2030-01-10 12:00:00');

    alertasRecalcular();

    expect(alertasFilas($m['j1'], ALERTA_SANCIONES))->toBe([]);
});

test('DA-105 (§6.2): la causa de la alerta académica lleva nivel_riesgo, promedio y materias_reprobadas', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasNivel($m, 'en_riesgo');

    alertasRecalcular();

    expect(alertasCausa(alertasUnica($m['j1'], ALERTA_ACADEMICA)))->toEqual(['nivel_riesgo' => 'en_riesgo', 'promedio' => '6.00', 'materias_reprobadas' => 1]);
});

test('DA-105 (§6.2): la causa de la alerta deportiva lleva resultado, promedio y acumulacion_sanciones', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasEvaluacion($m['j1'], $m['ea'], $m['a'], 'no_pasa', '5.00');

    alertasRecalcular();

    expect(alertasCausa(alertasUnica($m['j1'], ALERTA_DEPORTIVA)))->toEqual(['resultado' => 'no_pasa', 'promedio' => '5.00', 'acumulacion_sanciones' => false]);
});

test('DA-105 (§6.2): la causa de las sanciones lleva tarjetas_amarillas, tarjetas_rojas y faltas_en_ventana', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00'), $m['j1'], $m['ea'], $m['a'], 1, 1);
    alertasFalta($m, $m['j1'], '2026-03-02 18:00:00+00');

    alertasRecalcular();

    expect(alertasCausa(alertasUnica($m['j1'], ALERTA_SANCIONES)))->toEqual(['tarjetas_amarillas' => 1, 'tarjetas_rojas' => 1, 'faltas_en_ventana' => 1]);
});

test('DA-113: alertas:recalcular sale con 0 y no escribe texto', function () {
    /** @var TestCase $this */
    alertasMundo();

    $codigo = Artisan::call('alertas:recalcular');

    expect($codigo)->toBe(0)
        ->and(Artisan::output())->toBe('');
});

test('DA-113: alertas:recalcular sigue con los demás jugadores y sale con 1 si el recálculo de uno falla', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    $j2 = mundoJugador('M-9', 'Zeta', [$m['ea'] => 'medio']);
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00'), $m['j1'], $m['ea'], $m['a'], 0, 1);
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-02 18:00:00+00'), $j2, $m['ea'], $m['a'], 0, 1);
    app()->bind(EvaluacionesVigentesDelEquipo::class, fn () => new class($m['j1']) implements EvaluacionesVigentesDelEquipo
    {
        public function __construct(private readonly string $quienFalla) {}

        public function de(Identificador $jugador, Identificador $equipo): array
        {
            if ($jugador->valor() === $this->quienFalla) {
                throw new RuntimeException('falla simulada');
            }

            return [];
        }
    });

    consolaArtisan('alertas:recalcular')->assertExitCode(1);

    expect(alertasFilas($m['j1']))->toBe([])
        ->and(alertasAbiertas($j2, ALERTA_SANCIONES))->toBe(1);
});

test('RNF-09 (DA-100): sin el parámetro sanciones_deportivas vigente, el comando informa la falla y sale con 1', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    DB::connection('propietario')->table('parametros_institucionales')->where('clave', 'sanciones_deportivas')->update(['vigente_hasta' => '2026-01-02']);

    consolaArtisan('alertas:recalcular')->assertExitCode(1);

    expect(alertasFilas($m['j1']))->toBe([]);
});

test('DA-113: alertas:recalcular está programado a las 02:00 de America/Mexico_City', function () {
    /** @var TestCase $this */
    $eventos = diarioEventosDe('alertas:recalcular');

    expect($eventos)->toHaveCount(1)
        ->and($eventos[0]->expression)->toBe('0 2 * * *')
        ->and($eventos[0]->timezone instanceof DateTimeZone ? $eventos[0]->timezone->getName() : $eventos[0]->timezone)->toBe('America/Mexico_City');
});

test('DA-113: resumenes:conciliar está programado a las 02:00 de America/Mexico_City', function () {
    /** @var TestCase $this */
    $eventos = diarioEventosDe('resumenes:conciliar');

    expect($eventos)->toHaveCount(1)
        ->and($eventos[0]->expression)->toBe('0 2 * * *')
        ->and($eventos[0]->timezone instanceof DateTimeZone ? $eventos[0]->timezone->getName() : $eventos[0]->timezone)->toBe('America/Mexico_City');
});

test('DA-113: resumenes:conciliar concilia los resúmenes y no crea ni cierra alertas', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasNivel($m, 'en_riesgo');

    consolaArtisan('resumenes:conciliar')->assertExitCode(0);

    expect(alertasInstantanea())->toBe([])
        ->and(DB::table('resumenes_academicos')->where('jugador_id', $m['j1'])->count())->toBe(1);
});
