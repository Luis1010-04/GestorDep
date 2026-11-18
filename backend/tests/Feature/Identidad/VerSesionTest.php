<?php

use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Identidad\Aplicacion\VerSesion;
use Src\Identidad\Dominio\RepositorioDeUsuarios;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

const CLAVE_DE_MARTA = 'una-clave-larga-1';
const CORREO_DE_MARTA = 'marta@x.com';

beforeEach(function () {
    esquemaReiniciar();
    (new RolesSeeder)->run();
    config(['session.driver' => 'database']);
});

/** @param  list<string>  $roles */
function crearUsuarioDeMarta(array $roles = ['entrenador', 'docente']): string
{
    $id = Identificador::generar()->valor();
    DB::connection('propietario')->table('usuarios')->insert([
        'id' => $id,
        'nombre' => 'Marta',
        'correo' => CORREO_DE_MARTA,
        'contrasena_hash' => Hash::make(CLAVE_DE_MARTA),
        'activo' => true,
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
 * Una petición de un navegador con su propia cookie. Dentro de una prueba el guard y el almacén de sesión
 * sobreviven entre peticiones; en producción cada petición los crea de nuevo.
 *
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function peticionDeMarta(TestCase $prueba, string $metodo, string $ruta, array $cuerpo = [], ?string $idDeSesion = null): TestResponse
{
    Auth::forgetGuards();
    app('session')->forgetDrivers();
    app()->forgetInstance('session.store');

    (fn () => $this->defaultCookies = $idDeSesion === null ? [] : [config('session.cookie') => $idDeSesion])->call($prueba);

    return $prueba->withCredentials()->withHeaders(['Origin' => 'http://localhost'])->json($metodo, '/api/v1'.$ruta, $cuerpo);
}

/** @return array{0: string, 1: TestResponse<Response>} identificador de la sesión abierta y la respuesta de POST /sesion */
function abrirSesionDeMarta(TestCase $prueba): array
{
    $respuesta = peticionDeMarta($prueba, 'POST', '/sesion', ['correo' => CORREO_DE_MARTA, 'contrasena' => CLAVE_DE_MARTA]);
    $respuesta->assertOk();

    return [(string) DB::table('sessions')->whereNotNull('user_id')->value('id'), $respuesta];
}

test('PT-10: GET /sesion con sesión devuelve la misma forma de datos que POST /sesion', function () {
    /** @var TestCase $this */
    $id = crearUsuarioDeMarta();
    [$idDeSesion, $alIniciar] = abrirSesionDeMarta($this);

    peticionDeMarta($this, 'GET', '/sesion', idDeSesion: $idDeSesion)
        ->assertOk()
        ->assertExactJson(['datos' => ['id' => $id, 'nombre' => 'Marta', 'correo' => CORREO_DE_MARTA, 'roles' => ['docente', 'entrenador']]])
        ->assertExactJson($alIniciar->json());
});

test('PT-10: GET /sesion sin sesión responde 401 no_autenticado', function () {
    /** @var TestCase $this */
    peticionDeMarta($this, 'GET', '/sesion')
        ->assertUnauthorized()
        ->assertJsonPath('codigo', 'no_autenticado');
});

test('PT-10: GET /sesion tras cerrar la sesión responde 401 no_autenticado', function () {
    /** @var TestCase $this */
    crearUsuarioDeMarta();
    [$idDeSesion] = abrirSesionDeMarta($this);
    peticionDeMarta($this, 'DELETE', '/sesion', idDeSesion: $idDeSesion)->assertNoContent();

    peticionDeMarta($this, 'GET', '/sesion', idDeSesion: $idDeSesion)
        ->assertUnauthorized()
        ->assertJsonPath('codigo', 'no_autenticado');
});

test('PT-10: un usuario con borrado lógico y sesión viva recibe 401 no_autenticado', function () {
    /** @var TestCase $this */
    $id = crearUsuarioDeMarta();
    [$idDeSesion] = abrirSesionDeMarta($this);
    DB::connection('propietario')->table('usuarios')->where('id', $id)->update(['eliminado_en' => now()]);

    peticionDeMarta($this, 'GET', '/sesion', idDeSesion: $idDeSesion)
        ->assertUnauthorized()
        ->assertJsonPath('codigo', 'no_autenticado');
});

test('PT-10: VerSesion no devuelve usuario sin identificador ni con borrado lógico', function () {
    /** @var TestCase $this */
    $id = crearUsuarioDeMarta();
    $actualDe = fn (?string $usuarioId): UsuarioActual => new class($usuarioId) implements UsuarioActual
    {
        public function __construct(private readonly ?string $usuarioId) {}

        public function identificador(): ?Identificador
        {
            return $this->usuarioId === null ? null : Identificador::desdeTexto($this->usuarioId);
        }

        public function roles(): array
        {
            return [];
        }
    };

    expect(new VerSesion($actualDe(null), app(RepositorioDeUsuarios::class))->ejecutar())->toBeNull()
        ->and(new VerSesion($actualDe($id), app(RepositorioDeUsuarios::class))->ejecutar()?->correo())->toBe(CORREO_DE_MARTA);

    DB::connection('propietario')->table('usuarios')->where('id', $id)->update(['eliminado_en' => now()]);

    expect(new VerSesion($actualDe($id), app(RepositorioDeUsuarios::class))->ejecutar())->toBeNull();
});

test('PT-10: las respuestas 200 y 401 de GET /sesion cumplen el contrato', function () {
    /** @var TestCase $this */
    crearUsuarioDeMarta();
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $sinSesion = peticionDeMarta($this, 'GET', '/sesion');
    [$idDeSesion] = abrirSesionDeMarta($this);
    $conSesion = peticionDeMarta($this, 'GET', '/sesion', idDeSesion: $idDeSesion);

    foreach ([$conSesion, $sinSesion] as $respuesta) {
        expect($validador->validate($respuesta->baseResponse, '/sesion', 'get'))->toBeTrue();
    }
});
