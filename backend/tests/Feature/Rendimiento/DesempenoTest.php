<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Desempeño: pasa, no pasa y datos insuficientes (HU-16, RN-08; docs/hitos/f1-h4.md §7.3 filas 1 a 7 y §7.4 fila 5).
 * Datos de partida de §7.0 con las plantillas «Desarrollo» (6.00, ventana 5). Cada calificación es una captura por la
 * API en un partido jugado propio, para que también se escriba la evaluación.
 */
beforeEach(function () {
    sesionesPrepararBase();
    desempenoReloj('2026-10-01 12:00:00');
});

function desempenoReloj(string $instante): void
{
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable($instante, new DateTimeZone('UTC'))));
}

/**
 * Captura a F1 en un partido jugado nuevo por cada calificación; el primero es el más antiguo (un día por partido).
 *
 * @param  list<int>  $calificaciones
 * @return list<string> los identificadores de los rendimientos, en el mismo orden
 */
function desempenoCapturar(TestCase $prueba, string $equipo, array $calificaciones, string $correo = 'e1@x.com', int $diaInicial = 1, ?string $jugador = null): array
{
    $sesion = sesionesIniciar($prueba, $correo);
    $jugador ??= (string) DB::table('jugadores')->where('matricula', 'M-F1')->value('id');
    $ids = [];
    foreach ($calificaciones as $indice => $calificacion) {
        $dia = str_pad((string) ($diaInicial + $indice), 2, '0', STR_PAD_LEFT);
        $partido = desempenoPartidoJugado($equipo, "2026-08-{$dia} 23:00:00+00", [$jugador]);
        $ids[] = (string) sesionesPeticion($prueba, 'POST', "/api/v1/partidos/{$partido}/rendimientos", capturaCuerpo($jugador, 'delantero', ['calificacion_general' => $calificacion]), $sesion)
            ->assertCreated()->json('datos.id');
    }

    return $ids;
}

/** @return TestResponse<Response> */
function desempenoConsultar(TestCase $prueba, string $equipo, string $jugador, string $correo = 'e1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, 'GET', "/api/v1/equipos/{$equipo}/jugadores/{$jugador}/desempeno", idDeSesion: sesionesIniciar($prueba, $correo));
}

function desempenoEvaluaciones(): int
{
    return DB::table('evaluaciones_rendimiento')->count();
}

test('HU-16 (§7.3 fila 1): con cuatro calificaciones de E1 para F1 (7, 7, 8, 6) el resultado es datos_insuficientes', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    desempenoCapturar($this, $m['a'], [7, 7, 8, 6]);

    desempenoConsultar($this, $m['a'], $m['f1'])
        ->assertOk()
        ->assertExactJson(['datos' => [
            'resultado' => 'datos_insuficientes', 'promedio' => null, 'calificacion_minima' => '6.00', 'ventana_partidos' => 5,
            'partidos_considerados' => 4, 'calculado_en' => '2026-10-01T12:00:00+00:00',
        ]]);
});

test('HU-16 (§7.3 fila 2): con cinco calificaciones (7, 7, 8, 6, 5) el promedio es 6.60 y pasa', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    desempenoCapturar($this, $m['a'], [7, 7, 8, 6, 5]);

    desempenoConsultar($this, $m['a'], $m['f1'])->assertOk()
        ->assertJsonPath('datos.resultado', 'pasa')
        ->assertJsonPath('datos.promedio', '6.60')
        ->assertJsonPath('datos.partidos_considerados', 5);
});

test('HU-16 (§7.3 fila 3): con (5, 5, 6, 6, 5) el promedio es 5.40 y no pasa', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    desempenoCapturar($this, $m['a'], [5, 5, 6, 6, 5]);

    desempenoConsultar($this, $m['a'], $m['f1'])->assertOk()
        ->assertJsonPath('datos.resultado', 'no_pasa')
        ->assertJsonPath('datos.promedio', '5.40');
});

test('HU-16 (§7.3 fila 4): un promedio exactamente igual al umbral (6, 6, 6, 6, 6) pasa', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    desempenoCapturar($this, $m['a'], [6, 6, 6, 6, 6]);

    desempenoConsultar($this, $m['a'], $m['f1'])->assertOk()
        ->assertJsonPath('datos.resultado', 'pasa')
        ->assertJsonPath('datos.promedio', '6.00');
});

