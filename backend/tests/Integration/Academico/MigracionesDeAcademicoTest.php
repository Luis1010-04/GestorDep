<?php

use Database\Seeders\CriterioGeneralSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ParametrosInstitucionalesSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Compartido\Dominio\Identificador;

const TABLAS_DE_ACADEMICO = [
    'periodos_escolares', 'asignaturas', 'materias', 'inscripciones', 'criterios_aprobacion',
    'calificaciones', 'asistencias_clase', 'resumenes_academicos', 'parametros_institucionales',
];

const HISTORIALES_DE_ACADEMICO = [
    'materias_historial' => 'materia_id',
    'inscripciones_historial' => 'inscripcion_id',
    'criterios_aprobacion_historial' => 'criterio_aprobacion_id',
    'calificaciones_historial' => 'calificacion_id',
    'asistencias_clase_historial' => 'asistencia_clase_id',
    'parametros_institucionales_historial' => 'parametro_institucional_id',
];

beforeEach(function () {
    esquemaReiniciar();
});

/** @param  array<string, mixed>  $atributos */
function academicoInsertar(string $tabla, array $atributos): string
{
    $id = Identificador::generar()->valor();
    DB::connection('propietario')->table($tabla)->insert(['id' => $id, ...$atributos]);

    return $id;
}

function academicoRechazo(callable $operacion): ?string
{
    try {
        $operacion();
    } catch (QueryException $e) {
        return (string) $e->getCode();
    }

    return null;
}

function academicoEliminar(string $tabla, string $id): void
{
    DB::connection('propietario')->table($tabla)->where('id', $id)->update(['eliminado_en' => '2026-03-15 10:30:00+00']);
}

function academicoDocente(): string
{
    return academicoInsertar('usuarios', [
        'nombre' => 'Docente de prueba',
        'correo' => Identificador::generar()->valor().'@example.test',
        'contrasena_hash' => 'hash-de-prueba',
    ]);
}

function academicoJugador(string $matricula = 'M-001'): string
{
    return academicoInsertar('jugadores', [
        'nombres' => 'Jugador', 'apellidos' => 'De Prueba', 'fecha_nacimiento' => '2009-05-10', 'matricula' => $matricula,
    ]);
}

/** @param  array<string, mixed>  $atributos */
function academicoPeriodo(array $atributos = []): string
{
    return academicoInsertar('periodos_escolares', ['nombre' => '2026-1', 'fecha_inicio' => '2026-01-12', 'fecha_fin' => '2026-06-30', ...$atributos]);
}

/** @param  array<string, mixed>  $atributos */
function academicoAsignatura(array $atributos = []): string
{
    return academicoInsertar('asignaturas', ['clave' => 'MAT-101', 'nombre' => 'Matemáticas', 'grado' => 1, ...$atributos]);
}

/** @param  array<string, mixed>  $atributos */
function academicoMateria(array $atributos = []): string
{
    return academicoInsertar('materias', [
        'asignatura_id' => academicoAsignatura(['clave' => 'A-'.substr(Identificador::generar()->valor(), -8)]),
        'periodo_id' => academicoPeriodo(['nombre' => 'P-'.Identificador::generar()->valor()]),
        'docente_id' => academicoDocente(),
        ...$atributos,
    ]);
}

/** @param  array<string, mixed>  $atributos */
function academicoCriterioGeneral(array $atributos = []): string
{
    return academicoInsertar('criterios_aprobacion', [
        'ambito' => 'general', 'materia_id' => null, 'calificacion_minima' => '7.00', 'vigente_desde' => '2026-01-01', ...$atributos,
    ]);
}

function academicoInscripcion(?string $materia = null, ?string $jugador = null): string
{
    return academicoInsertar('inscripciones', [
        'materia_id' => $materia ?? academicoMateria(),
        'jugador_id' => $jugador ?? academicoJugador('M-'.substr(Identificador::generar()->valor(), -8)),
    ]);
}

/**
 * @param  array<string, mixed>  $atributos
 * @return array<string, mixed>
 */
function academicoCalificacion(string $inscripcion, string $criterio, array $atributos = []): array
{
    return [
        'inscripcion_id' => $inscripcion, 'tipo' => 'parcial', 'numero_parcial' => 1, 'valor' => '8.50',
        'criterio_id' => $criterio, 'fecha' => '2026-03-10', ...$atributos,
    ];
}

