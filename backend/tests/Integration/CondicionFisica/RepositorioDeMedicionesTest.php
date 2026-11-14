<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\CondicionFisica\Aplicacion\MedicionConcurrente;
use Src\CondicionFisica\Dominio\CatalogoDeRangos;
use Src\CondicionFisica\Dominio\MedicionCorporal;
use Src\CondicionFisica\Dominio\RepositorioDeMediciones;
use Src\CondicionFisica\Dominio\ValorFueraDeRango;
use Tests\Ayudantes\NoNulo;

/** @param  array<string, string>  $m */
function repositorioMedicion(array $m, string $peso = '70.00', string $estatura = '175.0', string $fecha = '2026-11-15'): MedicionCorporal
{
    return MedicionCorporal::registrar(
        Identificador::generar(),
        Identificador::desdeTexto($m['f1']),
        Identificador::desdeTexto($m['a']),
        Identificador::desdeTexto($m['e1']),
        $fecha,
        NumeroDecimal::desdeTexto($peso),
        NumeroDecimal::desdeTexto($estatura),
        new DateTimeImmutable('2026-11-15 18:00:00+00:00'),
    );
}

test('DA-05: el dominio y la columna generada dan el mismo IMC', function (string $peso, string $estatura) {
    $m = medicionesMundo();
    $medicion = repositorioMedicion($m, $peso, $estatura);

    app(RepositorioDeMediciones::class)->guardarNueva($medicion);

    expect(DB::table('mediciones_corporales')->where('id', $medicion->id()->valor())->value('imc'))->toBe($medicion->imc()->comoCadena(2));
})->with([
    '§7.5 fila 1' => ['70.00', '175.0'],
    '§7.5 fila 2' => ['72.50', '180.0'],
    '§7.5 fila 3' => ['50.02', '200.0'],
    'límites' => ['200.00', '100.0'],
    'decimales impares' => ['63.37', '171.3'],
]);

test('RNF-02: registrar escribe la fila y su historial crear en la misma operación', function () {
    $m = medicionesMundo();
    $medicion = repositorioMedicion($m);

    app(RepositorioDeMediciones::class)->guardarNueva($medicion);

    $historial = NoNulo::exigir(DB::table('mediciones_corporales_historial')->where('medicion_id', $medicion->id()->valor())->first(), 'la fila de historial');
    $nuevos = json_decode($historial->datos_nuevos, true);

    expect($historial->operacion)->toBe('crear')
        ->and($historial->datos_anteriores)->toBeNull()
        ->and($nuevos)->toMatchArray(['jugador_id' => $m['f1'], 'equipo_id' => $m['a'], 'registrado_por' => $m['e1'], 'fecha' => '2026-11-15', 'periodo' => '2026-11', 'peso_kg' => '70.00', 'estatura_cm' => '175.0'])
        ->and(DB::table('mediciones_corporales')->where('id', $medicion->id()->valor())->value('periodo'))->toBe('2026-11');
});

test('RNF-02: corregir escribe los valores anteriores y nuevos con el motivo', function () {
    $m = medicionesMundo();
    $repositorio = app(RepositorioDeMediciones::class);
    $anterior = repositorioMedicion($m);
    $repositorio->guardarNueva($anterior);
    $corregida = $anterior->corregir($anterior->equipoId(), '2026-11-20', NumeroDecimal::desdeTexto('71.00'), NumeroDecimal::desdeTexto('176.0'), new DateTimeImmutable('2026-11-16 18:00:00+00:00'));

    $repositorio->guardarCorreccion($anterior, $corregida, 'Báscula mal calibrada');

    $historial = NoNulo::exigir(DB::table('mediciones_corporales_historial')->where('medicion_id', $anterior->id()->valor())->where('version', 2)->first(), 'la fila de historial');
    $fila = NoNulo::exigir(DB::table('mediciones_corporales')->where('id', $anterior->id()->valor())->first(), 'la medición');

    expect($historial->operacion)->toBe('actualizar')
        ->and($historial->motivo)->toBe('Báscula mal calibrada')
        ->and(json_decode($historial->datos_anteriores, true)['peso_kg'])->toBe('70.00')
        ->and(json_decode($historial->datos_nuevos, true)['peso_kg'])->toBe('71.00')
        ->and($fila->peso_kg)->toBe('71.00')
        ->and($fila->estatura_cm)->toBe('176.0')
        ->and($fila->fecha)->toBe('2026-11-20')
        ->and($fila->actualizado_por)->toBeNull();
});

test('§6.3: dos mediciones simultáneas del mismo periodo chocan con el índice único: conflicto', function () {
    $m = medicionesMundo();
    $repositorio = app(RepositorioDeMediciones::class);
    $repositorio->guardarNueva(repositorioMedicion($m));

    expect(fn () => $repositorio->guardarNueva(repositorioMedicion($m, '71.00')))->toThrow(MedicionConcurrente::class);
});

test('DD-59: buscar del periodo distingue jugador, registrante y periodo, e ignora las borradas', function () {
    $m = medicionesMundo();
    $repositorio = app(RepositorioDeMediciones::class);
    $f1 = Identificador::desdeTexto($m['f1']);
    $e1 = Identificador::desdeTexto($m['e1']);
    $medicion = repositorioMedicion($m);
    $repositorio->guardarNueva($medicion);

    expect($repositorio->delPeriodoParaActualizar($f1, $e1, '2026-11')?->id()->valor())->toBe($medicion->id()->valor())
        ->and($repositorio->delPeriodoParaActualizar($f1, $e1, '2026-10'))->toBeNull()
        ->and($repositorio->delPeriodoParaActualizar($f1, Identificador::desdeTexto($m['e2']), '2026-11'))->toBeNull()
        ->and($repositorio->delPeriodoParaActualizar(Identificador::desdeTexto($m['j1']), $e1, '2026-11'))->toBeNull();

    DB::connection('propietario')->table('mediciones_corporales')->where('id', $medicion->id()->valor())->update(['eliminado_en' => '2026-11-16 10:00:00+00']);

    expect($repositorio->delPeriodoParaActualizar($f1, $e1, '2026-11'))->toBeNull();
});

test('DA-16: los rangos salen de la tabla, también tras cambiarlos en la base', function () {
    medicionesMundo();
    $catalogo = app(CatalogoDeRangos::class);

    $catalogo->de('peso_kg')->exigir(NumeroDecimal::desdeTexto('200.00'), 'peso_kg');
    DB::connection('propietario')->table('rangos_mediciones_corporales')->where('codigo', 'peso_kg')->update(['maximo' => '150.00']);

    expect(fn () => $catalogo->de('peso_kg')->exigir(NumeroDecimal::desdeTexto('150.01'), 'peso_kg'))->toThrow(ValorFueraDeRango::class);

    $catalogo->de('peso_kg')->exigir(NumeroDecimal::desdeTexto('150.00'), 'peso_kg');
    $catalogo->de('estatura_cm')->exigir(NumeroDecimal::desdeTexto('230.0'), 'estatura_cm');
});

test('AU-09: el máximo de cada columna sale de su tipo en la base', function () {
    medicionesMundo();
    $catalogo = app(CatalogoDeRangos::class);

    expect($catalogo->maximoDeColumna('peso_kg')->comoCadena(2))->toBe('999.99')
        ->and($catalogo->maximoDeColumna('estatura_cm')->comoCadena(1))->toBe('9999.9')
        ->and(fn () => $catalogo->maximoDeColumna('imc_inexistente'))->toThrow(RuntimeException::class);
});
