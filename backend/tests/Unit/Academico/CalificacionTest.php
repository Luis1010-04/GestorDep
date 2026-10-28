<?php

use Src\Academico\Dominio\Calificacion;
use Src\Academico\Dominio\TipoDeCalificacion;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;

function calificacionDePrueba(TipoDeCalificacion $tipo, ?int $numeroParcial, string $valor = '8.50'): Calificacion
{
    return new Calificacion(
        Identificador::generar(),
        Identificador::generar(),
        $tipo,
        $numeroParcial,
        NumeroDecimal::desdeTexto($valor),
        Identificador::generar(),
        new DateTimeImmutable('2026-03-10', new DateTimeZone('UTC')),
        new DateTimeImmutable('2026-03-10 12:00:00', new DateTimeZone('UTC')),
    );
}

test('HU-07: una calificación expone lo que se le dio', function () {
    $id = Identificador::generar();
    $inscripcion = Identificador::generar();
    $criterio = Identificador::generar();
    $valor = NumeroDecimal::desdeTexto('8.50');
    $fecha = new DateTimeImmutable('2026-03-01', new DateTimeZone('UTC'));
    $instante = new DateTimeImmutable('2026-03-10 12:00:00', new DateTimeZone('UTC'));

    $calificacion = new Calificacion($id, $inscripcion, TipoDeCalificacion::Parcial, 3, $valor, $criterio, $fecha, $instante);

    expect($calificacion->id())->toBe($id)
        ->and($calificacion->inscripcionId())->toBe($inscripcion)
        ->and($calificacion->tipo())->toBe(TipoDeCalificacion::Parcial)
        ->and($calificacion->numeroParcial())->toBe(3)
        ->and($calificacion->valor())->toBe($valor)
        ->and($calificacion->criterioId())->toBe($criterio)
        ->and($calificacion->fecha())->toBe($fecha)
        ->and($calificacion->actualizadoEn())->toBe($instante);
});

test('RN-02: los tipos de calificación son parcial y final', function () {
    expect(array_map(fn (TipoDeCalificacion $tipo): string => $tipo->value, TipoDeCalificacion::cases()))->toBe(['parcial', 'final']);
});

test('DD-51: un parcial lleva número de 1 a 10 y la final ninguno', function (TipoDeCalificacion $tipo, ?int $numero, bool $valida) {
    $crear = fn () => calificacionDePrueba($tipo, $numero);

    if ($valida) {
        expect($crear()->numeroParcial())->toBe($numero);
    } else {
        expect($crear)->toThrow(InvalidArgumentException::class, 'calificacion_invalida');
    }
})->with([
    'parcial 1' => [TipoDeCalificacion::Parcial, 1, true],
    'parcial 10' => [TipoDeCalificacion::Parcial, 10, true],
    'parcial 0' => [TipoDeCalificacion::Parcial, 0, false],
    'parcial 11' => [TipoDeCalificacion::Parcial, 11, false],
    'parcial sin número' => [TipoDeCalificacion::Parcial, null, false],
    'final sin número' => [TipoDeCalificacion::Final, null, true],
    'final con número' => [TipoDeCalificacion::Final, 1, false],
]);

test('RN-01: el valor de una calificación está en la escala de 0 a 10', function (string $valor, bool $valida) {
    $crear = fn () => calificacionDePrueba(TipoDeCalificacion::Final, null, $valor);

    if ($valida) {
        expect($crear()->valor()->comoCadena(2))->toBe(NumeroDecimal::desdeTexto($valor)->comoCadena(2));
    } else {
        expect($crear)->toThrow(InvalidArgumentException::class, 'calificacion_invalida');
    }
})->with([
    'cero' => ['0', true],
    'diez' => ['10.00', true],
    'sobre diez' => ['10.01', false],
    'bajo cero' => ['-0.01', false],
]);
