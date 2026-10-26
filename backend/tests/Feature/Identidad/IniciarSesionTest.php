<?php

use Database\Seeders\RolesSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

const CLAVE_CORRECTA = 'una-clave-larga-1';
const CORREO_DE_ANA = 'ana@x.com';

beforeEach(function () {
    esquemaReiniciar();
    (new RolesSeeder)->run();
    config(['session.driver' => 'database']);
    fijarReloj('2026-03-15 10:00:00');
});

function fijarReloj(string $momento): void
{
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable($momento, new DateTimeZone('UTC'))));
}

/** @param  list<string>  $roles */
function usuarioParaSesion(string $correo = CORREO_DE_ANA, bool $activo = true, array $roles = ['entrenador']): string
{
    $id = Identificador::generar()->valor();
    DB::connection('propietario')->table('usuarios')->insert([
        'id' => $id,
        'nombre' => 'Ana',
        'correo' => $correo,
        'contrasena_hash' => Hash::make(CLAVE_CORRECTA),
        'activo' => $activo,
    ]);
    foreach ($roles as $codigo) {
        DB::connection('propietario')->table('usuario_rol')->insert([
            'usuario_id' => $id,
            'rol_id' => DB::table('roles')->where('codigo', $codigo)->value('id'),
        ]);
    }

    return $id;
}

/**
 * Petición de un cliente SPA: el origen de un dominio con estado hace que Sanctum inicie la sesión.
 *
 * @param  array<string, mixed>  $cuerpo
 * @param  array<string, string>  $cookies  identificador de sesión; la prueba lo cifra como un navegador real
 * @return TestResponse<Response>
 */
function iniciarSesion(TestCase $prueba, array $cuerpo, array $cookies = []): TestResponse
{
    $prueba->withCredentials()->withHeaders(['Origin' => 'http://localhost']);
    foreach ($cookies as $nombre => $valor) {
        $prueba->withCookie($nombre, $valor);
    }

    return $prueba->postJson('/api/v1/sesion', $cuerpo);
}

function conClaveIncorrecta(TestCase $prueba, int $veces): void
{
    for ($i = 0; $i < $veces; $i++) {
        iniciarSesion($prueba, ['correo' => CORREO_DE_ANA, 'contrasena' => 'incorrecta-1234']);
    }
}

function intentosFallidos(): int
{
    return (int) DB::table('usuarios')->where('correo', CORREO_DE_ANA)->value('intentos_fallidos');
}

function bloqueadoHasta(): ?string
{
    $valor = DB::table('usuarios')->where('correo', CORREO_DE_ANA)->value('bloqueado_hasta');

    return is_string($valor) ? Carbon::parse($valor)->utc()->format('Y-m-d H:i:s') : null;
}

test('HU-01: credenciales válidas devuelven el usuario y regeneran la sesión', function () {
    /** @var TestCase $this */
    $id = usuarioParaSesion(roles: ['entrenador', 'docente']);

    $this->withHeaders(['Origin' => 'http://localhost'])->get('/sanctum/csrf-cookie')->assertNoContent();
    $idPrevio = DB::table('sessions')->value('id');

    iniciarSesion($this, ['correo' => CORREO_DE_ANA, 'contrasena' => CLAVE_CORRECTA], [config('session.cookie') => $idPrevio])
        ->assertOk()
        ->assertExactJson(['datos' => ['id' => $id, 'nombre' => 'Ana', 'correo' => CORREO_DE_ANA, 'roles' => ['docente', 'entrenador']]]);

    $autenticada = DB::table('sessions')->where('user_id', $id)->first();

    expect($autenticada)->not->toBeNull()
        ->and($autenticada->id)->not->toBe($idPrevio)
        ->and(DB::table('sessions')->where('id', $idPrevio)->value('user_id'))->toBeNull();
});

