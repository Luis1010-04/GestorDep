<?php

use Illuminate\Support\Facades\File;
use Src\Compartido\Dominio\Identificador;
use Src\Identidad\Infraestructura\Modelos\UsuarioModelo;
use Tests\TestCase;

function usuarioDelPlantel(TestCase $prueba): void
{
    $usuario = new UsuarioModelo;
    $usuario->id = Identificador::generar()->valor();
    $prueba->actingAs($usuario, 'web');
}

/**
 * Páginas de FRONT-R6, con identificadores de ejemplo donde la ruta los lleva.
 *
 * @return list<string>
 */
function paginasDelPlantel(): array
{
    return [
        '/plantel/categorias',
        '/plantel/equipos',
        '/plantel/equipos/'.Identificador::generar()->valor(),
        '/jugadores/crear',
        '/jugadores/'.Identificador::generar()->valor().'/editar',
    ];
}

/**
 * Controles de un formulario, en orden, como [name, tipo]; el tipo es el de input, o select o textarea.
 *
 * @return list<array{0: string, 1: string}>
 */
function controlesDelFormulario(string $html, string $id): array
{
    preg_match('/<form\b[^>]*id="'.preg_quote($id, '/').'".*?<\/form>/s', $html, $formulario);
    preg_match_all('/<(input|select|textarea)\b([^>]*)>/', $formulario[0] ?? '', $controles, PREG_SET_ORDER);

    return array_map(function (array $control): array {
        preg_match('/\bname="([^"]*)"/', $control[2], $nombre);
        preg_match('/\btype="([^"]*)"/', $control[2], $tipo);

        return [$nombre[1] ?? '', $control[1] === 'input' ? ($tipo[1] ?? 'text') : $control[1]];
    }, $controles);
}

test('FRONT-R6 1: las páginas del plantel sin sesión redirigen a /ingreso sin contenido privado (CA-GEN-005)', function () {
    /** @var TestCase $this */
    $prueba = $this;

    foreach (paginasDelPlantel() as $pagina) {
        $respuesta = $prueba->get($pagina)->assertRedirect('/ingreso');

        expect(str_contains((string) $respuesta->getContent(), 'data-endpoint'))->toBeFalse($pagina);
    }
});

test('FRONT-R6 2: con sesión responden 200 con el layout; las que llevan identificador lo pasan a la página y uno que no es UUID responde 404', function () {
    /** @var TestCase $this */
    usuarioDelPlantel($this);
    $equipo = Identificador::generar()->valor();
    $jugador = Identificador::generar()->valor();

    foreach (paginasDelPlantel() as $pagina) {
        $this->get($pagina)->assertOk()->assertSee('id="header"', false)->assertSee('id="sidebar"', false)->assertSee('id="footer"', false);
    }

    $this->get('/plantel/equipos/'.$equipo)->assertSee('data-equipo="'.$equipo.'"', false);
    $this->get('/jugadores/'.$jugador.'/editar')->assertSee('data-jugador="'.$jugador.'"', false);
    $this->get('/plantel/equipos/no-es-uuid')->assertNotFound();
    $this->get('/jugadores/no-es-uuid/editar')->assertNotFound();
});

test('FRONT-R6 3: el menú lateral agrupa «Plantel» con Categorías y Equipos solo para el entrenador, ocultos hasta conocer la sesión, y marca la sección activa (CA-LAY-002, CA-GEN-005)', function () {
    /** @var TestCase $this */
    usuarioDelPlantel($this);
    $html = (string) $this->get('/plantel/categorias')->getContent();
    preg_match('/<aside id="sidebar".*?<\/aside>/s', $html, $menu);

    expect($menu[0] ?? '')
        ->toMatch('/<li class="nav-heading" data-roles="entrenador" hidden>'.preg_quote(__('interfaz.menu.plantel'), '/').'<\/li>/')
        ->toMatch('/<li class="nav-item" data-roles="entrenador" hidden>\s*<a class="nav-link" href="\/plantel\/categorias" aria-current="page">/')
        ->toMatch('/<li class="nav-item" data-roles="entrenador" hidden>\s*<a class="nav-link collapsed" href="\/plantel\/equipos">/');

    $equipo = (string) $this->get('/plantel/equipos/'.Identificador::generar()->valor())->getContent();
    expect($equipo)->toMatch('/<a class="nav-link" href="\/plantel\/equipos" aria-current="page">/');
});

