<?php

use Database\Seeders\RolesSeeder;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

use function Pest\Laravel\artisan;

const CLAVE_VIGENTE = 'una-clave-larga-1';
const CORREO_DE_LUZ = 'luz@x.com';

beforeEach(function () {
    artisan('migrate:fresh', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    (new RolesSeeder)->run();
    config(['session.driver' => 'database']);
});

function crearUsuarioDeLuz(): string
{
    $id = Identificador::generar()->valor();
    DB::connection('propietario')->table('usuarios')->insert([
        'id' => $id,
        'nombre' => 'Luz',
        'correo' => CORREO_DE_LUZ,
        'contrasena_hash' => Hash::make(CLAVE_VIGENTE),
        'activo' => true,
    ]);
    DB::connection('propietario')->table('usuario_rol')->insert([
        'usuario_id' => $id,
        'rol_id' => DB::table('roles')->where('codigo', 'entrenador')->value('id'),
    ]);

    return $id;
}

/**
 * Una petición de un navegador con su propia cookie. Dentro de una prueba el guard y el almacén de sesión
 * sobreviven entre peticiones; en producción cada petición los crea de nuevo.
 *
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function peticionDeSesion(TestCase $prueba, string $metodo, string $ruta, array $cuerpo = [], ?string $idDeSesion = null): TestResponse
{
    Auth::forgetGuards();
    app('session')->forgetDrivers();
    app()->forgetInstance('session.store');

    (fn () => $this->defaultCookies = $idDeSesion === null ? [] : [config('session.cookie') => $idDeSesion])->call($prueba);

    return $prueba->withCredentials()->withHeaders(['Origin' => 'http://localhost'])->json($metodo, '/api/v1'.$ruta, $cuerpo);
}

/** Abre una sesión nueva y devuelve su identificador. */
function abrirSesionDeLuz(TestCase $prueba): string
{
    $antes = DB::table('sessions')->pluck('id')->all();
    peticionDeSesion($prueba, 'POST', '/sesion', ['correo' => CORREO_DE_LUZ, 'contrasena' => CLAVE_VIGENTE])->assertOk();

    return (string) DB::table('sessions')->whereNotNull('user_id')->whereNotIn('id', $antes)->value('id');
}

function hashDeLuz(): string
{
    return (string) DB::table('usuarios')->where('correo', CORREO_DE_LUZ)->value('contrasena_hash');
}

test('HU-27: cerrar sesión invalida la cookie', function () {
    /** @var TestCase $this */
    crearUsuarioDeLuz();
    $idDeSesion = abrirSesionDeLuz($this);

    peticionDeSesion($this, 'DELETE', '/sesion', idDeSesion: $idDeSesion)->assertNoContent();
    expect(DB::table('sessions')->where('id', $idDeSesion)->exists())->toBeFalse();

    peticionDeSesion($this, 'DELETE', '/sesion', idDeSesion: $idDeSesion)
        ->assertUnauthorized()
        ->assertJsonPath('codigo', 'no_autenticado');
});

test('HU-27: cerrar sesión sin sesión responde 401', function () {
    /** @var TestCase $this */
    peticionDeSesion($this, 'DELETE', '/sesion')->assertUnauthorized()->assertJsonPath('codigo', 'no_autenticado');
});

test('HU-27: cambiar la contraseña sin sesión responde 401', function () {
    /** @var TestCase $this */
    peticionDeSesion($this, 'PUT', '/contrasena', ['contrasena_actual' => 'x', 'contrasena_nueva' => 'otra-clave-larga'])
        ->assertUnauthorized()
        ->assertJsonPath('codigo', 'no_autenticado');
});

test('RNF-10: una contraseña nueva de 9 caracteres se rechaza con contrasena_muy_corta', function () {
    /** @var TestCase $this */
    crearUsuarioDeLuz();
    $idDeSesion = abrirSesionDeLuz($this);
    $hashPrevio = hashDeLuz();

    peticionDeSesion($this, 'PUT', '/contrasena', ['contrasena_actual' => CLAVE_VIGENTE, 'contrasena_nueva' => '123456789'], $idDeSesion)
        ->assertStatus(422)
        ->assertJsonPath('codigo', 'contrasena_muy_corta')
        ->assertJsonPath('errores.0.campo', 'contrasena_nueva')
        ->assertJsonPath('errores.0.codigo', 'contrasena_muy_corta')
        ->assertJsonPath('errores.0.mensaje', 'La contraseña debe tener al menos 10 caracteres.');

    expect(hashDeLuz())->toBe($hashPrevio);
});

test('RNF-10: una contraseña nueva de 10 caracteres se acepta', function () {
    /** @var TestCase $this */
    crearUsuarioDeLuz();
    $idDeSesion = abrirSesionDeLuz($this);

    peticionDeSesion($this, 'PUT', '/contrasena', ['contrasena_actual' => CLAVE_VIGENTE, 'contrasena_nueva' => '1234567890'], $idDeSesion)
        ->assertNoContent();

    expect(Hash::check('1234567890', hashDeLuz()))->toBeTrue();
});

test('HU-27: una contraseña actual incorrecta responde 422 sin más detalle y no cambia nada', function () {
    /** @var TestCase $this */
    crearUsuarioDeLuz();
    $idDeSesion = abrirSesionDeLuz($this);
    $hashPrevio = hashDeLuz();

    peticionDeSesion($this, 'PUT', '/contrasena', ['contrasena_actual' => 'incorrecta-1234', 'contrasena_nueva' => 'otra-clave-larga'], $idDeSesion)
        ->assertStatus(422)
        ->assertExactJson([
            'codigo' => 'contrasena_actual_incorrecta',
            'mensaje' => 'La contraseña actual no es correcta.',
            'errores' => [],
        ]);

    expect(hashDeLuz())->toBe($hashPrevio)
        ->and(DB::table('usuarios_historial')->where('operacion', 'actualizar')->exists())->toBeFalse();
});

test('HU-27: un cuerpo sin contraseña actual o sin contraseña nueva responde 422', function (array $cuerpo, string $campo) {
    /** @var TestCase $this */
    crearUsuarioDeLuz();
    $idDeSesion = abrirSesionDeLuz($this);

    peticionDeSesion($this, 'PUT', '/contrasena', $cuerpo, $idDeSesion)
        ->assertStatus(422)
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', $campo);
})->with([
    'sin contraseña actual' => [['contrasena_nueva' => 'otra-clave-larga'], 'contrasena_actual'],
    'sin contraseña nueva' => [['contrasena_actual' => CLAVE_VIGENTE], 'contrasena_nueva'],
]);

test('HU-27: el cambio correcto conserva la sesión actual, cierra la otra y deja historial sin hash', function () {
    /** @var TestCase $this */
    $id = crearUsuarioDeLuz();
    $sesionA = abrirSesionDeLuz($this);
    $sesionB = abrirSesionDeLuz($this);
    $hashPrevio = hashDeLuz();

    peticionDeSesion($this, 'PUT', '/contrasena', ['contrasena_actual' => CLAVE_VIGENTE, 'contrasena_nueva' => 'otra-clave-larga'], $sesionA)
        ->assertNoContent();

    expect(hashDeLuz())->not->toBe($hashPrevio)
        ->and(Hash::check('otra-clave-larga', hashDeLuz()))->toBeTrue()
        ->and(DB::table('sessions')->where('id', $sesionA)->where('user_id', $id)->exists())->toBeTrue()
        ->and(DB::table('sessions')->where('id', $sesionB)->exists())->toBeFalse();

    peticionDeSesion($this, 'DELETE', '/sesion', idDeSesion: $sesionB)->assertUnauthorized();
    peticionDeSesion($this, 'DELETE', '/sesion', idDeSesion: $sesionA)->assertNoContent();

    $filas = DB::table('usuarios_historial')->where('usuario_afectado_id', $id)->where('operacion', 'actualizar')->get();
    expect($filas)->toHaveCount(1);

    $fila = $filas->first();
    expect(json_decode((string) $fila->datos_anteriores, true))->toBeEmpty()
        ->and(json_decode((string) $fila->datos_nuevos, true))->toBe(['contrasena_modificada' => true])
        ->and($fila->usuario_id)->toBe($id)
        ->and(json_encode($fila))->not->toContain('$2y$')
        ->and(json_encode($fila))->not->toContain('argon');
});

test('HU-27: no se tocan las sesiones de otro usuario al cambiar la contraseña', function () {
    /** @var TestCase $this */
    crearUsuarioDeLuz();
    $otro = Identificador::generar()->valor();
    DB::connection('propietario')->table('usuarios')->insert([
        'id' => $otro, 'nombre' => 'Otro', 'correo' => 'otro@x.com', 'contrasena_hash' => Hash::make(CLAVE_VIGENTE), 'activo' => true,
    ]);
    $sesionLuz = abrirSesionDeLuz($this);
    // Fila de otro usuario: dentro de una prueba el guard de la petición anterior se reutilizaría al guardar su sesión.
    DB::table('sessions')->insert(['id' => 'sesion-de-otro', 'user_id' => $otro, 'payload' => '', 'last_activity' => time()]);

    peticionDeSesion($this, 'PUT', '/contrasena', ['contrasena_actual' => CLAVE_VIGENTE, 'contrasena_nueva' => 'otra-clave-larga'], $sesionLuz)
        ->assertNoContent();

    expect(DB::table('sessions')->where('user_id', $otro)->exists())->toBeTrue();
});

test('HU-27: las respuestas de DELETE /sesion y PUT /contrasena cumplen el contrato', function () {
    /** @var TestCase $this */
    crearUsuarioDeLuz();
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $idDeSesion = abrirSesionDeLuz($this);

    $corta = peticionDeSesion($this, 'PUT', '/contrasena', ['contrasena_actual' => CLAVE_VIGENTE, 'contrasena_nueva' => '123'], $idDeSesion);
    $incorrecta = peticionDeSesion($this, 'PUT', '/contrasena', ['contrasena_actual' => 'mala-clave-123', 'contrasena_nueva' => 'otra-clave-larga'], $idDeSesion);
    $vacia = peticionDeSesion($this, 'PUT', '/contrasena', [], $idDeSesion);
    $cambio = peticionDeSesion($this, 'PUT', '/contrasena', ['contrasena_actual' => CLAVE_VIGENTE, 'contrasena_nueva' => 'otra-clave-larga'], $idDeSesion);
    $cierre = peticionDeSesion($this, 'DELETE', '/sesion', idDeSesion: $idDeSesion);
    $sinSesion = peticionDeSesion($this, 'DELETE', '/sesion', idDeSesion: $idDeSesion);
    $sinSesionContrasena = peticionDeSesion($this, 'PUT', '/contrasena', ['contrasena_actual' => 'x', 'contrasena_nueva' => 'otra-clave-larga']);

    foreach ([$corta, $incorrecta, $vacia, $cambio, $sinSesionContrasena] as $respuesta) {
        expect($validador->validate($respuesta->baseResponse, '/contrasena', 'put'))->toBeTrue();
    }
    foreach ([$cierre, $sinSesion] as $respuesta) {
        expect($validador->validate($respuesta->baseResponse, '/sesion', 'delete'))->toBeTrue();
    }
});
