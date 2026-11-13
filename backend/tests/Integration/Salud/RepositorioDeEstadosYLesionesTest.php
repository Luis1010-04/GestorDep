<?php

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Salud\Aplicacion\EstadoConcurrente;
use Src\Salud\Dominio\CausaDeLesion;
use Src\Salud\Dominio\EstadoOficial;
use Src\Salud\Dominio\Lesion;
use Src\Salud\Dominio\RepositorioDeEstados;
use Src\Salud\Dominio\RepositorioDeLesiones;
use Src\Salud\Dominio\TipoDeEstado;
use Src\Salud\Infraestructura\RepositorioDeEstadosSql;
use Src\Salud\Infraestructura\RepositorioDeLesionesSql;
use Tests\Ayudantes\NoNulo;

/** @return array<string, string> el mundo de `avisosMundo()`, con E2 como usuario de la sesión */
function saludConSesionDeE2(): array
{
    $m = avisosMundo();
    app()->instance(UsuarioActual::class, new class($m['e2']) implements UsuarioActual
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

    return $m;
}

function lesionParaGuardar(string $jugador, string $autor, string $inicio = '2026-11-10'): Lesion
{
    return Lesion::registrar(
        Identificador::generar(), Identificador::desdeTexto($jugador), Identificador::desdeTexto($autor), 'Esguince', 'Tobillo',
        CausaDeLesion::Partido, $inicio, '2026-11-30', 'Con hielo', new DateTimeImmutable('2026-11-15 18:00:00+00:00'),
    );
}

function estadoParaGuardar(string $jugador, string $autor): EstadoOficial
{
    return EstadoOficial::apto(Identificador::generar(), Identificador::desdeTexto($jugador), Identificador::desdeTexto($autor), new DateTimeImmutable('2026-11-15 18:00:00+00:00'));
}

test('RNF-02: guardar una lesión escribe la fila, su auditoría y su historial, y se lee igual', function () {
    $m = saludConSesionDeE2();
    $repositorio = app(RepositorioDeLesiones::class);
    $lesion = lesionParaGuardar($m['f1'], $m['e2']);

    $repositorio->guardarNueva($lesion);

    $fila = NoNulo::exigir(DB::table('lesiones')->where('id', $lesion->id()->valor())->first(), 'la fila');
    $historial = NoNulo::exigir(DB::table('lesiones_historial')->where('lesion_id', $lesion->id()->valor())->first(), 'el historial');
    $leida = NoNulo::exigir($repositorio->buscar($lesion->id()), 'la lesión leída');

    expect($fila->creado_por)->toBe($m['e2'])
        ->and($fila->actualizado_por)->toBe($m['e2'])
        ->and($fila->creado_en)->toBe('2026-11-15 18:00:00+00')
        ->and($historial->operacion)->toBe('crear')
        ->and($historial->datos_anteriores)->toBeNull()
        ->and(json_decode((string) $historial->datos_nuevos, true)['zona'])->toBe('Tobillo')
        ->and($leida->jugadorId()->igualA($lesion->jugadorId()))->toBeTrue()
        ->and($leida->registradaPor()->igualA($lesion->registradaPor()))->toBeTrue()
        ->and($leida->tipo())->toBe('Esguince')
        ->and($leida->zona())->toBe('Tobillo')
        ->and($leida->causa())->toBe(CausaDeLesion::Partido)
        ->and($leida->fechaInicio())->toBe('2026-11-10')
        ->and($leida->fechaEstimadaRegreso())->toBe('2026-11-30')
        ->and($leida->fechaAlta())->toBeNull()
        ->and($leida->nota())->toBe('Con hielo')
        ->and($leida->actualizadoEn()->getTimestamp())->toBe($lesion->actualizadoEn()->getTimestamp());
});

test('RNF-02: guardar un cambio de la lesión escribe la instantánea anterior', function () {
    $m = saludConSesionDeE2();
    $repositorio = app(RepositorioDeLesiones::class);
    $lesion = lesionParaGuardar($m['f1'], $m['e2']);
    $repositorio->guardarNueva($lesion);
    $corregida = $lesion->corregir(['zona' => 'Rodilla'], new DateTimeImmutable('2026-11-16 09:00:00+00:00'));

    $repositorio->guardarCambio($lesion, $corregida);

    $fila = NoNulo::exigir(DB::table('lesiones')->where('id', $lesion->id()->valor())->first(), 'la fila');
    $historial = NoNulo::exigir(DB::table('lesiones_historial')->where('lesion_id', $lesion->id()->valor())->where('version', 2)->first(), 'el historial');
    expect($fila->zona)->toBe('Rodilla')
        ->and($fila->actualizado_en)->toBe('2026-11-16 09:00:00+00')
        ->and($fila->actualizado_por)->toBe($m['e2'])
        ->and($historial->operacion)->toBe('actualizar')
        ->and(json_decode((string) $historial->datos_anteriores, true)['zona'])->toBe('Tobillo');
});

test('§6.1: una lesión inexistente o eliminada no se encuentra', function () {
    $m = saludConSesionDeE2();
    $repositorio = app(RepositorioDeLesiones::class);
    $eliminada = lesionInsertar($m['f1'], $m['e2'], ['eliminado_en' => '2026-11-15 10:00:00+00']);

    expect($repositorio->buscar(Identificador::generar()))->toBeNull()
        ->and($repositorio->buscar(Identificador::desdeTexto($eliminada)))->toBeNull()
        ->and($repositorio->buscarParaActualizar(Identificador::desdeTexto($eliminada)))->toBeNull();
});

test('RN-15: delRegistrante solo trae las de ese usuario y ese jugador, sin eliminadas, por inicio e id descendentes', function () {
    $m = saludConSesionDeE2();
    $vieja = lesionInsertar($m['f1'], $m['e2'], ['fecha_inicio' => '2026-10-01']);
    // UUID v7 con milisegundos distintos y crecientes: con la misma fecha de inicio, el orden por id es determinista.
    $primera = lesionInsertar($m['f1'], $m['e2'], id: '019a5c00-0000-7000-8000-000000000001');
    $segunda = lesionInsertar($m['f1'], $m['e2'], id: '019a5c00-0001-7000-8000-000000000002');
    lesionInsertar($m['f1'], $m['e1']);
    lesionInsertar($m['j1'], $m['e2']);
    lesionInsertar($m['f1'], $m['e2'], ['eliminado_en' => '2026-11-15 10:00:00+00']);

    $listado = app(RepositorioDeLesiones::class)->delRegistrante(Identificador::desdeTexto($m['f1']), Identificador::desdeTexto($m['e2']), 1, 15);

    expect($listado['total'])->toBe(3)
        ->and(array_map(fn (Lesion $lesion) => $lesion->id()->valor(), $listado['elementos']))->toBe([$segunda, $primera, $vieja]);
});

test('RNF-02: guardar un estado escribe la fila, su auditoría y su historial, y se lee igual', function () {
    $m = saludConSesionDeE2();
    $repositorio = app(RepositorioDeEstados::class);
    $lesion = lesionInsertar($m['f1'], $m['e2']);
    $estado = EstadoOficial::conLesion(
        Identificador::generar(), Identificador::desdeTexto($m['f1']), TipoDeEstado::Lesionado, Identificador::desdeTexto($lesion), '2026-11-30',
        Identificador::desdeTexto($m['e2']), new DateTimeImmutable('2026-11-15 18:00:00+00:00'),
    );

    $repositorio->guardarNuevo($estado);

    $fila = NoNulo::exigir(DB::table('estados_disponibilidad')->where('id', $estado->id()->valor())->first(), 'la fila');
    $historial = NoNulo::exigir(DB::table('estados_disponibilidad_historial')->where('estado_id', $estado->id()->valor())->first(), 'el historial');
    $leido = NoNulo::exigir($repositorio->vigente(Identificador::desdeTexto($m['f1'])), 'el estado leído');

    expect($fila->creado_por)->toBe($m['e2'])
        ->and($fila->vigente_desde)->toBe('2026-11-15 18:00:00+00')
        ->and($historial->operacion)->toBe('crear')
        ->and(json_decode((string) $historial->datos_nuevos, true)['estado'])->toBe('lesionado')
        ->and($leido->id()->igualA($estado->id()))->toBeTrue()
        ->and($leido->tipo())->toBe(TipoDeEstado::Lesionado)
        ->and($leido->lesionId()?->valor())->toBe($lesion)
        ->and($leido->fechaEstimadaRegreso())->toBe('2026-11-30')
        ->and($leido->confirmadoPor()->valor())->toBe($m['e2'])
        ->and($leido->vigenteDesde()->getTimestamp())->toBe($estado->vigenteDesde()->getTimestamp())
        ->and($leido->vigenteHasta())->toBeNull();
});

test('RNF-02: cerrar un estado escribe vigente_hasta y el historial, y deja de ser el vigente', function () {
    $m = saludConSesionDeE2();
    $repositorio = app(RepositorioDeEstados::class);
    $estado = estadoParaGuardar($m['f1'], $m['e2']);
    $repositorio->guardarNuevo($estado);
    $cerrado = $estado->cerrar(new DateTimeImmutable('2026-11-16 09:00:00+00:00'));

    $repositorio->guardarCambio($estado, $cerrado);

    $historial = NoNulo::exigir(DB::table('estados_disponibilidad_historial')->where('estado_id', $estado->id()->valor())->where('version', 2)->first(), 'el historial');
    expect($repositorio->vigente(Identificador::desdeTexto($m['f1'])))->toBeNull()
        ->and(DB::table('estados_disponibilidad')->where('id', $estado->id()->valor())->value('vigente_hasta'))->toBe('2026-11-16 09:00:00+00')
        ->and(DB::table('estados_disponibilidad')->where('id', $estado->id()->valor())->value('actualizado_por'))->toBe($m['e2'])
        ->and($historial->operacion)->toBe('actualizar')
        ->and(json_decode((string) $historial->datos_anteriores, true)['vigente_hasta'])->toBeNull()
        ->and(json_decode((string) $historial->datos_nuevos, true)['vigente_hasta'])->toBe('2026-11-16T09:00:00+00:00');
});

test('§6.3: dos estados vigentes del mismo jugador responden EstadoConcurrente', function () {
    $m = saludConSesionDeE2();
    $repositorio = app(RepositorioDeEstados::class);
    $repositorio->guardarNuevo(estadoParaGuardar($m['f1'], $m['e2']));

    expect(fn () => $repositorio->guardarNuevo(estadoParaGuardar($m['f1'], $m['e2'])))->toThrow(EstadoConcurrente::class);
});

test('DA-79: las lecturas para actualizar bloquean la fila: una segunda conexión no la obtiene mientras la primera la tiene', function (string $lectura) {
    $m = saludConSesionDeE2();
    $lesion = lesionParaGuardar($m['f1'], $m['e2']);
    app(RepositorioDeLesiones::class)->guardarNueva($lesion);
    app(RepositorioDeEstados::class)->guardarNuevo(estadoParaGuardar($m['f1'], $m['e2']));
    config(['database.connections.salud_concurrente' => config('database.connections.pgsql')]);
    $principal = DB::connection();
    $otra = DB::connection('salud_concurrente');
    $leer = function (ConnectionInterface $conexion) use ($lectura, $lesion, $m): mixed {
        $historial = new EscritorDeHistorial($conexion, app(Reloj::class), app(UsuarioActual::class));

        return $lectura === 'estado'
            ? (new RepositorioDeEstadosSql($conexion, $historial, app(UsuarioActual::class)))->vigenteParaActualizar(Identificador::desdeTexto($m['f1']))
            : (new RepositorioDeLesionesSql($conexion, $historial, app(UsuarioActual::class)))->buscarParaActualizar($lesion->id());
    };

    $principal->beginTransaction();
    $leidoPorA = $leer($principal);
    $otra->beginTransaction();
    $otra->statement("set local lock_timeout = '200ms'");
    $espera = null;
    try {
        $leer($otra);
    } catch (QueryException $e) {
        $espera = $e;
    }
    $otra->rollBack();
    $principal->rollBack();
    DB::purge('salud_concurrente');

    // 55P03 (lock_not_available): sin el bloqueo, la segunda conexión leería la fila sin esperar.
    expect($leidoPorA)->not->toBeNull()
        ->and($espera?->getCode())->toBe('55P03');
})->with(['estado', 'lesion']);
