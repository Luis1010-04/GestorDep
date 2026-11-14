<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Src\Rendimiento\Dominio\PlantillaDeEvaluacion;
use Src\Rendimiento\Dominio\RepositorioDePlantillas;
use Src\Rendimiento\Dominio\RepositorioDeUmbrales;
use Src\Rendimiento\Dominio\UmbralDeRendimiento;
use Symfony\Component\HttpFoundation\Response;
use Tests\Ayudantes\NoNulo;
use Tests\TestCase;

/**
 * Plantillas y umbrales (HU-15; docs/hitos/f1-h4.md §7.2 filas 1 a 3 y 5 a 7; la 4 quedó reservada en v1.0.4).
 * Datos de partida de §7.0, con las plantillas «Desarrollo» de fábrica (6.00, ventana 5) en las cuatro posiciones.
 */
beforeEach(function () {
    sesionesPrepararBase();
    umbralReloj('2026-09-20 15:00:00');
});

function umbralReloj(string $instante): void
{
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable($instante, new DateTimeZone('UTC'))));
}

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function umbralPeticion(TestCase $prueba, string $metodo, string $ruta, array $cuerpo = [], string $correo = 'e1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, $metodo, '/api/v1'.$ruta, $cuerpo, sesionesIniciar($prueba, $correo));
}

/** @return array{umbrales: int, historial: int, plantillas: int, historial_plantillas: int} */
function umbralFilas(): array
{
    return [
        'umbrales' => DB::table('umbrales_rendimiento')->count(),
        'historial' => DB::table('umbrales_rendimiento_historial')->count(),
        'plantillas' => DB::table('plantillas_evaluacion')->count(),
        'historial_plantillas' => DB::table('plantillas_evaluacion_historial')->count(),
    ];
}

/** @param  array<string, mixed>  $cambios */
function umbralCrearPlantilla(TestCase $prueba, string $posicion, array $cambios = [], string $correo = 'e1@x.com'): string
{
    return (string) umbralPeticion($prueba, 'POST', '/plantillas-evaluacion', [
        'nombre' => 'Exigente', 'posicion_id' => umbralPosicion($posicion), 'calificacion_minima' => '7.50', 'ventana_partidos' => 4, ...$cambios,
    ], $correo)->assertCreated()->json('datos.id');
}

test('HU-15 (§7.2 fila 1): sin umbral propio, E1 consulta el umbral de F1 en A y recibe la plantilla «Desarrollo» de delantero', function () {
    /** @var TestCase $this */
    $m = umbralMundo();

    umbralPeticion($this, 'GET', "/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral")
        ->assertOk()
        ->assertExactJson(['datos' => [
            'origen' => 'plantilla', 'plantilla_id' => umbralPlantillaDeFabrica('delantero'), 'calificacion_minima' => '6.00',
            'ventana_partidos' => 5, 'vigente_desde' => null,
        ]]);
});

test('HU-15 (§7.2 fila 2): E1 cambia el umbral de F1 a 7.0 con ventana 3; el anterior queda cerrado y el historial guarda el valor anterior, el autor y la fecha', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    $ruta = "/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral";
    umbralPeticion($this, 'PUT', $ruta, ['calificacion_minima' => '6.50', 'ventana_partidos' => 4])->assertOk();
    $anterior = (string) DB::table('umbrales_rendimiento')->value('id');
    umbralReloj('2026-09-21 10:00:00');

    $respuesta = umbralPeticion($this, 'PUT', $ruta, ['calificacion_minima' => '7.0', 'ventana_partidos' => 3])->assertOk();

    expect($respuesta->json('datos'))->toBe([
        'origen' => 'umbral', 'plantilla_id' => null, 'calificacion_minima' => '7.00', 'ventana_partidos' => 3, 'vigente_desde' => '2026-09-21T10:00:00+00:00',
    ])
        ->and(DB::table('umbrales_rendimiento')->where('id', $anterior)->value('vigente_hasta'))->toBe('2026-09-21 10:00:00+00')
        ->and(DB::table('umbrales_rendimiento')->whereNull('vigente_hasta')->count())->toBe(1);
    $cierre = NoNulo::exigir(DB::table('umbrales_rendimiento_historial')->where('umbral_id', $anterior)->where('operacion', 'actualizar')->first(), 'el historial del cierre');
    $antes = json_decode((string) $cierre->datos_anteriores, true);
    $despues = json_decode((string) $cierre->datos_nuevos, true);
    expect($antes['calificacion_minima'])->toBe('6.50')
        ->and($antes['ventana_partidos'])->toBe(4)
        ->and($antes['vigente_hasta'])->toBeNull()
        ->and($despues['vigente_hasta'])->toBe('2026-09-21T10:00:00+00:00')
        ->and($cierre->usuario_id)->toBe($m['e1'])
        ->and($cierre->ocurrido_en)->toBe('2026-09-21 10:00:00+00')
        ->and(umbralFilas()['historial'])->toBe(3);
    umbralPeticion($this, 'GET', $ruta)->assertOk()->assertJsonPath('datos.calificacion_minima', '7.00');
});

