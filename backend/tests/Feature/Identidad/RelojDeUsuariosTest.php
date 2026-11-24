<?php

/*
 * AB-21 (PT-04; docs/hitos/f1-h7.md v0.6 §8, T06; DA-143): los instantes de `usuarios` salen de `Reloj`, truncados a
 * segundos, como en el resto de las tablas desde F1-H1-FIX. El reloj se fija lejos y con fracción de segundo: ni el
 * reloj de la base ni un instante sin truncar dan ese valor por casualidad.
 */

use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Tests\TestCase;

beforeEach(function () {
    sesionesPrepararBase();
});

function relojDeUsuariosFijar(): void
{
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2030-01-02 03:04:05.678901', new DateTimeZone('UTC'))));
}

/** Un instante guardado, en UTC y con microsegundos, para ver si lleva fracción. */
function relojDeUsuariosEnUtc(mixed $guardado): ?string
{
    return is_string($guardado) ? (new DateTimeImmutable($guardado))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.u') : null;
}

test('AB-21: usuarios.actualizado_en sale de Reloj al cambiar la contraseña y coincide con su historial', function () {
    /** @var TestCase $this */
    $id = sesionesCrearUsuario('ana@x.com', ['entrenador']);
    $sesion = sesionesIniciar($this, 'ana@x.com');
    relojDeUsuariosFijar();

    sesionesPeticion($this, 'PUT', '/api/v1/contrasena', ['contrasena_actual' => CLAVE_DE_SESIONES_DE_PRUEBA, 'contrasena_nueva' => 'otra-clave-larga-2'], $sesion)
        ->assertNoContent();

    expect(relojDeUsuariosEnUtc(DB::table('usuarios')->where('id', $id)->value('actualizado_en')))->toBe('2030-01-02 03:04:05.000000')
        ->and(relojDeUsuariosEnUtc(DB::table('usuarios_historial')->where('usuario_afectado_id', $id)->where('operacion', 'actualizar')->value('ocurrido_en')))
        ->toBe('2030-01-02 03:04:05.000000');
});

test('AB-21: bloqueado_hasta sale de Reloj, truncado a segundos', function () {
    /** @var TestCase $this */
    $id = sesionesCrearUsuario('ana@x.com', ['entrenador']);
    relojDeUsuariosFijar();

    for ($intento = 1; $intento <= 5; $intento++) {
        sesionesPeticion($this, 'POST', '/api/v1/sesion', ['correo' => 'ana@x.com', 'contrasena' => 'incorrecta-1234'])->assertUnauthorized();
    }

    expect(relojDeUsuariosEnUtc(DB::table('usuarios')->where('id', $id)->value('bloqueado_hasta')))->toBe('2030-01-02 03:19:05.000000');
});

test('AB-21: usuarios:crear escribe creado_en y actualizado_en desde Reloj', function () {
    relojDeUsuariosFijar();

    consolaArtisan('usuarios:crear', ['correo' => 'nuevo@x.com', 'nombre' => 'Nuevo', '--rol' => ['docente'], '--contrasena' => 'una-clave-larga-1'])
        ->assertSuccessful();

    expect(relojDeUsuariosEnUtc(DB::table('usuarios')->where('correo', 'nuevo@x.com')->value('creado_en')))->toBe('2030-01-02 03:04:05.000000')
        ->and(relojDeUsuariosEnUtc(DB::table('usuarios')->where('correo', 'nuevo@x.com')->value('actualizado_en')))->toBe('2030-01-02 03:04:05.000000');
});
