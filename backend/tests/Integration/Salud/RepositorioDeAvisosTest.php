<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Salud\Dominio\AvisoDeLesion;
use Src\Salud\Dominio\EstadoDeAviso;
use Src\Salud\Dominio\RepositorioDeAvisos;
use Tests\Ayudantes\NoNulo;

/** @return array{m: array<string, string>, repositorio: RepositorioDeAvisos} */
function repositorioDeAvisosConSesionDe(string $usuario = 'e1'): array
{
    $m = avisosMundo();
    app()->instance(UsuarioActual::class, new class($m[$usuario]) implements UsuarioActual
    {
        public function __construct(private readonly string $id) {}

        public function identificador(): Identificador
        {
            return Identificador::desdeTexto($this->id);
        }

        public function roles(): array
        {
            return ['entrenador'];
        }
    });

    return ['m' => $m, 'repositorio' => app(RepositorioDeAvisos::class)];
}

function avisoParaGuardar(string $jugador, string $autor, string $fecha = '2026-11-14'): AvisoDeLesion
{
    return AvisoDeLesion::crear(
        Identificador::generar(), Identificador::desdeTexto($jugador), Identificador::desdeTexto($autor), true, 'Molestia',
        $fecha, new DateTimeImmutable('2026-11-15 18:00:00+00:00'),
    );
}

test('RNF-02: guardar un aviso escribe la fila, su auditoría y su historial', function () {
    ['m' => $m, 'repositorio' => $repositorio] = repositorioDeAvisosConSesionDe();
    $aviso = avisoParaGuardar($m['f1'], $m['e1']);

    $repositorio->guardarNuevo($aviso);

    $fila = NoNulo::exigir(DB::table('avisos_lesion')->where('id', $aviso->id()->valor())->first(), 'la fila del aviso');
    $historial = NoNulo::exigir(DB::table('avisos_lesion_historial')->where('aviso_id', $aviso->id()->valor())->first(), 'el historial del aviso');
    $datos = json_decode($historial->datos_nuevos, true);

    expect($fila->creado_por)->toBe($m['e1'])
        ->and($fila->actualizado_por)->toBe($m['e1'])
        ->and($fila->estado)->toBe('pendiente')
        ->and($fila->reportado_por_jugador)->toBeTrue()
        ->and($fila->creado_en)->toBe('2026-11-15 18:00:00+00')
        ->and($historial->operacion)->toBe('crear')
        ->and($historial->version)->toBe(1)
        ->and($historial->usuario_id)->toBe($m['e1'])
        ->and($historial->datos_anteriores)->toBeNull()
        ->and($datos['estado'])->toBe('pendiente')
        ->and($datos['nota'])->toBe('Molestia')
        ->and($datos['jugador_id'])->toBe($m['f1']);
});

test('RNF-04: un aviso guardado se lee igual, con sus instantes', function () {
    ['m' => $m, 'repositorio' => $repositorio] = repositorioDeAvisosConSesionDe();
    $aviso = avisoParaGuardar($m['f1'], $m['e1']);
    $repositorio->guardarNuevo($aviso);

    $leido = NoNulo::exigir($repositorio->buscar($aviso->id()), 'el aviso leído');

    expect($leido->id()->igualA($aviso->id()))->toBeTrue()
        ->and($leido->jugadorId()->igualA($aviso->jugadorId()))->toBeTrue()
        ->and($leido->creadoPor()->igualA($aviso->creadoPor()))->toBeTrue()
        ->and($leido->reportadoPorJugador())->toBeTrue()
        ->and($leido->nota())->toBe('Molestia')
        ->and($leido->estado())->toBe(EstadoDeAviso::Pendiente)
        ->and($leido->fechaAviso())->toBe('2026-11-14')
        ->and($leido->creadoEn()->getTimestamp())->toBe($aviso->creadoEn()->getTimestamp())
        ->and($leido->actualizadoEn()->getTimestamp())->toBe($aviso->actualizadoEn()->getTimestamp());
});

test('RNF-02: guardar un descarte actualiza la fila y escribe la versión 2 del historial con la instantánea anterior', function () {
    ['m' => $m, 'repositorio' => $repositorio] = repositorioDeAvisosConSesionDe('e2');
    $aviso = avisoParaGuardar($m['f1'], $m['d1']);
    $repositorio->guardarNuevo($aviso);
    $descartado = $aviso->descartar(Identificador::desdeTexto($m['e2']), 'No aplica', new DateTimeImmutable('2026-11-16 09:00:00+00:00'));

    $repositorio->guardarDescarte($aviso, $descartado);

    $fila = NoNulo::exigir(DB::table('avisos_lesion')->where('id', $aviso->id()->valor())->first(), 'la fila del aviso');
    $historial = NoNulo::exigir(DB::table('avisos_lesion_historial')->where('aviso_id', $aviso->id()->valor())->where('version', 2)->first(), 'el historial del descarte');
    $anteriores = json_decode((string) $historial->datos_anteriores, true);
    $nuevos = json_decode((string) $historial->datos_nuevos, true);

    expect($fila->estado)->toBe('descartado')
        ->and($fila->descartado_por)->toBe($m['e2'])
        ->and($fila->nota_descarte)->toBe('No aplica')
        ->and($fila->actualizado_por)->toBe($m['e2'])
        ->and($fila->actualizado_en)->toBe('2026-11-16 09:00:00+00')
        ->and($fila->creado_por)->toBe($m['d1'])
        ->and($historial->operacion)->toBe('actualizar')
        ->and($anteriores['estado'])->toBe('pendiente')
        ->and($anteriores['nota_descarte'])->toBeNull()
        ->and($nuevos['estado'])->toBe('descartado')
        ->and($nuevos['descartado_por'])->toBe($m['e2'])
        ->and($nuevos['nota_descarte'])->toBe('No aplica');
});