test('HU-15 (§7.2 fila 3): E1 consulta o cambia el umbral de F1 en B, que no entrena, y recibe 404 sin escribir nada', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    $ruta = "/equipos/{$m['b']}/jugadores/{$m['f1']}/umbral";

    umbralPeticion($this, 'GET', $ruta)->assertNotFound()->assertExactJson(['codigo' => 'recurso_no_encontrado', 'mensaje' => __('errores.recurso_no_encontrado'), 'errores' => []]);
    umbralPeticion($this, 'PUT', $ruta, ['calificacion_minima' => '0'])->assertNotFound();

    expect(umbralFilas()['umbrales'])->toBe(0);
});

test('HU-15 (§7.2 fila 5): E1 crea una plantilla de defensa; creada_por es E1, con historial', function () {
    /** @var TestCase $this */
    $m = umbralMundo();

    $respuesta = umbralPeticion($this, 'POST', '/plantillas-evaluacion', [
        'nombre' => 'Exigente', 'posicion_id' => umbralPosicion('defensa'), 'calificacion_minima' => '7.5', 'ventana_partidos' => 4,
    ])->assertCreated();

    $id = (string) $respuesta->json('datos.id');
    expect($respuesta->json('datos'))->toBe([
        'id' => $id, 'nombre' => 'Exigente', 'posicion_id' => umbralPosicion('defensa'), 'calificacion_minima' => '7.50', 'ventana_partidos' => 4, 'es_de_fabrica' => false,
    ])
        ->and(DB::table('plantillas_evaluacion')->where('id', $id)->first(['creada_por', 'es_de_fabrica']))->toEqual((object) ['creada_por' => $m['e1'], 'es_de_fabrica' => false])
        ->and(DB::table('plantillas_evaluacion_historial')->where('plantilla_id', $id)->first(['operacion', 'usuario_id']))->toEqual((object) ['operacion' => 'crear', 'usuario_id' => $m['e1']]);
});

test('HU-15 (§7.2 fila 6): E1 usa una plantilla de portero para F1 y recibe 422 plantilla_de_otra_posicion', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    $antes = umbralFilas();

    umbralPeticion($this, 'PUT', "/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral", ['plantilla_id' => umbralPlantillaDeFabrica('portero')])
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'plantilla_de_otra_posicion')
        ->assertJsonPath('errores.0.campo', 'plantilla_id');

    expect(umbralFilas())->toBe($antes);
});

test('HU-15 (§7.2 fila 7): un umbral con calificación 0, o con una ventana fuera de rango, da 422 valor_fuera_de_rango en su campo', function (array $cuerpo, string $campo) {
    /** @var TestCase $this */
    $m = umbralMundo();

    umbralPeticion($this, 'PUT', "/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral", $cuerpo)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'valor_fuera_de_rango')
        ->assertJsonPath('mensaje', __('errores.rendimiento.valor_fuera_de_rango'))
        ->assertJsonPath('errores.0.campo', $campo);

    expect(umbralFilas()['umbrales'])->toBe(0);
})->with([
    'calificación 0' => [['calificacion_minima' => '0', 'ventana_partidos' => 3], 'calificacion_minima'],
    'calificación 10.01' => [['calificacion_minima' => '10.01', 'ventana_partidos' => 3], 'calificacion_minima'],
    'ventana 0' => [['calificacion_minima' => '7.00', 'ventana_partidos' => 0], 'ventana_partidos'],
    'ventana sobre el límite técnico' => [['calificacion_minima' => '7.00', 'ventana_partidos' => 32768], 'ventana_partidos'],
]);

