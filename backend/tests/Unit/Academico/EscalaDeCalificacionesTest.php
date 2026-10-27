<?php

use Src\Academico\Dominio\AmbitoDeCriterio;
use Src\Academico\Dominio\CriterioDeAprobacion;
use Src\Academico\Dominio\CriterioVigente;
use Src\Academico\Dominio\EscalaDeCalificaciones;
use Src\Compartido\Dominio\NumeroDecimal;

test('RN-01: la escala de calificaciones va de 0 a 10, ambos extremos incluidos', function (string $valor, bool $dentro) {
    expect(EscalaDeCalificaciones::contiene(NumeroDecimal::desdeTexto($valor)))->toBe($dentro);
})->with([
    'justo debajo de cero' => ['-0.01', false],
    'cero' => ['0.00', true],
    'cero sin decimales' => ['0', true],
    'dentro' => ['5.55', true],
    'diez' => ['10.00', true],
    'justo sobre diez' => ['10.01', false],
    'muy sobre diez' => ['99', false],
    'muy bajo cero' => ['-5', false],
]);

test('RN-03: un criterio vigente expone el criterio y la fecha desde la que rige', function () {
    $criterio = new CriterioDeAprobacion(AmbitoDeCriterio::Materia, NumeroDecimal::desdeTexto('6.00'));
    $desde = new DateTimeImmutable('2026-03-10', new DateTimeZone('UTC'));

    $vigente = new CriterioVigente($criterio, $desde);

    expect($vigente->criterio())->toBe($criterio)->and($vigente->vigenteDesde())->toBe($desde);
});
