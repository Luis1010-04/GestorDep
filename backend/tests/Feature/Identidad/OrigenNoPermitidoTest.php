<?php

use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

const CLAVE_DE_ELENA = 'una-clave-larga-1';
const CORREO_DE_ELENA = 'elena@x.com';
const MENSAJE_DE_ORIGEN = 'La solicitud no proviene de un origen permitido para iniciar sesión.';

beforeEach(function () {
    esquemaReiniciar();
    (new RolesSeeder)->run();
    config(['session.driver' => 'database']);
});

function crearUsuarioDeElena(): string
{
    $id = Identificador::generar()->valor();
    DB::connection('propietario')->table('usuarios')->insert([
        'id' => $id,
        'nombre' => 'Elena',
        'correo' => CORREO_DE_ELENA,
        'contrasena_hash' => Hash::make(CLAVE_DE_ELENA),
        'activo' => true,
    ]);
    DB::connection('propietario')->table('usuario_rol')->insert([
        'usuario_id' => $id,
        'rol_id' => DB::table('roles')->where('codigo', 'entrenador')->value('id'),
    ]);

    return $id;
}

/**
 * POST /sesion con las cabeceras de origen que se indiquen (ninguna por omisión).
 *
 * @param  array<string, string>  $cabeceras
 * @param  array<string, mixed>|null  $cuerpo  por omisión, las credenciales correctas de Elena
 * @return TestResponse<Response>
 */
function iniciarSesionDeElena(TestCase $prueba, array $cabeceras = [], ?array $cuerpo = null): TestResponse
{
    return $prueba->withHeaders($cabeceras)->postJson(
        '/api/v1/sesion',
        $cuerpo ?? ['correo' => CORREO_DE_ELENA, 'contrasena' => CLAVE_DE_ELENA],
    );
}

test('PT-11: POST /sesion sin Origin ni Referer responde 403 origen_no_permitido con el formato de error', function () {
    /** @var TestCase $this */
    crearUsuarioDeElena();

    iniciarSesionDeElena($this)
        ->assertForbidden()
        ->assertExactJson(['codigo' => 'origen_no_permitido', 'mensaje' => MENSAJE_DE_ORIGEN, 'errores' => []]);
});

test('PT-11: POST /sesion con un Origin que no es de un dominio con estado responde 403 origen_no_permitido', function () {
    /** @var TestCase $this */
    crearUsuarioDeElena();

    iniciarSesionDeElena($this, ['Origin' => 'http://otro-sitio.test'])
        ->assertForbidden()
        ->assertJsonPath('codigo', 'origen_no_permitido');
});

test('PT-11: el 403 de origen no cuenta un intento fallido ni consulta credenciales', function () {
    /** @var TestCase $this */
    crearUsuarioDeElena();

    iniciarSesionDeElena($this, cuerpo: ['correo' => CORREO_DE_ELENA, 'contrasena' => 'incorrecta-1234'])
        ->assertForbidden();

    expect((int) DB::table('usuarios')->where('correo', CORREO_DE_ELENA)->value('intentos_fallidos'))->toBe(0)
        ->and(DB::table('sessions')->count())->toBe(0);
});

test('PT-11: sin Origin ni Referer y con cuerpo inválido responde 422 validacion_fallida', function () {
    /** @var TestCase $this */
    iniciarSesionDeElena($this, cuerpo: [])
        ->assertStatus(422)
        ->assertJsonPath('codigo', 'validacion_fallida');
});

test('PT-11: POST /sesion con Referer permitido y sin Origin sigue respondiendo 200', function () {
    /** @var TestCase $this */
    $id = crearUsuarioDeElena();

    iniciarSesionDeElena($this, ['Referer' => 'http://localhost/ingreso'])
        ->assertOk()
        ->assertJsonPath('datos.id', $id);
});

test('PT-11: POST /sesion con un Origin permitido no cambia su respuesta 200', function () {
    /** @var TestCase $this */
    $id = crearUsuarioDeElena();

    iniciarSesionDeElena($this, ['Origin' => 'http://localhost'])
        ->assertOk()
        ->assertExactJson(['datos' => ['id' => $id, 'nombre' => 'Elena', 'correo' => CORREO_DE_ELENA, 'roles' => ['entrenador']]]);
});

test('PT-11: el 403 de POST /sesion cumple el contrato de OpenAPI', function () {
    /** @var TestCase $this */
    crearUsuarioDeElena();
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();

    $respuesta = iniciarSesionDeElena($this);

    expect($validador->validate($respuesta->baseResponse, '/sesion', 'post'))->toBeTrue();
});