test('HU-15 (criterio 2): el umbral de E1 se aplica solo a F1, en A, y para E1; E3 y otro jugador siguen con la plantilla', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    umbralPeticion($this, 'PUT', "/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral", ['calificacion_minima' => '7.00', 'ventana_partidos' => 3])->assertOk();

    umbralPeticion($this, 'GET', "/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral", correo: 'e3@x.com')->assertOk()->assertJsonPath('datos.origen', 'plantilla');
    umbralPeticion($this, 'GET', "/equipos/{$m['b']}/jugadores/{$m['f1']}/umbral", correo: 'e3@x.com')->assertOk()->assertJsonPath('datos.origen', 'plantilla');
    umbralPeticion($this, 'GET', "/equipos/{$m['a']}/jugadores/{$m['d1']}/umbral")->assertOk()
        ->assertJsonPath('datos.plantilla_id', umbralPlantillaDeFabrica('defensa'));
    umbralPeticion($this, 'GET', "/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral")->assertOk()->assertJsonPath('datos.origen', 'umbral');
});

test('RN-07: cada posición tiene su plantilla de fábrica, y el umbral de un jugador sin umbral propio es la de su posición en ese equipo', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    $posicionDe = fn (string $jugador, string $equipo, string $correo = 'e1@x.com') => umbralPeticion($this, 'GET', "/equipos/{$equipo}/jugadores/{$jugador}/umbral", correo: $correo)
        ->assertOk()->assertJsonPath('datos.origen', 'plantilla')->json('datos.plantilla_id');

    expect(DB::table('plantillas_evaluacion')->whereNull('creada_por')->whereNull('eliminado_en')->distinct()->count('posicion_id'))->toBe(DB::table('posiciones')->count())
        ->and($posicionDe($m['p1'], $m['a']))->toBe(umbralPlantillaDeFabrica('portero'))
        ->and($posicionDe($m['d1'], $m['a']))->toBe(umbralPlantillaDeFabrica('defensa'))
        ->and($posicionDe($m['m1'], $m['a']))->toBe(umbralPlantillaDeFabrica('medio'))
        ->and($posicionDe($m['f1'], $m['a']))->toBe(umbralPlantillaDeFabrica('delantero'))
        ->and($posicionDe($m['f1'], $m['b'], 'e3@x.com'))->toBe(umbralPlantillaDeFabrica('delantero'));
});

test('HU-15: el primer PUT no cierra nada; sin cuerpo copia la plantilla de fábrica de la posición', function () {
    /** @var TestCase $this */
    $m = umbralMundo();

    umbralPeticion($this, 'PUT', "/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral")
        ->assertOk()
        ->assertExactJson(['datos' => [
            'origen' => 'umbral', 'plantilla_id' => umbralPlantillaDeFabrica('delantero'), 'calificacion_minima' => '6.00',
            'ventana_partidos' => 5, 'vigente_desde' => '2026-09-20T15:00:00+00:00',
        ]]);

    expect(umbralFilas()['umbrales'])->toBe(1)
        ->and(DB::table('umbrales_rendimiento_historial')->pluck('operacion')->all())->toBe(['crear'])
        ->and(DB::table('umbrales_rendimiento')->value('entrenador_id'))->toBe($m['e1']);
});

test('HU-15: un umbral con una plantilla propia de la posición copia sus valores', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    $plantilla = umbralCrearPlantilla($this, 'delantero');

    umbralPeticion($this, 'PUT', "/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral", ['plantilla_id' => $plantilla])
        ->assertOk()
        ->assertJsonPath('datos.plantilla_id', $plantilla)
        ->assertJsonPath('datos.calificacion_minima', '7.50')
        ->assertJsonPath('datos.ventana_partidos', 4);
});