test('RNF-03 (§7.11 fila 1): una calificación de 10.01 o de -0.01 la rechaza la base', function () {
    $inscripcion = academicoInscripcion();
    $criterio = academicoCriterioGeneral();

    expect(academicoRechazo(fn () => academicoInsertar('calificaciones', academicoCalificacion($inscripcion, $criterio, ['valor' => '10.01']))))->toBe('23514')
        ->and(academicoRechazo(fn () => academicoInsertar('calificaciones', academicoCalificacion($inscripcion, $criterio, ['valor' => '-0.01']))))->toBe('23514')
        ->and(academicoRechazo(fn () => academicoInsertar('calificaciones', academicoCalificacion($inscripcion, $criterio, ['valor' => '0.00']))))->toBeNull()
        ->and(academicoRechazo(fn () => academicoInsertar('calificaciones', academicoCalificacion($inscripcion, $criterio, ['numero_parcial' => 2, 'valor' => '10.00']))))->toBeNull();
});

test('RNF-03 (§7.11 fila 2): numero_parcial con tipo final, nulo con parcial o fuera de 1 a 10 lo rechaza la base', function () {
    $inscripcion = academicoInscripcion();
    $criterio = academicoCriterioGeneral();
    $insertar = fn (array $extra) => academicoRechazo(fn () => academicoInsertar('calificaciones', academicoCalificacion($inscripcion, $criterio, $extra)));

    expect($insertar(['tipo' => 'final', 'numero_parcial' => 1]))->toBe('23514')
        ->and($insertar(['tipo' => 'parcial', 'numero_parcial' => null]))->toBe('23514')
        ->and($insertar(['tipo' => 'parcial', 'numero_parcial' => 0]))->toBe('23514')
        ->and($insertar(['tipo' => 'parcial', 'numero_parcial' => 11]))->toBe('23514')
        ->and($insertar(['tipo' => 'otro', 'numero_parcial' => 1]))->toBe('23514')
        ->and($insertar(['tipo' => 'parcial', 'numero_parcial' => 1]))->toBeNull()
        ->and($insertar(['tipo' => 'parcial', 'numero_parcial' => 10]))->toBeNull()
        ->and($insertar(['tipo' => 'final', 'numero_parcial' => null]))->toBeNull();
});

test('RNF-03 (§7.11 fila 3): dos finales de la misma inscripción o dos parciales con el mismo número chocan', function () {
    $inscripcion = academicoInscripcion();
    $otra = academicoInscripcion();
    $criterio = academicoCriterioGeneral();
    $insertar = fn (string $de, array $extra) => academicoRechazo(fn () => academicoInsertar('calificaciones', academicoCalificacion($de, $criterio, $extra)));

    expect($insertar($inscripcion, ['tipo' => 'final', 'numero_parcial' => null]))->toBeNull()
        ->and($insertar($inscripcion, ['tipo' => 'final', 'numero_parcial' => null]))->toBe('23505')
        ->and($insertar($otra, ['tipo' => 'final', 'numero_parcial' => null]))->toBeNull()
        ->and($insertar($inscripcion, ['numero_parcial' => 1]))->toBeNull()
        ->and($insertar($inscripcion, ['numero_parcial' => 1]))->toBe('23505')
        ->and($insertar($inscripcion, ['numero_parcial' => 2]))->toBeNull();
});

test('RNF-03 (§7.11 fila 4): tras el borrado lógico se puede insertar otra calificación igual', function () {
    $inscripcion = academicoInscripcion();
    $criterio = academicoCriterioGeneral();
    $primera = academicoInsertar('calificaciones', academicoCalificacion($inscripcion, $criterio));
    $final = academicoInsertar('calificaciones', academicoCalificacion($inscripcion, $criterio, ['tipo' => 'final', 'numero_parcial' => null]));

    academicoEliminar('calificaciones', $primera);
    academicoEliminar('calificaciones', $final);

    expect(academicoRechazo(fn () => academicoInsertar('calificaciones', academicoCalificacion($inscripcion, $criterio))))->toBeNull()
        ->and(academicoRechazo(fn () => academicoInsertar('calificaciones', academicoCalificacion($inscripcion, $criterio, ['tipo' => 'final', 'numero_parcial' => null]))))->toBeNull();
});