test('FRONT-R6 4: cada formulario del plantel tiene exactamente los campos de su endpoint, con el tipo de control adecuado (CA-FOR-001)', function () {
    /** @var TestCase $this */
    usuarioDelPlantel($this);
    $paginas = [
        'categorias' => (string) $this->get('/plantel/categorias')->getContent(),
        'equipos' => (string) $this->get('/plantel/equipos')->getContent(),
        'equipo' => (string) $this->get('/plantel/equipos/'.Identificador::generar()->valor())->getContent(),
        'crear' => (string) $this->get('/jugadores/crear')->getContent(),
        'editar' => (string) $this->get('/jugadores/'.Identificador::generar()->valor().'/editar')->getContent(),
        'ficha' => (string) $this->get('/jugadores/'.Identificador::generar()->valor())->getContent(),
    ];

    expect(controlesDelFormulario($paginas['categorias'], 'formulario-categoria'))->toBe([['nombre', 'text']])
        ->and(controlesDelFormulario($paginas['equipos'], 'formulario-equipo'))->toBe([['nombre', 'text'], ['categoria_id', 'select']])
        ->and(controlesDelFormulario($paginas['equipo'], 'formulario-asignar'))->toBe([['matricula', 'text'], ['posicion', 'select']])
        ->and(controlesDelFormulario($paginas['equipo'], 'formulario-posicion'))->toBe([['posicion', 'select']])
        ->and(controlesDelFormulario($paginas['crear'], 'formulario-jugador'))->toBe([
            ['nombres', 'text'], ['apellidos', 'text'], ['fecha_nacimiento', 'date'], ['matricula', 'text'], ['equipo_id', 'select'], ['posicion', 'select'],
        ])
        ->and(controlesDelFormulario($paginas['editar'], 'formulario-edicion'))->toBe([['nombres', 'text'], ['apellidos', 'text'], ['fecha_nacimiento', 'date']])
        ->and(controlesDelFormulario($paginas['ficha'], 'formulario-baja'))->toBe([['motivo', 'textarea'], ['fecha', 'date']]);
});

test('FRONT-R6 5: las tablas del plantel solo ordenan por los campos que admite su endpoint y su columna de acciones no se exporta (CA-TAB-003, F4)', function () {
    /** @var TestCase $this */
    usuarioDelPlantel($this);
    $casos = [
        ['/plantel/categorias', 'tabla-categorias', ['nombre']],
        ['/plantel/equipos', 'tabla-equipos', ['nombre']],
        ['/plantel/equipos/'.Identificador::generar()->valor(), 'tabla-jugadores-equipo', ['apellidos', 'nombres']],
    ];

    foreach ($casos as [$pagina, $id, $ordenes]) {
        preg_match('/<table id="'.$id.'".*?<\/table>/s', (string) $this->get($pagina)->getContent(), $tabla);
        preg_match_all('/data-orden="([^"]+)"/', $tabla[0] ?? '', $encontradas);

        expect($encontradas[1])->toBe($ordenes, $id)
            ->and($tabla[0] ?? '')->toContain('<th class="no-exportar"');
    }
});

test('FRONT-R6 6: los textos del plantel salen de lang/es/interfaz.php', function () {
    /** @var TestCase $this */
    usuarioDelPlantel($this);

    $this->get('/plantel/categorias')->assertOk()->assertSee(__('interfaz.categorias.titulo'))->assertSee(__('interfaz.categorias.nueva'));
    $this->get('/plantel/equipos')->assertOk()->assertSee(__('interfaz.equipos.titulo'))->assertSee(__('interfaz.equipos.nuevo'));
    $this->get('/plantel/equipos/'.Identificador::generar()->valor())->assertOk()
        ->assertSee(__('interfaz.equipos.asignar_titulo'))->assertSee(__('interfaz.equipos.registrar_jugador'));
    $this->get('/jugadores/crear')->assertOk()->assertSee(__('interfaz.jugadores.crear_titulo'));
    $this->get('/jugadores/'.Identificador::generar()->valor().'/editar')->assertOk()->assertSee(__('interfaz.jugadores.editar_titulo'));
    $this->get('/jugadores/'.Identificador::generar()->valor())->assertOk()
        ->assertSee(__('interfaz.jugadores.editar_datos'))->assertSee(__('interfaz.jugadores.registrar_baja'));

    $claves = [
        'interfaz.js.plantel.categoria_creada', 'interfaz.js.plantel.categoria_eliminada', 'interfaz.js.plantel.eliminar_categoria_titulo',
        'interfaz.js.plantel.eliminar_categoria_texto', 'interfaz.js.plantel.equipo_creado', 'interfaz.js.plantel.jugador_asignado',
        'interfaz.js.plantel.posicion_cambiada', 'interfaz.js.plantel.jugador_registrado', 'interfaz.js.plantel.jugador_actualizado',
        'interfaz.js.plantel.baja_titulo', 'interfaz.js.plantel.baja_texto', 'interfaz.js.plantel.baja_registrada',
        'interfaz.js.plantel.equipo_no_disponible',
    ];
    foreach ($claves as $clave) {
        expect(__($clave))->not->toBe($clave);
    }
});

