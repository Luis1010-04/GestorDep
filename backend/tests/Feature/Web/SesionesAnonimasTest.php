<?php

use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

const CORREO_ANONIMAS = 'anonimas@x.com';
const CLAVE_ANONIMAS = 'una-clave-larga-1';

beforeEach(function () {
    esquemaReiniciar();
    (new RolesSeeder)->run();
    config(['session.driver' => 'database']);
});

/**
 * Una visita de un navegador: el guard y el almacén de sesión se crean de nuevo, como en producción, y la cookie de
 * sesión es la indicada (o ninguna).
 *
 * @return TestResponse<Response>
 */
function visitaWeb(TestCase $prueba, string $ruta, ?string $idDeSesion = null): TestResponse
{
    Auth::forgetGuards();
    app('session')->forgetDrivers();
    app()->forgetInstance('session.store');

    (fn () => $this->defaultCookies = $idDeSesion === null ? [] : [config('session.cookie') => $idDeSesion])->call($prueba);

    return $prueba->get($ruta);
}

function filasDeSesion(): int
{
    return DB::table('sessions')->count();
}

/** Abre una sesión autenticada por la API y devuelve su identificador. */
function abrirSesionAutenticada(TestCase $prueba): string
{
    $id = Identificador::generar()->valor();
    DB::connection('propietario')->table('usuarios')->insert([
        'id' => $id,
        'nombre' => 'Anónimas',
        'correo' => CORREO_ANONIMAS,
        'contrasena_hash' => Hash::make(CLAVE_ANONIMAS),
        'activo' => true,
    ]);
    DB::connection('propietario')->table('usuario_rol')->insert([
        'usuario_id' => $id,
        'rol_id' => DB::table('roles')->where('codigo', 'entrenador')->value('id'),
    ]);

    Auth::forgetGuards();
    app('session')->forgetDrivers();
    app()->forgetInstance('session.store');
    $prueba->withCredentials()->withHeaders(['Origin' => 'http://localhost'])
        ->postJson('/api/v1/sesion', ['correo' => CORREO_ANONIMAS, 'contrasena' => CLAVE_ANONIMAS])
        ->assertOk();
    $prueba->withoutHeader('Origin');

    return (string) DB::table('sessions')->where('user_id', $id)->value('id');
}

/** @return array<string, mixed> */
function datosDeSesion(string $idDeSesion): array
{
    $datos = json_decode(base64_decode((string) DB::table('sessions')->where('id', $idDeSesion)->value('payload')), true);

    return is_array($datos) ? $datos : [];
}

test('PT-16: GET / anónimo sin cookie redirige a /panel sin crear fila en sessions ni enviar cookie de sesión', function () {
    /** @var TestCase $this */
    visitaWeb($this, '/')
        ->assertRedirect('/panel')
        ->assertCookieMissing((string) config('session.cookie'));

    expect(filasDeSesion())->toBe(0);
});

test('PT-16: GET /ingreso anónimo sin cookie responde 200 sin crear fila en sessions ni enviar cookie de sesión', function () {
    /** @var TestCase $this */
    visitaWeb($this, '/ingreso')
        ->assertOk()
        ->assertCookieMissing((string) config('session.cookie'));

    expect(filasDeSesion())->toBe(0);
});

test('PT-16: GET /panel anónimo sin cookie redirige a /ingreso sin crear fila en sessions ni enviar cookie de sesión', function () {
    /** @var TestCase $this */
    visitaWeb($this, '/panel')
        ->assertRedirect('/ingreso')
        ->assertCookieMissing((string) config('session.cookie'));

    expect(filasDeSesion())->toBe(0);
});

test('PT-16: una cookie de sesión que no se puede descifrar se trata como ausente y no crea fila', function () {
    /** @var TestCase $this */
    Auth::forgetGuards();
    app('session')->forgetDrivers();
    app()->forgetInstance('session.store');

    $this->withUnencryptedCookie((string) config('session.cookie'), 'basura-sin-cifrar')
        ->get('/ingreso')
        ->assertOk()
        ->assertCookieMissing((string) config('session.cookie'));

    expect(filasDeSesion())->toBe(0);
});

test('PT-16: HEAD /ingreso anónimo sin cookie responde 200 sin crear fila en sessions ni enviar cookie de sesión', function () {
    /** @var TestCase $this */
    Auth::forgetGuards();
    app('session')->forgetDrivers();
    app()->forgetInstance('session.store');

    $this->call('HEAD', '/ingreso')
        ->assertOk()
        ->assertCookieMissing((string) config('session.cookie'));

    expect(filasDeSesion())->toBe(0);
});

test('PT-16: con la cookie de una sesión autenticada, GET /panel responde 200 y sigue guardando su fila', function () {
    /** @var TestCase $this */
    $idDeSesion = abrirSesionAutenticada($this);

    visitaWeb($this, '/panel', $idDeSesion)
        ->assertOk()
        ->assertCookie((string) config('session.cookie'));

    expect(filasDeSesion())->toBe(1)
        ->and(datosDeSesion($idDeSesion)['_previous']['url'] ?? null)->toBe(url('/panel'));
});

test('PT-16: con la cookie de una sesión anónima existente, GET /ingreso responde 200 y la conserva', function () {
    /** @var TestCase $this */
    visitaWeb($this, '/sanctum/csrf-cookie')->assertNoContent();
    $idDeSesion = (string) DB::table('sessions')->value('id');

    visitaWeb($this, '/ingreso', $idDeSesion)
        ->assertOk()
        ->assertCookie((string) config('session.cookie'));

    expect(filasDeSesion())->toBe(1)
        ->and(datosDeSesion($idDeSesion)['_previous']['url'] ?? null)->toBe(url('/ingreso'));
});

test('PT-16: un usuario autenticado en GET /ingreso sigue redirigido a /panel', function () {
    /** @var TestCase $this */
    $idDeSesion = abrirSesionAutenticada($this);

    visitaWeb($this, '/ingreso', $idDeSesion)->assertRedirect('/panel');
});

test('PT-16: GET /sanctum/csrf-cookie sin cookie sigue creando la sesión y entregando sus cookies', function () {
    /** @var TestCase $this */
    visitaWeb($this, '/sanctum/csrf-cookie')
        ->assertNoContent()
        ->assertCookie((string) config('session.cookie'))
        ->assertCookie('XSRF-TOKEN', null, false);

    expect(filasDeSesion())->toBe(1);
});

test('PT-16: una cookie de sesión válida con fila inexistente crea la fila con ese id, como hoy', function () {
    /** @var TestCase $this */
    $idDeSesion = Str::random(40);

    visitaWeb($this, '/ingreso', $idDeSesion)->assertOk();

    expect(DB::table('sessions')->pluck('id')->all())->toBe([$idDeSesion]);
});