test('HU-15 (v1.0.4): una plantilla inexistente, ajena o con UUID no v7 da 422 plantilla_inexistente con el mismo cuerpo', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    $ajena = umbralCrearPlantilla($this, 'delantero', correo: 'e2@x.com');
    $ruta = "/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral";
    $antes = umbralFilas();

    $cuerpos = array_map(
        fn (string $plantilla) => umbralPeticion($this, 'PUT', $ruta, ['plantilla_id' => $plantilla])->assertUnprocessable()->json(),
        [$ajena, Identificador::generar()->valor(), '0b6e4f1a-5c2d-4e8f-9a1b-2c3d4e5f6a7b'],
    );

    expect($cuerpos[0])->toBe([
        'codigo' => 'plantilla_inexistente', 'mensaje' => __('errores.reglas.plantilla_inexistente'),
        'errores' => [['campo' => 'plantilla_id', 'codigo' => 'plantilla_inexistente', 'mensaje' => __('errores.reglas.plantilla_inexistente')]],
    ])
        ->and($cuerpos[1])->toBe($cuerpos[0])
        ->and($cuerpos[2])->toBe($cuerpos[0])
        ->and(umbralFilas())->toBe($antes);
});

test('HU-15 (v1.0.4): plantilla_id junto con valores propios da 422 umbral_ambiguo', function () {
    /** @var TestCase $this */
    $m = umbralMundo();

    umbralPeticion($this, 'PUT', "/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral", [
        'plantilla_id' => umbralPlantillaDeFabrica('delantero'), 'calificacion_minima' => '7.00', 'ventana_partidos' => 3,
    ])
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'umbral_ambiguo')
        ->assertJsonPath('errores.0.campo', 'plantilla_id');

    expect(umbralFilas()['umbrales'])->toBe(0);
});

test('HU-15: el PUT del umbral valida tipos, exige la calificación y la ventana juntas y rechaza campos desconocidos', function (array $cuerpo, string $campo) {
    /** @var TestCase $this */
    $m = umbralMundo();

    umbralPeticion($this, 'PUT', "/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral", $cuerpo)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', $campo);
})->with([
    'solo calificación' => [['calificacion_minima' => '7.00'], 'ventana_partidos'],
    'solo ventana' => [['ventana_partidos' => 3], 'calificacion_minima'],
    'tres decimales' => [['calificacion_minima' => '7.005', 'ventana_partidos' => 3], 'calificacion_minima'],
    'calificación numérica' => [['calificacion_minima' => 7, 'ventana_partidos' => 3], 'calificacion_minima'],
    'ventana como texto' => [['calificacion_minima' => '7.00', 'ventana_partidos' => '3'], 'ventana_partidos'],
    'plantilla que no es UUID' => [['plantilla_id' => 'x'], 'plantilla_id'],
    'campo desconocido' => [['jugador_id' => 'x'], 'jugador_id'],
]);

test('HU-15 (RNF-01): un jugador fuera del alcance, sin membresía en el equipo o inexistente da el 404 de uno inexistente', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    $deB = mundoJugador('M-B1', 'Solo B', [$m['b'] => 'medio'], nombres: 'B1');
    $esperado = ['codigo' => 'recurso_no_encontrado', 'mensaje' => __('errores.recurso_no_encontrado'), 'errores' => []];

    foreach ([$deB, Identificador::generar()->valor(), '0b6e4f1a-5c2d-4e8f-9a1b-2c3d4e5f6a7b'] as $jugador) {
        umbralPeticion($this, 'GET', "/equipos/{$m['a']}/jugadores/{$jugador}/umbral")->assertNotFound()->assertExactJson($esperado);
        umbralPeticion($this, 'PUT', "/equipos/{$m['a']}/jugadores/{$jugador}/umbral", ['calificacion_minima' => 'x'])->assertNotFound()->assertExactJson($esperado);
    }
    umbralPeticion($this, 'GET', "/equipos/{$m['b']}/jugadores/{$deB}/umbral", correo: 'e3@x.com')->assertOk();

    expect(umbralFilas()['umbrales'])->toBe(0);
});

test('HU-15: la carrera de dos PUT que choca con el índice del umbral vigente da 409 conflicto y no deja dos vigentes', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    $ruta = "/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral";
    umbralPeticion($this, 'PUT', $ruta, ['calificacion_minima' => '7.00', 'ventana_partidos' => 3])->assertOk();
    $real = app(RepositorioDeUmbrales::class);
    app()->instance(RepositorioDeUmbrales::class, new class($real) implements RepositorioDeUmbrales
    {
        public function __construct(private readonly RepositorioDeUmbrales $real) {}

        public function vigenteDe(Identificador $jugador, Identificador $equipo, Identificador $entrenador): ?UmbralDeRendimiento
        {
            return null;
        }

        public function reemplazar(?UmbralDeRendimiento $anterior, UmbralDeRendimiento $nuevo): void
        {
            $this->real->reemplazar($anterior, $nuevo);
        }
    });
    $antes = umbralFilas();

    umbralPeticion($this, 'PUT', $ruta, ['calificacion_minima' => '8.00', 'ventana_partidos' => 3])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'conflicto', 'mensaje' => __('errores.conflicto'), 'errores' => []]);

    expect(umbralFilas())->toBe($antes);
});

