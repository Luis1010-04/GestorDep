<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Src\Academico\Aplicacion\RecalcularResumenDeJugador;
use Src\Academico\Infraestructura\Escuchas\RecalcularResumenAcademico;
use Src\Compartido\Aplicacion\PublicadorDeEventos;
use Src\Compartido\Dominio\Eventos\AsistenciaDeClaseRegistrada;
use Src\Compartido\Dominio\Eventos\CalificacionRegistrada;
use Src\Compartido\Dominio\Eventos\CriterioDeMateriaModificado;
use Src\Compartido\Dominio\Eventos\JugadorInscrito;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Tests\TestCase;

/** Recálculo de `resumenes_academicos` (HU-07, HU-09, RN-04, RN-05, DD-45, DD-46, arquitectura §11) con datos sembrados directo. */
function recalculoFijarReloj(string $momento): void
{
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable($momento, new DateTimeZone('UTC'))));
}

function recalculoResumen(string $jugadorId, ?string $periodoId = null): ?stdClass
{
    $consulta = DB::table('resumenes_academicos')->where('jugador_id', $jugadorId);

    return ($periodoId === null ? $consulta : $consulta->where('periodo_id', $periodoId))->first();
}

function recalculoCalcular(string $jugadorId): bool
{
    return app(RecalcularResumenDeJugador::class)->ejecutar(Identificador::desdeTexto($jugadorId));
}

/** @return list<array<string, mixed>> */
function recalculoInstantanea(): array
{
    return array_values(array_map(fn (stdClass $fila): array => (array) $fila, DB::table('resumenes_academicos')->orderBy('jugador_id')->orderBy('periodo_id')->get(['jugador_id', 'periodo_id', 'promedio', 'materias_reprobadas', 'materias_sin_calificar', 'porcentaje_asistencia', 'nivel_riesgo', 'calculado_en'])->all()));
}

test('HU-07 (§7.10 fila 1, caso de uso): J1 inscrito sin calificaciones tiene resumen con promedio nulo y dato incompleto', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    expect(recalculoCalcular($m['j1']))->toBeTrue();

    $resumen = recalculoResumen($m['j1']);
    expect($resumen->periodo_id)->toBe($m['p1'])
        ->and($resumen->promedio)->toBeNull()
        ->and($resumen->materias_reprobadas)->toBe(0)
        ->and($resumen->materias_sin_calificar)->toBe(1)
        ->and($resumen->porcentaje_asistencia)->toBeNull()
        ->and($resumen->nivel_riesgo)->toBeNull();
});

test('RN-04 (§7.10 fila 2): con la final 8.00 el promedio es 8.00 y sin_riesgo', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    mundoCalificar($m['i1'], 'final', '8.00');

    recalculoCalcular($m['j1']);

    expect(recalculoResumen($m['j1']))->toMatchObject(['promedio' => '8.00', 'materias_reprobadas' => 0, 'materias_sin_calificar' => 0, 'nivel_riesgo' => 'sin_riesgo']);
});

test('RN-02: la final prevalece sobre los parciales y sin final se promedian los parciales', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    mundoCalificar($m['i1'], 'parcial', '8.00', 1);
    mundoCalificar($m['i1'], 'parcial', '7.00', 2);
    recalculoCalcular($m['j1']);
    expect(recalculoResumen($m['j1'])->promedio)->toBe('7.50');

    mundoCalificar($m['i1'], 'final', '6.00');
    recalculoCalcular($m['j1']);
    expect(recalculoResumen($m['j1']))->toMatchObject(['promedio' => '6.00', 'materias_reprobadas' => 1, 'nivel_riesgo' => 'en_riesgo']);
});

test('RN-04 (§7.10 fila 7): el riesgo de J3 es único por jugador con calificaciones de dos docentes', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    mundoCalificar($m['i3a'], 'final', '9.00');
    mundoCalificar($m['i3b'], 'final', '6.00');

    recalculoCalcular($m['j3']);

    expect(recalculoResumen($m['j3']))->toMatchObject(['promedio' => '7.50', 'materias_reprobadas' => 1, 'nivel_riesgo' => 'en_observacion']);
    expect(DB::table('resumenes_academicos')->where('jugador_id', $m['j3'])->count())->toBe(1);
});

test('RN-03 (§7.10 filas 5 y 6): el criterio propio vigente decide si la materia está aprobada', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    mundoCalificar($m['i1'], 'final', '7.50');
    recalculoCalcular($m['j1']);
    expect(recalculoResumen($m['j1'])->materias_reprobadas)->toBe(0);

    $propio = mundoCriterioPropio($m['m1'], '8.00');
    recalculoCalcular($m['j1']);
    expect(recalculoResumen($m['j1'])->materias_reprobadas)->toBe(1);

    DB::connection('propietario')->table('calificaciones')->where('inscripcion_id', $m['i1'])->update(['valor' => '8.00']);
    recalculoCalcular($m['j1']);
    expect(recalculoResumen($m['j1'])->materias_reprobadas)->toBe(0);

    DB::connection('propietario')->table('criterios_aprobacion')->where('id', $propio)->update(['calificacion_minima' => '6.00']);
    DB::connection('propietario')->table('calificaciones')->where('inscripcion_id', $m['i1'])->update(['valor' => '6.50']);
    recalculoCalcular($m['j1']);
    expect(recalculoResumen($m['j1'])->materias_reprobadas)->toBe(0);
});

