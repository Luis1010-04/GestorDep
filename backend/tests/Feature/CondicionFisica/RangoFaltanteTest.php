<?php

use Database\Seeders\RangosMedicionesCorporalesSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Rango faltante en rangos_mediciones_corporales (DA-70): 422 rango_no_configurado, nunca 500. */
const MENSAJE_DE_RANGO_NO_CONFIGURADO = 'No está configurado el rango permitido de esta medida, así que no se puede validar el valor.';

test('DA-70: sin la fila de peso, registrar una medición responde 422 rango_no_configurado y no escribe nada', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    DB::connection('propietario')->table('rangos_mediciones_corporales')->where('codigo', 'peso_kg')->delete();

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a']), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertExactJson(['codigo' => 'rango_no_configurado', 'mensaje' => MENSAJE_DE_RANGO_NO_CONFIGURADO, 'errores' => []]);

    expect(DB::table('mediciones_corporales')->where('jugador_id', $m['f1'])->count())->toBe(0);
});

test('DA-70: con la fila de estatura borrada lógicamente, responde lo mismo', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    DB::connection('propietario')->table('rangos_mediciones_corporales')->where('codigo', 'estatura_cm')->update(['eliminado_en' => '2026-11-01 10:00:00+00']);

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a']), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'rango_no_configurado');
});

test('DA-78: correr el seeder de rangos dos veces no repone la fila borrada lógicamente', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    DB::connection('propietario')->table('rangos_mediciones_corporales')->where('codigo', 'estatura_cm')->update(['eliminado_en' => '2026-11-01 10:00:00+00']);

    consolaArtisan('db:seed', ['--class' => RangosMedicionesCorporalesSeeder::class, '--database' => 'propietario', '--force' => true])->assertSuccessful();
    consolaArtisan('db:seed', ['--class' => RangosMedicionesCorporalesSeeder::class, '--database' => 'propietario', '--force' => true])->assertSuccessful();

    $filas = DB::table('rangos_mediciones_corporales')->where('codigo', 'estatura_cm')->get();
    expect($filas)->toHaveCount(1)
        ->and($filas->first()?->eliminado_en)->toBe('2026-11-01 10:00:00+00');
    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a']), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'rango_no_configurado');
});
