<?php

use Illuminate\Support\Facades\File;
use Src\Compartido\Dominio\Identificador;
use Src\Identidad\Infraestructura\Modelos\UsuarioModelo;
use Tests\TestCase;

function usuarioDeJugadores(TestCase $prueba): void
{
    $usuario = new UsuarioModelo;
    $usuario->id = Identificador::generar()->valor();
    $prueba->actingAs($usuario, 'web');
}

/** Bibliotecas y scripts comunes que cargan las dos páginas de jugadores, en orden. */
const SCRIPTS_DE_TABLAS = [
    '/assets/js/sesion.js',
    '/assets/vendor/sweetalert2/sweetalert2.min.js',
    '/assets/js/comun/avisos.js',
    '/assets/vendor/datatables/js/dataTables.min.js',
    '/assets/vendor/datatables/js/dataTables.bootstrap5.min.js',
    '/assets/vendor/datatables/js/dataTables.buttons.min.js',
    '/assets/vendor/datatables/js/buttons.bootstrap5.min.js',
    '/assets/vendor/jszip/jszip.min.js',
    '/assets/vendor/pdfmake/pdfmake.min.js',
    '/assets/vendor/pdfmake/vfs_fonts.js',
    '/assets/js/comun/tabla.js',
];

/** @param list<string> $scripts */
function cargaEnOrden(string $html, array $scripts): bool
{
    $posiciones = array_map(fn (string $script) => strpos($html, '<script src="'.$script.'"></script>'), $scripts);

    return ! in_array(false, $posiciones, true) && $posiciones === collect($posiciones)->sort()->values()->all();
}

test('FRONT-R3 1: /jugadores y la ficha sin sesión redirigen a /ingreso sin contenido privado (CA-GEN-005)', function () {
    /** @var TestCase $this */
    $prueba = $this;

    foreach (['/jugadores', '/jugadores/'.Identificador::generar()->valor()] as $pagina) {
        $respuesta = $prueba->get($pagina)->assertRedirect('/ingreso');

        expect((string) $respuesta->getContent())->not->toContain('id="header"')
            ->and((string) $respuesta->getContent())->not->toContain('data-endpoint');
    }
});

test('FRONT-R3 2: /jugadores con sesión responde 200 con el layout, la búsqueda por matrícula y la tabla (CA-LAY-001, CA-TAB-001)', function () {
    /** @var TestCase $this */
    usuarioDeJugadores($this);

    $this->get('/jugadores')
        ->assertOk()
        ->assertSee('id="header"', false)
        ->assertSee('id="sidebar"', false)
        ->assertSee('id="footer"', false)
        ->assertSee('id="formulario-busqueda"', false)
        ->assertSee('id="tabla-jugadores"', false);
});

test('FRONT-R3 3: la ficha con sesión responde 200 y lleva el identificador de la ruta; uno que no es UUID responde 404', function () {
    /** @var TestCase $this */
    usuarioDeJugadores($this);
    $id = Identificador::generar()->valor();

    $this->get('/jugadores/'.$id)
        ->assertOk()
        ->assertSee('data-jugador="'.$id.'"', false)
        ->assertSee('data-zona="'.config('app.zona_presentacion').'"', false);

    $this->get('/jugadores/no-es-uuid')->assertNotFound();
});

test('FRONT-R3 4: el menú lateral muestra «Jugadores» a docente y entrenador, oculto hasta conocer la sesión, y lo marca activo en sus páginas (CA-LAY-002, CA-GEN-005)', function () {
    /** @var TestCase $this */
    usuarioDeJugadores($this);

    foreach (['/jugadores', '/jugadores/'.Identificador::generar()->valor()] as $pagina) {
        $html = (string) $this->get($pagina)->getContent();
        preg_match('/<aside id="sidebar".*?<\/aside>/s', $html, $menu);

        expect($menu[0] ?? '')->toMatch('/<li class="nav-item" data-roles="docente entrenador" hidden>\s*<a class="nav-link" href="\/jugadores" aria-current="page">/');
    }

    $panel = (string) $this->get('/panel')->getContent();
    expect($panel)->toMatch('/<a class="nav-link collapsed" href="\/jugadores">/');
});

test('FRONT-R3 5: la tabla de jugadores solo ordena por apellidos y nombres, y la columna de acciones no se exporta (CA-TAB-003, F4)', function () {
    /** @var TestCase $this */
    usuarioDeJugadores($this);
    $html = (string) $this->get('/jugadores')->getContent();
    preg_match('/<table id="tabla-jugadores".*?<\/table>/s', $html, $tabla);
    preg_match_all('/data-orden="([^"]+)"/', $tabla[0] ?? '', $ordenes);

    expect($ordenes[1])->toBe(['apellidos', 'nombres'])
        ->and($tabla[0] ?? '')->toContain('<th class="no-exportar"');
});

