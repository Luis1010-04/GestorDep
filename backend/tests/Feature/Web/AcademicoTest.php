<?php

use Illuminate\Support\Facades\File;
use Src\Compartido\Dominio\Identificador;
use Src\Identidad\Infraestructura\Modelos\UsuarioModelo;
use Tests\TestCase;

function usuarioAcademico(TestCase $prueba): void
{
    $usuario = new UsuarioModelo;
    $usuario->id = Identificador::generar()->valor();
    $prueba->actingAs($usuario, 'web');
}

/**
 * Páginas de FRONT-R5a, con un identificador de ejemplo en la ficha de la materia.
 *
 * @return list<string>
 */
function paginasAcademicas(): array
{
    return [
        '/academico/periodos',
        '/academico/asignaturas',
        '/academico/materias',
        '/academico/materias/'.Identificador::generar()->valor(),
    ];
}

/**
 * Controles de un formulario, en orden, como [name, tipo]; el tipo es el de input, o select o textarea.
 *
 * @return list<array{0: string, 1: string}>
 */
function controlesAcademicos(string $html, string $id): array
{
    preg_match('/<form\b[^>]*id="'.preg_quote($id, '/').'".*?<\/form>/s', $html, $formulario);
    preg_match_all('/<(input|select|textarea)\b([^>]*)>/', $formulario[0] ?? '', $controles, PREG_SET_ORDER);

    return array_map(function (array $control): array {
        preg_match('/\bname="([^"]*)"/', $control[2], $nombre);
        preg_match('/\btype="([^"]*)"/', $control[2], $tipo);

        return [$nombre[1] ?? '', $control[1] === 'input' ? ($tipo[1] ?? 'text') : $control[1]];
    }, $controles);
}

test('FRONT-R5a 1: las páginas académicas sin sesión redirigen a /ingreso sin contenido privado (CA-GEN-005)', function () {
    /** @var TestCase $this */
    $prueba = $this;

    foreach (paginasAcademicas() as $pagina) {
        $respuesta = $prueba->get($pagina)->assertRedirect('/ingreso');

        expect(str_contains((string) $respuesta->getContent(), 'data-endpoint'))->toBeFalse($pagina);
    }
});

test('FRONT-R5a 2: con sesión responden 200 con el layout; la ficha de la materia lleva su identificador y uno que no es UUID responde 404', function () {
    /** @var TestCase $this */
    usuarioAcademico($this);
    $materia = Identificador::generar()->valor();

    foreach (paginasAcademicas() as $pagina) {
        $this->get($pagina)->assertOk()->assertSee('id="header"', false)->assertSee('id="sidebar"', false)->assertSee('id="footer"', false);
    }

    $this->get('/academico/materias/'.$materia)->assertSee('data-materia="'.$materia.'"', false);
    $this->get('/academico/materias/no-es-uuid')->assertNotFound();
});

test('FRONT-R5a 3: el menú agrupa «Académico» con Periodos, Asignaturas y Materias solo para el docente, ocultos hasta conocer la sesión, y marca la sección activa (CA-LAY-002, CA-GEN-005)', function () {
    /** @var TestCase $this */
    usuarioAcademico($this);
    preg_match('/<aside id="sidebar".*?<\/aside>/s', (string) $this->get('/academico/asignaturas')->getContent(), $menu);

    expect($menu[0] ?? '')
        ->toMatch('/<li class="nav-heading" data-roles="docente" hidden>'.preg_quote(__('interfaz.menu.academico'), '/').'<\/li>/')
        ->toMatch('/<li class="nav-item" data-roles="docente" hidden>\s*<a class="nav-link collapsed" href="\/academico\/periodos">/')
        ->toMatch('/<li class="nav-item" data-roles="docente" hidden>\s*<a class="nav-link" href="\/academico\/asignaturas" aria-current="page">/')
        ->toMatch('/<li class="nav-item" data-roles="docente" hidden>\s*<a class="nav-link collapsed" href="\/academico\/materias">/');

    $ficha = (string) $this->get('/academico/materias/'.Identificador::generar()->valor())->getContent();
    expect($ficha)->toMatch('/<a class="nav-link" href="\/academico\/materias" aria-current="page">/');
});