test('RN-03 (§7.11 fila 5): dos criterios vigentes de la misma materia o dos generales vigentes chocan', function () {
    $materia = academicoMateria();
    $otraMateria = academicoMateria();
    $propio = fn (string $de, array $extra = []) => academicoRechazo(fn () => academicoInsertar('criterios_aprobacion', [
        'ambito' => 'materia', 'materia_id' => $de, 'calificacion_minima' => '6.00', 'vigente_desde' => '2026-03-10', ...$extra,
    ]));

    expect(academicoRechazo(fn () => academicoCriterioGeneral()))->toBeNull()
        ->and(academicoRechazo(fn () => academicoCriterioGeneral(['calificacion_minima' => '8.00'])))->toBe('23505')
        ->and($propio($materia))->toBeNull()
        ->and($propio($materia, ['calificacion_minima' => '8.00']))->toBe('23505')
        ->and($propio($otraMateria))->toBeNull();
});

test('RN-03: un criterio cerrado o con borrado lógico no bloquea a uno nuevo', function () {
    $materia = academicoMateria();
    $general = academicoCriterioGeneral();
    $propio = fn (array $extra = []) => academicoInsertar('criterios_aprobacion', [
        'ambito' => 'materia', 'materia_id' => $materia, 'calificacion_minima' => '6.00', 'vigente_desde' => '2026-03-10', ...$extra,
    ]);
    $primero = $propio();

    DB::connection('propietario')->table('criterios_aprobacion')->whereIn('id', [$general, $primero])->update(['vigente_hasta' => '2026-03-10']);

    expect(academicoRechazo(fn () => academicoCriterioGeneral(['calificacion_minima' => '8.00'])))->toBeNull()
        ->and(academicoRechazo(fn () => $propio(['calificacion_minima' => '8.00'])))->toBeNull();

    $vigente = DB::connection('propietario')->table('criterios_aprobacion')->where('materia_id', $materia)->whereNull('vigente_hasta')->value('id');
    academicoEliminar('criterios_aprobacion', $vigente);

    expect(academicoRechazo(fn () => $propio(['calificacion_minima' => '9.00'])))->toBeNull();
});

test('RN-03 (§7.11 fila 6): materia_id nulo con ámbito materia, o no nulo con general, lo rechaza la base', function () {
    $materia = academicoMateria();

    expect(academicoRechazo(fn () => academicoInsertar('criterios_aprobacion', [
        'ambito' => 'materia', 'materia_id' => null, 'calificacion_minima' => '6.00', 'vigente_desde' => '2026-03-10',
    ])))->toBe('23514')
        ->and(academicoRechazo(fn () => academicoCriterioGeneral(['materia_id' => $materia])))->toBe('23514')
        ->and(academicoRechazo(fn () => academicoCriterioGeneral(['ambito' => 'otro'])))->toBe('23514');
});

test('RN-01 (§7.11 fila 7): calificacion_minima fuera de 0 a 10 la rechaza la base', function () {
    expect(academicoRechazo(fn () => academicoCriterioGeneral(['calificacion_minima' => '10.01'])))->toBe('23514')
        ->and(academicoRechazo(fn () => academicoCriterioGeneral(['calificacion_minima' => '-0.01'])))->toBe('23514')
        ->and(academicoRechazo(fn () => academicoCriterioGeneral(['calificacion_minima' => '0.00'])))->toBeNull();
});

test('RN-01: los límites de 0 y 10 de calificacion_minima se aceptan para el criterio propio', function () {
    $materia = academicoMateria();
    $propio = fn (string $minima) => academicoRechazo(fn () => academicoInsertar('criterios_aprobacion', [
        'ambito' => 'materia', 'materia_id' => $materia, 'calificacion_minima' => $minima, 'vigente_desde' => '2026-03-10', 'vigente_hasta' => '2026-03-11',
    ]));

    expect($propio('10.00'))->toBeNull()
        ->and($propio('10.01'))->toBe('23514');
});

