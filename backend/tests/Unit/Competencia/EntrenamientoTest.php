<?php

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;
use Src\Competencia\Dominio\Entrenamiento;
use Src\Competencia\Dominio\EntrenamientoNoProgramado;
use Src\Competencia\Dominio\EstadoDeEntrenamiento;
use Src\Competencia\Dominio\TransicionNoPermitida;

function entrenamientoDePrueba(EstadoDeEntrenamiento $estado = EstadoDeEntrenamiento::Programado, string $inicia = '2026-09-10 23:00:00', string $termina = '2026-09-11 00:00:00'): Entrenamiento
{
    $utc = new DateTimeZone('UTC');

    return new Entrenamiento(
        Identificador::generar(), Identificador::generar(), Identificador::generar(),
        new DateTimeImmutable($inicia, $utc), new DateTimeImmutable($termina, $utc),
        Identificador::generar(), $estado, new DateTimeImmutable('2026-09-01 15:00:00', $utc),
    );
}

function instanteDePrueba(): DateTimeImmutable
{
    return new DateTimeImmutable('2026-09-02 10:00:00', new DateTimeZone('UTC'));
}

test('HU-10: un entrenamiento que termina igual o antes de empezar viola rango_de_fechas_invalido', function (string $termina) {
    expect(fn () => entrenamientoDePrueba(termina: $termina))->toThrow(ReglaDeNegocioViolada::class, 'rango_de_fechas_invalido');
})->with(['2026-09-10 23:00:00', '2026-09-10 22:59:59']);

test('HU-10: un segundo después del inicio ya es un horario válido', function () {
    expect(entrenamientoDePrueba(termina: '2026-09-10 23:00:01')->terminaEn()->format('H:i:s'))->toBe('23:00:01');
});

test('HU-10: conDatos cambia solo lo indicado, conserva lo demás y fija el instante', function () {
    $original = entrenamientoDePrueba();
    $lugar = Identificador::generar();
    $responsable = Identificador::generar();
    $inicia = new DateTimeImmutable('2026-09-10 22:00:00', new DateTimeZone('UTC'));

    $soloLugar = $original->conDatos(null, null, $lugar, null, instanteDePrueba());
    $todo = $original->conDatos($inicia, $inicia->modify('+30 minutes'), $lugar, $responsable, instanteDePrueba());

    expect($soloLugar->lugarId())->toBe($lugar)
        ->and($soloLugar->iniciaEn())->toBe($original->iniciaEn())
        ->and($soloLugar->terminaEn())->toBe($original->terminaEn())
        ->and($soloLugar->responsableId())->toBe($original->responsableId())
        ->and($soloLugar->id())->toBe($original->id())
        ->and($soloLugar->equipoId())->toBe($original->equipoId())
        ->and($soloLugar->estado())->toBe(EstadoDeEntrenamiento::Programado)
        ->and($soloLugar->actualizadoEn())->toEqual(instanteDePrueba())
        ->and($todo->iniciaEn())->toBe($inicia)
        ->and($todo->terminaEn()->format('H:i'))->toBe('22:30')
        ->and($todo->responsableId())->toBe($responsable)
        ->and($original->lugarId())->not->toBe($lugar);
});

test('HU-10: conDatos revalida el horario resultante', function () {
    expect(fn () => entrenamientoDePrueba()->conDatos(null, new DateTimeImmutable('2026-09-10 22:00:00', new DateTimeZone('UTC')), null, null, instanteDePrueba()))
        ->toThrow(ReglaDeNegocioViolada::class, 'rango_de_fechas_invalido');
});

test('HU-10 (DA-08): conDatos en un entrenamiento realizado o cancelado lanza EntrenamientoNoProgramado', function (EstadoDeEntrenamiento $estado) {
    expect(fn () => entrenamientoDePrueba($estado)->conDatos(null, null, null, null, instanteDePrueba()))
        ->toThrow(EntrenamientoNoProgramado::class, 'entrenamiento_no_programado');
})->with([EstadoDeEntrenamiento::Realizado, EstadoDeEntrenamiento::Cancelado]);

test('HU-10: de programado se pasa a realizado o a cancelado y se fija el instante', function (EstadoDeEntrenamiento $destino) {
    $cambiado = entrenamientoDePrueba()->conEstado($destino, instanteDePrueba());

    expect($cambiado->estado())->toBe($destino)
        ->and($cambiado->actualizadoEn())->toEqual(instanteDePrueba());
})->with([EstadoDeEntrenamiento::Realizado, EstadoDeEntrenamiento::Cancelado]);

test('HU-10: cualquier otra transición lanza TransicionNoPermitida en el campo estado', function (EstadoDeEntrenamiento $origen, EstadoDeEntrenamiento $destino) {
    $lanzada = null;
    try {
        entrenamientoDePrueba($origen)->conEstado($destino, instanteDePrueba());
    } catch (TransicionNoPermitida $e) {
        $lanzada = $e;
    }

    expect($lanzada)->toBeInstanceOf(TransicionNoPermitida::class)
        ->and($lanzada?->codigo())->toBe('transicion_no_permitida')
        ->and($lanzada?->campo())->toBe('estado');
})->with([
    [EstadoDeEntrenamiento::Programado, EstadoDeEntrenamiento::Programado],
    [EstadoDeEntrenamiento::Realizado, EstadoDeEntrenamiento::Cancelado],
    [EstadoDeEntrenamiento::Realizado, EstadoDeEntrenamiento::Realizado],
    [EstadoDeEntrenamiento::Cancelado, EstadoDeEntrenamiento::Realizado],
    [EstadoDeEntrenamiento::Cancelado, EstadoDeEntrenamiento::Programado],
]);

test('HU-10: solo un entrenamiento cancelado deja de contar para empalmes', function () {
    expect(entrenamientoDePrueba()->cuentaParaEmpalmes())->toBeTrue()
        ->and(entrenamientoDePrueba(EstadoDeEntrenamiento::Realizado)->cuentaParaEmpalmes())->toBeTrue()
        ->and(entrenamientoDePrueba(EstadoDeEntrenamiento::Cancelado)->cuentaParaEmpalmes())->toBeFalse();
});
