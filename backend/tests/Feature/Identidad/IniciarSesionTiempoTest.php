<?php

use Database\Seeders\RolesSeeder;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

const CORREO_TIEMPO = 'tiempo@x.com';
const CLAVE_TIEMPO = 'una-clave-larga-1';

/** Espía del Hasher: delega en el hasher real y registra cada llamada a make y a check. */
final class HasherEspia implements Hasher
{
    /** @var list<string> */
    public array $contrasenasHasheadas = [];

    /** @var list<array{string, string}> */
    public array $comparaciones = [];

    public function __construct(private readonly Hasher $real) {}

    /** @return array<string, mixed> */
    public function info($hashedValue): array
    {
        return $this->real->info($hashedValue);
    }

    /** @param  array<string, mixed>  $options */
    public function make($value, array $options = []): string
    {
        $this->contrasenasHasheadas[] = (string) $value;

        return $this->real->make($value, $options);
    }

    /** @param  array<string, mixed>  $options */
    public function check($value, $hashedValue, array $options = []): bool
    {
        $this->comparaciones[] = [(string) $value, (string) $hashedValue];

        return $this->real->check($value, $hashedValue, $options);
    }

    /** @param  array<string, mixed>  $options */
    public function needsRehash($hashedValue, array $options = []): bool
    {
        return $this->real->needsRehash($hashedValue, $options);
    }
}

beforeEach(function () {
    esquemaReiniciar();
    (new RolesSeeder)->run();
    config(['session.driver' => 'database']);
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-03-15 10:00:00', new DateTimeZone('UTC'))));
});

function hashDeUsuarioDeTiempo(): string
{
    return (string) DB::table('usuarios')->where('correo', CORREO_TIEMPO)->value('contrasena_hash');
}

function usuarioDeTiempo(bool $activo = true, ?string $bloqueadoHasta = null): void
{
    DB::connection('propietario')->table('usuarios')->insert([
        'id' => Identificador::generar()->valor(),
        'nombre' => 'Tiempo',
        'correo' => CORREO_TIEMPO,
        'contrasena_hash' => Hash::make(CLAVE_TIEMPO),
        'activo' => $activo,
        'bloqueado_hasta' => $bloqueadoHasta,
    ]);
}

function espiarHasher(): HasherEspia
{
    $espia = new HasherEspia(app(Hasher::class));
    app()->instance(Hasher::class, $espia);

    return $espia;
}

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function ingresarParaTiempo(TestCase $prueba, array $cuerpo)
{
    return $prueba->withCredentials()->withHeaders(['Origin' => 'http://localhost'])->postJson('/api/v1/sesion', $cuerpo);
}

test('PT-14: un correo inexistente genera un hash de la contraseña recibida y no compara', function () {
    /** @var TestCase $this */
    usuarioDeTiempo();
    $espia = espiarHasher();

    ingresarParaTiempo($this, ['correo' => 'nadie@x.com', 'contrasena' => 'incorrecta-1234'])
        ->assertUnauthorized()
        ->assertJsonPath('codigo', 'credenciales_invalidas');

    expect($espia->contrasenasHasheadas)->toBe(['incorrecta-1234'])
        ->and($espia->comparaciones)->toBe([]);
});

test('PT-14: un usuario desactivado genera un hash de la contraseña recibida y no compara', function () {
    /** @var TestCase $this */
    usuarioDeTiempo(activo: false);
    $espia = espiarHasher();

    ingresarParaTiempo($this, ['correo' => CORREO_TIEMPO, 'contrasena' => CLAVE_TIEMPO])
        ->assertUnauthorized()
        ->assertJsonPath('codigo', 'credenciales_invalidas');

    expect($espia->contrasenasHasheadas)->toBe([CLAVE_TIEMPO])
        ->and($espia->comparaciones)->toBe([]);
});

test('PT-14: una contraseña incorrecta compara una vez con el hash del usuario y no genera otro', function () {
    /** @var TestCase $this */
    usuarioDeTiempo();
    $espia = espiarHasher();

    ingresarParaTiempo($this, ['correo' => CORREO_TIEMPO, 'contrasena' => 'incorrecta-1234'])->assertUnauthorized();

    expect($espia->comparaciones)->toBe([['incorrecta-1234', hashDeUsuarioDeTiempo()]])
        ->and($espia->contrasenasHasheadas)->toBe([]);
});

test('PT-14: la respuesta de un correo inexistente es idéntica a la de una contraseña incorrecta', function () {
    /** @var TestCase $this */
    usuarioDeTiempo();

    $conocido = ingresarParaTiempo($this, ['correo' => CORREO_TIEMPO, 'contrasena' => 'incorrecta-1234']);
    $inexistente = ingresarParaTiempo($this, ['correo' => 'nadie@x.com', 'contrasena' => 'incorrecta-1234']);

    expect($inexistente->getStatusCode())->toBe($conocido->getStatusCode())
        ->and((string) $inexistente->getContent())->toBe((string) $conocido->getContent());
});

test('PT-14: una cuenta bloqueada responde 429 sin generar hash ni comparar (riesgo aceptado, DD-29)', function () {
    /** @var TestCase $this */
    usuarioDeTiempo(bloqueadoHasta: '2026-03-15 10:10:00');
    $espia = espiarHasher();

    ingresarParaTiempo($this, ['correo' => CORREO_TIEMPO, 'contrasena' => CLAVE_TIEMPO])->assertStatus(429);

    expect($espia->contrasenasHasheadas)->toBe([])
        ->and($espia->comparaciones)->toBe([]);
});