test('RN-05 (§7.10 fila 8): 7 presentes, 1 falta justificada y 2 faltas dan 80.00 y la asistencia no entra en el riesgo', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    mundoCalificar($m['i1'], 'final', '9.00');
    foreach (range(1, 7) as $dia) {
        mundoAsistir($m['i1'], sprintf('2026-02-%02d', $dia), 'presente');
    }
    mundoAsistir($m['i1'], '2026-02-08', 'falta_justificada');
    mundoAsistir($m['i1'], '2026-02-09', 'falta');
    mundoAsistir($m['i1'], '2026-02-10', 'falta');

    recalculoCalcular($m['j1']);

    expect(recalculoResumen($m['j1']))->toMatchObject(['porcentaje_asistencia' => '80.00', 'nivel_riesgo' => 'sin_riesgo']);
});

test('RNF-02: las calificaciones y las inscripciones eliminadas no cuentan', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $calificacion = mundoCalificar($m['i1'], 'final', '3.00');
    DB::connection('propietario')->table('calificaciones')->where('id', $calificacion)->update(['eliminado_en' => '2026-03-01 00:00:00+00', 'eliminado_por' => $m['d1']]);
    recalculoCalcular($m['j1']);
    expect(recalculoResumen($m['j1']))->toMatchObject(['promedio' => null, 'materias_sin_calificar' => 1]);

    DB::connection('propietario')->table('inscripciones')->where('id', $m['i1'])->update(['eliminado_en' => '2026-03-01 00:00:00+00', 'eliminado_por' => $m['d1']]);
    recalculoCalcular($m['j1']);
    expect(recalculoResumen($m['j1']))->toMatchObject(['promedio' => null, 'materias_sin_calificar' => 0]);
});

test('DD-45 (§7.10 fila 15, caso de uso): sin periodo vigente no se calcula ni se escribe ningún resumen', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    recalculoFijarReloj('2026-07-15 12:00:00');

    expect(recalculoCalcular($m['j1']))->toBeFalse()
        ->and(DB::table('resumenes_academicos')->count())->toBe(0);
});

test('DD-45 (§7.10 fila 16): al cambiar el día vigente, alertas:recalcular crea el resumen del periodo P2 con valores nulos', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    mundoCalificar($m['i1'], 'final', '8.00');
    consolaArtisan('alertas:recalcular')->assertSuccessful();
    expect(recalculoResumen($m['j1'], $m['p1'])->promedio)->toBe('8.00');

    recalculoFijarReloj('2026-08-03 12:00:00');
    consolaArtisan('alertas:recalcular')->assertSuccessful();

    expect(recalculoResumen($m['j1'], $m['p2']))->toMatchObject(['promedio' => null, 'materias_reprobadas' => 0, 'materias_sin_calificar' => 0, 'nivel_riesgo' => null])
        ->and(recalculoResumen($m['j1'], $m['p1'])->promedio)->toBe('8.00');
});

test('HU-09 (§7.10 fila 17): alertas:recalcular dos veces da el mismo resultado, sin filas duplicadas, con código 0', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    mundoCalificar($m['i1'], 'final', '8.00');
    mundoCalificar($m['i3a'], 'final', '9.00');
    mundoCalificar($m['i3b'], 'final', '6.00');

    consolaArtisan('alertas:recalcular')->assertExitCode(0);
    $primera = recalculoInstantanea();
    consolaArtisan('alertas:recalcular')->assertExitCode(0);

    // J1, J2, J3 y J5 tienen inscripciones; J4 no.
    expect($primera)->toHaveCount(4)
        ->and(recalculoInstantanea())->toEqual($primera)
        ->and(DB::table('resumenes_academicos')->where('jugador_id', $m['j4'])->count())->toBe(0);
});

test('HU-09 (§7.10 fila 18): una fila borrada de resumenes_academicos se reconstruye con los mismos valores', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    mundoCalificar($m['i1'], 'final', '7.20');
    consolaArtisan('alertas:recalcular')->assertSuccessful();
    $antes = recalculoInstantanea();

    DB::table('resumenes_academicos')->where('jugador_id', $m['j1'])->delete();
    expect(recalculoResumen($m['j1']))->toBeNull();
    consolaArtisan('alertas:recalcular')->assertSuccessful();

    expect(recalculoInstantanea())->toEqual($antes);
});

