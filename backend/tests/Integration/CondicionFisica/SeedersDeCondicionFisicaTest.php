<?php

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RangosMedicionesCorporalesSeeder;
use Database\Seeders\TiposPruebaSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    esquemaReiniciar();
});

/** @return array<string, array{unidad: string, mejor_resultado: string, minimo: string, maximo: string}> */
function seedersTiposDePrueba(): array
{
    return DB::table('tipos_prueba')->orderBy('codigo')->get()->mapWithKeys(fn (stdClass $fila) => [$fila->codigo => [
        'unidad' => $fila->unidad, 'mejor_resultado' => $fila->mejor_resultado, 'minimo' => $fila->minimo, 'maximo' => $fila->maximo,
    ]])->all();
}

/** @return array<string, array{unidad: string, minimo: string, maximo: string}> */
function seedersRangosCorporales(): array
{
    return DB::table('rangos_mediciones_corporales')->orderBy('codigo')->get()->mapWithKeys(fn (stdClass $fila) => [$fila->codigo => [
        'unidad' => $fila->unidad, 'minimo' => $fila->minimo, 'maximo' => $fila->maximo,
    ]])->all();
}

test('RNF-09 (DD-62, DA-16): TiposPruebaSeeder siembra los tres tipos de §5.2 con su unidad, sentido de mejora y rango', function () {
    (new TiposPruebaSeeder)->run();

    expect(seedersTiposDePrueba())->toBe([
        'resistencia' => ['unidad' => 'm', 'mejor_resultado' => 'mayor', 'minimo' => '100.00', 'maximo' => '6000.00'],
        'tiempo_prueba' => ['unidad' => 's', 'mejor_resultado' => 'menor', 'minimo' => '1.00', 'maximo' => '3600.00'],
        'velocidad' => ['unidad' => 's', 'mejor_resultado' => 'menor', 'minimo' => '1.00', 'maximo' => '60.00'],
    ]);
});

test('RNF-09 (DD-62, DA-16): RangosMedicionesCorporalesSeeder siembra peso y estatura de §5.2', function () {
    (new RangosMedicionesCorporalesSeeder)->run();

    expect(seedersRangosCorporales())->toBe([
        'estatura_cm' => ['unidad' => 'cm', 'minimo' => '100.00', 'maximo' => '230.00'],
        'peso_kg' => ['unidad' => 'kg', 'minimo' => '20.00', 'maximo' => '200.00'],
    ]);
});

test('RNF-09: cada tipo y cada medida sembrados tienen un nombre', function () {
    (new TiposPruebaSeeder)->run();
    (new RangosMedicionesCorporalesSeeder)->run();

    expect(DB::table('tipos_prueba')->orderBy('codigo')->pluck('nombre', 'codigo')->all())->toBe([
        'resistencia' => 'Resistencia', 'tiempo_prueba' => 'Tiempo de prueba', 'velocidad' => 'Velocidad',
    ])->and(DB::table('rangos_mediciones_corporales')->orderBy('codigo')->pluck('nombre', 'codigo')->all())->toBe([
        'estatura_cm' => 'Estatura', 'peso_kg' => 'Peso',
    ]);
});

test('RNF-09: TiposPruebaSeeder es idempotente, no deshace un ajuste en la base y solo inserta lo que falta', function () {
    (new TiposPruebaSeeder)->run();
    $ids = DB::table('tipos_prueba')->orderBy('id')->pluck('id')->all();
    DB::table('tipos_prueba')->where('codigo', 'resistencia')->update(['maximo' => '5000.00', 'unidad' => 'km']);

    (new TiposPruebaSeeder)->run();

    expect(DB::table('tipos_prueba')->orderBy('id')->pluck('id')->all())->toBe($ids)
        ->and(seedersTiposDePrueba()['resistencia'])->toBe(['unidad' => 'km', 'mejor_resultado' => 'mayor', 'minimo' => '100.00', 'maximo' => '5000.00']);

    DB::connection('propietario')->table('tipos_prueba')->where('codigo', 'velocidad')->delete();
    (new TiposPruebaSeeder)->run();

    expect(array_keys(seedersTiposDePrueba()))->toBe(['resistencia', 'tiempo_prueba', 'velocidad'])
        ->and(seedersTiposDePrueba()['resistencia']['maximo'])->toBe('5000.00');
});

test('RNF-09: RangosMedicionesCorporalesSeeder es idempotente, no deshace un ajuste en la base y solo inserta lo que falta', function () {
    (new RangosMedicionesCorporalesSeeder)->run();
    $ids = DB::table('rangos_mediciones_corporales')->orderBy('id')->pluck('id')->all();
    DB::table('rangos_mediciones_corporales')->where('codigo', 'peso_kg')->update(['maximo' => '150.00']);

    (new RangosMedicionesCorporalesSeeder)->run();

    expect(DB::table('rangos_mediciones_corporales')->orderBy('id')->pluck('id')->all())->toBe($ids)
        ->and(seedersRangosCorporales()['peso_kg']['maximo'])->toBe('150.00');

    DB::connection('propietario')->table('rangos_mediciones_corporales')->where('codigo', 'estatura_cm')->delete();
    (new RangosMedicionesCorporalesSeeder)->run();

    expect(array_keys(seedersRangosCorporales()))->toBe(['estatura_cm', 'peso_kg'])
        ->and(seedersRangosCorporales()['peso_kg']['maximo'])->toBe('150.00');
});

test('DA-16: tras db:seed existen los rangos de cada medida y de cada tipo de prueba, y repetirlo no duplica nada', function () {
    consolaArtisan('db:seed', ['--force' => true])->assertSuccessful();
    consolaArtisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(array_keys(seedersRangosCorporales()))->toBe(['estatura_cm', 'peso_kg'])
        ->and(array_keys(seedersTiposDePrueba()))->toBe(['resistencia', 'tiempo_prueba', 'velocidad']);

    foreach ([...seedersRangosCorporales(), ...seedersTiposDePrueba()] as $codigo => $rango) {
        expect((float) $rango['minimo'])->toBeGreaterThan(0.0, $codigo)
            ->and((float) $rango['maximo'])->toBeGreaterThan((float) $rango['minimo'], $codigo);
    }
});

test('DA-16: DatabaseSeeder siembra los rangos también en producción', function () {
    app()->detectEnvironment(fn () => 'production');

    (new DatabaseSeeder)->run();

    expect(DB::connection('propietario')->table('tipos_prueba')->count())->toBe(3)
        ->and(DB::connection('propietario')->table('rangos_mediciones_corporales')->count())->toBe(2)
        ->and(DB::connection('propietario')->table('usuarios')->count())->toBe(0);
});
