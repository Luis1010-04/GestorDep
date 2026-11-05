<?php

use Src\Compartido\Dominio\Identificador;
use Src\Salud\Dominio\CausaDeLesion;
use Src\Salud\Dominio\FechaAnteriorAlInicio;
use Src\Salud\Dominio\Lesion;

function lesionDePrueba(?Identificador $registrante = null, string $inicio = '2026-11-10', string $regreso = '2026-11-30', ?string $nota = 'Con hielo'): Lesion
{
    return Lesion::registrar(
        Identificador::generar(), Identificador::generar(), $registrante ?? Identificador::generar(), 'Esguince', 'Tobillo',
        CausaDeLesion::Partido, $inicio, $regreso, $nota, new DateTimeImmutable('2026-11-15 18:00:00+00:00'),
    );
}

test('HU-20 c1: registrar una lesión guarda su detalle, sin fecha de alta', function () {
    $id = Identificador::generar();
    $jugador = Identificador::generar();
    $registrante = Identificador::generar();
    $instante = new DateTimeImmutable('2026-11-15 18:00:00+00:00');

    $lesion = Lesion::registrar($id, $jugador, $registrante, 'Esguince', 'Tobillo', CausaDeLesion::Entrenamiento, '2026-11-10', '2026-11-30', 'Con hielo', $instante);

    expect($lesion->id())->toBe($id)
        ->and($lesion->jugadorId())->toBe($jugador)
        ->and($lesion->registradaPor())->toBe($registrante)
        ->and($lesion->tipo())->toBe('Esguince')
        ->and($lesion->zona())->toBe('Tobillo')
        ->and($lesion->causa())->toBe(CausaDeLesion::Entrenamiento)
        ->and($lesion->fechaInicio())->toBe('2026-11-10')
        ->and($lesion->fechaEstimadaRegreso())->toBe('2026-11-30')
        ->and($lesion->fechaAlta())->toBeNull()
        ->and($lesion->nota())->toBe('Con hielo')
        ->and($lesion->creadoEn())->toBe($instante)
        ->and($lesion->actualizadoEn())->toBe($instante);
});

test('DA-17: la fecha estimada no puede ser anterior al inicio; la misma fecha sí', function () {
    expect(fn () => lesionDePrueba(inicio: '2026-11-10', regreso: '2026-11-09'))->toThrow(FechaAnteriorAlInicio::class)
        ->and(lesionDePrueba(inicio: '2026-11-10', regreso: '2026-11-10')->fechaEstimadaRegreso())->toBe('2026-11-10');
});

test('DA-17: comprobar una fecha nueva de regreso sigue la misma regla', function () {
    $lesion = lesionDePrueba(inicio: '2026-11-10');

    $lesion->comprobarFechaDeRegreso('2026-11-10');

    expect(fn () => $lesion->comprobarFechaDeRegreso('2026-11-09'))->toThrow(FechaAnteriorAlInicio::class);
});

test('HU-30 c1: dar de alta pone la fecha y el instante y conserva el detalle, sin tocar la original', function () {
    $lesion = lesionDePrueba();
    $instante = new DateTimeImmutable('2026-11-20 09:00:00+00:00');

    $conAlta = $lesion->darDeAlta('2026-11-20', $instante);

    expect($conAlta->fechaAlta())->toBe('2026-11-20')
        ->and($conAlta->actualizadoEn())->toBe($instante)
        ->and($conAlta->creadoEn())->toBe($lesion->creadoEn())
        ->and($conAlta->id())->toBe($lesion->id())
        ->and($conAlta->jugadorId())->toBe($lesion->jugadorId())
        ->and($conAlta->registradaPor())->toBe($lesion->registradaPor())
        ->and($conAlta->tipo())->toBe($lesion->tipo())
        ->and($conAlta->zona())->toBe($lesion->zona())
        ->and($conAlta->causa())->toBe($lesion->causa())
        ->and($conAlta->fechaInicio())->toBe($lesion->fechaInicio())
        ->and($conAlta->fechaEstimadaRegreso())->toBe($lesion->fechaEstimadaRegreso())
        ->and($conAlta->nota())->toBe($lesion->nota())
        ->and($lesion->fechaAlta())->toBeNull();
});