test('RNF-03 (§7.11 fila 8): un periodo con fecha_fin igual o anterior a fecha_inicio lo rechaza la base', function () {
    expect(academicoRechazo(fn () => academicoPeriodo(['fecha_fin' => '2026-01-12'])))->toBe('23514')
        ->and(academicoRechazo(fn () => academicoPeriodo(['nombre' => 'otro', 'fecha_fin' => '2026-01-11'])))->toBe('23514')
        ->and(academicoRechazo(fn () => academicoPeriodo(['nombre' => 'otro', 'fecha_fin' => '2026-01-13'])))->toBeNull();
});

test('RNF-03 (§7.11 fila 9): el mismo nombre de periodo o la misma clave de asignatura con otras mayúsculas chocan', function () {
    $periodo = academicoPeriodo(['nombre' => 'Primavera 2026']);
    $asignatura = academicoAsignatura(['clave' => 'MAT-101']);

    expect(academicoRechazo(fn () => academicoPeriodo(['nombre' => 'PRIMAVERA 2026'])))->toBe('23505')
        ->and(academicoRechazo(fn () => academicoAsignatura(['clave' => 'mat-101'])))->toBe('23505');

    academicoEliminar('periodos_escolares', $periodo);
    academicoEliminar('asignaturas', $asignatura);

    expect(academicoRechazo(fn () => academicoPeriodo(['nombre' => 'primavera 2026'])))->toBeNull()
        ->and(academicoRechazo(fn () => academicoAsignatura(['clave' => 'Mat-101'])))->toBeNull();
});

test('RNF-03: nombre de periodo de más de 100 caracteres, clave de más de 20 y nombre de más de 150 se rechazan', function () {
    expect(academicoRechazo(fn () => academicoPeriodo(['nombre' => str_repeat('a', 101)])))->toBe('23514')
        ->and(academicoRechazo(fn () => academicoPeriodo(['nombre' => str_repeat('a', 100)])))->toBeNull()
        ->and(academicoRechazo(fn () => academicoAsignatura(['clave' => str_repeat('b', 21)])))->toBe('23514')
        ->and(academicoRechazo(fn () => academicoAsignatura(['clave' => str_repeat('b', 20)])))->toBeNull()
        ->and(academicoRechazo(fn () => academicoAsignatura(['clave' => 'X-1', 'nombre' => str_repeat('c', 151)])))->toBe('22001')
        ->and(academicoRechazo(fn () => academicoAsignatura(['clave' => 'X-2', 'nombre' => str_repeat('c', 150)])))->toBeNull();
});

test('RNF-03 (§7.11 fila 10): un grado fuera de 1 a 12 lo rechaza la base', function () {
    expect(academicoRechazo(fn () => academicoAsignatura(['clave' => 'G-0', 'grado' => 0])))->toBe('23514')
        ->and(academicoRechazo(fn () => academicoAsignatura(['clave' => 'G-13', 'grado' => 13])))->toBe('23514')
        ->and(academicoRechazo(fn () => academicoAsignatura(['clave' => 'G-1', 'grado' => 1])))->toBeNull()
        ->and(academicoRechazo(fn () => academicoAsignatura(['clave' => 'G-12', 'grado' => 12])))->toBeNull();
});

test('RNF-03 (§7.11 fila 11): dos materias, dos inscripciones o dos asistencias iguales chocan', function () {
    $asignatura = academicoAsignatura();
    $periodo = academicoPeriodo();
    $docente = academicoDocente();
    $materia = fn (?string $docenteId = null) => academicoRechazo(fn () => academicoInsertar('materias', [
        'asignatura_id' => $asignatura, 'periodo_id' => $periodo, 'docente_id' => $docenteId ?? $docente,
    ]));

    expect($materia())->toBeNull()
        ->and($materia())->toBe('23505')
        ->and($materia(academicoDocente()))->toBeNull();

    $primera = DB::connection('propietario')->table('materias')->where('docente_id', $docente)->value('id');
    $jugador = academicoJugador();
    $inscribir = fn () => academicoRechazo(fn () => academicoInsertar('inscripciones', ['materia_id' => $primera, 'jugador_id' => $jugador]));

    expect($inscribir())->toBeNull()
        ->and($inscribir())->toBe('23505');

    $inscripcion = DB::connection('propietario')->table('inscripciones')->value('id');
    $asistir = fn (string $fecha = '2026-03-09') => academicoRechazo(fn () => academicoInsertar('asistencias_clase', ['inscripcion_id' => $inscripcion, 'fecha' => $fecha, 'estado' => 'presente']));

    expect($asistir())->toBeNull()
        ->and($asistir())->toBe('23505')
        ->and($asistir('2026-03-10'))->toBeNull();
});

