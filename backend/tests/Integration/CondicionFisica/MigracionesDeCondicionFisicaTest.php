<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Compartido\Dominio\Identificador;
use Tests\Ayudantes\NoNulo;

const TABLAS_DE_CONDICION_FISICA = ['mediciones_corporales', 'tipos_prueba', 'pruebas_fisicas', 'rangos_mediciones_corporales'];

const HISTORIALES_DE_CONDICION_FISICA = [
    'mediciones_corporales_historial' => 'medicion_id',
    'pruebas_fisicas_historial' => 'prueba_id',
];

beforeEach(function () {
    esquemaReiniciar();
});

/** @param  array<string, mixed>  $atributos */
function fisicaInsertar(string $tabla, array $atributos): string
{
    $id = Identificador::generar()->valor();
    DB::connection('propietario')->table($tabla)->insert(['id' => $id, ...$atributos]);

    return $id;
}

function fisicaRechazo(callable $operacion): ?string
{
    try {
        $operacion();
    } catch (QueryException $e) {
        return (string) $e->getCode();
    }

    return null;
}

function fisicaEliminar(string $tabla, string $id): void
{
    DB::connection('propietario')->table($tabla)->where('id', $id)->update(['eliminado_en' => '2026-11-15 10:30:00+00']);
}

function fisicaUnico(): string
{
    return substr(Identificador::generar()->valor(), -12);
}

function fisicaUsuario(): string
{
    return fisicaInsertar('usuarios', [
        'nombre' => 'Entrenador de prueba', 'correo' => fisicaUnico().'@example.test', 'contrasena_hash' => 'hash-de-prueba',
    ]);
}

function fisicaEquipo(): string
{
    return fisicaInsertar('equipos', [
        'nombre' => 'Equipo '.fisicaUnico(),
        'categoria_id' => fisicaInsertar('categorias', ['nombre' => 'Sub-'.fisicaUnico()]),
    ]);
}

function fisicaJugador(): string
{
    return fisicaInsertar('jugadores', [
        'nombres' => 'Jugador', 'apellidos' => 'De Prueba', 'fecha_nacimiento' => '2009-05-10', 'matricula' => 'M-'.fisicaUnico(),
    ]);
}

/**
 * @param  array<string, mixed>  $atributos
 * @return array<string, mixed>
 */
function fisicaDatosDeMedicion(array $atributos = []): array
{
    return [
        'jugador_id' => fisicaJugador(), 'equipo_id' => fisicaEquipo(), 'registrado_por' => fisicaUsuario(),
        'fecha' => '2026-11-03', 'periodo' => '2026-11', 'peso_kg' => '70.00', 'estatura_cm' => '175.0', ...$atributos,
    ];
}

/** @param  array<string, mixed>  $atributos */
function fisicaTipoDePrueba(array $atributos = []): string
{
    return fisicaInsertar('tipos_prueba', [
        'codigo' => 't_'.fisicaUnico(), 'nombre' => 'Prueba', 'unidad' => 's', 'mejor_resultado' => 'menor',
        'minimo' => '1.00', 'maximo' => '60.00', ...$atributos,
    ]);
}

/**
 * @param  array<string, mixed>  $atributos
 * @return array<string, mixed>
 */
function fisicaDatosDePrueba(array $atributos = []): array
{
    return [
        'jugador_id' => fisicaJugador(), 'equipo_id' => fisicaEquipo(), 'registrado_por' => fisicaUsuario(), 'tipo_prueba_id' => fisicaTipoDePrueba(),
        'fecha' => '2026-11-03', 'periodo' => '2026-11', 'resultado' => '4.35', ...$atributos,
    ];
}

/**
 * @param  array<string, mixed>  $atributos
 * @return array<string, mixed>
 */
function fisicaDatosDeRango(array $atributos = []): array
{
    return ['codigo' => 'peso_kg', 'nombre' => 'Peso', 'unidad' => 'kg', 'minimo' => '20.00', 'maximo' => '200.00', ...$atributos];
}

function fisicaImc(string $id): string
{
    $imc = DB::connection('propietario')->table('mediciones_corporales')->where('id', $id)->value('imc');

    return is_string($imc) ? $imc : '';
}