test('HU-16 (§7.3 fila 5, RN-14): con calificaciones de E1 y de E2 para F1, cada uno ve el resultado de las suyas', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    desempenoCapturar($this, $m['a'], [9, 9, 9, 9, 9]);
    desempenoCapturar($this, $m['b'], [5, 5, 5, 5, 5], 'e2@x.com', diaInicial: 10);

    desempenoConsultar($this, $m['b'], $m['f1'], 'e2@x.com')->assertOk()
        ->assertJsonPath('datos.resultado', 'no_pasa')
        ->assertJsonPath('datos.promedio', '5.00');
    desempenoConsultar($this, $m['a'], $m['f1'])->assertOk()
        ->assertJsonPath('datos.resultado', 'pasa')
        ->assertJsonPath('datos.promedio', '9.00');
});

test('HU-16 (§7.3 fila 6): una captura que cambia el resultado inserta una evaluación nueva en la misma transacción y la lectura devuelve la última', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    desempenoCapturar($this, $m['a'], [7, 7, 8, 6]);
    $ultimaAntes = DB::table('evaluaciones_rendimiento')->orderByDesc('calculado_en')->orderByDesc('id')->first();
    desempenoReloj('2026-10-02 09:30:00');

    desempenoCapturar($this, $m['a'], [5], diaInicial: 20);

    $ultima = DB::table('evaluaciones_rendimiento')->orderByDesc('calculado_en')->orderByDesc('id')->first();
    expect(desempenoEvaluaciones())->toBe(5)
        ->and($ultimaAntes->resultado)->toBe('datos_insuficientes')
        ->and($ultimaAntes->promedio)->toBeNull()
        ->and([$ultima->resultado, $ultima->promedio, $ultima->partidos_considerados, $ultima->entrenador_id, $ultima->umbral_id, $ultima->plantilla_id])
        ->toBe(['pasa', '6.60', 5, $m['e1'], null, umbralPlantillaDeFabrica('delantero')])
        ->and($ultima->calculado_en)->toBe('2026-10-02 09:30:00+00');
    desempenoConsultar($this, $m['a'], $m['f1'])->assertOk()
        ->assertJsonPath('datos.resultado', 'pasa')
        ->assertJsonPath('datos.calculado_en', '2026-10-02T09:30:00+00:00');
});

test('HU-16 (§7.3 fila 7): un cambio de umbral inserta una evaluación con el umbral nuevo', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    desempenoCapturar($this, $m['a'], [7, 7, 6]);
    $antes = desempenoEvaluaciones();

    sesionesPeticion($this, 'PUT', "/api/v1/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral", ['calificacion_minima' => '7.00', 'ventana_partidos' => 3], sesionesIniciar($this, 'e1@x.com'))
        ->assertOk();

    $ultima = DB::table('evaluaciones_rendimiento')->orderByDesc('calculado_en')->orderByDesc('id')->first();
    expect(desempenoEvaluaciones())->toBe($antes + 1)
        ->and([$ultima->umbral_id, $ultima->calificacion_minima, $ultima->ventana_partidos, $ultima->resultado, $ultima->promedio])
        ->toBe([(string) DB::table('umbrales_rendimiento')->whereNull('vigente_hasta')->value('id'), '7.00', 3, 'no_pasa', '6.67']);
    desempenoConsultar($this, $m['a'], $m['f1'])->assertOk()
        ->assertJsonPath('datos.resultado', 'no_pasa')
        ->assertJsonPath('datos.calificacion_minima', '7.00')
        ->assertJsonPath('datos.ventana_partidos', 3);
});

test('RN-08 (v1.0.5): las últimas N se ordenan por la fecha del partido, no por el orden de captura', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    desempenoCapturar($this, $m['a'], [8, 8, 8, 8, 8], diaInicial: 10);

    desempenoCapturar($this, $m['a'], [1], diaInicial: 1);

    desempenoConsultar($this, $m['a'], $m['f1'])->assertOk()
        ->assertJsonPath('datos.resultado', 'pasa')
        ->assertJsonPath('datos.promedio', '8.00');
});

