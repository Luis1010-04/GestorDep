<?php

use Illuminate\Support\Facades\File;
use Src\Compartido\Dominio\Identificador;
use Src\Identidad\Infraestructura\Modelos\UsuarioModelo;
use Tests\TestCase;

function usuarioDelEstatus(TestCase $prueba): void
{
    $usuario = new UsuarioModelo;
    $usuario->id = Identificador::generar()->valor();
    $prueba->actingAs($usuario, 'web');
}

/**
 * Atributos de los controles de un formulario, en orden, como [name, tipo]; el tipo es el de input, o select o textarea.
 *
 * @return list<array{0: string, 1: string}>
 */
function controlesDelEstatus(string $html, string $id): array
{
    preg_match('/<form\b[^>]*id="'.preg_quote($id, '/').'".*?<\/form>/s', $html, $formulario);
    preg_match_all('/<(input|select|textarea)\b([^>]*)>/', $formulario[0] ?? '', $controles, PREG_SET_ORDER);

    return array_map(function (array $control): array {
        preg_match('/\bname="([^"]*)"/', $control[2], $nombre);
        preg_match('/\btype="([^"]*)"/', $control[2], $tipo);

        return [$nombre[1] ?? '', $control[1] === 'input' ? ($tipo[1] ?? 'text') : $control[1]];
    }, $controles);
}

test('FRONT-R4 1: /estatus sin sesión redirige a /ingreso sin contenido privado (CA-GEN-005)', function () {
    /** @var TestCase $this */
    $respuesta = $this->get('/estatus')->assertRedirect('/ingreso');

    expect((string) $respuesta->getContent())->not->toContain('data-endpoint');
});

test('FRONT-R4 2: /estatus con sesión responde 200 con el layout, los filtros y la tabla (CA-LAY-001, CA-TAB-001)', function () {
    /** @var TestCase $this */
    usuarioDelEstatus($this);

    $this->get('/estatus')->assertOk()
        ->assertSee('id="header"', false)->assertSee('id="sidebar"', false)->assertSee('id="footer"', false)
        ->assertSee('id="filtros-estatus"', false)->assertSee('id="tabla-estatus"', false);
});

test('FRONT-R4 3: el menú muestra «Estatus de jugadores» a docente y entrenador, oculto hasta conocer la sesión, y activo en su página (CA-LAY-002)', function () {
    /** @var TestCase $this */
    usuarioDelEstatus($this);
    preg_match('/<aside id="sidebar".*?<\/aside>/s', (string) $this->get('/estatus')->getContent(), $menu);

    expect($menu[0] ?? '')->toMatch('/<li class="nav-item" data-roles="docente entrenador" hidden>\s*<a class="nav-link" href="\/estatus" aria-current="page">/');
});

test('FRONT-R4 4: los filtros del estatus son exactamente los del contrato; el de equipo es del entrenador y el de materia del docente (CA-TAB-003, DA-105)', function () {
    /** @var TestCase $this */
    usuarioDelEstatus($this);
    $html = (string) $this->get('/estatus')->getContent();

    expect(controlesDelEstatus($html, 'filtros-estatus'))->toBe([
        ['filtro[equipo_id]', 'select'], ['filtro[materia_id]', 'select'], ['filtro[tipo_alerta]', 'select'],
    ])
        ->and($html)->toMatch('/<div class="col-sm-6 col-lg-4" data-roles="entrenador" hidden>\s*<label for="filtro-equipo"/')
        ->and($html)->toMatch('/<div class="col-sm-6 col-lg-4" data-roles="docente" hidden>\s*<label for="filtro-materia"/');
});

test('FRONT-R4 5: la tabla de estatus no ofrece orden (la ruta no lo admite), lleva las columnas de cada rol y su columna de acciones no se exporta (CA-TAB-003, DA-105)', function () {
    /** @var TestCase $this */
    usuarioDelEstatus($this);
    preg_match('/<table id="tabla-estatus".*?<\/table>/s', (string) $this->get('/estatus')->getContent(), $tabla);

    expect($tabla[0] ?? '')->not->toContain('data-orden')
        ->toContain('data-columna-de="entrenador"')
        ->toContain('data-columna-de="docente"')
        ->toContain('<th class="no-exportar"');
});