test('RNF-03 (DA-05, §7.5 filas 1 a 3): la base calcula el IMC redondeado a dos decimales', function (string $peso, string $estatura, string $imc) {
    $id = fisicaInsertar('mediciones_corporales', fisicaDatosDeMedicion(['peso_kg' => $peso, 'estatura_cm' => $estatura]));

    expect(fisicaImc($id))->toBe($imc);
})->with([
    'fila 1' => ['70.00', '175.0', '22.86'],
    'fila 2' => ['72.50', '180.0', '22.38'],
    'fila 3, 12.505 se redondea hacia arriba' => ['50.02', '200.0', '12.51'],
]);

test('RNF-03 (DA-05): el IMC es una columna generada: no se captura y cambia con el peso y la estatura', function () {
    $id = fisicaInsertar('mediciones_corporales', fisicaDatosDeMedicion());

    DB::connection('propietario')->table('mediciones_corporales')->where('id', $id)->update(['peso_kg' => '72.50', 'estatura_cm' => '180.0']);

    expect(fisicaImc($id))->toBe('22.38')
        ->and(fisicaRechazo(fn () => fisicaInsertar('mediciones_corporales', fisicaDatosDeMedicion(['imc' => '10.00']))))->toBe('428C9');
});

test('RNF-03: los tipos de las columnas de mediciones_corporales son los de §5.1', function () {
    $columnas = collect(DB::connection('propietario')->select(
        "select column_name, data_type, numeric_precision, numeric_scale, character_maximum_length from information_schema.columns where table_name = 'mediciones_corporales'",
    ))->keyBy('column_name');
    $numerico = fn (string $columna) => [$columnas[$columna]->numeric_precision, $columnas[$columna]->numeric_scale];

    expect($numerico('peso_kg'))->toBe([5, 2])
        ->and($numerico('estatura_cm'))->toBe([5, 1])
        ->and($numerico('imc'))->toBe([5, 2])
        ->and($columnas['fecha']->data_type)->toBe('date')
        ->and($columnas['periodo']->data_type)->toBe('character')
        ->and($columnas['periodo']->character_maximum_length)->toBe(7);
});

test('RNF-03: el peso debe ser positivo', function (string $peso) {
    expect(fisicaRechazo(fn () => fisicaInsertar('mediciones_corporales', fisicaDatosDeMedicion(['peso_kg' => $peso]))))->toBe('23514');
})->with(['cero' => '0.00', 'negativo' => '-1.00']);

test('RNF-03: una estatura negativa la rechaza el CHECK', function () {
    expect(fisicaRechazo(fn () => fisicaInsertar('mediciones_corporales', fisicaDatosDeMedicion(['estatura_cm' => '-175.0']))))->toBe('23514');
});

test('RNF-03: una estatura de cero no entra: la columna generada falla por división entre cero antes del CHECK', function () {
    expect(fisicaRechazo(fn () => fisicaInsertar('mediciones_corporales', fisicaDatosDeMedicion(['estatura_cm' => '0.0']))))->toBe('22012')
        ->and(DB::connection('propietario')->table('mediciones_corporales')->count())->toBe(0);
});

test('RNF-03 (DA-03): el periodo de una medición debe coincidir con su fecha', function () {
    expect(fisicaRechazo(fn () => fisicaInsertar('mediciones_corporales', fisicaDatosDeMedicion(['fecha' => '2026-11-03', 'periodo' => '2026-10']))))->toBe('23514')
        ->and(fisicaRechazo(fn () => fisicaInsertar('mediciones_corporales', fisicaDatosDeMedicion(['fecha' => '2026-10-31', 'periodo' => '2026-10']))))->toBeNull();
});

test('RNF-03 (DD-59, DA-13): una medición por jugador, registrante y periodo; otro entrenador u otro periodo no chocan, y el equipo no cuenta', function () {
    $datos = fisicaDatosDeMedicion();
    fisicaInsertar('mediciones_corporales', $datos);

    expect(fisicaRechazo(fn () => fisicaInsertar('mediciones_corporales', $datos)))->toBe('23505')
        ->and(fisicaRechazo(fn () => fisicaInsertar('mediciones_corporales', [...$datos, 'equipo_id' => fisicaEquipo()])))->toBe('23505')
        ->and(fisicaRechazo(fn () => fisicaInsertar('mediciones_corporales', [...$datos, 'registrado_por' => fisicaUsuario()])))->toBeNull()
        ->and(fisicaRechazo(fn () => fisicaInsertar('mediciones_corporales', [...$datos, 'fecha' => '2026-10-05', 'periodo' => '2026-10'])))->toBeNull();
});