test('FRONT-R5a 4: cada formulario académico tiene exactamente los campos de su endpoint, con el tipo de control adecuado (CA-FOR-001)', function () {
    /** @var TestCase $this */
    usuarioAcademico($this);
    $periodos = (string) $this->get('/academico/periodos')->getContent();
    $asignaturas = (string) $this->get('/academico/asignaturas')->getContent();
    $materias = (string) $this->get('/academico/materias')->getContent();
    $materia = (string) $this->get('/academico/materias/'.Identificador::generar()->valor())->getContent();

    expect(controlesAcademicos($periodos, 'formulario-periodo'))->toBe([['nombre', 'text'], ['fecha_inicio', 'date'], ['fecha_fin', 'date']])
        ->and(controlesAcademicos($asignaturas, 'formulario-asignatura'))->toBe([['clave', 'text'], ['nombre', 'text'], ['grado', 'number']])
        ->and(controlesAcademicos($materias, 'formulario-materia'))->toBe([['asignatura_id', 'select'], ['periodo_id', 'select']])
        ->and(controlesAcademicos($materias, 'filtros-materias'))->toBe([['filtro[periodo_id]', 'select']])
        ->and(controlesAcademicos($materia, 'formulario-criterio'))->toBe([['calificacion_minima', 'text']]);
});

test('FRONT-R5a 5: las tablas académicas solo ordenan por los campos que admite su endpoint y su columna de acciones no se exporta (CA-TAB-003, F4)', function () {
    /** @var TestCase $this */
    usuarioAcademico($this);
    $casos = [
        ['/academico/periodos', 'tabla-periodos', ['fecha_inicio']],
        ['/academico/asignaturas', 'tabla-asignaturas', ['clave']],
        ['/academico/materias', 'tabla-materias', []],
    ];

    foreach ($casos as [$pagina, $id, $ordenes]) {
        preg_match('/<table id="'.$id.'".*?<\/table>/s', (string) $this->get($pagina)->getContent(), $tabla);
        preg_match_all('/data-orden="([^"]+)"/', $tabla[0] ?? '', $encontradas);

        expect($encontradas[1])->toBe($ordenes, $id)
            ->and($tabla[0] ?? '')->toContain('<th class="no-exportar"');
    }
});

test('FRONT-R5a 6: los textos académicos salen de lang/es/interfaz.php', function () {
    /** @var TestCase $this */
    usuarioAcademico($this);

    $this->get('/academico/periodos')->assertOk()->assertSee(__('interfaz.periodos.titulo'))->assertSee(__('interfaz.periodos.nuevo'));
    $this->get('/academico/asignaturas')->assertOk()->assertSee(__('interfaz.asignaturas.titulo'))->assertSee(__('interfaz.asignaturas.nueva'));
    $this->get('/academico/materias')->assertOk()->assertSee(__('interfaz.materias.titulo'))->assertSee(__('interfaz.materias.nueva'));
    $this->get('/academico/materias/'.Identificador::generar()->valor())->assertOk()->assertSee(__('interfaz.materias.criterio_titulo'));

    $claves = [
        'interfaz.js.academico.periodo_creado', 'interfaz.js.academico.periodo_eliminado', 'interfaz.js.academico.eliminar_periodo_titulo',
        'interfaz.js.academico.asignatura_creada', 'interfaz.js.academico.asignatura_eliminada', 'interfaz.js.academico.eliminar_asignatura_titulo',
        'interfaz.js.academico.eliminar_texto', 'interfaz.js.academico.materia_creada', 'interfaz.js.academico.criterio_titulo',
        'interfaz.js.academico.criterio_texto', 'interfaz.js.academico.criterio_guardado', 'interfaz.js.academico.materia_no_disponible',
        'interfaz.js.academico.ambitos.general', 'interfaz.js.academico.ambitos.materia',
    ];
    foreach ($claves as $clave) {
        expect(__($clave))->not->toBe($clave);
    }
});