test('HU-15: E1 lista las plantillas de fábrica y las suyas, nunca las de otro entrenador, y filtra por posición', function () {
    /** @var TestCase $this */
    umbralMundo();
    $propia = umbralCrearPlantilla($this, 'defensa', ['nombre' => 'Alta']);
    umbralCrearPlantilla($this, 'defensa', ['nombre' => 'Ajena'], 'e2@x.com');

    $todas = umbralPeticion($this, 'GET', '/plantillas-evaluacion')->assertOk();
    $defensa = umbralPeticion($this, 'GET', '/plantillas-evaluacion?filtro[posicion_id]='.umbralPosicion('defensa'))->assertOk();

    expect($todas->json('meta.total'))->toBe(5)
        ->and(array_column($todas->json('datos'), 'es_de_fabrica'))->toBe([true, true, true, true, false])
        ->and(array_column($defensa->json('datos'), 'id'))->toBe([umbralPlantillaDeFabrica('defensa'), $propia])
        ->and($defensa->json('datos.0'))->toBe([
            'id' => umbralPlantillaDeFabrica('defensa'), 'nombre' => 'Desarrollo', 'posicion_id' => umbralPosicion('defensa'),
            'calificacion_minima' => '6.00', 'ventana_partidos' => 5, 'es_de_fabrica' => true,
        ]);
});

test('HU-15 (DA-26): un filtro de posición con UUID válido no v7 da una lista vacía; uno que no es UUID da 422', function () {
    /** @var TestCase $this */
    umbralMundo();

    umbralPeticion($this, 'GET', '/plantillas-evaluacion?filtro[posicion_id]=0b6e4f1a-5c2d-4e8f-9a1b-2c3d4e5f6a7b')->assertOk()
        ->assertJsonPath('datos', [])
        ->assertJsonPath('meta.total', 0);
    umbralPeticion($this, 'GET', '/plantillas-evaluacion?filtro[posicion_id]=x')->assertUnprocessable()->assertJsonPath('errores.0.campo', 'filtro.posicion_id');
    umbralPeticion($this, 'GET', '/plantillas-evaluacion?por_pagina=101')->assertUnprocessable();
});

test('HU-15 (v1.0.4): una plantilla propia con el nombre de otra suya o de una de fábrica en la misma posición da 409 plantilla_duplicada', function () {
    /** @var TestCase $this */
    umbralMundo();
    umbralCrearPlantilla($this, 'defensa');
    $antes = umbralFilas();

    foreach (['Exigente', 'Desarrollo'] as $nombre) {
        umbralPeticion($this, 'POST', '/plantillas-evaluacion', [
            'nombre' => $nombre, 'posicion_id' => umbralPosicion('defensa'), 'calificacion_minima' => '7.00', 'ventana_partidos' => 3,
        ])->assertStatus(409)->assertJsonPath('codigo', 'plantilla_duplicada')->assertJsonPath('errores.0.campo', 'nombre');
    }

    expect(umbralFilas())->toBe($antes);
});

test('HU-15 (v1.0.4): el nombre de una plantilla de otro entrenador o de otra posición no choca, así que no se revela', function () {
    /** @var TestCase $this */
    umbralMundo();
    umbralCrearPlantilla($this, 'defensa');

    umbralCrearPlantilla($this, 'defensa', correo: 'e2@x.com');
    umbralCrearPlantilla($this, 'medio');

    expect(DB::table('plantillas_evaluacion')->where('nombre', 'Exigente')->count())->toBe(3);
});