test('RNF-03: el borrado lógico libera la unicidad de la medición', function () {
    $datos = fisicaDatosDeMedicion();
    $id = fisicaInsertar('mediciones_corporales', $datos);

    fisicaEliminar('mediciones_corporales', $id);

    expect(fisicaRechazo(fn () => fisicaInsertar('mediciones_corporales', $datos)))->toBeNull();
});

test('RNF-03: el código del tipo de prueba es único y mejor_resultado solo admite mayor o menor', function () {
    fisicaTipoDePrueba(['codigo' => 'velocidad']);

    expect(fisicaRechazo(fn () => fisicaTipoDePrueba(['codigo' => 'velocidad'])))->toBe('23505')
        ->and(fisicaRechazo(fn () => fisicaTipoDePrueba(['mejor_resultado' => 'igual'])))->toBe('23514')
        ->and(fisicaRechazo(fn () => fisicaTipoDePrueba(['mejor_resultado' => 'mayor'])))->toBeNull();
});

test('RNF-03 (DA-16): el rango de un tipo de prueba es obligatorio, con mínimo positivo y máximo mayor que el mínimo', function () {
    expect(fisicaRechazo(fn () => fisicaTipoDePrueba(['minimo' => null])))->toBe('23502')
        ->and(fisicaRechazo(fn () => fisicaTipoDePrueba(['maximo' => null])))->toBe('23502')
        ->and(fisicaRechazo(fn () => fisicaTipoDePrueba(['minimo' => '0.00'])))->toBe('23514')
        ->and(fisicaRechazo(fn () => fisicaTipoDePrueba(['minimo' => '60.00', 'maximo' => '60.00'])))->toBe('23514')
        ->and(fisicaRechazo(fn () => fisicaTipoDePrueba(['minimo' => '60.00', 'maximo' => '1.00'])))->toBe('23514');
});

test('RNF-03 (DA-16): rangos_mediciones_corporales solo admite peso y estatura, sin repetir el código', function () {
    fisicaInsertar('rangos_mediciones_corporales', fisicaDatosDeRango());

    expect(fisicaRechazo(fn () => fisicaInsertar('rangos_mediciones_corporales', fisicaDatosDeRango())))->toBe('23505')
        ->and(fisicaRechazo(fn () => fisicaInsertar('rangos_mediciones_corporales', fisicaDatosDeRango(['codigo' => 'imc']))))->toBe('23514')
        ->and(fisicaRechazo(fn () => fisicaInsertar('rangos_mediciones_corporales', fisicaDatosDeRango(['codigo' => 'estatura_cm', 'unidad' => 'cm']))))->toBeNull();
});

test('RNF-03 (DA-16): el rango de una medida es obligatorio, con mínimo positivo y máximo mayor que el mínimo', function (array $atributos, string $codigo) {
    expect(fisicaRechazo(fn () => fisicaInsertar('rangos_mediciones_corporales', fisicaDatosDeRango($atributos))))->toBe($codigo);
})->with([
    'sin mínimo' => [['minimo' => null], '23502'],
    'sin máximo' => [['maximo' => null], '23502'],
    'mínimo cero' => [['minimo' => '0.00'], '23514'],
    'máximo igual al mínimo' => [['minimo' => '20.00', 'maximo' => '20.00'], '23514'],
    'máximo menor que el mínimo' => [['minimo' => '20.00', 'maximo' => '10.00'], '23514'],
]);

test('RNF-03: el resultado de una prueba debe ser positivo', function (string $resultado) {
    expect(fisicaRechazo(fn () => fisicaInsertar('pruebas_fisicas', fisicaDatosDePrueba(['resultado' => $resultado]))))->toBe('23514');
})->with(['cero' => '0.00', 'negativo' => '-4.35']);

