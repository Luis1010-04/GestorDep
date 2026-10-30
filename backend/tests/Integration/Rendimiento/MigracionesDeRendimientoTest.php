<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Compartido\Infraestructura\RelojFijo;

const TABLAS_DE_RENDIMIENTO = [
    'metricas', 'metricas_posicion', 'rendimientos_partido', 'valores_metrica', 'plantillas_evaluacion', 'umbrales_rendimiento', 'evaluaciones_rendimiento',
];

const TABLAS_DE_RENDIMIENTO_CON_AUDITORIA = ['metricas', 'rendimientos_partido', 'valores_metrica', 'plantillas_evaluacion', 'umbrales_rendimiento'];

const HISTORIALES_DE_RENDIMIENTO = [
    'rendimientos_partido_historial' => 'rendimiento_id',
    'valores_metrica_historial' => 'valor_metrica_id',
    'plantillas_evaluacion_historial' => 'plantilla_id',
    'umbrales_rendimiento_historial' => 'umbral_id',
];

beforeEach(function () {
    esquemaReiniciar();
});

/** @param  array<string, mixed>  $atributos */
function rendimientoInsertar(string $tabla, array $atributos): string
{
    $id = Identificador::generar()->valor();
    DB::connection('propietario')->table($tabla)->insert(['id' => $id, ...$atributos]);

    return $id;
}

function rendimientoRechazo(callable $operacion): ?string
{
    try {
        $operacion();
    } catch (QueryException $e) {
        return (string) $e->getCode();
    }

    return null;
}

function rendimientoEliminar(string $tabla, string $id): void
{
    DB::connection('propietario')->table($tabla)->where('id', $id)->update(['eliminado_en' => '2026-09-15 10:30:00+00']);
}

function rendimientoUnico(): string
{
    return substr(Identificador::generar()->valor(), -12);
}

function rendimientoUsuario(): string
{
    return rendimientoInsertar('usuarios', [
        'nombre' => 'Entrenador de prueba', 'correo' => rendimientoUnico().'@example.test', 'contrasena_hash' => 'hash-de-prueba',
    ]);
}

function rendimientoPosicion(string $codigo = 'defensa'): string
{
    $existente = DB::connection('propietario')->table('posiciones')->where('codigo', $codigo)->value('id');

    return is_string($existente) ? $existente : rendimientoInsertar('posiciones', ['codigo' => $codigo, 'nombre' => ucfirst($codigo)]);
}

function rendimientoEquipo(): string
{
    return rendimientoInsertar('equipos', [
        'nombre' => 'Equipo '.rendimientoUnico(),
        'categoria_id' => rendimientoInsertar('categorias', ['nombre' => 'Sub-'.rendimientoUnico()]),
    ]);
}

function rendimientoJugador(): string
{
    return rendimientoInsertar('jugadores', [
        'nombres' => 'Jugador', 'apellidos' => 'De Prueba', 'fecha_nacimiento' => '2009-05-10', 'matricula' => 'M-'.rendimientoUnico(),
    ]);
}

