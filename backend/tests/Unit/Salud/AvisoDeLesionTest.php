<?php

use Src\Compartido\Dominio\Identificador;
use Src\Salud\Dominio\AvisoDeLesion;
use Src\Salud\Dominio\AvisoNoPendiente;
use Src\Salud\Dominio\EstadoDeAviso;

function avisoNuevoDePrueba(?Identificador $creadoPor = null, bool $reportadoPorJugador = false): AvisoDeLesion
{
    return AvisoDeLesion::crear(
        Identificador::generar(), Identificador::generar(), $creadoPor ?? Identificador::generar(), $reportadoPorJugador,
        'Molestia en el tobillo', '2026-11-14', new DateTimeImmutable('2026-11-15 18:00:00+00:00'),
    );
}

function avisoConfirmadoDePrueba(Identificador $creadoPor, Identificador $confirmadoPor): AvisoDeLesion
{
    $instante = new DateTimeImmutable('2026-11-15 18:00:00+00:00');

    return new AvisoDeLesion(
        Identificador::generar(), Identificador::generar(), $creadoPor, false, 'Molestia', EstadoDeAviso::Confirmado, '2026-11-14',
        $confirmadoPor, null, null, $instante, $instante,
    );
}

test('HU-19 c1: un aviso nuevo queda pendiente, sin confirmación ni descarte', function () {
    $creadoPor = Identificador::generar();
    $id = Identificador::generar();
    $jugador = Identificador::generar();
    $instante = new DateTimeImmutable('2026-11-15 18:00:00+00:00');

    $aviso = AvisoDeLesion::crear($id, $jugador, $creadoPor, false, 'Molestia', '2026-11-14', $instante);

    expect($aviso->id())->toBe($id)
        ->and($aviso->jugadorId())->toBe($jugador)
        ->and($aviso->creadoPor())->toBe($creadoPor)
        ->and($aviso->reportadoPorJugador())->toBeFalse()
        ->and($aviso->nota())->toBe('Molestia')
        ->and($aviso->estado())->toBe(EstadoDeAviso::Pendiente)
        ->and($aviso->fechaAviso())->toBe('2026-11-14')
        ->and($aviso->confirmadoPor())->toBeNull()
        ->and($aviso->descartadoPor())->toBeNull()
        ->and($aviso->notaDescarte())->toBeNull()
        ->and($aviso->creadoEn())->toBe($instante)
        ->and($aviso->actualizadoEn())->toBe($instante);
});

test('HU-19 c2: el aviso conserva que lo reportó el jugador', function () {
    expect(avisoNuevoDePrueba(reportadoPorJugador: true)->reportadoPorJugador())->toBeTrue();
});

test('HU-30 c3: descartar deja el aviso descartado con su autor, su nota y el instante, sin tocar el original', function () {
    $aviso = avisoNuevoDePrueba();
    $autor = Identificador::generar();
    $instante = new DateTimeImmutable('2026-11-16 09:00:00+00:00');

    $descartado = $aviso->descartar($autor, 'No fue una lesión', $instante);

    expect($descartado->estado())->toBe(EstadoDeAviso::Descartado)
        ->and($descartado->descartadoPor())->toBe($autor)
        ->and($descartado->notaDescarte())->toBe('No fue una lesión')
        ->and($descartado->actualizadoEn())->toBe($instante)
        ->and($descartado->creadoEn())->toBe($aviso->creadoEn())
        ->and($descartado->id())->toBe($aviso->id())
        ->and($descartado->jugadorId())->toBe($aviso->jugadorId())
        ->and($descartado->creadoPor())->toBe($aviso->creadoPor())
        ->and($descartado->nota())->toBe($aviso->nota())
        ->and($descartado->fechaAviso())->toBe($aviso->fechaAviso())
        ->and($descartado->confirmadoPor())->toBeNull()
        ->and($aviso->estado())->toBe(EstadoDeAviso::Pendiente)
        ->and($aviso->descartadoPor())->toBeNull();
});

