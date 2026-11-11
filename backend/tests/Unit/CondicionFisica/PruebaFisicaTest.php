<?php

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\CondicionFisica\Dominio\PruebaFisica;
use Src\CondicionFisica\Dominio\PruebaYaRegistrada;
use Src\CondicionFisica\Dominio\RangoDeMedida;
use Src\CondicionFisica\Dominio\ReglaDeCampoViolada;
use Src\CondicionFisica\Dominio\TipoDePrueba;
use Src\CondicionFisica\Dominio\TipoPruebaInexistente;
use Src\CondicionFisica\Dominio\ValorFueraDeRango;

function pruebaDePrueba(string $resultado = '4.35', string $fecha = '2026-11-03'): PruebaFisica
{
    return PruebaFisica::registrar(
        Identificador::generar(), Identificador::generar(), Identificador::generar(), Identificador::generar(), Identificador::generar(),
        $fecha, NumeroDecimal::desdeTexto($resultado), new DateTimeImmutable('2026-11-15 18:00:00+00:00'),
    );
}

function tipoDePruebaDePrueba(): TipoDePrueba
{
    return new TipoDePrueba(
        Identificador::generar(), 'resistencia', 'Resistencia', 'm', 'mayor',
        new RangoDeMedida(NumeroDecimal::desdeTexto('100.00'), NumeroDecimal::desdeTexto('6000.00')),
    );
}

test('DA-03: el periodo de la prueba se deriva de la fecha', function (string $fecha, string $periodo) {
    expect(pruebaDePrueba(fecha: $fecha)->periodo())->toBe($periodo);
})->with([
    'noviembre' => ['2026-11-03', '2026-11'],
    'enero' => ['2027-01-31', '2027-01'],
]);

test('HU-18: corregir reemplaza equipo, fecha y resultado, y conserva el tipo y lo demás', function () {
    $anterior = pruebaDePrueba();
    $equipo = Identificador::generar();
    $instante = new DateTimeImmutable('2026-11-16 18:00:00+00:00');

    $corregida = $anterior->corregir($equipo, '2026-11-20', NumeroDecimal::desdeTexto('4.10'), $instante);

    expect($corregida->id())->toBe($anterior->id())
        ->and($corregida->jugadorId())->toBe($anterior->jugadorId())
        ->and($corregida->registradoPor())->toBe($anterior->registradoPor())
        ->and($corregida->tipoPruebaId())->toBe($anterior->tipoPruebaId())
        ->and($corregida->equipoId())->toBe($equipo)
        ->and($corregida->fecha())->toBe('2026-11-20')
        ->and($corregida->resultado()->comoCadena(2))->toBe('4.10')
        ->and($corregida->creadoEn())->toEqual($anterior->creadoEn())
        ->and($corregida->actualizadoEn())->toBe($instante);
});

test('DA-40: dos pruebas con los mismos valores son iguales, aunque el resultado se escriba distinto', function () {
    $anterior = pruebaDePrueba('4.30');
    $igual = $anterior->corregir($anterior->equipoId(), $anterior->fecha(), NumeroDecimal::desdeTexto('4.3'), new DateTimeImmutable('2026-11-16 18:00:00+00:00'));

    expect($igual->tieneLosMismosValoresQue($anterior))->toBeTrue();
});

test('DA-40: cualquier valor distinto hace que la prueba cambie', function (string $campo) {
    $anterior = pruebaDePrueba();
    $distinta = $anterior->corregir(
        $campo === 'equipo' ? Identificador::generar() : $anterior->equipoId(),
        $campo === 'fecha' ? '2026-11-04' : $anterior->fecha(),
        $campo === 'resultado' ? NumeroDecimal::desdeTexto('4.36') : $anterior->resultado(),
        new DateTimeImmutable('2026-11-16 18:00:00+00:00'),
    );

    expect($distinta->tieneLosMismosValoresQue($anterior))->toBeFalse();
})->with(['equipo', 'fecha', 'resultado']);

test('Reconstruir devuelve la prueba con sus valores guardados', function () {
    $original = pruebaDePrueba();
    $actualizada = new DateTimeImmutable('2026-11-16 18:00:00+00:00');

    $prueba = PruebaFisica::reconstruir(
        $original->id(), $original->jugadorId(), $original->equipoId(), $original->registradoPor(), $original->tipoPruebaId(),
        $original->fecha(), $original->resultado(), $original->creadoEn(), $actualizada,
    );

    expect($prueba->id())->toBe($original->id())
        ->and($prueba->tieneLosMismosValoresQue($original))->toBeTrue()
        ->and($prueba->tipoPruebaId())->toBe($original->tipoPruebaId())
        ->and($prueba->creadoEn())->toEqual($original->creadoEn())
        ->and($prueba->actualizadoEn())->toBe($actualizada);
});

test('DA-16: el tipo de prueba guarda su catálogo y valida el resultado con su rango, en el campo resultado', function () {
    $tipo = tipoDePruebaDePrueba();
    $tipo->exigirResultado(NumeroDecimal::desdeTexto('100.00'));
    $tipo->exigirResultado(NumeroDecimal::desdeTexto('6000.00'));
    $error = null;

    try {
        $tipo->exigirResultado(NumeroDecimal::desdeTexto('6000.01'));
    } catch (ValorFueraDeRango $e) {
        $error = $e;
    }

    expect($error?->campo())->toBe('resultado')
        ->and($tipo->codigo())->toBe('resistencia')
        ->and($tipo->nombre())->toBe('Resistencia')
        ->and($tipo->unidad())->toBe('m')
        ->and($tipo->mejorResultado())->toBe('mayor');
});

test('§6.3: el tipo inexistente lleva su código y su campo', function (Closure $crear, string $codigo, string $campo) {
    /** @var ReglaDeCampoViolada $regla */
    $regla = $crear();

    expect($regla->codigo())->toBe($codigo)
        ->and($regla->campo())->toBe($campo);
})->with([
    'tipo de prueba inexistente' => [fn () => new TipoPruebaInexistente, 'tipo_prueba_inexistente', 'tipo_prueba_id'],
]);

test('§6.3: una prueba ya registrada lleva su código', function () {
    expect((new PruebaYaRegistrada)->codigo())->toBe('prueba_ya_registrada');
});

test('DA-16: el tipo de prueba conserva su identificador', function () {
    $id = Identificador::generar();
    $tipo = new TipoDePrueba($id, 'velocidad', 'Velocidad', 's', 'menor', new RangoDeMedida(NumeroDecimal::desdeTexto('1.00'), NumeroDecimal::desdeTexto('60.00')));

    expect($tipo->id())->toBe($id);
});