test('RN-08 (v1.0.5): solo cuentan las calificaciones del entrenador en ese equipo', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    desempenoCapturar($this, $m['a'], [9, 9, 9], 'e3@x.com');
    desempenoCapturar($this, $m['b'], [2, 2], 'e3@x.com', diaInicial: 10);

    desempenoConsultar($this, $m['a'], $m['f1'], 'e3@x.com')->assertOk()
        ->assertJsonPath('datos.partidos_considerados', 3)
        ->assertJsonPath('datos.resultado', 'datos_insuficientes');
    desempenoConsultar($this, $m['b'], $m['f1'], 'e3@x.com')->assertOk()->assertJsonPath('datos.partidos_considerados', 2);
    desempenoConsultar($this, $m['a'], $m['f1'])->assertOk()->assertJsonPath('datos.partidos_considerados', 0);
});

test('HU-16: una corrección inserta una evaluación, y una captura rechazada no deja ninguna', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    [$id] = desempenoCapturar($this, $m['a'], [7]);
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $partido = desempenoPartidoJugado($m['a'], '2026-08-15 23:00:00+00', [$m['f1']]);

    sesionesPeticion($this, 'PATCH', "/api/v1/rendimientos/{$id}", ['calificacion_general' => 4], $sesion)->assertOk();
    sesionesPeticion($this, 'POST', "/api/v1/partidos/{$partido}/rendimientos", capturaCuerpo($m['f1'], 'delantero', ['minutos' => 151]), $sesion)->assertUnprocessable();

    expect(desempenoEvaluaciones())->toBe(2);
});

test('HU-16 (v1.0.5): al corregir el rendimiento de un jugador que ya no está en el equipo, la plantilla se elige por la posición guardada', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    // E3 entrena A y B: después de que F1 deja A, lo sigue viendo por B y puede corregir lo que capturó en A.
    [$id] = desempenoCapturar($this, $m['a'], [7], 'e3@x.com');
    DB::connection('propietario')->table('jugador_equipo')->where('jugador_id', $m['f1'])->where('equipo_id', $m['a'])->update(['hasta' => '2026-09-30']);

    sesionesPeticion($this, 'PATCH', "/api/v1/rendimientos/{$id}", ['calificacion_general' => 8], sesionesIniciar($this, 'e3@x.com'))->assertOk();

    $ultima = DB::table('evaluaciones_rendimiento')->orderByDesc('calculado_en')->orderByDesc('id')->first();
    expect(desempenoEvaluaciones())->toBe(2)
        ->and([$ultima->plantilla_id, $ultima->umbral_id, $ultima->partidos_considerados])->toBe([umbralPlantillaDeFabrica('delantero'), null, 1]);
});

test('HU-16 (v1.0.5): sin evaluaciones guardadas, la lectura calcula sin guardar, con calculado_en a la hora actual', function () {
    /** @var TestCase $this */
    $m = umbralMundo();

    desempenoConsultar($this, $m['a'], $m['d1'])->assertOk()->assertExactJson(['datos' => [
        'resultado' => 'datos_insuficientes', 'promedio' => null, 'calificacion_minima' => '6.00', 'ventana_partidos' => 5,
        'partidos_considerados' => 0, 'calculado_en' => '2026-10-01T12:00:00+00:00',
    ]]);

    expect(desempenoEvaluaciones())->toBe(0);
});

test('RNF-01 (§7.4 fila 5): un jugador ajeno al alcance de E2 da el 404 de uno inexistente, como un UUID no v7 o un equipo que no entrena', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    $esperado = ['codigo' => 'recurso_no_encontrado', 'mensaje' => __('errores.recurso_no_encontrado'), 'errores' => []];

    desempenoConsultar($this, $m['b'], $m['d1'], 'e2@x.com')->assertNotFound()->assertExactJson($esperado);
    desempenoConsultar($this, $m['b'], Identificador::generar()->valor(), 'e2@x.com')->assertNotFound()->assertExactJson($esperado);
    desempenoConsultar($this, $m['b'], '0b6e4f1a-5c2d-4e8f-9a1b-2c3d4e5f6a7b', 'e2@x.com')->assertNotFound()->assertExactJson($esperado);
    desempenoConsultar($this, $m['a'], $m['f1'], 'e2@x.com')->assertNotFound()->assertExactJson($esperado);
});

test('HU-16 (RNF-01): un docente recibe 403 en el desempeño, y sin sesión 401', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    $ruta = "/api/v1/equipos/{$m['a']}/jugadores/{$m['f1']}/desempeno";

    sesionesPeticion($this, 'GET', $ruta, idDeSesion: sesionesIniciar($this, 'd@x.com'))->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    sesionesPeticion($this, 'GET', $ruta)->assertUnauthorized();
});