test('HU-30 c3: descartar conserva que lo reportó el jugador', function () {
    $descartado = avisoNuevoDePrueba(reportadoPorJugador: true)
        ->descartar(Identificador::generar(), 'No aplica', new DateTimeImmutable('2026-11-16 09:00:00+00:00'));

    expect($descartado->reportadoPorJugador())->toBeTrue();
});

test('§6.3: un aviso que ya no está pendiente no se descarta', function (Closure $aviso) {
    expect(fn () => $aviso()->descartar(Identificador::generar(), 'Otra vez', new DateTimeImmutable('2026-11-16 09:00:00+00:00')))
        ->toThrow(AvisoNoPendiente::class);
})->with([
    'descartado' => fn () => avisoNuevoDePrueba()->descartar(Identificador::generar(), 'Primera', new DateTimeImmutable('2026-11-16 08:00:00+00:00')),
    'confirmado' => fn () => avisoConfirmadoDePrueba(Identificador::generar(), Identificador::generar()),
]);

test('§6.3: el código de AvisoNoPendiente es aviso_no_pendiente', function () {
    expect((new AvisoNoPendiente)->codigo())->toBe('aviso_no_pendiente');
});

test('DD-58: la nota la ve quien la escribió, no otro usuario', function () {
    $autor = Identificador::generar();
    $aviso = avisoNuevoDePrueba($autor);

    expect($aviso->notaPara($autor))->toBe('Molestia en el tobillo')
        ->and($aviso->notaPara(Identificador::generar()))->toBeNull();
});

test('DD-58: la nota de un aviso confirmado la ven quien la escribió y quien lo confirmó', function () {
    $autor = Identificador::generar();
    $confirmador = Identificador::generar();
    $aviso = avisoConfirmadoDePrueba($autor, $confirmador);

    expect($aviso->notaPara($autor))->toBe('Molestia')
        ->and($aviso->notaPara($confirmador))->toBe('Molestia')
        ->and($aviso->notaPara(Identificador::generar()))->toBeNull();
});

test('DA-11: en un aviso descartado, quien descartó no ve la nota si no la escribió', function () {
    $autor = Identificador::generar();
    $descartador = Identificador::generar();
    $aviso = avisoNuevoDePrueba($autor)->descartar($descartador, 'No aplica', new DateTimeImmutable('2026-11-16 09:00:00+00:00'));

    expect($aviso->notaPara($descartador))->toBeNull()
        ->and($aviso->notaPara($autor))->toBe('Molestia en el tobillo');
});

test('DA-11: la nota del descarte la ven quien descartó y quien creó el aviso', function () {
    $autor = Identificador::generar();
    $descartador = Identificador::generar();
    $aviso = avisoNuevoDePrueba($autor)->descartar($descartador, 'No aplica', new DateTimeImmutable('2026-11-16 09:00:00+00:00'));

    expect($aviso->notaDescartePara($autor))->toBe('No aplica')
        ->and($aviso->notaDescartePara($descartador))->toBe('No aplica')
        ->and($aviso->notaDescartePara(Identificador::generar()))->toBeNull();
});

test('DA-11: un aviso sin descarte no tiene nota del descarte para nadie', function () {
    $autor = Identificador::generar();

    expect(avisoNuevoDePrueba($autor)->notaDescartePara($autor))->toBeNull();
});

test('§6.2: es_propio solo para quien creó el aviso', function () {
    $autor = Identificador::generar();
    $aviso = avisoNuevoDePrueba($autor);

    expect($aviso->esPropioDe($autor))->toBeTrue()
        ->and($aviso->esPropioDe(Identificador::generar()))->toBeFalse();
});

test('§5.1: los valores de EstadoDeAviso son los de la tabla', function () {
    expect(array_map(fn (EstadoDeAviso $estado) => $estado->value, EstadoDeAviso::cases()))->toBe(['pendiente', 'confirmado', 'descartado']);
});