test('RNF-03: el estado de asistencia solo admite presente, falta o falta_justificada', function () {
    $inscripcion = academicoInscripcion();
    $estado = fn (string $valor, string $fecha) => academicoRechazo(fn () => academicoInsertar('asistencias_clase', ['inscripcion_id' => $inscripcion, 'fecha' => $fecha, 'estado' => $valor]));

    expect($estado('presente', '2026-03-01'))->toBeNull()
        ->and($estado('falta', '2026-03-02'))->toBeNull()
        ->and($estado('falta_justificada', '2026-03-03'))->toBeNull()
        ->and($estado('retardo', '2026-03-04'))->toBe('23514');
});

test('RNF-03: las materias, inscripciones y asistencias eliminadas lógicamente liberan su índice único', function () {
    $materia = academicoMateria();
    $jugador = academicoJugador();
    $inscripcion = academicoInsertar('inscripciones', ['materia_id' => $materia, 'jugador_id' => $jugador]);
    $asistencia = academicoInsertar('asistencias_clase', ['inscripcion_id' => $inscripcion, 'fecha' => '2026-03-09', 'estado' => 'presente']);
    $datos = DB::connection('propietario')->table('materias')->where('id', $materia)->first();

    academicoEliminar('asistencias_clase', $asistencia);
    academicoEliminar('inscripciones', $inscripcion);
    academicoEliminar('materias', $materia);

    expect(academicoRechazo(fn () => academicoInsertar('materias', ['asignatura_id' => $datos->asignatura_id, 'periodo_id' => $datos->periodo_id, 'docente_id' => $datos->docente_id])))->toBeNull()
        ->and(academicoRechazo(fn () => academicoInsertar('inscripciones', ['materia_id' => academicoMateria(), 'jugador_id' => $jugador])))->toBeNull()
        ->and(academicoRechazo(fn () => academicoInsertar('asistencias_clase', ['inscripcion_id' => $inscripcion, 'fecha' => '2026-03-09', 'estado' => 'falta'])))->toBeNull();
});

test('RNF-03: las claves foráneas de Académico no borran en cascada', function () {
    $criterio = academicoCriterioGeneral();
    $inscripcion = academicoInscripcion();
    $calificacion = academicoInsertar('calificaciones', academicoCalificacion($inscripcion, $criterio));
    $materia = DB::connection('propietario')->table('inscripciones')->where('id', $inscripcion)->value('materia_id');
    $datos = DB::connection('propietario')->table('materias')->where('id', $materia)->first();
    $borrar = fn (string $tabla, string $id) => academicoRechazo(fn () => DB::connection('propietario')->table($tabla)->where('id', $id)->delete());

    expect($borrar('criterios_aprobacion', $criterio))->toBe('23503')
        ->and($borrar('inscripciones', $inscripcion))->toBe('23503')
        ->and($borrar('materias', $materia))->toBe('23503')
        ->and($borrar('asignaturas', $datos->asignatura_id))->toBe('23503')
        ->and($borrar('periodos_escolares', $datos->periodo_id))->toBe('23503')
        ->and(DB::connection('propietario')->table('calificaciones')->where('id', $calificacion)->count())->toBe(1);
});