test('RNF-03 (DA-03): el periodo de una prueba debe coincidir con su fecha', function () {
    expect(fisicaRechazo(fn () => fisicaInsertar('pruebas_fisicas', fisicaDatosDePrueba(['fecha' => '2026-11-03', 'periodo' => '2026-10']))))->toBe('23514')
        ->and(fisicaRechazo(fn () => fisicaInsertar('pruebas_fisicas', fisicaDatosDePrueba(['fecha' => '2026-10-31', 'periodo' => '2026-10']))))->toBeNull();
});

test('RNF-03 (DA-04): una prueba por jugador, tipo, registrante y periodo; otro tipo, entrenador o periodo no chocan, y el equipo no cuenta', function () {
    $datos = fisicaDatosDePrueba();
    fisicaInsertar('pruebas_fisicas', $datos);

    expect(fisicaRechazo(fn () => fisicaInsertar('pruebas_fisicas', $datos)))->toBe('23505')
        ->and(fisicaRechazo(fn () => fisicaInsertar('pruebas_fisicas', [...$datos, 'equipo_id' => fisicaEquipo()])))->toBe('23505')
        ->and(fisicaRechazo(fn () => fisicaInsertar('pruebas_fisicas', [...$datos, 'tipo_prueba_id' => fisicaTipoDePrueba()])))->toBeNull()
        ->and(fisicaRechazo(fn () => fisicaInsertar('pruebas_fisicas', [...$datos, 'registrado_por' => fisicaUsuario()])))->toBeNull()
        ->and(fisicaRechazo(fn () => fisicaInsertar('pruebas_fisicas', [...$datos, 'fecha' => '2026-10-05', 'periodo' => '2026-10'])))->toBeNull();
});

test('RNF-03: el borrado lógico libera la unicidad de la prueba', function () {
    $datos = fisicaDatosDePrueba();
    $id = fisicaInsertar('pruebas_fisicas', $datos);

    fisicaEliminar('pruebas_fisicas', $id);

    expect(fisicaRechazo(fn () => fisicaInsertar('pruebas_fisicas', $datos)))->toBeNull();
});

test('RNF-03: los índices de §5.1 existen con sus columnas', function () {
    $indices = collect(DB::connection('propietario')->select(
        "select indexname, indexdef from pg_indexes where schemaname = 'public' and tablename in ('mediciones_corporales', 'pruebas_fisicas')",
    ))->pluck('indexdef', 'indexname');

    expect($indices['uq_mediciones_corporales_jugador_registrado_periodo'])->toContain('(jugador_id, registrado_por, periodo) WHERE (eliminado_en IS NULL)')
        ->and($indices['uq_pruebas_fisicas_jugador_tipo_registrado_periodo'])->toContain('(jugador_id, tipo_prueba_id, registrado_por, periodo) WHERE (eliminado_en IS NULL)')
        ->and($indices['ix_pruebas_fisicas_jugador_tipo_fecha'])->toContain('(jugador_id, tipo_prueba_id, fecha)');
});

test('RNF-03: las claves foráneas de Condición física no borran en cascada', function () {
    $medicion = fisicaDatosDeMedicion();
    $idMedicion = fisicaInsertar('mediciones_corporales', $medicion);
    $prueba = fisicaDatosDePrueba();
    fisicaInsertar('pruebas_fisicas', $prueba);
    $borrar = fn (string $tabla, mixed $id) => fisicaRechazo(fn () => DB::connection('propietario')->table($tabla)->where('id', $id)->delete());

    DB::connection('propietario')->table('mediciones_corporales_historial')->insert([
        'id' => Identificador::generar()->valor(), 'medicion_id' => $idMedicion, 'version' => 1, 'operacion' => 'crear',
        'datos_nuevos' => json_encode(['dato' => 'x']), 'usuario_id' => null, 'ocurrido_en' => '2026-11-15 10:30:00+00',
    ]);

    expect($borrar('jugadores', $medicion['jugador_id']))->toBe('23503')
        ->and($borrar('equipos', $medicion['equipo_id']))->toBe('23503')
        ->and($borrar('usuarios', $medicion['registrado_por']))->toBe('23503')
        ->and($borrar('mediciones_corporales', $idMedicion))->toBe('23503')
        ->and($borrar('jugadores', $prueba['jugador_id']))->toBe('23503')
        ->and($borrar('equipos', $prueba['equipo_id']))->toBe('23503')
        ->and($borrar('usuarios', $prueba['registrado_por']))->toBe('23503')
        ->and($borrar('tipos_prueba', $prueba['tipo_prueba_id']))->toBe('23503');
});

