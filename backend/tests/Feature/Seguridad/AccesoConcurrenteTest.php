<?php

/*
 * AB-17 (docs/hitos/f1-h7.md v0.6 §8, T06; DA-126): el contador de intentos fallidos con peticiones simultáneas.
 * «La otra petición» la hace `concurrenciaMientrasOtraTransaccion` (tests/Ayudantes/TransaccionConcurrente.php).
 */

use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

const ACCESO_CORREO = 'concurrente@x.com';

beforeEach(function () {
    esquemaReiniciar();
    (new RolesSeeder)->run();
    config(['session.driver' => 'database']);
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-03-15 10:00:00', new DateTimeZone('UTC'))));
});

function accesoUsuario(int $intentosFallidos = 0): string
{
    $id = Identificador::generar()->valor();
    DB::connection('propietario')->table('usuarios')->insert([
        'id' => $id,
        'nombre' => 'Concurrente',
        'correo' => ACCESO_CORREO,
        'contrasena_hash' => Hash::make('una-clave-larga-1'),
        'activo' => true,
        'intentos_fallidos' => $intentosFallidos,
    ]);

    return $id;
}

/** @return TestResponse<Response> */
function accesoFallar(TestCase $prueba): TestResponse
{
    return $prueba->withCredentials()->withHeaders(['Origin' => 'http://localhost'])
        ->postJson('/api/v1/sesion', ['correo' => ACCESO_CORREO, 'contrasena' => 'incorrecta-1234']);
}

function accesoIntentos(): int
{
    return (int) DB::table('usuarios')->where('correo', ACCESO_CORREO)->value('intentos_fallidos');
}

test('AB-17: dos intentos fallidos simultáneos cuentan dos', function () {
    /** @var TestCase $this */
    $usuario = accesoUsuario();

    ['resultado' => $respuesta, 'hubo_espera' => $espero] = concurrenciaMientrasOtraTransaccion(
        [['update usuarios set intentos_fallidos = intentos_fallidos + 1 where id = ?', [$usuario]]],
        fn () => accesoFallar($this),
    );

    expect($respuesta->getStatusCode())->toBe(401)
        ->and(accesoIntentos())->toBe(2)
        ->and($espero)->toBeTrue();
});

test('AB-17: un fallo que llega con la cuenta recién bloqueada por otro intento responde 429 y no cuenta', function () {
    /** @var TestCase $this */
    $usuario = accesoUsuario(intentosFallidos: 4);

    ['resultado' => $respuesta, 'hubo_espera' => $espero] = concurrenciaMientrasOtraTransaccion(
        [['update usuarios set intentos_fallidos = 5, bloqueado_hasta = ? where id = ?', ['2026-03-15 10:15:00+00', $usuario]]],
        fn () => accesoFallar($this),
    );

    expect($respuesta->getStatusCode())->toBe(429)
        ->and($respuesta->json('codigo'))->toBe('cuenta_bloqueada')
        ->and($respuesta->headers->get('Retry-After'))->toBe('900')
        ->and(accesoIntentos())->toBe(5)
        ->and($espero)->toBeTrue();
});