test('RNF-02 (§7.11 fila 12): cada tabla de historial de Académico es de solo inserción, para la aplicación y para el propietario', function () {
    $materia = academicoMateria();
    $inscripcion = academicoInscripcion();
    $criterio = academicoCriterioGeneral();
    $entidades = [
        'materias_historial' => $materia,
        'inscripciones_historial' => $inscripcion,
        'criterios_aprobacion_historial' => $criterio,
        'calificaciones_historial' => academicoInsertar('calificaciones', academicoCalificacion($inscripcion, $criterio)),
        'asistencias_clase_historial' => academicoInsertar('asistencias_clase', ['inscripcion_id' => $inscripcion, 'fecha' => '2026-03-09', 'estado' => 'presente']),
        'parametros_institucionales_historial' => academicoInsertar('parametros_institucionales', [
            'clave' => 'prueba', 'valor' => json_encode(['x' => 1]), 'descripcion' => 'Prueba', 'vigente_desde' => '2026-01-01',
        ]),
    ];

    foreach (HISTORIALES_DE_ACADEMICO as $tabla => $columna) {
        DB::connection('propietario')->table($tabla)->insert([
            'id' => Identificador::generar()->valor(),
            $columna => $entidades[$tabla],
            'version' => 1,
            'operacion' => 'crear',
            'datos_nuevos' => json_encode(['dato' => 'x']),
            'usuario_id' => null,
            'ocurrido_en' => '2026-03-15 10:30:00+00',
        ]);

        expect(academicoRechazo(fn () => DB::table($tabla)->update(['motivo' => 'x'])))->not->toBeNull("UPDATE de la aplicación sobre {$tabla}")
            ->and(academicoRechazo(fn () => DB::table($tabla)->delete()))->not->toBeNull("DELETE de la aplicación sobre {$tabla}")
            ->and(academicoRechazo(fn () => DB::connection('propietario')->table($tabla)->update(['motivo' => 'x'])))->not->toBeNull("UPDATE del propietario sobre {$tabla}")
            ->and(academicoRechazo(fn () => DB::connection('propietario')->table($tabla)->delete()))->not->toBeNull("DELETE del propietario sobre {$tabla}")
            ->and(DB::connection('propietario')->table($tabla)->count())->toBe(1);
    }
});

test('RNF-02: periodos y asignaturas no llevan historial, y las demás tablas con historial sí', function () {
    expect(Schema::connection('propietario')->hasTable('periodos_escolares_historial'))->toBeFalse()
        ->and(Schema::connection('propietario')->hasTable('asignaturas_historial'))->toBeFalse()
        ->and(Schema::connection('propietario')->hasTable('resumenes_academicos_historial'))->toBeFalse();

    foreach (HISTORIALES_DE_ACADEMICO as $tabla => $columna) {
        expect(Schema::connection('propietario')->hasColumn($tabla, $columna))->toBeTrue($tabla);
    }
});

test('RNF-04 (§7.11 fila 13): una fila de resumenes_academicos se puede actualizar y borrar, y es única por jugador y periodo', function () {
    $jugador = academicoJugador();
    $periodo = academicoPeriodo();
    $resumen = fn () => academicoRechazo(fn () => academicoInsertar('resumenes_academicos', [
        'jugador_id' => $jugador, 'periodo_id' => $periodo, 'promedio' => '8.00', 'calculado_en' => '2026-03-10 12:00:00+00',
    ]));

    expect($resumen())->toBeNull()
        ->and($resumen())->toBe('23505')
        ->and(DB::table('resumenes_academicos')->where('jugador_id', $jugador)->update(['promedio' => '7.50']))->toBe(1)
        ->and(DB::table('resumenes_academicos')->where('jugador_id', $jugador)->first()->promedio)->toBe('7.50')
        ->and(DB::table('resumenes_academicos')->where('jugador_id', $jugador)->delete())->toBe(1)
        ->and(DB::table('resumenes_academicos')->count())->toBe(0);
});

test('RNF-03: resumenes_academicos acepta los tres niveles de riesgo y nulo, y rechaza otros', function () {
    $periodo = academicoPeriodo();
    $nivel = fn (?string $valor) => academicoRechazo(fn () => academicoInsertar('resumenes_academicos', [
        'jugador_id' => academicoJugador('M-'.substr(Identificador::generar()->valor(), -8)), 'periodo_id' => $periodo,
        'nivel_riesgo' => $valor, 'calculado_en' => '2026-03-10 12:00:00+00',
    ]));

    expect($nivel('sin_riesgo'))->toBeNull()
        ->and($nivel('en_observacion'))->toBeNull()
        ->and($nivel('en_riesgo'))->toBeNull()
        ->and($nivel(null))->toBeNull()
        ->and($nivel('grave'))->toBe('23514');

    $fila = DB::table('resumenes_academicos')->whereNull('nivel_riesgo')->first();
    expect($fila->materias_reprobadas)->toBe(0)
        ->and($fila->materias_sin_calificar)->toBe(0)
        ->and($fila->promedio)->toBeNull()
        ->and($fila->porcentaje_asistencia)->toBeNull();
});

