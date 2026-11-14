<?php

use App\Exceptions\FormatoDeError;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Salud\Dominio\AvisoDeLesion;
use Src\Salud\Dominio\AvisoNoPendiente;
use Src\Salud\Dominio\EstadoDeAviso;
use Src\Salud\Dominio\RepositorioDeAvisos;
use Src\Salud\Infraestructura\RepositorioDeAvisosSql;
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

/** Una segunda conexión a la misma base, para simular dos peticiones (DA-79; modelo: ConcurrenciaTest). */
function avisosOtraConexion(): ConnectionInterface
{
    config(['database.connections.avisos_concurrente' => config('database.connections.pgsql')]);

    return DB::connection('avisos_concurrente');
}

function avisosRepositorioEn(ConnectionInterface $conexion): RepositorioDeAvisosSql
{
    return new RepositorioDeAvisosSql($conexion, new EscritorDeHistorial($conexion, app(Reloj::class), app(UsuarioActual::class)), app(UsuarioActual::class));
}

test('DA-79: buscarParaActualizar bloquea el aviso: una segunda conexión espera hasta que termina la primera', function () {
    ['m' => $m, 'repositorio' => $repositorio] = repositorioDeAvisosConSesionDe();
    $aviso = avisoParaGuardar($m['f1'], $m['e1']);
    $repositorio->guardarNuevo($aviso);
    $principal = DB::connection();
    $otra = avisosOtraConexion();

    $principal->beginTransaction();
    $leidoPorA = avisosRepositorioEn($principal)->buscarParaActualizar($aviso->id());
    $otra->beginTransaction();
    $otra->statement("set local lock_timeout = '200ms'");
    $espera = null;
    try {
        avisosRepositorioEn($otra)->buscarParaActualizar($aviso->id());
    } catch (QueryException $e) {
        $espera = $e;
    }
    $otra->rollBack();
    $principal->rollBack();
    DB::purge('avisos_concurrente');

    // 55P03 (lock_not_available): la segunda no pudo leer mientras la primera tenía la fila; sin el bloqueo, la leería.
    expect($leidoPorA)->not->toBeNull()
        ->and($espera?->getCode())->toBe('55P03');
});

test('DA-79: descarte y confirmación en paralelo: la segunda encuentra el aviso ya descartado y responde 409, nunca 500', function () {
    ['m' => $m, 'repositorio' => $repositorio] = repositorioDeAvisosConSesionDe();
    $aviso = avisoParaGuardar($m['f1'], $m['e1']);
    $repositorio->guardarNuevo($aviso);
    $principal = DB::connection();
    $otra = avisosOtraConexion();
    $instante = new DateTimeImmutable('2026-11-15 18:00:05+00:00');

    $otra->beginTransaction();
    $principal->transaction(function () use ($principal, $aviso, $m, $instante) {
        $repositorioA = avisosRepositorioEn($principal);
        $leido = NoNulo::exigir($repositorioA->buscarParaActualizar($aviso->id()), 'el aviso');
        $repositorioA->guardarDescarte($leido, $leido->descartar(Identificador::desdeTexto($m['e1']), 'No aplica', $instante));
    });
    $leidoPorB = NoNulo::exigir(avisosRepositorioEn($otra)->buscarParaActualizar($aviso->id()), 'el aviso');
    $error = null;
    try {
        $leidoPorB->confirmar(Identificador::desdeTexto($m['e2']), $instante);
    } catch (AvisoNoPendiente $e) {
        $error = $e;
    }
    $otra->rollBack();
    DB::purge('avisos_concurrente');
    $respuesta = FormatoDeError::responder(NoNulo::exigir($error, 'el rechazo de la segunda'));

    expect($leidoPorB->estado())->toBe(EstadoDeAviso::Descartado)
        ->and($respuesta->getStatusCode())->toBe(409)
        ->and(json_decode((string) $respuesta->getContent(), true)['codigo'])->toBe('aviso_no_pendiente')
        ->and(DB::table('avisos_lesion_historial')->where('aviso_id', $aviso->id()->valor())->orderBy('version')->pluck('version')->all())->toBe([1, 2]);
});

test('DA-79: si escribir el descarte o la confirmación choca con un índice único, responde 409 conflicto, nunca 500', function (string $operacion) {
    ['m' => $m, 'repositorio' => $repositorio] = repositorioDeAvisosConSesionDe();
    $aviso = avisoParaGuardar($m['f1'], $m['e1']);
    $repositorio->guardarNuevo($aviso);
    $instante = new DateTimeImmutable('2026-11-15 18:00:05+00:00');
    $propietario = DB::connection('propietario');
    // Disparador solo de esta prueba: simula el choque con el índice único (entidad, versión) de dos escrituras a la vez.
    $propietario->statement("create function public.prueba_da79_choque() returns trigger language plpgsql as \$\$ begin raise exception 'choque simulado' using errcode = 'unique_violation'; end \$\$");
    $propietario->statement("create trigger prueba_da79_choque before insert on avisos_lesion_historial for each row when (new.operacion = 'actualizar') execute function public.prueba_da79_choque()");
    $error = null;
    try {
        $operacion === 'descarte'
            ? $repositorio->guardarDescarte($aviso, $aviso->descartar(Identificador::desdeTexto($m['e1']), 'No aplica', $instante))
            : $repositorio->guardarConfirmacion($aviso, $aviso->confirmar(Identificador::desdeTexto($m['e1']), $instante), null);
    } catch (Throwable $e) {
        $error = $e;
    } finally {
        $propietario->statement('drop trigger prueba_da79_choque on avisos_lesion_historial');
        $propietario->statement('drop function public.prueba_da79_choque()');
    }
    $respuesta = FormatoDeError::responder(NoNulo::exigir($error, 'el choque'));

    expect($respuesta->getStatusCode())->toBe(409)
        ->and(json_decode((string) $respuesta->getContent(), true)['codigo'])->toBe('conflicto')
        ->and(DB::table('avisos_lesion')->where('id', $aviso->id()->valor())->value('estado'))->toBe('pendiente');
})->with(['descarte', 'confirmacion']);

test('Normalización UTC: el aviso leído trae creado_en y actualizado_en en UTC, como estados y mediciones', function () {
    ['m' => $m, 'repositorio' => $repositorio] = repositorioDeAvisosConSesionDe();
    $aviso = avisoParaGuardar($m['f1'], $m['e1']);
    $repositorio->guardarNuevo($aviso);

    $leido = NoNulo::exigir($repositorio->buscar($aviso->id()), 'el aviso');

    expect($leido->creadoEn()->getTimezone()->getName())->toBe('UTC')
        ->and($leido->actualizadoEn()->getTimezone()->getName())->toBe('UTC')
        ->and($leido->creadoEn()->format(DATE_ATOM))->toBe('2026-11-15T18:00:00+00:00');
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
    // UUID v7 con milisegundos distintos y crecientes: con la misma fecha del aviso, el orden por id es determinista.
    $primero = avisoInsertar($m['f1'], $m['e1'], ['fecha_aviso' => '2026-11-14'], '019a5c00-0000-7000-8000-000000000001');
    $segundo = avisoInsertar($m['f1'], $m['e1'], ['fecha_aviso' => '2026-11-14'], '019a5c00-0001-7000-8000-000000000002');
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