test('HU-01: contraseña incorrecta de un usuario existente responde 401 y cuenta un intento', function () {
    /** @var TestCase $this */
    usuarioParaSesion();

    iniciarSesion($this, ['correo' => CORREO_DE_ANA, 'contrasena' => 'incorrecta-1234'])
        ->assertUnauthorized()
        ->assertExactJson(['codigo' => 'credenciales_invalidas', 'mensaje' => 'Las credenciales no son válidas.', 'errores' => []]);

    expect(intentosFallidos())->toBe(1);
});

test('HU-01: un correo inexistente responde lo mismo que una contraseña incorrecta', function () {
    /** @var TestCase $this */
    usuarioParaSesion();

    $conocido = iniciarSesion($this, ['correo' => CORREO_DE_ANA, 'contrasena' => 'incorrecta-1234']);
    $inexistente = iniciarSesion($this, ['correo' => 'nadie@x.com', 'contrasena' => 'incorrecta-1234']);

    $inexistente->assertUnauthorized()->assertJsonPath('codigo', 'credenciales_invalidas');
    expect((string) $inexistente->getContent())->toBe((string) $conocido->getContent());
});

test('HU-01: un usuario desactivado con la contraseña correcta responde lo mismo', function () {
    /** @var TestCase $this */
    usuarioParaSesion(activo: false);
    usuarioParaSesion('beto@x.com');

    $referencia = iniciarSesion($this, ['correo' => 'beto@x.com', 'contrasena' => 'incorrecta-1234']);
    $desactivado = iniciarSesion($this, ['correo' => CORREO_DE_ANA, 'contrasena' => CLAVE_CORRECTA]);

    $desactivado->assertUnauthorized();
    expect((string) $desactivado->getContent())->toBe((string) $referencia->getContent())
        ->and(intentosFallidos())->toBe(0);
});

test('HU-01: el correo no distingue mayúsculas', function () {
    /** @var TestCase $this */
    usuarioParaSesion();

    iniciarSesion($this, ['correo' => 'ANA@X.com', 'contrasena' => CLAVE_CORRECTA])
        ->assertOk()
        ->assertJsonPath('datos.correo', CORREO_DE_ANA);
});

test('RNF-10: la quinta contraseña incorrecta responde 401 y bloquea 15 minutos', function () {
    /** @var TestCase $this */
    usuarioParaSesion();

    conClaveIncorrecta($this, 4);
    iniciarSesion($this, ['correo' => CORREO_DE_ANA, 'contrasena' => 'incorrecta-1234'])->assertUnauthorized();

    expect(intentosFallidos())->toBe(5)
        ->and(bloqueadoHasta())->toBe('2026-03-15 10:15:00');
});

test('RNF-10: la contraseña correcta con la cuenta bloqueada responde 429 con Retry-After', function () {
    /** @var TestCase $this */
    usuarioParaSesion();
    conClaveIncorrecta($this, 5);

    iniciarSesion($this, ['correo' => CORREO_DE_ANA, 'contrasena' => CLAVE_CORRECTA])
        ->assertStatus(429)
        ->assertHeader('Retry-After', '900')
        ->assertExactJson(['codigo' => 'cuenta_bloqueada', 'mensaje' => 'La cuenta está bloqueada temporalmente por intentos fallidos.', 'errores' => []]);
});

test('RNF-10: un intento a los 14 min 59 s del bloqueo responde 429', function () {
    /** @var TestCase $this */
    usuarioParaSesion();
    conClaveIncorrecta($this, 5);
    fijarReloj('2026-03-15 10:14:59');

    iniciarSesion($this, ['correo' => CORREO_DE_ANA, 'contrasena' => CLAVE_CORRECTA])
        ->assertStatus(429)
        ->assertHeader('Retry-After', '1');
});

test('RNF-10: un intento correcto a los 15 min 00 s del bloqueo responde 200 y limpia el bloqueo', function () {
    /** @var TestCase $this */
    usuarioParaSesion();
    conClaveIncorrecta($this, 5);
    fijarReloj('2026-03-15 10:15:00');

    iniciarSesion($this, ['correo' => CORREO_DE_ANA, 'contrasena' => CLAVE_CORRECTA])->assertOk();

    expect(intentosFallidos())->toBe(0)
        ->and(bloqueadoHasta())->toBeNull();
});

