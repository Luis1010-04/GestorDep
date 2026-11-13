<?php

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\CondicionFisica\Dominio\EquipoInexistente;
use Src\CondicionFisica\Dominio\MedicionCorporal;
use Src\CondicionFisica\Dominio\MedicionYaRegistrada;
use Src\CondicionFisica\Dominio\NadaQueCorregir;
use Src\CondicionFisica\Dominio\RangoDeMedida;
use Src\CondicionFisica\Dominio\ReglaDeCampoViolada;
use Src\CondicionFisica\Dominio\ValorFueraDeRango;

function medicionDePrueba(string $peso = '70.00', string $estatura = '175.0', string $fecha = '2026-11-15'): MedicionCorporal
{
    return MedicionCorporal::registrar(
        Identificador::generar(),
        Identificador::generar(),
        Identificador::generar(),
        Identificador::generar(),
        $fecha,
        NumeroDecimal::desdeTexto($peso),
        NumeroDecimal::desdeTexto($estatura),
        new DateTimeImmutable('2026-11-15 18:00:00+00:00'),
    );
}

function rangoDePrueba(): RangoDeMedida
{
    return new RangoDeMedida(NumeroDecimal::desdeTexto('20.00'), NumeroDecimal::desdeTexto('200.00'));
}

test('DA-05: el IMC es el peso entre la estatura en metros al cuadrado, a dos decimales', function (string $peso, string $estatura, string $imc) {
    expect(medicionDePrueba($peso, $estatura)->imc()->comoCadena(2))->toBe($imc);
})->with([
    '§7.5 fila 1' => ['70.00', '175.0', '22.86'],
    '§7.5 fila 2' => ['72.50', '180.0', '22.38'],
    '§7.5 fila 3, HALF_UP sobre 12.505' => ['50.02', '200.0', '12.51'],
    'justo debajo del medio' => ['50.01', '200.0', '12.50'],
    'límites del rango' => ['200.00', '100.0', '200.00'],
]);

test('DA-03: el periodo se deriva de la fecha', function (string $fecha, string $periodo) {
    expect(medicionDePrueba(fecha: $fecha)->periodo())->toBe($periodo);
})->with([
    'noviembre' => ['2026-11-15', '2026-11'],
    'enero' => ['2027-01-01', '2027-01'],
    'fin de mes' => ['2026-10-31', '2026-10'],
]);

test('HU-17 c2: corregir reemplaza equipo, fecha y valores, y conserva lo demás', function () {
    $anterior = medicionDePrueba();
    $equipo = Identificador::generar();
    $instante = new DateTimeImmutable('2026-11-16 18:00:00+00:00');

    $corregida = $anterior->corregir($equipo, '2026-11-20', NumeroDecimal::desdeTexto('71.00'), NumeroDecimal::desdeTexto('176.0'), $instante);

    expect($corregida->id()->igualA($anterior->id()))->toBeTrue()
        ->and($corregida->jugadorId()->igualA($anterior->jugadorId()))->toBeTrue()
        ->and($corregida->registradoPor()->igualA($anterior->registradoPor()))->toBeTrue()
        ->and($corregida->equipoId()->igualA($equipo))->toBeTrue()
        ->and($corregida->fecha())->toBe('2026-11-20')
        ->and($corregida->pesoKg()->comoCadena(2))->toBe('71.00')
        ->and($corregida->estaturaCm()->comoCadena(1))->toBe('176.0')
        ->and($corregida->creadoEn())->toEqual($anterior->creadoEn())
        ->and($corregida->actualizadoEn())->toEqual($instante);
});

test('DA-40: dos mediciones con los mismos valores son iguales, aunque se escriban distinto', function () {
    $anterior = medicionDePrueba();
    $instante = new DateTimeImmutable('2026-11-16 18:00:00+00:00');

    $igual = $anterior->corregir($anterior->equipoId(), $anterior->fecha(), NumeroDecimal::desdeTexto('70'), NumeroDecimal::desdeTexto('175'), $instante);

    expect($igual->tieneLosMismosValoresQue($anterior))->toBeTrue();
});

