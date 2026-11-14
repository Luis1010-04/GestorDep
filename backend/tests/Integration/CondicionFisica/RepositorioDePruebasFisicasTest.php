<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\CondicionFisica\Aplicacion\PruebaConcurrente;
use Src\CondicionFisica\Dominio\CatalogoDeTiposDePrueba;
use Src\CondicionFisica\Dominio\PruebaFisica;
use Src\CondicionFisica\Dominio\RepositorioDePruebasFisicas;
use Src\CondicionFisica\Dominio\ValorFueraDeRango;
use Tests\Ayudantes\NoNulo;

/** @param  array<string, string>  $m */
function repositorioPrueba(array $m, string $resultado = '4.35', string $tipo = 'velocidad'): PruebaFisica
{
    return PruebaFisica::registrar(
        Identificador::generar(), Identificador::desdeTexto($m['f1']), Identificador::desdeTexto($m['a']), Identificador::desdeTexto($m['e1']),
        Identificador::desdeTexto($m[$tipo]), '2026-11-03', NumeroDecimal::desdeTexto($resultado), new DateTimeImmutable('2026-11-15 18:00:00+00:00'),
    );
}

test('RNF-02: registrar escribe la fila y su historial crear', function () {
    $m = pruebasMundo();
    $prueba = repositorioPrueba($m);

    app(RepositorioDePruebasFisicas::class)->guardarNueva($prueba);

    $historial = NoNulo::exigir(DB::table('pruebas_fisicas_historial')->where('prueba_id', $prueba->id()->valor())->first(), 'la fila de historial');

    expect($historial->operacion)->toBe('crear')
        ->and($historial->datos_anteriores)->toBeNull()
        ->and(json_decode($historial->datos_nuevos, true))->toMatchArray([
            'jugador_id' => $m['f1'], 'equipo_id' => $m['a'], 'registrado_por' => $m['e1'], 'tipo_prueba_id' => $m['velocidad'],
            'fecha' => '2026-11-03', 'periodo' => '2026-11', 'resultado' => '4.35',
        ])
        ->and(DB::table('pruebas_fisicas')->where('id', $prueba->id()->valor())->value('resultado'))->toBe('4.35');
});

test('RNF-02: corregir escribe los valores anteriores y nuevos con el motivo', function () {
    $m = pruebasMundo();
    $repositorio = app(RepositorioDePruebasFisicas::class);
    $anterior = repositorioPrueba($m);
    $repositorio->guardarNueva($anterior);
    $corregida = $anterior->corregir($anterior->equipoId(), '2026-11-04', NumeroDecimal::desdeTexto('4.20'), new DateTimeImmutable('2026-11-16 18:00:00+00:00'));

    $repositorio->guardarCorreccion($anterior, $corregida, 'Cronómetro mal iniciado');

    $historial = NoNulo::exigir(DB::table('pruebas_fisicas_historial')->where('prueba_id', $anterior->id()->valor())->where('version', 2)->first(), 'la fila de historial');
    $fila = NoNulo::exigir(DB::table('pruebas_fisicas')->where('id', $anterior->id()->valor())->first(), 'la prueba');

    expect($historial->operacion)->toBe('actualizar')
        ->and($historial->motivo)->toBe('Cronómetro mal iniciado')
        ->and(json_decode($historial->datos_anteriores, true)['resultado'])->toBe('4.35')
        ->and(json_decode($historial->datos_nuevos, true)['resultado'])->toBe('4.20')
        ->and($fila->resultado)->toBe('4.20')
        ->and($fila->fecha)->toBe('2026-11-04');
});

test('§6.3, DA-64: dos pruebas del mismo tipo, registrante y periodo chocan con el índice único: conflicto', function () {
    $m = pruebasMundo();
    $repositorio = app(RepositorioDePruebasFisicas::class);
    $repositorio->guardarNueva(repositorioPrueba($m));

    expect(fn () => $repositorio->guardarNueva(repositorioPrueba($m, '4.20')))->toThrow(PruebaConcurrente::class)
        ->and(DB::table('pruebas_fisicas')->where('jugador_id', $m['f1'])->count())->toBe(1);
});

test('DA-04, DD-59: buscar del periodo distingue jugador, tipo, registrante y periodo, e ignora las borradas', function () {
    $m = pruebasMundo();
    $repositorio = app(RepositorioDePruebasFisicas::class);
    $prueba = repositorioPrueba($m);
    $repositorio->guardarNueva($prueba);
    $f1 = Identificador::desdeTexto($m['f1']);
    $e1 = Identificador::desdeTexto($m['e1']);
    $velocidad = Identificador::desdeTexto($m['velocidad']);

    expect($repositorio->delPeriodoParaActualizar($f1, $velocidad, $e1, '2026-11')?->id()->valor())->toBe($prueba->id()->valor())
        ->and($repositorio->delPeriodoParaActualizar($f1, Identificador::desdeTexto($m['resistencia']), $e1, '2026-11'))->toBeNull()
        ->and($repositorio->delPeriodoParaActualizar($f1, $velocidad, Identificador::desdeTexto($m['e2']), '2026-11'))->toBeNull()
        ->and($repositorio->delPeriodoParaActualizar($f1, $velocidad, $e1, '2026-10'))->toBeNull();

    DB::connection('propietario')->table('pruebas_fisicas')->where('id', $prueba->id()->valor())->update(['eliminado_en' => '2026-11-16 10:00:00+00']);

    expect($repositorio->delPeriodoParaActualizar($f1, $velocidad, $e1, '2026-11'))->toBeNull()
        ->and($repositorio->delPeriodo($f1, $e1, '2026-11'))->toBe([]);
});

test('DA-16: el catálogo lee el rango del tipo de la tabla, también tras cambiarlo, e ignora los eliminados', function () {
    $m = pruebasMundo();
    $catalogo = app(CatalogoDeTiposDePrueba::class);
    $resistencia = Identificador::desdeTexto($m['resistencia']);

    NoNulo::exigir($catalogo->buscar($resistencia), 'el tipo')->exigirResultado(NumeroDecimal::desdeTexto('6000.00'));
    DB::connection('propietario')->table('tipos_prueba')->where('codigo', 'resistencia')->update(['maximo' => '5000.00']);

    expect(fn () => NoNulo::exigir($catalogo->buscar($resistencia), 'el tipo')->exigirResultado(NumeroDecimal::desdeTexto('5000.01')))->toThrow(ValorFueraDeRango::class)
        ->and(array_map(fn ($tipo) => $tipo->codigo(), $catalogo->todos()))->toBe(['resistencia', 'tiempo_prueba', 'velocidad']);

    DB::connection('propietario')->table('tipos_prueba')->where('codigo', 'tiempo_prueba')->update(['eliminado_en' => '2026-11-01 10:00:00+00']);

    expect($catalogo->buscar(Identificador::desdeTexto($m['tiempo_prueba'])))->toBeNull()
        ->and(array_map(fn ($tipo) => $tipo->codigo(), $catalogo->todos()))->toBe(['resistencia', 'velocidad']);
});