/** @return array{id: string, equipo_id: string} */
function rendimientoPartido(): array
{
    $equipo = rendimientoEquipo();
    $id = rendimientoInsertar('partidos', [
        'equipo_id' => $equipo,
        'temporada_id' => rendimientoInsertar('temporadas', ['nombre' => 'T-'.rendimientoUnico(), 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-12-15']),
        'rival_id' => rendimientoInsertar('rivales', ['nombre' => 'R-'.rendimientoUnico()]),
        'lugar_id' => rendimientoInsertar('lugares', ['nombre' => 'L-'.rendimientoUnico()]),
        'inicia_en' => '2026-09-12 17:00:00+00', 'tipo' => 'amistoso', 'condicion' => 'local', 'estado' => 'jugado',
        'goles_favor' => 2, 'goles_contra' => 1,
    ]);

    return ['id' => $id, 'equipo_id' => $equipo];
}

/**
 * @param  array<string, mixed>  $atributos
 * @return array<string, mixed>
 */
function rendimientoDatosDeCaptura(array $atributos = []): array
{
    $partido = rendimientoPartido();

    return [
        'partido_id' => $partido['id'], 'equipo_id' => $partido['equipo_id'], 'jugador_id' => rendimientoJugador(),
        'registrado_por' => rendimientoUsuario(), 'posicion_id' => rendimientoPosicion(), 'minutos' => 90, 'fue_titular' => true,
        'tarjetas_amarillas' => 0, 'tarjetas_rojas' => 0, 'calificacion_general' => 7, 'observacion' => null, ...$atributos,
    ];
}

/** @param  array<string, mixed>  $atributos */
function rendimientoMetrica(array $atributos = []): string
{
    return rendimientoInsertar('metricas', [
        'codigo' => 'm_'.rendimientoUnico(), 'nombre' => 'Métrica', 'tipo_dato' => 'entero', 'minimo' => '0', 'maximo' => '99', ...$atributos,
    ]);
}

/**
 * @param  array<string, mixed>  $atributos
 * @return array<string, mixed>
 */
function rendimientoDatosDePlantilla(array $atributos = []): array
{
    return [
        'nombre' => 'P-'.rendimientoUnico(), 'posicion_id' => rendimientoPosicion(), 'creada_por' => rendimientoUsuario(),
        'calificacion_minima' => '6.00', 'ventana_partidos' => 5, 'es_de_fabrica' => false, ...$atributos,
    ];
}

/**
 * @param  array<string, mixed>  $atributos
 * @return array<string, mixed>
 */
function rendimientoDatosDeUmbral(array $atributos = []): array
{
    return [
        'jugador_id' => rendimientoJugador(), 'equipo_id' => rendimientoEquipo(), 'entrenador_id' => rendimientoUsuario(),
        'plantilla_id' => null, 'calificacion_minima' => '7.00', 'ventana_partidos' => 3,
        'vigente_desde' => '2026-09-15 10:30:00+00', 'vigente_hasta' => null, ...$atributos,
    ];
}

/**
 * @param  array<string, mixed>  $atributos
 * @return array<string, mixed>
 */
function rendimientoDatosDeEvaluacion(array $atributos = []): array
{
    return [
        'jugador_id' => rendimientoJugador(), 'equipo_id' => rendimientoEquipo(), 'entrenador_id' => rendimientoUsuario(),
        'umbral_id' => null, 'plantilla_id' => null, 'calificacion_minima' => '6.00', 'ventana_partidos' => 5,
        'promedio' => '6.60', 'partidos_considerados' => 5, 'resultado' => 'pasa', 'calculado_en' => '2026-09-15 10:30:00+00', ...$atributos,
    ];
}

test('RNF-03: el código de métrica es único y tipo_dato solo admite entero o decimal', function () {
    rendimientoMetrica(['codigo' => 'goles_de_prueba']);

    expect(rendimientoRechazo(fn () => rendimientoMetrica(['codigo' => 'goles_de_prueba'])))->toBe('23505')
        ->and(rendimientoRechazo(fn () => rendimientoMetrica(['tipo_dato' => 'decimal'])))->toBeNull()
        ->and(rendimientoRechazo(fn () => rendimientoMetrica(['tipo_dato' => 'texto'])))->toBe('23514');
});

test('RNF-03: metricas_posicion tiene clave primaria compuesta y claves foráneas a métrica y posición', function () {
    $metrica = rendimientoMetrica();
    $relacionar = fn (string $metricaId, string $posicionId) => rendimientoRechazo(fn () => DB::connection('propietario')->table('metricas_posicion')->insert([
        'metrica_id' => $metricaId, 'posicion_id' => $posicionId, 'obligatoria' => true, 'orden' => 1,
    ]));

    expect($relacionar($metrica, rendimientoPosicion('defensa')))->toBeNull()
        ->and($relacionar($metrica, rendimientoPosicion('defensa')))->toBe('23505')
        ->and($relacionar($metrica, rendimientoPosicion('portero')))->toBeNull()
        ->and($relacionar(Identificador::generar()->valor(), rendimientoPosicion('medio')))->toBe('23503')
        ->and($relacionar($metrica, Identificador::generar()->valor()))->toBe('23503');
});

test('RNF-03: los minutos de un rendimiento van de 0 a 150', function () {
    $capturar = fn (int $minutos) => rendimientoRechazo(fn () => rendimientoInsertar('rendimientos_partido', rendimientoDatosDeCaptura(['minutos' => $minutos])));

    expect($capturar(0))->toBeNull()
        ->and($capturar(150))->toBeNull()
        ->and($capturar(-1))->toBe('23514')
        ->and($capturar(151))->toBe('23514');
});

test('RNF-03 (DD-09): la calificación general es un entero de 1 a 10', function () {
    $capturar = fn (int $calificacion) => rendimientoRechazo(fn () => rendimientoInsertar('rendimientos_partido', rendimientoDatosDeCaptura(['calificacion_general' => $calificacion])));

    expect($capturar(1))->toBeNull()
        ->and($capturar(10))->toBeNull()
        ->and($capturar(0))->toBe('23514')
        ->and($capturar(11))->toBe('23514')
        ->and(DB::connection('propietario')->selectOne(
            "select data_type from information_schema.columns where table_name = 'rendimientos_partido' and column_name = 'calificacion_general'",
        )->data_type)->toBe('smallint');
});

test('RNF-03: las tarjetas amarillas van de 0 a 2 y las rojas de 0 a 1', function () {
    $capturar = fn (int $amarillas, int $rojas) => rendimientoRechazo(fn () => rendimientoInsertar('rendimientos_partido', rendimientoDatosDeCaptura([
        'tarjetas_amarillas' => $amarillas, 'tarjetas_rojas' => $rojas,
    ])));

    expect($capturar(0, 0))->toBeNull()
        ->and($capturar(2, 1))->toBeNull()
        ->and($capturar(3, 0))->toBe('23514')
        ->and($capturar(-1, 0))->toBe('23514')
        ->and($capturar(0, 2))->toBe('23514')
        ->and($capturar(0, -1))->toBe('23514');
});

test('RNF-03: la observación admite hasta 500 caracteres', function () {
    $capturar = fn (string $observacion) => rendimientoRechazo(fn () => rendimientoInsertar('rendimientos_partido', rendimientoDatosDeCaptura(['observacion' => $observacion])));

    expect($capturar(str_repeat('a', 500)))->toBeNull()
        ->and($capturar(str_repeat('a', 501)))->toBe('22001');
});

test('RNF-03: la clave foránea compuesta impide un rendimiento con un equipo distinto al del partido', function () {
    expect(rendimientoRechazo(fn () => rendimientoInsertar('rendimientos_partido', rendimientoDatosDeCaptura(['equipo_id' => rendimientoEquipo()]))))->toBe('23503');
});

test('RNF-03: un jugador tiene un solo rendimiento por partido, y el borrado lógico libera el índice', function () {
    $datos = rendimientoDatosDeCaptura();
    $primero = rendimientoInsertar('rendimientos_partido', $datos);

    expect(rendimientoRechazo(fn () => rendimientoInsertar('rendimientos_partido', $datos)))->toBe('23505');

    rendimientoEliminar('rendimientos_partido', $primero);

    expect(rendimientoRechazo(fn () => rendimientoInsertar('rendimientos_partido', $datos)))->toBeNull();
});

test('RNF-03: un rendimiento tiene un solo valor por métrica, con dos decimales, y el borrado lógico libera el índice', function () {
    $rendimiento = rendimientoInsertar('rendimientos_partido', rendimientoDatosDeCaptura());
    $metrica = rendimientoMetrica();
    $valor = fn (string $de) => rendimientoRechazo(fn () => rendimientoInsertar('valores_metrica', ['rendimiento_id' => $de, 'metrica_id' => $metrica, 'valor' => '12.345']));

    expect($valor($rendimiento))->toBeNull()
        ->and($valor($rendimiento))->toBe('23505')
        ->and($valor(rendimientoInsertar('rendimientos_partido', rendimientoDatosDeCaptura())))->toBeNull()
        ->and(DB::connection('propietario')->table('valores_metrica')->where('rendimiento_id', $rendimiento)->value('valor'))->toBe('12.35');

    rendimientoEliminar('valores_metrica', (string) DB::connection('propietario')->table('valores_metrica')->where('rendimiento_id', $rendimiento)->value('id'));

    expect($valor($rendimiento))->toBeNull();
});

test('RNF-03 (RNF-09): la calificación mínima de una plantilla va de 1 a 10 y la ventana es mayor que cero', function () {
    $plantilla = fn (array $extra) => rendimientoRechazo(fn () => rendimientoInsertar('plantillas_evaluacion', rendimientoDatosDePlantilla($extra)));

    expect($plantilla(['calificacion_minima' => '1.00']))->toBeNull()
        ->and($plantilla(['calificacion_minima' => '10.00']))->toBeNull()
        ->and($plantilla(['calificacion_minima' => '0.99']))->toBe('23514')
        ->and($plantilla(['calificacion_minima' => '10.01']))->toBe('23514')
        ->and($plantilla(['ventana_partidos' => 1]))->toBeNull()
        ->and($plantilla(['ventana_partidos' => 0]))->toBe('23514');
});

test('RNF-03: el nombre de una plantilla es único en su posición, y el borrado lógico lo libera', function () {
    $datos = rendimientoDatosDePlantilla(['nombre' => 'Desarrollo']);
    $primera = rendimientoInsertar('plantillas_evaluacion', $datos);

    expect(rendimientoRechazo(fn () => rendimientoInsertar('plantillas_evaluacion', $datos)))->toBe('23505')
        ->and(rendimientoRechazo(fn () => rendimientoInsertar('plantillas_evaluacion', [...$datos, 'posicion_id' => rendimientoPosicion('portero')])))->toBeNull();

    rendimientoEliminar('plantillas_evaluacion', $primera);

    expect(rendimientoRechazo(fn () => rendimientoInsertar('plantillas_evaluacion', $datos)))->toBeNull();
});

test('RNF-03 (DA-06): creada_por es nulo si y solo si la plantilla es de fábrica', function () {
    $plantilla = fn (?string $creadaPor, bool $deFabrica) => rendimientoRechazo(fn () => rendimientoInsertar('plantillas_evaluacion', rendimientoDatosDePlantilla([
        'creada_por' => $creadaPor, 'es_de_fabrica' => $deFabrica,
    ])));

    expect($plantilla(null, true))->toBeNull()
        ->and($plantilla(rendimientoUsuario(), false))->toBeNull()
        ->and($plantilla(rendimientoUsuario(), true))->toBe('23514')
        ->and($plantilla(null, false))->toBe('23514');
});

test('RNF-03 (RNF-09): el umbral tiene los mismos límites que la plantilla', function () {
    $umbral = fn (array $extra) => rendimientoRechazo(fn () => rendimientoInsertar('umbrales_rendimiento', rendimientoDatosDeUmbral($extra)));

    expect($umbral(['calificacion_minima' => '1.00']))->toBeNull()
        ->and($umbral(['calificacion_minima' => '10.00']))->toBeNull()
        ->and($umbral(['calificacion_minima' => '0.00']))->toBe('23514')
        ->and($umbral(['calificacion_minima' => '10.01']))->toBe('23514')
        ->and($umbral(['ventana_partidos' => 0]))->toBe('23514')
        ->and($umbral(['plantilla_id' => rendimientoInsertar('plantillas_evaluacion', rendimientoDatosDePlantilla())]))->toBeNull()
        ->and($umbral(['plantilla_id' => Identificador::generar()->valor()]))->toBe('23503');
});

test('RNF-03 (HU-15, DA-04): un solo umbral vigente por jugador, equipo y entrenador; cerrarlo permite abrir otro', function () {
    $datos = rendimientoDatosDeUmbral();
    $vigente = rendimientoInsertar('umbrales_rendimiento', $datos);
    $abrir = fn (array $extra = []) => rendimientoRechazo(fn () => rendimientoInsertar('umbrales_rendimiento', [...$datos, ...$extra]));

    expect($abrir())->toBe('23505')
        ->and($abrir(['entrenador_id' => rendimientoUsuario()]))->toBeNull()
        ->and($abrir(['vigente_hasta' => '2026-09-16 10:30:00+00']))->toBeNull();

    DB::connection('propietario')->table('umbrales_rendimiento')->where('id', $vigente)->update(['vigente_hasta' => '2026-09-16 10:30:00+00']);

    expect($abrir(['vigente_desde' => '2026-09-16 10:30:00+00']))->toBeNull();
});

test('RNF-03 (RN-08): el resultado de una evaluación solo admite pasa, no_pasa o datos_insuficientes', function () {
    $evaluar = fn (array $extra) => rendimientoRechazo(fn () => rendimientoInsertar('evaluaciones_rendimiento', rendimientoDatosDeEvaluacion($extra)));

    expect($evaluar(['resultado' => 'pasa']))->toBeNull()
        ->and($evaluar(['resultado' => 'no_pasa', 'promedio' => '5.40']))->toBeNull()
        ->and($evaluar(['resultado' => 'bajo']))->toBe('23514');
});

test('RNF-03 (DD-10, DA-06): el promedio es nulo si y solo si el resultado es datos_insuficientes', function () {
    $evaluar = fn (?string $promedio, string $resultado) => rendimientoRechazo(fn () => rendimientoInsertar('evaluaciones_rendimiento', rendimientoDatosDeEvaluacion([
        'promedio' => $promedio, 'resultado' => $resultado, 'partidos_considerados' => 4,
    ])));

    expect($evaluar(null, 'datos_insuficientes'))->toBeNull()
        ->and($evaluar('7.00', 'pasa'))->toBeNull()
        ->and($evaluar('7.00', 'datos_insuficientes'))->toBe('23514')
        ->and($evaluar(null, 'pasa'))->toBe('23514')
        ->and($evaluar(null, 'no_pasa'))->toBe('23514');
});

test('RNF-03 (DA-05): la evaluación copia la calificación mínima y la ventana aplicadas; umbral y plantilla son opcionales', function () {
    $plantilla = rendimientoInsertar('plantillas_evaluacion', rendimientoDatosDePlantilla());
    $umbral = rendimientoInsertar('umbrales_rendimiento', rendimientoDatosDeUmbral());
    $evaluar = fn (array $extra) => rendimientoRechazo(fn () => rendimientoInsertar('evaluaciones_rendimiento', rendimientoDatosDeEvaluacion($extra)));

    expect($evaluar(['plantilla_id' => $plantilla]))->toBeNull()
        ->and($evaluar(['umbral_id' => $umbral, 'plantilla_id' => $plantilla]))->toBeNull()
        ->and($evaluar(['umbral_id' => null, 'plantilla_id' => null]))->toBeNull()
        ->and($evaluar(['calificacion_minima' => null]))->toBe('23502')
        ->and($evaluar(['ventana_partidos' => null]))->toBe('23502')
        ->and($evaluar(['umbral_id' => Identificador::generar()->valor()]))->toBe('23503')
        ->and($evaluar(['plantilla_id' => Identificador::generar()->valor()]))->toBe('23503');
});

test('RNF-02: evaluaciones_rendimiento es de solo inserción, para la aplicación y para el propietario', function () {
    rendimientoInsertar('evaluaciones_rendimiento', rendimientoDatosDeEvaluacion());

    expect(rendimientoRechazo(fn () => DB::table('evaluaciones_rendimiento')->update(['partidos_considerados' => 1])))->not->toBeNull()
        ->and(rendimientoRechazo(fn () => DB::table('evaluaciones_rendimiento')->delete()))->not->toBeNull()
        ->and(rendimientoRechazo(fn () => DB::connection('propietario')->table('evaluaciones_rendimiento')->update(['partidos_considerados' => 1])))->not->toBeNull()
        ->and(rendimientoRechazo(fn () => DB::connection('propietario')->table('evaluaciones_rendimiento')->delete()))->not->toBeNull()
        ->and(DB::connection('propietario')->table('evaluaciones_rendimiento')->count())->toBe(1);
});

test('RNF-03: los índices de §5.1 existen con sus columnas', function () {
    $indices = collect(DB::connection('propietario')->select(
        "select indexname, indexdef from pg_indexes where schemaname = 'public' and tablename in ('rendimientos_partido', 'valores_metrica', 'plantillas_evaluacion', 'umbrales_rendimiento', 'evaluaciones_rendimiento')",
    ))->pluck('indexdef', 'indexname');

    expect($indices['uq_rendimientos_partido_partido_jugador'])->toContain('(partido_id, jugador_id) WHERE (eliminado_en IS NULL)')
        ->and($indices['ix_rendimientos_partido_jugador_partido'])->toContain('(jugador_id, partido_id)')
        ->and($indices['ix_rendimientos_partido_registrado_por'])->toContain('(registrado_por)')
        ->and($indices['uq_valores_metrica_rendimiento_metrica'])->toContain('(rendimiento_id, metrica_id) WHERE (eliminado_en IS NULL)')
        ->and($indices['uq_plantillas_evaluacion_nombre_posicion'])->toContain('(nombre, posicion_id) WHERE (eliminado_en IS NULL)')
        ->and($indices['uq_umbrales_rendimiento_vigente'])->toContain('(jugador_id, equipo_id, entrenador_id) WHERE ((vigente_hasta IS NULL) AND (eliminado_en IS NULL))')
        ->and($indices['ix_evaluaciones_rendimiento_jugador_equipo_entrenador'])->toContain('(jugador_id, equipo_id, entrenador_id, calculado_en DESC)');
});

test('RNF-03: las claves foráneas de Rendimiento no borran en cascada', function () {
    $captura = rendimientoDatosDeCaptura();
    $rendimiento = rendimientoInsertar('rendimientos_partido', $captura);
    $metrica = rendimientoMetrica();
    rendimientoInsertar('valores_metrica', ['rendimiento_id' => $rendimiento, 'metrica_id' => $metrica, 'valor' => '1']);
    DB::connection('propietario')->table('metricas_posicion')->insert(['metrica_id' => $metrica, 'posicion_id' => $captura['posicion_id'], 'obligatoria' => true, 'orden' => 1]);
    $plantilla = rendimientoInsertar('plantillas_evaluacion', rendimientoDatosDePlantilla());
    $umbralDatos = rendimientoDatosDeUmbral(['plantilla_id' => $plantilla]);
    $umbral = rendimientoInsertar('umbrales_rendimiento', $umbralDatos);
    rendimientoInsertar('evaluaciones_rendimiento', rendimientoDatosDeEvaluacion(['umbral_id' => $umbral, 'plantilla_id' => $plantilla]));
    $borrar = fn (string $tabla, string $id) => rendimientoRechazo(fn () => DB::connection('propietario')->table($tabla)->where('id', $id)->delete());

    expect($borrar('rendimientos_partido', $rendimiento))->toBe('23503')
        ->and($borrar('metricas', $metrica))->toBe('23503')
        ->and($borrar('partidos', $captura['partido_id']))->toBe('23503')
        ->and($borrar('jugadores', $captura['jugador_id']))->toBe('23503')
        ->and($borrar('usuarios', $captura['registrado_por']))->toBe('23503')
        ->and($borrar('posiciones', $captura['posicion_id']))->toBe('23503')
        ->and($borrar('plantillas_evaluacion', $plantilla))->toBe('23503')
        ->and($borrar('umbrales_rendimiento', $umbral))->toBe('23503')
        ->and($borrar('equipos', $umbralDatos['equipo_id']))->toBe('23503')
        ->and($borrar('usuarios', $umbralDatos['entrenador_id']))->toBe('23503');
});

test('RNF-02: cada tabla de historial de Rendimiento es de solo inserción, para la aplicación y para el propietario', function () {
    $rendimiento = rendimientoInsertar('rendimientos_partido', rendimientoDatosDeCaptura());
    $entidades = [
        'rendimientos_partido_historial' => $rendimiento,
        'valores_metrica_historial' => rendimientoInsertar('valores_metrica', ['rendimiento_id' => $rendimiento, 'metrica_id' => rendimientoMetrica(), 'valor' => '1']),
        'plantillas_evaluacion_historial' => rendimientoInsertar('plantillas_evaluacion', rendimientoDatosDePlantilla()),
        'umbrales_rendimiento_historial' => rendimientoInsertar('umbrales_rendimiento', rendimientoDatosDeUmbral()),
    ];

    foreach (HISTORIALES_DE_RENDIMIENTO as $tabla => $columna) {
        DB::connection('propietario')->table($tabla)->insert([
            'id' => Identificador::generar()->valor(), $columna => $entidades[$tabla], 'version' => 1, 'operacion' => 'crear',
            'datos_nuevos' => json_encode(['dato' => 'x']), 'usuario_id' => null, 'ocurrido_en' => '2026-09-15 10:30:00+00',
        ]);

        expect(rendimientoRechazo(fn () => DB::table($tabla)->update(['motivo' => 'x'])))->not->toBeNull("UPDATE de la aplicación sobre {$tabla}")
            ->and(rendimientoRechazo(fn () => DB::table($tabla)->delete()))->not->toBeNull("DELETE de la aplicación sobre {$tabla}")
            ->and(rendimientoRechazo(fn () => DB::connection('propietario')->table($tabla)->update(['motivo' => 'x'])))->not->toBeNull("UPDATE del propietario sobre {$tabla}")
            ->and(rendimientoRechazo(fn () => DB::connection('propietario')->table($tabla)->delete()))->not->toBeNull("DELETE del propietario sobre {$tabla}")
            ->and(DB::connection('propietario')->table($tabla)->count())->toBe(1);
    }
});

test('RNF-02: el cambio en una tabla de Rendimiento y su historial se escriben en la misma transacción, y un fallo revierte los dos', function () {
    $autor = rendimientoUsuario();
    $usuario = new class($autor) implements UsuarioActual
    {
        public function __construct(private readonly string $id) {}

        public function identificador(): Identificador
        {
            return Identificador::desdeTexto($this->id);
        }

        public function roles(): array
        {
            return ['entrenador'];
        }
    };
    $escritor = new EscritorDeHistorial(DB::connection(), new RelojFijo(new DateTimeImmutable('2026-09-15 10:30:00+00')), $usuario);
    $datos = rendimientoDatosDePlantilla(['creada_por' => $autor]);
    $id = Identificador::generar();

    $escritor->registrar('plantillas_evaluacion', 'plantilla_id', $id, 'crear', null, ['nombre' => $datos['nombre']], fn () => DB::table('plantillas_evaluacion')->insert(['id' => $id->valor(), ...$datos]));

    $fila = DB::table('plantillas_evaluacion_historial')->where('plantilla_id', $id->valor())->first();
    expect($fila->usuario_id)->toBe($autor)
        ->and($fila->version)->toBe(1)
        ->and(json_decode($fila->datos_nuevos, true))->toBe(['nombre' => $datos['nombre']]);

    $otro = Identificador::generar();
    $fallido = rendimientoRechazo(fn () => $escritor->registrar('plantillas_evaluacion', 'plantilla_id', $otro, 'crear', null, [], function () use ($otro, $datos) {
        DB::table('plantillas_evaluacion')->insert(['id' => $otro->valor(), ...$datos, 'nombre' => 'Otra']);
        DB::table('plantillas_evaluacion')->insert(['id' => Identificador::generar()->valor(), ...$datos, 'nombre' => 'Otra']);
    }));

    expect($fallido)->toBe('23505')
        ->and(DB::table('plantillas_evaluacion')->where('id', $otro->valor())->count())->toBe(0)
        ->and(DB::table('plantillas_evaluacion_historial')->where('plantilla_id', $otro->valor())->count())->toBe(0);
});

test('RNF-02: cada tabla de Rendimiento con historial tiene su columna de entidad y la auditoría básica', function () {
    foreach (HISTORIALES_DE_RENDIMIENTO as $tabla => $columna) {
        expect(Schema::connection('propietario')->hasColumn($tabla, $columna))->toBeTrue($tabla);
    }
    foreach (TABLAS_DE_RENDIMIENTO_CON_AUDITORIA as $tabla) {
        expect(Schema::connection('propietario')->hasColumns($tabla, ['creado_en', 'creado_por', 'actualizado_en', 'actualizado_por', 'eliminado_en', 'eliminado_por']))->toBeTrue($tabla);
    }
    foreach (['metricas_posicion', 'evaluaciones_rendimiento'] as $tabla) {
        expect(Schema::connection('propietario')->hasColumn($tabla, 'eliminado_en'))->toBeFalse($tabla);
    }
});

test('RNF-03: migrate:rollback de Rendimiento no falla y se vuelve a aplicar', function () {
    esquemaMarcarSucio();
    $tablas = [...TABLAS_DE_RENDIMIENTO, ...array_keys(HISTORIALES_DE_RENDIMIENTO)];

    consolaArtisan('migrate:rollback', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    foreach ($tablas as $tabla) {
        expect(Schema::connection('propietario')->hasTable($tabla))->toBeFalse($tabla);
    }

    consolaArtisan('migrate', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    foreach ($tablas as $tabla) {
        expect(Schema::connection('propietario')->hasTable($tabla))->toBeTrue($tabla);
    }
});
