<?php

use Database\Seeders\ParametrosInstitucionalesSeeder;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;

/*
 * F1-H7-T01 (docs/hitos/f1-h7.md §8 y §5.2): parámetro de conservación del historial tras la baja (AB-03, RN-17,
 * RNF-09). [Propuesta] provisional; ningún proceso lo lee en H7 (D-4).
 */

beforeEach(function () {
    esquemaReiniciar();
});

/** @return list<array{id: string, valor: mixed}> las filas vigentes de `conservacion_historial` */
function conservacionVigente(): array
{
    return array_values(DB::connection('propietario')->table('parametros_institucionales')
        ->where('clave', 'conservacion_historial')
        ->whereNull('vigente_hasta')
        ->get(['id', 'valor'])
        ->map(fn (stdClass $fila): array => ['id' => (string) $fila->id, 'valor' => json_decode((string) $fila->valor, true)])
        ->all());
}

test('AB-03: parametros_institucionales contiene conservacion_historial con 6 meses tras la baja', function () {
    (new ParametrosInstitucionalesSeeder)->run();

    $filas = conservacionVigente();

    expect($filas)->toHaveCount(1)
        ->and($filas[0]['valor'])->toBe(['meses_tras_baja' => 6]);
});

test('AB-03: el seeder es idempotente y no sobrescribe un valor existente', function () {
    (new ParametrosInstitucionalesSeeder)->run();
    $sembradas = conservacionVigente();
    expect($sembradas)->toHaveCount(1);

    (new ParametrosInstitucionalesSeeder)->run();

    $tras = conservacionVigente();
    expect($tras)->toHaveCount(1)
        ->and($tras[0]['id'])->toBe($sembradas[0]['id'])
        ->and(DB::connection('propietario')->table('parametros_institucionales_historial')->count())->toBe(0);

    esquemaReiniciar();
    DB::connection('propietario')->table('parametros_institucionales')->insert([
        'id' => Identificador::generar()->valor(),
        'clave' => 'conservacion_historial',
        'valor' => json_encode(['meses_tras_baja' => 12]),
        'descripcion' => 'Ajustado',
        'vigente_desde' => '2026-02-01',
    ]);

    (new ParametrosInstitucionalesSeeder)->run();

    $ajustada = conservacionVigente();
    expect($ajustada)->toHaveCount(1)
        ->and($ajustada[0]['valor'])->toBe(['meses_tras_baja' => 12]);
});
