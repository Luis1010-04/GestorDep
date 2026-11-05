<?php

use Src\Compartido\Dominio\Identificador;
use Src\Salud\Dominio\EstadoEnElMismoInstante;
use Src\Salud\Dominio\EstadoOficial;
use Src\Salud\Dominio\NoEsRegistrante;
use Src\Salud\Dominio\SinLesionVigente;
use Src\Salud\Dominio\TipoDeEstado;

function estadoLesionadoDePrueba(?Identificador $registrante = null, string $regreso = '2026-11-30'): EstadoOficial
{
    return EstadoOficial::conLesion(
        Identificador::generar(), Identificador::generar(), TipoDeEstado::Lesionado, Identificador::generar(), $regreso,
        $registrante ?? Identificador::generar(), new DateTimeImmutable('2026-11-15 18:00:00+00:00'),
    );
}

function estadoAptoDePrueba(?Identificador $registrante = null): EstadoOficial
{
    return EstadoOficial::apto(Identificador::generar(), Identificador::generar(), $registrante ?? Identificador::generar(), new DateTimeImmutable('2026-11-15 18:00:00+00:00'));
}

test('HU-20 c1: un estado con lesión guarda su tipo, lesión, fecha y registrante, y queda vigente', function () {
    $id = Identificador::generar();
    $jugador = Identificador::generar();
    $lesion = Identificador::generar();
    $registrante = Identificador::generar();
    $instante = new DateTimeImmutable('2026-11-15 18:00:00+00:00');

    $estado = EstadoOficial::conLesion($id, $jugador, TipoDeEstado::ConMolestia, $lesion, '2026-11-30', $registrante, $instante);

    expect($estado->id())->toBe($id)
        ->and($estado->jugadorId())->toBe($jugador)
        ->and($estado->tipo())->toBe(TipoDeEstado::ConMolestia)
        ->and($estado->lesionId())->toBe($lesion)
        ->and($estado->fechaEstimadaRegreso())->toBe('2026-11-30')
        ->and($estado->confirmadoPor())->toBe($registrante)
        ->and($estado->vigenteDesde())->toBe($instante)
        ->and($estado->vigenteHasta())->toBeNull();
});

test('DD-57: un estado apto no tiene lesión ni fecha estimada', function () {
    $id = Identificador::generar();
    $jugador = Identificador::generar();
    $registrante = Identificador::generar();
    $instante = new DateTimeImmutable('2026-11-15 18:00:00+00:00');

    $estado = EstadoOficial::apto($id, $jugador, $registrante, $instante);

    expect($estado->id())->toBe($id)
        ->and($estado->jugadorId())->toBe($jugador)
        ->and($estado->tipo())->toBe(TipoDeEstado::Apto)
        ->and($estado->lesionId())->toBeNull()
        ->and($estado->fechaEstimadaRegreso())->toBeNull()
        ->and($estado->confirmadoPor())->toBe($registrante)
        ->and($estado->vigenteDesde())->toBe($instante)
        ->and($estado->vigenteHasta())->toBeNull();
});

test('DA-14: cerrar pone vigente_hasta y conserva lo demás, sin tocar el original', function () {
    $estado = estadoLesionadoDePrueba();
    $cierre = new DateTimeImmutable('2026-11-16 09:00:00+00:00');

    $cerrado = $estado->cerrar($cierre);

    expect($cerrado->vigenteHasta())->toBe($cierre)
        ->and($cerrado->id())->toBe($estado->id())
        ->and($cerrado->jugadorId())->toBe($estado->jugadorId())
        ->and($cerrado->tipo())->toBe($estado->tipo())
        ->and($cerrado->lesionId())->toBe($estado->lesionId())
        ->and($cerrado->fechaEstimadaRegreso())->toBe($estado->fechaEstimadaRegreso())
        ->and($cerrado->confirmadoPor())->toBe($estado->confirmadoPor())
        ->and($cerrado->vigenteDesde())->toBe($estado->vigenteDesde())
        ->and($estado->vigenteHasta())->toBeNull();
});

test('DA-30: un estado no se cierra en el mismo instante en que se abrió, ni antes', function (string $instante) {
    expect(fn () => estadoLesionadoDePrueba()->cerrar(new DateTimeImmutable($instante)))->toThrow(EstadoEnElMismoInstante::class);
})->with(['mismo segundo' => '2026-11-15 18:00:00+00:00', 'antes' => '2026-11-15 17:59:59+00:00']);