test('RNF-10: cuatro fallos y un acierto reinician el contador', function () {
    /** @var TestCase $this */
    usuarioParaSesion();
    conClaveIncorrecta($this, 4);

    iniciarSesion($this, ['correo' => CORREO_DE_ANA, 'contrasena' => CLAVE_CORRECTA])->assertOk();

    expect(intentosFallidos())->toBe(0);
});

test('RNF-10: con el bloqueo vencido, un nuevo fallo reinicia el contador en 1', function () {
    /** @var TestCase $this */
    usuarioParaSesion();
    conClaveIncorrecta($this, 5);
    fijarReloj('2026-03-15 10:20:00');

    iniciarSesion($this, ['correo' => CORREO_DE_ANA, 'contrasena' => 'incorrecta-1234'])->assertUnauthorized();

    expect(intentosFallidos())->toBe(1)
        ->and(bloqueadoHasta())->toBeNull();
});

test('HU-01: un cuerpo sin correo o sin contraseña responde 422', function (array $cuerpo, string $campo) {
    /** @var TestCase $this */
    usuarioParaSesion();

    iniciarSesion($this, $cuerpo)
        ->assertStatus(422)
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', $campo);
})->with([
    'sin correo' => [['contrasena' => CLAVE_CORRECTA], 'correo'],
    'sin contraseña' => [['correo' => CORREO_DE_ANA], 'contrasena'],
]);

/**
 * Inicia sesión, deja la actividad de la sesión hace $minutos y consulta una ruta que exige sesión.
 *
 * @return TestResponse<Response>
 */
function consultarTrasInactividad(TestCase $prueba, int $minutos): TestResponse
{
    Route::prefix('api/v1')->middleware(['api', 'auth:web'])->get('/prueba-sesion', fn () => response()->json(['ok' => true]));

    iniciarSesion($prueba, ['correo' => CORREO_DE_ANA, 'contrasena' => CLAVE_CORRECTA])->assertOk();
    $idDeSesion = DB::table('sessions')->whereNotNull('user_id')->value('id');

    DB::table('sessions')->update(['last_activity' => time() - $minutos * 60]);
    // Dentro de una prueba el guard y el almacén de sesión sobreviven entre peticiones; en producción cada petición los crea de nuevo.
    Auth::forgetGuards();
    app('session')->forgetDrivers();
    app()->forgetInstance('session.store');

    return $prueba->withCredentials()->withHeaders(['Origin' => 'http://localhost'])
        ->withCookie(config('session.cookie'), $idDeSesion)
        ->getJson('/api/v1/prueba-sesion');
}

test('HU-01: una sesión inactiva 119 minutos sigue vigente', function () {
    /** @var TestCase $this */
    usuarioParaSesion();

    consultarTrasInactividad($this, 119)->assertOk();
});

test('HU-01: una sesión inactiva 121 minutos responde 401', function () {
    /** @var TestCase $this */
    usuarioParaSesion();

    consultarTrasInactividad($this, 121)->assertUnauthorized()->assertJsonPath('codigo', 'no_autenticado');
});

test('HU-01: las respuestas 200, 401 y 429 de POST /sesion cumplen el contrato', function () {
    /** @var TestCase $this */
    usuarioParaSesion();
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();

    $incorrecta = iniciarSesion($this, ['correo' => CORREO_DE_ANA, 'contrasena' => 'incorrecta-1234']);
    conClaveIncorrecta($this, 4);
    $bloqueada = iniciarSesion($this, ['correo' => CORREO_DE_ANA, 'contrasena' => CLAVE_CORRECTA]);
    fijarReloj('2026-03-15 10:15:00');
    $correcta = iniciarSesion($this, ['correo' => CORREO_DE_ANA, 'contrasena' => CLAVE_CORRECTA]);
    $invalida = iniciarSesion($this, []);

    foreach ([$correcta, $incorrecta, $bloqueada, $invalida] as $respuesta) {
        expect($validador->validate($respuesta->baseResponse, '/sesion', 'post'))->toBeTrue();
    }
});