test('DA-17: la fecha de alta no puede ser anterior al inicio; la misma fecha sí', function () {
    $instante = new DateTimeImmutable('2026-11-20 09:00:00+00:00');
    $lesion = lesionDePrueba(inicio: '2026-11-10');

    expect(fn () => $lesion->darDeAlta('2026-11-09', $instante))->toThrow(FechaAnteriorAlInicio::class)
        ->and($lesion->darDeAlta('2026-11-10', $instante)->fechaAlta())->toBe('2026-11-10');
});

test('§6.2: corregir cambia solo lo enviado y conserva lo demás, sin tocar la original', function () {
    $lesion = lesionDePrueba();
    $instante = new DateTimeImmutable('2026-11-20 09:00:00+00:00');

    $corregida = $lesion->corregir(['zona' => 'Rodilla'], $instante);

    expect($corregida->zona())->toBe('Rodilla')
        ->and($corregida->tipo())->toBe('Esguince')
        ->and($corregida->causa())->toBe(CausaDeLesion::Partido)
        ->and($corregida->nota())->toBe('Con hielo')
        ->and($corregida->actualizadoEn())->toBe($instante)
        ->and($corregida->creadoEn())->toBe($lesion->creadoEn())
        ->and($corregida->id())->toBe($lesion->id())
        ->and($corregida->jugadorId())->toBe($lesion->jugadorId())
        ->and($corregida->registradaPor())->toBe($lesion->registradaPor())
        ->and($corregida->fechaInicio())->toBe($lesion->fechaInicio())
        ->and($corregida->fechaEstimadaRegreso())->toBe($lesion->fechaEstimadaRegreso())
        ->and($corregida->fechaAlta())->toBe($lesion->fechaAlta())
        ->and($lesion->zona())->toBe('Tobillo');
});

test('§6.2: corregir admite tipo, causa y nota', function () {
    $corregida = lesionDePrueba()->corregir(['tipo' => 'Desgarro', 'causa' => 'otra', 'nota' => 'Sin hielo'], new DateTimeImmutable('2026-11-20 09:00:00+00:00'));

    expect($corregida->tipo())->toBe('Desgarro')
        ->and($corregida->causa())->toBe(CausaDeLesion::Otra)
        ->and($corregida->nota())->toBe('Sin hielo');
});

test('§6.2: tieneElMismoDetalleQue compara tipo, zona, causa y nota', function (array $cambio, bool $esperado) {
    $lesion = lesionDePrueba();

    expect($lesion->tieneElMismoDetalleQue($lesion->corregir($cambio, new DateTimeImmutable('2026-11-20 09:00:00+00:00'))))->toBe($esperado);
})->with([
    'sin cambios' => [['zona' => 'Tobillo'], true],
    'tipo' => [['tipo' => 'Desgarro'], false],
    'zona' => [['zona' => 'Rodilla'], false],
    'causa' => [['causa' => 'otra'], false],
    'nota' => [['nota' => 'Sin hielo'], false],
]);

test('RN-15: esDe solo para quien la registró', function () {
    $registrante = Identificador::generar();
    $lesion = lesionDePrueba($registrante);

    expect($lesion->esDe($registrante))->toBeTrue()
        ->and($lesion->esDe(Identificador::generar()))->toBeFalse();
});

test('DA-17: el código de la regla es rango_de_fechas_invalido', function () {
    expect((new FechaAnteriorAlInicio)->codigo())->toBe('rango_de_fechas_invalido');
});

test('§5.1: los valores de CausaDeLesion son los de la tabla', function () {
    expect(array_map(fn (CausaDeLesion $causa) => $causa->value, CausaDeLesion::cases()))->toBe(['entrenamiento', 'partido', 'otra']);
});