test('DA-30: un estado se cierra un segundo después de abrirse', function () {
    $cierre = new DateTimeImmutable('2026-11-15 18:00:01+00:00');

    expect(estadoLesionadoDePrueba()->cerrar($cierre)->vigenteHasta())->toBe($cierre);
});

test('DA-14: cambiar la fecha conserva la misma fila, sin tocar el original', function () {
    $estado = estadoLesionadoDePrueba();

    $cambiado = $estado->conFecha('2026-12-10');

    expect($cambiado->fechaEstimadaRegreso())->toBe('2026-12-10')
        ->and($cambiado->id())->toBe($estado->id())
        ->and($cambiado->jugadorId())->toBe($estado->jugadorId())
        ->and($cambiado->tipo())->toBe($estado->tipo())
        ->and($cambiado->lesionId())->toBe($estado->lesionId())
        ->and($cambiado->confirmadoPor())->toBe($estado->confirmadoPor())
        ->and($cambiado->vigenteDesde())->toBe($estado->vigenteDesde())
        ->and($cambiado->vigenteHasta())->toBeNull()
        ->and($estado->fechaEstimadaRegreso())->toBe('2026-11-30');
});

test('DA-14: cambiar el estado abre otro con la misma lesión y el mismo registrante', function () {
    $estado = estadoLesionadoDePrueba();
    $id = Identificador::generar();
    $instante = new DateTimeImmutable('2026-11-16 09:00:00+00:00');

    $nuevo = $estado->sucesor($id, TipoDeEstado::ConMolestia, '2026-12-10', $instante);

    expect($nuevo->id())->toBe($id)
        ->and($nuevo->jugadorId())->toBe($estado->jugadorId())
        ->and($nuevo->tipo())->toBe(TipoDeEstado::ConMolestia)
        ->and($nuevo->lesionId())->toBe($estado->lesionId())
        ->and($nuevo->fechaEstimadaRegreso())->toBe('2026-12-10')
        ->and($nuevo->confirmadoPor())->toBe($estado->confirmadoPor())
        ->and($nuevo->vigenteDesde())->toBe($instante)
        ->and($nuevo->vigenteHasta())->toBeNull();
});

test('RN-16: solo el registrante modifica un estado con lesión', function () {
    $registrante = Identificador::generar();
    $estado = estadoLesionadoDePrueba($registrante);

    $estado->exigirModificablePor($registrante);

    expect($estado->esSostenidoPor($registrante))->toBeTrue()
        ->and($estado->esSostenidoPor(Identificador::generar()))->toBeFalse()
        ->and(fn () => $estado->exigirModificablePor(Identificador::generar()))->toThrow(NoEsRegistrante::class);
});

test('§3.4: un estado apto no se modifica, tampoco por quien lo sostiene ni por otro (sin_lesion_vigente)', function () {
    $registrante = Identificador::generar();
    $estado = estadoAptoDePrueba($registrante);

    expect(fn () => $estado->exigirModificablePor($registrante))->toThrow(SinLesionVigente::class)
        ->and(fn () => $estado->exigirModificablePor(Identificador::generar()))->toThrow(SinLesionVigente::class);
});

test('DA-09: requiere actualización si la fecha estimada es anterior a hoy', function (string $regreso, string $hoy, bool $esperado) {
    expect(estadoLesionadoDePrueba(regreso: $regreso)->requiereActualizacion($hoy))->toBe($esperado);
})->with([
    'ayer' => ['2026-11-14', '2026-11-15', true],
    'hoy' => ['2026-11-15', '2026-11-15', false],
    'mañana' => ['2026-11-16', '2026-11-15', false],
]);

test('DA-09: un estado apto no requiere actualización', function () {
    expect(estadoAptoDePrueba()->requiereActualizacion('2026-11-15'))->toBeFalse();
});

test('§6.3: los códigos de las reglas del estado', function () {
    expect((new SinLesionVigente)->codigo())->toBe('sin_lesion_vigente')
        ->and((new NoEsRegistrante)->codigo())->toBe('no_es_registrante');
});

test('§5.1: los valores de TipoDeEstado son los de la tabla', function () {
    expect(array_map(fn (TipoDeEstado $tipo) => $tipo->value, TipoDeEstado::cases()))->toBe(['apto', 'con_molestia', 'lesionado']);
});