test('FRONT-R6 7: cada página del plantel carga SweetAlert2, avisos, formularios y su script de módulo; las que tienen tabla cargan además DataTables y tablas.css (DAF-07)', function () {
    /** @var TestCase $this */
    usuarioDelPlantel($this);
    $casos = [
        ['/plantel/categorias', '/assets/js/modulos/categorias/index.js', true],
        ['/plantel/equipos', '/assets/js/modulos/equipos/index.js', true],
        ['/plantel/equipos/'.Identificador::generar()->valor(), '/assets/js/modulos/equipos/ver.js', true],
        ['/jugadores/crear', '/assets/js/modulos/jugadores/crear.js', false],
        ['/jugadores/'.Identificador::generar()->valor().'/editar', '/assets/js/modulos/jugadores/editar.js', false],
    ];

    foreach ($casos as [$pagina, $script, $conTabla]) {
        $html = (string) $this->get($pagina)->getContent();
        $orden = array_map(
            fn (string $archivo) => strpos($html, '<script src="'.$archivo.'"></script>'),
            ['/assets/js/sesion.js', '/assets/vendor/sweetalert2/sweetalert2.min.js', '/assets/js/comun/avisos.js', '/assets/js/comun/formularios.js', $script],
        );

        expect($orden)->not->toContain(false)
            ->and($orden)->toBe(collect($orden)->sort()->values()->all())
            ->and(str_contains($html, '/assets/vendor/datatables/js/dataTables.min.js'))->toBe($conTabla, $pagina)
            ->and(str_contains($html, '/assets/css/comun/tablas.css'))->toBe($conTabla, $pagina);
    }
});

test('FRONT-R6 8: eliminar una categoría y registrar una baja piden confirmación de peligro antes de llamar a la API (CA-FOR-003)', function () {
    $categorias = File::get(public_path('assets/js/modulos/categorias/index.js'));
    $ficha = File::get(public_path('assets/js/modulos/jugadores/ver.js'));

    expect($categorias)->toMatch("/window\.Avisos\.confirmar\(\{[^}]*peligro: true[^}]*\}\)\.then\(function \(confirmado\) \{\s*if \(!confirmado\) \{\s*return;\s*\}[\s\S]*?'DELETE', '\/categorias\/'/")
        ->and($ficha)->toMatch("/window\.Avisos\.confirmar\(\{[^}]*peligro: true[^}]*\}\)\.then\(function \(confirmado\) \{\s*if \(!confirmado\) \{\s*return;\s*\}[\s\S]*?'POST', ruta \+ '\/baja'/");
});

test('FRONT-R6 9: en la ficha, editar datos y registrar la baja solo se muestran al entrenador (CA-LAY-002)', function () {
    /** @var TestCase $this */
    usuarioDelPlantel($this);
    $id = Identificador::generar()->valor();
    $html = (string) $this->get('/jugadores/'.$id)->getContent();

    expect($html)->toMatch('/<div id="acciones-del-entrenador"[^>]*data-roles="entrenador" hidden>/')
        ->and($html)->toContain('<a id="enlace-editar" class="btn btn-outline-primary btn-sm" href="/jugadores/'.$id.'/editar">')
        ->and($html)->toMatch('/<form id="formulario-baja"[^>]*data-endpoint="POST \/jugadores\/\{jugador\}\/baja"[^>]*hidden/');
});