test('FRONT-R5a 7: cada página académica carga SweetAlert2, avisos, formularios y su script de módulo en orden, y DataTables solo las que tienen tabla (DAF-07)', function () {
    /** @var TestCase $this */
    usuarioAcademico($this);
    $casos = [
        ['/academico/periodos', '/assets/js/modulos/periodos/index.js', true],
        ['/academico/asignaturas', '/assets/js/modulos/asignaturas/index.js', true],
        ['/academico/materias', '/assets/js/modulos/materias/index.js', true],
        ['/academico/materias/'.Identificador::generar()->valor(), '/assets/js/modulos/materias/ver.js', false],
    ];

    foreach ($casos as [$pagina, $script, $conTabla]) {
        $html = (string) $this->get($pagina)->getContent();
        $archivos = $conTabla
            ? ['/assets/js/sesion.js', '/assets/vendor/sweetalert2/sweetalert2.min.js', '/assets/js/comun/avisos.js', '/assets/js/comun/formularios.js', '/assets/vendor/datatables/js/dataTables.min.js', '/assets/js/comun/tabla.js', $script]
            : ['/assets/js/sesion.js', '/assets/vendor/sweetalert2/sweetalert2.min.js', '/assets/js/comun/avisos.js', '/assets/js/comun/formularios.js', $script];
        $orden = array_map(fn (string $archivo) => strpos($html, '<script src="'.$archivo.'"></script>'), $archivos);

        expect(in_array(false, $orden, true))->toBeFalse($pagina)
            ->and($orden)->toBe(collect($orden)->sort()->values()->all(), $pagina)
            ->and(str_contains($html, '/assets/vendor/datatables/js/dataTables.min.js'))->toBe($conTabla, $pagina);
    }
});

test('FRONT-R5a 8: eliminar periodo y asignatura piden confirmación de peligro y definir el criterio pide confirmación antes de llamar a la API (CA-FOR-003)', function () {
    $periodos = File::get(public_path('assets/js/modulos/periodos/index.js'));
    $asignaturas = File::get(public_path('assets/js/modulos/asignaturas/index.js'));
    $materia = File::get(public_path('assets/js/modulos/materias/ver.js'));
    $confirmacion = "window\.Avisos\.confirmar\(\{[^}]*\}\)\.then\(function \(confirmado\) \{\s*if \(!confirmado\) \{\s*return;\s*\}[\s\S]*?";

    expect($periodos)->toMatch("/window\.Avisos\.confirmar\(\{[^}]*peligro: true[^}]*\}\)[\s\S]*?'DELETE', '\/periodos-escolares\/'/")
        ->and($asignaturas)->toMatch("/window\.Avisos\.confirmar\(\{[^}]*peligro: true[^}]*\}\)[\s\S]*?'DELETE', '\/asignaturas\/'/")
        ->and($materia)->toMatch('/'.$confirmacion."'PUT', ruta \+ '\/criterio'/");
});

test('FRONT-R5a 9: formularios.js envía un campo numérico con valor como número y uno vacío como texto vacío, para que la API diga que falta (campo entero del contrato: grado)', function () {
    $formularios = File::get(public_path('assets/js/comun/formularios.js'));

    expect($formularios)->toContain("control.type === 'number' && control.value !== '' ? Number(control.value) : control.value");
});

test('FRONT-R5a 10: Avisos.confirmar muestra el título como texto y no como HTML, porque lleva nombres que llegan de la API (periodo, asignatura, categoría, jugador)', function () {
    $avisos = File::get(public_path('assets/js/comun/avisos.js'));

    expect($avisos)->toContain('titleText: opciones.titulo')
        ->and(preg_match('/\b(title|html):/', $avisos))->toBe(0);
});