test('RNF-03: las columnas decimales tienen la precisión de la especificación', function () {
    $tipo = fn (string $tabla, string $columna) => DB::connection('propietario')->selectOne(
        'select numeric_precision, numeric_scale from information_schema.columns where table_name = ? and column_name = ?',
        [$tabla, $columna],
    );

    expect([$tipo('calificaciones', 'valor')->numeric_precision, $tipo('calificaciones', 'valor')->numeric_scale])->toBe([4, 2])
        ->and([$tipo('criterios_aprobacion', 'calificacion_minima')->numeric_precision, $tipo('criterios_aprobacion', 'calificacion_minima')->numeric_scale])->toBe([4, 2])
        ->and([$tipo('resumenes_academicos', 'promedio')->numeric_precision, $tipo('resumenes_academicos', 'promedio')->numeric_scale])->toBe([4, 2])
        ->and([$tipo('resumenes_academicos', 'porcentaje_asistencia')->numeric_precision, $tipo('resumenes_academicos', 'porcentaje_asistencia')->numeric_scale])->toBe([5, 2]);
});

test('RNF-03 (§7.11 fila 15): una clave vigente de parametros_institucionales repetida la rechaza la base', function () {
    $parametro = fn (array $extra = []) => academicoRechazo(fn () => academicoInsertar('parametros_institucionales', [
        'clave' => 'riesgo_academico', 'valor' => json_encode(['x' => 1]), 'descripcion' => 'Prueba', 'vigente_desde' => '2026-01-01', ...$extra,
    ]));

    expect($parametro())->toBeNull()
        ->and($parametro())->toBe('23505')
        ->and($parametro(['clave' => 'otra']))->toBeNull();

    DB::connection('propietario')->table('parametros_institucionales')->where('clave', 'riesgo_academico')->update(['vigente_hasta' => '2026-03-01']);

    expect($parametro(['vigente_desde' => '2026-03-02']))->toBeNull();
});

