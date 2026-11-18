<?php

use Src\Compartido\Dominio\Identificador;
use Src\Identidad\Infraestructura\Modelos\UsuarioModelo;
use Tests\TestCase;

function usuarioDeIngreso(TestCase $prueba): void
{
    $usuario = new UsuarioModelo;
    $usuario->id = Identificador::generar()->valor();
    $prueba->actingAs($usuario, 'web');
}

test('PT-12 1: GET /ingreso sin sesión responde 200 con el formulario de ingreso con solo correo y contrasena', function () {
    /** @var TestCase $this */
    $respuesta = $this->get('/ingreso')->assertOk();

    preg_match_all('/<input\b[^>]*\bname="([^"]+)"/i', (string) $respuesta->getContent(), $campos);

    expect($campos[1])->toBe(['correo', 'contrasena']);
});

test('PT-12 2: GET /ingreso con sesión redirige a /panel', function () {
    /** @var TestCase $this */
    usuarioDeIngreso($this);

    $this->get('/ingreso')->assertRedirect('/panel');
});

test('PT-12 3: GET / redirige a /panel', function () {
    /** @var TestCase $this */
    $this->get('/')->assertRedirect('/panel');
});