test('FRONT-R4 6: la ficha suma las pestañas Alertas, Disponibilidad y Avisos de lesión para docente y entrenador, y el formulario de aviso tiene los campos del contrato (CA-FOR-001)', function () {
    /** @var TestCase $this */
    usuarioDelEstatus($this);
    $html = (string) $this->get('/jugadores/'.Identificador::generar()->valor())->getContent();

    foreach (['alertas', 'disponibilidad', 'avisos'] as $pestana) {
        expect($html)->toMatch('/<li class="nav-item" role="presentation" data-roles="docente entrenador" hidden>\s*<button class="nav-link" id="boton-'.$pestana.'"/');
    }
    expect(controlesDelEstatus($html, 'formulario-aviso'))->toBe([['nota', 'textarea'], ['fecha', 'date'], ['reportado_por_jugador', 'checkbox']]);
});

test('FRONT-R4 7: la tabla de avisos de lesión no ofrece descargas (DAF-16) y tabla.js respeta esa opción', function () {
    /** @var TestCase $this */
    usuarioDelEstatus($this);
    $html = (string) $this->get('/jugadores/'.Identificador::generar()->valor())->getContent();
    $avisos = File::get(public_path('assets/js/modulos/jugadores/ver.js'));
    $tabla = File::get(public_path('assets/js/comun/tabla.js'));

    expect($html)->toMatch('/<table id="tabla-avisos"[^>]*data-endpoint="GET \/jugadores\/\{jugador\}\/avisos-lesion"/')
        ->and($avisos)->toMatch("/ruta: ruta \+ '\/avisos-lesion',[\s\S]*?descargas: false/")
        ->and($tabla)->toContain('buttons: config.descargas === false ? [] : botones()');
});

test('FRONT-R4 8: los textos del estatus, de las alertas y de los avisos salen de lang/es/interfaz.php', function () {
    /** @var TestCase $this */
    usuarioDelEstatus($this);

    $this->get('/estatus')->assertOk()->assertSee(__('interfaz.estatus.titulo'))->assertSee(__('interfaz.estatus.filtro_tipo_alerta'));
    $this->get('/jugadores/'.Identificador::generar()->valor())->assertOk()
        ->assertSee(__('interfaz.jugadores.pestanas.alertas'))->assertSee(__('interfaz.jugadores.pestanas.disponibilidad'))
        ->assertSee(__('interfaz.jugadores.pestanas.avisos'))->assertSee(__('interfaz.avisos.nuevo'));

    $claves = [
        'interfaz.tipos_de_alerta.riesgo_baja_academica', 'interfaz.tipos_de_alerta.riesgo_baja_deportiva',
        'interfaz.tipos_de_alerta.acumulacion_sanciones_deportivas',
        'interfaz.js.estatus.estados.apto', 'interfaz.js.estatus.estados.con_molestia', 'interfaz.js.estatus.estados.lesionado',
        'interfaz.js.estatus.estados_de_aviso.pendiente', 'interfaz.js.estatus.estados_de_aviso.confirmado',
        'interfaz.js.estatus.estados_de_aviso.descartado', 'interfaz.js.estatus.sin_alertas', 'interfaz.js.estatus.nota_no_visible',
        'interfaz.js.estatus.aviso_creado', 'interfaz.js.estatus.requiere_actualizacion', 'interfaz.js.estatus.es_implicito',
    ];
    foreach ($claves as $clave) {
        expect(__($clave))->not->toBe($clave);
    }
});

test('FRONT-R4 9: /estatus carga SweetAlert2, avisos, formularios, DataTables y su script de módulo en orden (DAF-07)', function () {
    /** @var TestCase $this */
    usuarioDelEstatus($this);
    $html = (string) $this->get('/estatus')->getContent();
    $orden = array_map(
        fn (string $archivo) => strpos($html, '<script src="'.$archivo.'"></script>'),
        ['/assets/js/sesion.js', '/assets/vendor/sweetalert2/sweetalert2.min.js', '/assets/js/comun/avisos.js', '/assets/vendor/datatables/js/dataTables.min.js', '/assets/js/comun/tabla.js', '/assets/js/modulos/estatus/index.js'],
    );

    expect($orden)->not->toContain(false)
        ->and($orden)->toBe(collect($orden)->sort()->values()->all())
        ->and($html)->toContain('<link href="/assets/css/comun/tablas.css" rel="stylesheet">');
});
