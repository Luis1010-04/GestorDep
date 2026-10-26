<?php

use Src\Compartido\Dominio\Identificador;
use Src\Identidad\Dominio\Usuario;

const INTENTOS_MAXIMOS = 5;
const MINUTOS_DE_BLOQUEO = 15;

function usuarioNuevo(): Usuario
{
    return Usuario::crear(Identificador::generar(), 'Ana', 'ana@example.test', 'hash', ['entrenador']);
}

function instante(string $hora): DateTimeImmutable
{
    return new DateTimeImmutable("2026-03-15 $hora", new DateTimeZone('UTC'));
}

function fallar(Usuario $usuario, int $veces, string $hora = '10:00:00'): void
{
    for ($i = 0; $i < $veces; $i++) {
        $usuario->registrarIntentoFallido(instante($hora), INTENTOS_MAXIMOS, MINUTOS_DE_BLOQUEO);
    }
}

test('RNF-10 (HU-01 fila 6): la quinta contraseña incorrecta bloquea 15 minutos', function () {
    $usuario = usuarioNuevo();

    fallar($usuario, 4);
    expect($usuario->intentosFallidos())->toBe(4)
        ->and($usuario->bloqueadoHasta())->toBeNull()
        ->and($usuario->estaBloqueado(instante('10:00:00')))->toBeFalse();

    fallar($usuario, 1);
    expect($usuario->intentosFallidos())->toBe(5)
        ->and($usuario->bloqueadoHasta())->toEqual(instante('10:15:00'))
        ->and($usuario->estaBloqueado(instante('10:00:00')))->toBeTrue();
});

test('RNF-10 (HU-01 fila 8): a los 14 min 59 s del bloqueo sigue bloqueado', function () {
    $usuario = usuarioNuevo();
    fallar($usuario, 5);

    expect($usuario->estaBloqueado(instante('10:14:59')))->toBeTrue();
});

test('RNF-10 (HU-01 fila 9): a los 15 min 00 s el bloqueo vence y el acceso reinicia el contador', function () {
    $usuario = usuarioNuevo();
    fallar($usuario, 5);

    expect($usuario->estaBloqueado(instante('10:15:00')))->toBeFalse();

    $usuario->registrarAcceso();
    expect($usuario->intentosFallidos())->toBe(0)
        ->and($usuario->bloqueadoHasta())->toBeNull();
});

test('RNF-10 (HU-01 fila 10): cuatro fallos y un acierto reinician el contador', function () {
    $usuario = usuarioNuevo();
    fallar($usuario, 4);

    $usuario->registrarAcceso();

    expect($usuario->intentosFallidos())->toBe(0)
        ->and($usuario->estaBloqueado(instante('10:00:00')))->toBeFalse();
});

test('RNF-10 (HU-01 fila 11): un fallo con el bloqueo vencido reinicia el contador en 1', function () {
    $usuario = usuarioNuevo();
    fallar($usuario, 5);

    $usuario->registrarIntentoFallido(instante('10:15:00'), INTENTOS_MAXIMOS, MINUTOS_DE_BLOQUEO);

    expect($usuario->intentosFallidos())->toBe(1)
        ->and($usuario->bloqueadoHasta())->toBeNull();
});

test('RNF-10: los parámetros del bloqueo salen de los argumentos y no del código', function () {
    $usuario = usuarioNuevo();

    $usuario->registrarIntentoFallido(instante('10:00:00'), 1, 30);

    expect($usuario->bloqueadoHasta())->toEqual(instante('10:30:00'));
});

test('RN-19: un usuario nuevo nace activo, sin intentos y con sus roles', function () {
    $usuario = Usuario::crear(Identificador::generar(), 'Ana', 'ana@example.test', 'hash', ['docente', 'entrenador']);

    expect($usuario->activo())->toBeTrue()
        ->and($usuario->intentosFallidos())->toBe(0)
        ->and($usuario->roles())->toBe(['docente', 'entrenador'])
        ->and($usuario->nombre())->toBe('Ana')
        ->and($usuario->correo())->toBe('ana@example.test')
        ->and($usuario->contrasenaHash())->toBe('hash');
});
