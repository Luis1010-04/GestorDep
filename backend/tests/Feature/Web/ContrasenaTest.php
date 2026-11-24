<?php

use Illuminate\Support\Facades\Auth;
use Src\Compartido\Dominio\Identificador;
use Src\Identidad\Infraestructura\Modelos\UsuarioModelo;
use Tests\TestCase;

function usuarioDeLaCuenta(TestCase $prueba): void
{
    $usuario = new UsuarioModelo;
    $usuario->id = Identificador::generar()->valor();
    $prueba->actingAs($usuario, 'web');
}

/**
 * Atributos de cada control del formulario de cambio de contraseña, en orden.
 *
 * @return list<array<string, string>>
 */
function controlesDelFormularioDeContrasena(string $html): array
{
    preg_match('/<form\b[^>]*id="formulario-contrasena".*?<\/form>/s', $html, $formulario);
    preg_match_all('/<(?:input|select|textarea)\b[^>]*>/', $formulario[0] ?? '', $controles);

    return array_map(function (string $control): array {
        preg_match_all('/([a-z-]+)="([^"]*)"/', $control, $atributos);

        return array_combine($atributos[1], $atributos[2]);
    }, $controles[0]);
}

test('FRONT-R2 1: GET /contrasena sin sesión redirige a /ingreso y no envía contenido privado (CA-GEN-005)', function () {
    /** @var TestCase $this */
    $respuesta = $this->get('/contrasena')->assertRedirect('/ingreso');

    expect((string) $respuesta->getContent())->not->toContain('id="formulario-contrasena"')
        ->and((string) $respuesta->getContent())->not->toContain('id="header"');
});

test('FRONT-R2 2: GET /contrasena con sesión responde 200 con el layout y sus tres parciales (CA-LAY-001)', function () {
    /** @var TestCase $this */
    usuarioDeLaCuenta($this);

    $this->get('/contrasena')
        ->assertOk()
        ->assertSee('id="header"', false)
        ->assertSee('id="sidebar"', false)
        ->assertSee('id="footer"', false)
        ->assertSee('id="formulario-contrasena"', false);
});

test('FRONT-R2 3: el formulario envía a PUT /contrasena solo contrasena_actual y contrasena_nueva, de tipo password (CA-FOR-001)', function () {
    /** @var TestCase $this */
    usuarioDeLaCuenta($this);
    $html = (string) $this->get('/contrasena')->getContent();

    expect($html)->toMatch('/<form\b[^>]*id="formulario-contrasena"[^>]*data-endpoint="PUT \/contrasena"/');

    $controles = array_map(
        fn (array $control) => [$control['name'] ?? '', $control['type'] ?? '', $control['autocomplete'] ?? ''],
        controlesDelFormularioDeContrasena($html),
    );

    expect($controles)->toBe([
        ['contrasena_actual', 'password', 'current-password'],
        ['contrasena_nueva', 'password', 'new-password'],
    ]);
});

test('FRONT-R2 4: los textos de la página salen de lang/es/interfaz.php', function () {
    /** @var TestCase $this */
    usuarioDeLaCuenta($this);

    $this->get('/contrasena')
        ->assertOk()
        ->assertSee(__('interfaz.contrasena.titulo'))
        ->assertSee(__('interfaz.contrasena.actual'))
        ->assertSee(__('interfaz.contrasena.nueva'))
        ->assertSee(__('interfaz.contrasena.guardar'))
        ->assertSee(__('interfaz.contrasena.nota'));

    foreach (['titulo', 'actual', 'nueva', 'guardar', 'nota'] as $clave) {
        expect(__('interfaz.contrasena.'.$clave))->not->toBe('interfaz.contrasena.'.$clave);
    }
    foreach (['confirmar_titulo', 'confirmar_texto', 'exito'] as $clave) {
        expect(__('interfaz.js.contrasena.'.$clave))->not->toBe('interfaz.js.contrasena.'.$clave);
    }
});

test('FRONT-R2 5: la cabecera de las páginas con sesión enlaza a /contrasena (CA-GEN-005)', function () {
    /** @var TestCase $this */
    usuarioDeLaCuenta($this);

    foreach (['/panel', '/contrasena'] as $pagina) {
        $html = (string) $this->get($pagina)->getContent();
        preg_match('/<header id="header".*?<\/header>/s', $html, $cabecera);

        expect($cabecera[0] ?? '')->toMatch('/<a\b[^>]*href="\/contrasena"[^>]*>.*?'.preg_quote(__('interfaz.menu.cambiar_contrasena'), '/').'/s');
    }

    Auth::forgetGuards();
    expect((string) $this->get('/ingreso')->getContent())->not->toContain('href="/contrasena"');
});

test('FRONT-R2 6: la página carga SweetAlert2 y sus scripts después de sesion.js, y el panel no carga SweetAlert2 (DAF-07)', function () {
    /** @var TestCase $this */
    usuarioDeLaCuenta($this);
    $html = (string) $this->get('/contrasena')->getContent();

    expect($html)->toContain('<link href="/assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">');

    $posiciones = array_map(
        fn (string $script) => strpos($html, '<script src="'.$script.'"></script>'),
        [
            '/assets/js/sesion.js',
            '/assets/vendor/sweetalert2/sweetalert2.min.js',
            '/assets/js/comun/avisos.js',
            '/assets/js/comun/formularios.js',
            '/assets/js/modulos/contrasena/editar.js',
        ],
    );

    expect($posiciones)->not->toContain(false)
        ->and($posiciones)->toBe(collect($posiciones)->sort()->values()->all())
        ->and((string) $this->get('/panel')->getContent())->not->toContain('sweetalert2');
});
