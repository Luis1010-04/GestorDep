<?php

use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\CondicionDePartido;
use Src\Competencia\Dominio\EstadoDePartido;
use Src\Competencia\Dominio\Partido;
use Src\Competencia\Dominio\PartidoNoProgramado;
use Src\Competencia\Dominio\TipoDePartido;
use Src\Competencia\Dominio\TransicionNoPermitida;

function partidoDePrueba(EstadoDePartido $estado = EstadoDePartido::Programado, TipoDePartido $tipo = TipoDePartido::Oficial, string $inicia = '2026-09-12 23:00:00'): Partido
{
    $utc = new DateTimeZone('UTC');

    return new Partido(
        Identificador::generar(), Identificador::generar(), Identificador::generar(), Identificador::generar(),
        Identificador::generar(), Identificador::generar(), new DateTimeImmutable($inicia, $utc),
        $tipo, CondicionDePartido::Local, $estado, null, null, new DateTimeImmutable('2026-09-01 15:00:00', $utc),
    );
}

function instanteDePartido(): DateTimeImmutable
{
    return new DateTimeImmutable('2026-09-05 10:00:00', new DateTimeZone('UTC'));
}

test('HU-12 (§7.4 fila 8): el día local de inicia_en se toma en la zona indicada', function (string $inicia, string $dia) {
    expect(partidoDePrueba(inicia: $inicia)->diaLocal(new DateTimeZone('America/Mexico_City'))->format('Y-m-d H:i:s'))->toBe("$dia 00:00:00");
})->with([
    '00:30 UTC del 16 es el 15 local' => ['2026-12-16 00:30:00', '2026-12-15'],
    '05:59:59 UTC del 16 es el 15 local' => ['2026-12-16 05:59:59', '2026-12-15'],
    '06:00 UTC del 16 ya es el 16 local' => ['2026-12-16 06:00:00', '2026-12-16'],
]);

test('HU-12 (§3.4): el torneo es obligatorio en oficial y torneo, y opcional en amistoso e interno', function (TipoDePartido $tipo, bool $exige) {
    expect($tipo->exigeTorneo())->toBe($exige);
})->with([
    [TipoDePartido::Oficial, true], [TipoDePartido::Torneo, true],
    [TipoDePartido::Amistoso, false], [TipoDePartido::Interno, false],
]);

test('HU-12: conDatos reemplaza lo indicado, conserva lo demás y fija el instante', function () {
    $original = partidoDePrueba();
    $temporada = Identificador::generar();
    $inicia = new DateTimeImmutable('2027-02-01 23:00:00', new DateTimeZone('UTC'));

    $cambiado = $original->conDatos($temporada, null, $original->rivalId(), $original->lugarId(), $inicia, TipoDePartido::Amistoso, CondicionDePartido::Visitante, instanteDePartido());

    expect($cambiado->temporadaId())->toBe($temporada)
        ->and($cambiado->torneoId())->toBeNull()
        ->and($cambiado->iniciaEn())->toBe($inicia)
        ->and($cambiado->tipo())->toBe(TipoDePartido::Amistoso)
        ->and($cambiado->condicion())->toBe(CondicionDePartido::Visitante)
        ->and($cambiado->estado())->toBe(EstadoDePartido::Programado)
        ->and($cambiado->id())->toBe($original->id())
        ->and($cambiado->equipoId())->toBe($original->equipoId())
        ->and($cambiado->actualizadoEn())->toEqual(instanteDePartido());
});

test('HU-12: conDatos en un partido jugado o cancelado lanza PartidoNoProgramado', function (EstadoDePartido $estado) {
    $partido = partidoDePrueba($estado);

    expect(fn () => $partido->conDatos($partido->temporadaId(), null, $partido->rivalId(), $partido->lugarId(), $partido->iniciaEn(), TipoDePartido::Amistoso, CondicionDePartido::Local, instanteDePartido()))
        ->toThrow(PartidoNoProgramado::class, 'partido_no_programado');
})->with([EstadoDePartido::Jugado, EstadoDePartido::Cancelado]);

test('HU-12 (§7.4 fila 6): de programado se cancela; un partido jugado o cancelado no se cancela', function () {
    $cancelado = partidoDePrueba()->cancelado(instanteDePartido());

    expect($cancelado->estado())->toBe(EstadoDePartido::Cancelado)
        ->and($cancelado->actualizadoEn())->toEqual(instanteDePartido())
        ->and(fn () => $cancelado->cancelado(instanteDePartido()))->toThrow(TransicionNoPermitida::class)
        ->and(fn () => partidoDePrueba(EstadoDePartido::Jugado)->cancelado(instanteDePartido()))->toThrow(TransicionNoPermitida::class);
});

test('HU-12 (§7.4 fila 6, parcial hasta T07): un partido cancelado no admite resultado; uno programado sí y pasa a jugado', function () {
    $jugado = partidoDePrueba()->conResultado(3, 1, instanteDePartido());

    expect($jugado->estado())->toBe(EstadoDePartido::Jugado)
        ->and([$jugado->golesFavor(), $jugado->golesContra()])->toBe([3, 1])
        ->and($jugado->actualizadoEn())->toEqual(instanteDePartido())
        ->and(fn () => partidoDePrueba(EstadoDePartido::Cancelado)->conResultado(3, 1, instanteDePartido()))->toThrow(PartidoNoProgramado::class)
        ->and(fn () => $jugado->conResultado(2, 2, instanteDePartido()))->toThrow(PartidoNoProgramado::class);
});