test('§6.3: buscarParaActualizar bloquea la fila, para que dos descartes simultáneos no pasen los dos', function () {
    ['m' => $m, 'repositorio' => $repositorio] = repositorioDeAvisosConSesionDe();
    $aviso = avisoParaGuardar($m['f1'], $m['e1']);
    $repositorio->guardarNuevo($aviso);
    $consultas = [];
    DB::listen(function ($consulta) use (&$consultas) {
        $consultas[] = $consulta->sql;
    });

    $leido = DB::transaction(fn () => $repositorio->buscarParaActualizar($aviso->id()));

    expect($leido)->not->toBeNull()
        ->and(collect($consultas)->contains(fn (string $sql) => str_contains($sql, 'avisos_lesion') && str_contains($sql, 'for update')))->toBeTrue();
});

test('§6.1: un aviso inexistente o eliminado no se encuentra', function () {
    ['m' => $m, 'repositorio' => $repositorio] = repositorioDeAvisosConSesionDe();
    $eliminado = avisoInsertar($m['f1'], $m['e1'], ['eliminado_en' => '2026-11-15 10:00:00+00']);

    expect($repositorio->buscar(Identificador::generar()))->toBeNull()
        ->and($repositorio->buscar(Identificador::desdeTexto($eliminado)))->toBeNull()
        ->and($repositorio->buscarParaActualizar(Identificador::generar()))->toBeNull()
        ->and($repositorio->buscarParaActualizar(Identificador::desdeTexto($eliminado)))->toBeNull();
});

test('DD-56: delJugador ordena por fecha y por id descendentes, y solo trae avisos no eliminados de ese jugador', function () {
    ['m' => $m, 'repositorio' => $repositorio] = repositorioDeAvisosConSesionDe();
    $viejo = avisoInsertar($m['f1'], $m['e1'], ['fecha_aviso' => '2026-11-10']);
    $primero = avisoInsertar($m['f1'], $m['e1'], ['fecha_aviso' => '2026-11-14']);
    $segundo = avisoInsertar($m['f1'], $m['e1'], ['fecha_aviso' => '2026-11-14']);
    avisoInsertar($m['f1'], $m['e1'], ['eliminado_en' => '2026-11-15 10:00:00+00']);
    avisoInsertar($m['j1'], $m['e1']);

    $listado = $repositorio->delJugador(Identificador::desdeTexto($m['f1']), null, 1, 15);

    expect($listado['total'])->toBe(3)
        ->and(array_map(fn (AvisoDeLesion $aviso) => $aviso->id()->valor(), $listado['elementos']))->toBe([$segundo, $primero, $viejo]);
});

test('DA-25: delJugador filtra por autor y pagina', function () {
    ['m' => $m, 'repositorio' => $repositorio] = repositorioDeAvisosConSesionDe();
    avisoInsertar($m['f1'], $m['e1'], ['fecha_aviso' => '2026-11-12']);
    $propioViejo = avisoInsertar($m['f1'], $m['d1'], ['fecha_aviso' => '2026-11-10']);
    avisoInsertar($m['f1'], $m['d1'], ['fecha_aviso' => '2026-11-14']);

    $listado = $repositorio->delJugador(Identificador::desdeTexto($m['f1']), Identificador::desdeTexto($m['d1']), 2, 1);

    expect($listado['total'])->toBe(2)
        ->and(array_map(fn (AvisoDeLesion $aviso) => $aviso->id()->valor(), $listado['elementos']))->toBe([$propioViejo]);
});

test('DD-58: la lectura conserva quién confirmó y quién descartó un aviso', function () {
    ['m' => $m, 'repositorio' => $repositorio] = repositorioDeAvisosConSesionDe();
    $confirmado = avisoInsertar($m['f1'], $m['e1'], ['estado' => 'confirmado', 'confirmado_por' => $m['e2']]);
    $descartado = avisoInsertar($m['f1'], $m['e1'], ['estado' => 'descartado', 'descartado_por' => $m['e2'], 'nota_descarte' => 'No aplica']);

    $unConfirmado = NoNulo::exigir($repositorio->buscar(Identificador::desdeTexto($confirmado)), 'el aviso confirmado');
    $unDescartado = NoNulo::exigir($repositorio->buscar(Identificador::desdeTexto($descartado)), 'el aviso descartado');

    expect($unConfirmado->estado())->toBe(EstadoDeAviso::Confirmado)
        ->and($unConfirmado->confirmadoPor()?->valor())->toBe($m['e2'])
        ->and($unDescartado->estado())->toBe(EstadoDeAviso::Descartado)
        ->and($unDescartado->descartadoPor()?->valor())->toBe($m['e2'])
        ->and($unDescartado->notaDescarte())->toBe('No aplica');
});