test('RNF-03 (§7.11 fila 14): migrate:rollback de Académico no falla y se vuelve a aplicar', function () {
    esquemaMarcarSucio();
    $tablas = [...TABLAS_DE_ACADEMICO, ...array_keys(HISTORIALES_DE_ACADEMICO)];

    consolaArtisan('migrate:rollback', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    foreach ($tablas as $tabla) {
        expect(Schema::connection('propietario')->hasTable($tabla))->toBeFalse($tabla);
    }

    consolaArtisan('migrate', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    foreach ($tablas as $tabla) {
        expect(Schema::connection('propietario')->hasTable($tabla))->toBeTrue($tabla);
    }
});

test('RNF-03 (§7.11 fila 14): migrate:fresh deja las tablas de Académico, sin errores', function () {
    esquemaMarcarSucio();

    consolaArtisan('migrate:fresh', ['--database' => 'propietario', '--force' => true])->assertSuccessful();

    foreach ([...TABLAS_DE_ACADEMICO, ...array_keys(HISTORIALES_DE_ACADEMICO)] as $tabla) {
        expect(Schema::connection('propietario')->hasTable($tabla))->toBeTrue($tabla);
    }
});

test('RNF-03 (§7.11 fila 17): la huella del esquema de pruebas incluye las tablas nuevas y el reinicio rápido las deja vacías', function () {
    esquemaReconstruir();
    $huella = esquemaEstructura();
    $tablas = DB::connection('propietario')->table('pg_tables')->where('schemaname', 'public')->pluck('tablename')->all();

    expect($tablas)->toContain(...TABLAS_DE_ACADEMICO, ...array_keys(HISTORIALES_DE_ACADEMICO));

    $inscripcion = academicoInscripcion();
    academicoInsertar('calificaciones', academicoCalificacion($inscripcion, academicoCriterioGeneral()));
    academicoInsertar('parametros_institucionales', ['clave' => 'x', 'valor' => '{}', 'descripcion' => 'x', 'vigente_desde' => '2026-01-01']);
    academicoInsertar('resumenes_academicos', [
        'jugador_id' => DB::connection('propietario')->table('inscripciones')->value('jugador_id'),
        'periodo_id' => DB::connection('propietario')->table('periodos_escolares')->value('id'),
        'calculado_en' => '2026-03-10 12:00:00+00',
    ]);

    expect(esquemaReiniciar())->toBe('rapido')
        ->and(esquemaEstructura())->toBe($huella);

    foreach (TABLAS_DE_ACADEMICO as $tabla) {
        expect(DB::connection('propietario')->table($tabla)->count())->toBe(0, $tabla);
    }

    esquemaMarcarSucio();
    DB::connection('propietario')->statement('drop table resumenes_academicos');

    expect(esquemaEstructura())->not->toBe($huella);
});

test('RN-01: CriterioGeneralSeeder siembra un criterio general vigente de 7.00 y es idempotente', function () {
    (new CriterioGeneralSeeder)->run();
    (new CriterioGeneralSeeder)->run();

    $criterios = DB::connection('propietario')->table('criterios_aprobacion')->get();

    expect($criterios)->toHaveCount(1)
        ->and($criterios[0]->ambito)->toBe('general')
        ->and($criterios[0]->materia_id)->toBeNull()
        ->and($criterios[0]->calificacion_minima)->toBe('7.00')
        ->and($criterios[0]->vigente_desde)->toBe('2026-01-01')
        ->and($criterios[0]->vigente_hasta)->toBeNull()
        ->and(DB::connection('propietario')->table('criterios_aprobacion_historial')->count())->toBe(0);
});

test('RN-01: CriterioGeneralSeeder no pisa un criterio general vigente ya ajustado', function () {
    academicoCriterioGeneral(['calificacion_minima' => '8.00']);

    (new CriterioGeneralSeeder)->run();

    expect(DB::connection('propietario')->table('criterios_aprobacion')->pluck('calificacion_minima')->all())->toBe(['8.00']);
});

test('RN-04: ParametrosInstitucionalesSeeder siembra solo riesgo_academico con los valores de §5.3 y es idempotente', function () {
    (new ParametrosInstitucionalesSeeder)->run();
    (new ParametrosInstitucionalesSeeder)->run();

    $parametros = DB::connection('propietario')->table('parametros_institucionales')->get();

    expect($parametros)->toHaveCount(1)
        ->and($parametros[0]->clave)->toBe('riesgo_academico')
        ->and($parametros[0]->vigente_hasta)->toBeNull()
        ->and(json_decode($parametros[0]->valor, true))->toEqual([
            'promedio_sin_riesgo_minimo' => '7.50',
            'promedio_observacion_minimo' => '7.00',
            'reprobadas_observacion' => 1,
            'reprobadas_riesgo' => 2,
        ])
        ->and(DB::connection('propietario')->table('parametros_institucionales_historial')->count())->toBe(0);
});

test('RN-04: ParametrosInstitucionalesSeeder no pisa un parámetro vigente ya ajustado', function () {
    academicoInsertar('parametros_institucionales', [
        'clave' => 'riesgo_academico', 'valor' => json_encode(['ajustado' => true]), 'descripcion' => 'Ajustado', 'vigente_desde' => '2026-02-01',
    ]);

    (new ParametrosInstitucionalesSeeder)->run();

    expect(DB::connection('propietario')->table('parametros_institucionales')->count())->toBe(1)
        ->and(json_decode((string) DB::connection('propietario')->table('parametros_institucionales')->value('valor'), true))->toBe(['ajustado' => true]);
});

test('RN-04: DatabaseSeeder siembra el criterio general y el parámetro, también en producción', function () {
    app()->detectEnvironment(fn () => 'production');

    (new DatabaseSeeder)->run();
    (new DatabaseSeeder)->run();

    expect(DB::connection('propietario')->table('criterios_aprobacion')->count())->toBe(1)
        ->and(DB::connection('propietario')->table('parametros_institucionales')->count())->toBe(1)
        ->and(DB::connection('propietario')->table('usuarios')->count())->toBe(0);
});