test('RNF-02: cada tabla de historial de Condición física es de solo inserción, para la aplicación y para el propietario', function () {
    $entidades = [
        'mediciones_corporales_historial' => fisicaInsertar('mediciones_corporales', fisicaDatosDeMedicion()),
        'pruebas_fisicas_historial' => fisicaInsertar('pruebas_fisicas', fisicaDatosDePrueba()),
    ];

    foreach (HISTORIALES_DE_CONDICION_FISICA as $tabla => $columna) {
        DB::connection('propietario')->table($tabla)->insert([
            'id' => Identificador::generar()->valor(), $columna => $entidades[$tabla], 'version' => 1, 'operacion' => 'crear',
            'datos_nuevos' => json_encode(['dato' => 'x']), 'usuario_id' => null, 'ocurrido_en' => '2026-11-15 10:30:00+00',
        ]);

        expect(fisicaRechazo(fn () => DB::table($tabla)->update(['motivo' => 'x'])))->not->toBeNull("UPDATE de la aplicación sobre {$tabla}")
            ->and(fisicaRechazo(fn () => DB::table($tabla)->delete()))->not->toBeNull("DELETE de la aplicación sobre {$tabla}")
            ->and(fisicaRechazo(fn () => DB::connection('propietario')->table($tabla)->update(['motivo' => 'x'])))->not->toBeNull("UPDATE del propietario sobre {$tabla}")
            ->and(fisicaRechazo(fn () => DB::connection('propietario')->table($tabla)->delete()))->not->toBeNull("DELETE del propietario sobre {$tabla}")
            ->and(DB::connection('propietario')->table($tabla)->count())->toBe(1);
    }
});

test('RNF-02: el historial guarda el motivo de una corrección, y la aplicación puede leer e insertar en Condición física', function () {
    $id = fisicaInsertar('mediciones_corporales', fisicaDatosDeMedicion());

    DB::table('mediciones_corporales_historial')->insert([
        'id' => Identificador::generar()->valor(), 'medicion_id' => $id, 'version' => 1, 'operacion' => 'actualizar',
        'datos_anteriores' => json_encode(['peso_kg' => '70.00']), 'usuario_id' => null, 'ocurrido_en' => '2026-11-15 10:30:00+00', 'motivo' => 'Báscula mal calibrada',
    ]);

    $fila = NoNulo::exigir(DB::table('mediciones_corporales_historial')->where('medicion_id', $id)->first(), 'el historial de la medición');

    expect($fila->operacion)->toBe('actualizar')
        ->and($fila->motivo)->toBe('Báscula mal calibrada');
});

test('RNF-02 (DA-16): las tablas de Condición física llevan auditoría básica; los dos catálogos no tienen historial', function () {
    foreach (HISTORIALES_DE_CONDICION_FISICA as $tabla => $columna) {
        expect(Schema::connection('propietario')->hasColumn($tabla, $columna))->toBeTrue($tabla);
    }
    foreach (TABLAS_DE_CONDICION_FISICA as $tabla) {
        expect(Schema::connection('propietario')->hasColumns($tabla, ['creado_en', 'creado_por', 'actualizado_en', 'actualizado_por', 'eliminado_en', 'eliminado_por']))->toBeTrue($tabla);
    }
    foreach (['tipos_prueba_historial', 'rangos_mediciones_corporales_historial'] as $tabla) {
        expect(Schema::connection('propietario')->hasTable($tabla))->toBeFalse($tabla);
    }
});

test('RNF-03: migrate:rollback de Condición física no falla y se vuelve a aplicar', function () {
    esquemaMarcarSucio();
    $tablas = [...TABLAS_DE_CONDICION_FISICA, ...array_keys(HISTORIALES_DE_CONDICION_FISICA)];

    consolaArtisan('migrate:rollback', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    foreach ($tablas as $tabla) {
        expect(Schema::connection('propietario')->hasTable($tabla))->toBeFalse($tabla);
    }

    consolaArtisan('migrate', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    foreach ($tablas as $tabla) {
        expect(Schema::connection('propietario')->hasTable($tabla))->toBeTrue($tabla);
    }
});