test('HU-07 (§7.10 fila 19): el mismo evento procesado dos veces deja una sola fila con los mismos valores', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    mundoCalificar($m['i1'], 'final', '8.00');
    $evento = new CalificacionRegistrada($m['i1'], $m['m1'], $m['j1']);

    Event::dispatch($evento);
    $primera = recalculoInstantanea();
    Event::dispatch($evento);

    expect(DB::table('resumenes_academicos')->count())->toBe(1)
        ->and(recalculoInstantanea())->toEqual($primera)
        ->and($primera[0]['promedio'])->toBe('8.00');
});

test('HU-07 (§7.10 fila 22): calculado_en es el instante del reloj truncado a segundos', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    recalculoFijarReloj('2026-03-10 12:00:00.900');

    recalculoCalcular($m['j1']);

    expect((new DateTimeImmutable(recalculoResumen($m['j1'])->calculado_en))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.u'))->toBe('2026-03-10 12:00:00.000000');
});

test('HU-09 (§7.10 fila 24): alertas:recalcular está programado a las 02:00 de America/Mexico_City', function () {
    /** @var TestCase $this */
    $eventos = array_values(array_filter(
        app(Schedule::class)->events(),
        fn ($evento) => str_contains((string) $evento->command, 'alertas:recalcular'),
    ));

    expect($eventos)->toHaveCount(1)
        ->and($eventos[0]->expression)->toBe('0 2 * * *')
        ->and($eventos[0]->timezone instanceof DateTimeZone ? $eventos[0]->timezone->getName() : $eventos[0]->timezone)->toBe('America/Mexico_City');
});

test('CONSOLA (§7.12 fila 8): alertas:recalcular sin datos de H2 sale con 0 y no crea filas', function () {
    /** @var TestCase $this */
    sesionesPrepararBase();

    consolaArtisan('alertas:recalcular')->assertExitCode(0);

    expect(DB::table('resumenes_academicos')->count())->toBe(0);
});

test('RNF-09: sin el parámetro riesgo_academico el recálculo falla y no escribe nada (reintentable)', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    DB::connection('propietario')->table('parametros_institucionales')->delete();

    expect(fn () => recalculoCalcular($m['j1']))->toThrow(LogicException::class)
        ->and(DB::table('resumenes_academicos')->count())->toBe(0);
});

test('HU-07: el escucha recalcula al jugador de cada evento y, ante un criterio nuevo, a todos los de la materia', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    Event::dispatch(new AsistenciaDeClaseRegistrada($m['i1'], $m['m1'], $m['j1']));
    expect(DB::table('resumenes_academicos')->pluck('jugador_id')->all())->toBe([$m['j1']]);

    Event::dispatch(new JugadorInscrito($m['i3b'], $m['m2'], $m['j3']));
    expect(DB::table('resumenes_academicos')->pluck('jugador_id')->sort()->values()->all())->toEqual(collect([$m['j1'], $m['j3']])->sort()->values()->all());

    DB::table('resumenes_academicos')->delete();
    Event::dispatch(new CriterioDeMateriaModificado($m['m1']));
    // M1 tiene inscritos J1, J3 y J5 (en baja); J2 es de M2.
    expect(DB::table('resumenes_academicos')->pluck('jugador_id')->sort()->values()->all())
        ->toEqual(collect([$m['j1'], $m['j3'], $m['j5']])->sort()->values()->all());
});

test('arquitectura §11: el escucha va en cola, después del commit, y los eventos solo llevan identificadores', function () {
    $escucha = new ReflectionClass(RecalcularResumenAcademico::class);
    expect($escucha->implementsInterface(ShouldQueue::class))->toBeTrue()
        ->and($escucha->getProperty('afterCommit')->getDefaultValue())->toBeTrue();

    foreach ([CalificacionRegistrada::class, AsistenciaDeClaseRegistrada::class, JugadorInscrito::class, CriterioDeMateriaModificado::class] as $clase) {
        $constructor = (new ReflectionClass($clase))->getConstructor();
        expect($constructor)->not->toBeNull();
        foreach ($constructor->getParameters() as $parametro) {
            expect((string) $parametro->getType())->toBe('string');
        }
    }
});

test('arquitectura §11: el publicador despacha después del commit y nada si la transacción se revierte', function () {
    /** @var TestCase $this */
    Event::fake([JugadorInscrito::class]);
    $publicador = app(PublicadorDeEventos::class);
    $evento = new JugadorInscrito('i', 'm', 'j');

    DB::transaction(function () use ($publicador, $evento): void {
        $publicador->publicar($evento);
        Event::assertNotDispatched(JugadorInscrito::class);
    });
    Event::assertDispatchedTimes(JugadorInscrito::class, 1);

    try {
        DB::transaction(function () use ($publicador, $evento): void {
            $publicador->publicar($evento);

            throw new RuntimeException('revierte');
        });
    } catch (RuntimeException) {
    }
    Event::assertDispatchedTimes(JugadorInscrito::class, 1);

    $publicador->publicar($evento);
    Event::assertDispatchedTimes(JugadorInscrito::class, 2);
});