test('FRONT-R3 6: los textos de las páginas de jugadores y de las tablas salen de lang/es/interfaz.php', function () {
    /** @var TestCase $this */
    usuarioDeJugadores($this);

    $this->get('/jugadores')->assertOk()
        ->assertSee(__('interfaz.jugadores.titulo'))
        ->assertSee(__('interfaz.jugadores.buscar_titulo'))
        ->assertSee(__('interfaz.jugadores.listado_titulo'));
    $this->get('/jugadores/'.Identificador::generar()->valor())->assertOk()
        ->assertSee(__('interfaz.jugadores.ficha_titulo'))
        ->assertSee(__('interfaz.jugadores.pestanas.datos'))
        ->assertSee(__('interfaz.jugadores.pestanas.academica'))
        ->assertSee(__('interfaz.jugadores.pestanas.asistencia'));

    $claves = [
        'interfaz.js.tabla.descargar_pagina', 'interfaz.js.tabla.descargar_todo', 'interfaz.js.tabla.todo_confirmar',
        'interfaz.js.tabla.todo_tope', 'interfaz.js.tabla.vacia', 'interfaz.js.tabla.error_carga', 'interfaz.js.tabla.info',
        'interfaz.js.jugadores.posiciones.portero', 'interfaz.js.jugadores.posiciones.defensa',
        'interfaz.js.jugadores.posiciones.medio', 'interfaz.js.jugadores.posiciones.delantero',
        'interfaz.js.jugadores.niveles.sin_riesgo', 'interfaz.js.jugadores.niveles.en_observacion', 'interfaz.js.jugadores.niveles.en_riesgo',
        'interfaz.js.jugadores.estatus.activo', 'interfaz.js.jugadores.estatus.baja',
    ];
    foreach ($claves as $clave) {
        expect(__($clave))->not->toBe($clave);
    }
});

test('FRONT-R3 7: las páginas de jugadores cargan SweetAlert2, DataTables, Buttons, JSZip, pdfmake y tabla.js en orden; el panel y la contraseña no cargan DataTables (DAF-07)', function () {
    /** @var TestCase $this */
    usuarioDeJugadores($this);
    $listado = (string) $this->get('/jugadores')->getContent();
    $ficha = (string) $this->get('/jugadores/'.Identificador::generar()->valor())->getContent();

    expect(cargaEnOrden($listado, [...SCRIPTS_DE_TABLAS, '/assets/js/modulos/jugadores/index.js']))->toBeTrue()
        ->and(cargaEnOrden($ficha, [...SCRIPTS_DE_TABLAS, '/assets/js/modulos/jugadores/ver.js']))->toBeTrue()
        ->and($listado)->toContain('<link href="/assets/vendor/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet">')
        ->and($listado)->toContain('<link href="/assets/vendor/datatables/css/buttons.bootstrap5.min.css" rel="stylesheet">')
        ->and($listado)->toContain('<link href="/assets/css/comun/tablas.css" rel="stylesheet">')
        ->and($ficha)->toContain('<link href="/assets/css/comun/tablas.css" rel="stylesheet">')
        ->and(File::get(public_path('assets/css/comun/tablas.css')))->toContain('div.dt-container div.dt-layout-table > div')
        ->and((string) $this->get('/panel')->getContent())->not->toContain('datatables')
        ->and((string) $this->get('/contrasena')->getContent())->not->toContain('datatables');
});

test('FRONT-R3 8: comun/tabla.js traduce la paginación al contrato y ofrece «Descargar esta página» y «Descargar todo» con tope de 1000 filas (CA-TAB-002, DAF-09)', function () {
    $tabla = File::get(public_path('assets/js/comun/tabla.js'));

    expect($tabla)->toContain('var TOPE = 1000;')
        ->and($tabla)->toContain('var POR_PAGINA_MAXIMA = 100;')
        ->and($tabla)->toContain('lengthMenu: [15, 25, 50, 100]')
        ->and($tabla)->toContain("consulta.set('pagina', Math.floor(solicitud.start / solicitud.length) + 1);")
        ->and($tabla)->toContain("consulta.set('por_pagina', solicitud.length);")
        ->and($tabla)->toContain('recordsTotal: respuesta.meta.total')
        ->and($tabla)->toContain('text: t.descargar_pagina')
        ->and($tabla)->toContain('text: t.descargar_todo')
        ->and($tabla)->toContain('window.Avisos.confirmar({ titulo: t.todo_titulo, texto: texto, boton: t.descargar })')
        ->and($tabla)->toContain('searching: false');
});