test('DA-40: cualquier valor distinto hace que la medición cambie', function (string $campo) {
    $anterior = medicionDePrueba();
    $instante = new DateTimeImmutable('2026-11-16 18:00:00+00:00');

    $distinta = $anterior->corregir(
        $campo === 'equipo' ? Identificador::generar() : $anterior->equipoId(),
        $campo === 'fecha' ? '2026-11-16' : $anterior->fecha(),
        $campo === 'peso' ? NumeroDecimal::desdeTexto('70.01') : $anterior->pesoKg(),
        $campo === 'estatura' ? NumeroDecimal::desdeTexto('175.1') : $anterior->estaturaCm(),
        $instante,
    );

    expect($distinta->tieneLosMismosValoresQue($anterior))->toBeFalse();
})->with(['equipo', 'fecha', 'peso', 'estatura']);

test('DA-16: el rango admite sus dos límites', function (string $valor) {
    expect(fn () => rangoDePrueba()->exigir(NumeroDecimal::desdeTexto($valor), 'peso_kg'))->not->toThrow(ValorFueraDeRango::class);
})->with(['mínimo de §5.2' => '20.00', 'máximo de §5.2' => '200.00', 'interior' => '100.50']);

test('DA-16: fuera del rango se señala el campo', function (string $valor) {
    $error = null;
    try {
        rangoDePrueba()->exigir(NumeroDecimal::desdeTexto($valor), 'estatura_cm');
    } catch (ValorFueraDeRango $e) {
        $error = $e;
    }

    expect($error?->codigo())->toBe('valor_fuera_de_rango')
        ->and($error?->campo())->toBe('estatura_cm');
})->with(['19.99', '200.01', '0', '-1', '99999.99']);

test('§6.3: las reglas de la medición llevan su código y su campo', function (Closure $crear, string $codigo, string $campo) {
    /** @var ReglaDeCampoViolada $regla */
    $regla = $crear();

    expect($regla->codigo())->toBe($codigo)
        ->and($regla->campo())->toBe($campo);
})->with([
    'equipo inexistente' => [fn () => new EquipoInexistente, 'equipo_inexistente', 'equipo_id'],
    'nada que corregir' => [fn () => new NadaQueCorregir, 'nada_que_corregir', 'es_correccion'],
]);

test('§6.3: una medición ya registrada lleva su código', function () {
    expect((new MedicionYaRegistrada)->codigo())->toBe('medicion_ya_registrada');
});

test('Reconstruir devuelve la medición con sus valores guardados', function () {
    $original = medicionDePrueba();
    $actualizada = new DateTimeImmutable('2026-11-16 18:00:00+00:00');

    $medicion = MedicionCorporal::reconstruir(
        $original->id(), $original->jugadorId(), $original->equipoId(), $original->registradoPor(), $original->fecha(),
        $original->pesoKg(), $original->estaturaCm(), $original->creadoEn(), $actualizada,
    );

    expect($medicion->id()->igualA($original->id()))->toBeTrue()
        ->and($medicion->tieneLosMismosValoresQue($original))->toBeTrue()
        ->and($medicion->creadoEn())->toEqual($original->creadoEn())
        ->and($medicion->actualizadoEn())->toEqual($actualizada);
});

test('AU-09: el rango acotado a la columna toma el menor de los dos máximos', function () {
    $rango = rangoDePrueba();
    $acotado = $rango->acotadoA(NumeroDecimal::desdeTexto('150.00'));

    $acotado->exigir(NumeroDecimal::desdeTexto('150.00'), 'peso_kg');
    $error = null;
    try {
        $acotado->exigir(NumeroDecimal::desdeTexto('150.01'), 'peso_kg');
    } catch (ValorFueraDeRango $e) {
        $error = $e;
    }

    expect($error?->campo())->toBe('peso_kg')
        ->and($rango->acotadoA(NumeroDecimal::desdeTexto('999.99')))->toBe($rango)
        ->and($rango->acotadoA(NumeroDecimal::desdeTexto('200.00')))->toBe($rango);
});