test('HU-15 (v1.0.4): la carrera de dos plantillas con el mismo nombre que choca con el índice único da 409 plantilla_duplicada', function () {
    /** @var TestCase $this */
    umbralMundo();
    umbralCrearPlantilla($this, 'defensa');
    $real = app(RepositorioDePlantillas::class);
    app()->instance(RepositorioDePlantillas::class, new class($real) implements RepositorioDePlantillas
    {
        public function __construct(private readonly RepositorioDePlantillas $real) {}

        public function guardarNueva(PlantillaDeEvaluacion $plantilla, DateTimeImmutable $instante): void
        {
            $this->real->guardarNueva($plantilla, $instante);
        }

        public function nombreOcupado(string $nombre, Identificador $posicion, Identificador $entrenador): bool
        {
            return false;
        }

        public function buscarVisible(Identificador $id, Identificador $entrenador): ?PlantillaDeEvaluacion
        {
            return $this->real->buscarVisible($id, $entrenador);
        }

        public function deFabricaDe(Identificador $posicion): ?PlantillaDeEvaluacion
        {
            return $this->real->deFabricaDe($posicion);
        }
    });
    $antes = umbralFilas();

    umbralPeticion($this, 'POST', '/plantillas-evaluacion', [
        'nombre' => 'Exigente', 'posicion_id' => umbralPosicion('defensa'), 'calificacion_minima' => '7.00', 'ventana_partidos' => 3,
    ])->assertStatus(409)->assertJsonPath('codigo', 'plantilla_duplicada')->assertJsonPath('errores.0.campo', 'nombre');

    expect(umbralFilas())->toBe($antes);
});

test('HU-15: una plantilla con una posición inexistente o con UUID no v7 da 422 posicion_inexistente', function (string $posicion) {
    /** @var TestCase $this */
    umbralMundo();
    $antes = umbralFilas();

    umbralPeticion($this, 'POST', '/plantillas-evaluacion', [
        'nombre' => 'Exigente', 'posicion_id' => $posicion, 'calificacion_minima' => '7.00', 'ventana_partidos' => 3,
    ])->assertUnprocessable()->assertJsonPath('codigo', 'posicion_inexistente')->assertJsonPath('errores.0.campo', 'posicion_id');

    expect(umbralFilas())->toBe($antes);
})->with([
    'inexistente' => fn () => Identificador::generar()->valor(),
    'no v7' => '0b6e4f1a-5c2d-4e8f-9a1b-2c3d4e5f6a7b',
]);

test('HU-15: una plantilla fuera de rango da valor_fuera_de_rango y una mal formada validacion_fallida', function (array $cambios, string $codigo, string $campo) {
    /** @var TestCase $this */
    umbralMundo();

    umbralPeticion($this, 'POST', '/plantillas-evaluacion', [
        'nombre' => 'Exigente', 'posicion_id' => umbralPosicion('defensa'), 'calificacion_minima' => '7.00', 'ventana_partidos' => 3, ...$cambios,
    ])->assertUnprocessable()->assertJsonPath('codigo', $codigo)->assertJsonPath('errores.0.campo', $campo);
})->with([
    'calificación 0' => [['calificacion_minima' => '0.00'], 'valor_fuera_de_rango', 'calificacion_minima'],
    'ventana 0' => [['ventana_partidos' => 0], 'valor_fuera_de_rango', 'ventana_partidos'],
    'sin nombre' => [['nombre' => null], 'validacion_fallida', 'nombre'],
    'nombre largo' => [['nombre' => str_repeat('a', 101)], 'validacion_fallida', 'nombre'],
    'posición que no es UUID' => [['posicion_id' => 'x'], 'validacion_fallida', 'posicion_id'],
    'tres decimales' => [['calificacion_minima' => '7.001'], 'validacion_fallida', 'calificacion_minima'],
    'ventana como texto' => [['ventana_partidos' => '3'], 'validacion_fallida', 'ventana_partidos'],
    'campo desconocido' => [['es_de_fabrica' => true], 'validacion_fallida', 'es_de_fabrica'],
]);

test('HU-15 (RNF-01): un docente recibe 403 en las rutas de plantillas y umbral, y sin sesión 401', function () {
    /** @var TestCase $this */
    $m = umbralMundo();
    $docente = sesionesIniciar($this, 'd@x.com');
    $rutas = [
        ['GET', '/api/v1/plantillas-evaluacion'],
        ['POST', '/api/v1/plantillas-evaluacion'],
        ['GET', "/api/v1/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral"],
        ['PUT', "/api/v1/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral"],
    ];

    foreach ($rutas as [$metodo, $ruta]) {
        sesionesPeticion($this, $metodo, $ruta, idDeSesion: $docente)->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
        sesionesPeticion($this, $metodo, $ruta)->assertUnauthorized();
    }
});
