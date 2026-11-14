<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Aplicacion\EstadoDeDisponibilidadOficial;
use Src\Compartido\Dominio\Identificador;

/** @return array<string, string> */
function estadoOficialDe(string ...$jugadores): array
{
    return app(EstadoDeDisponibilidadOficial::class)->de(array_values(array_map(fn (string $id) => Identificador::desdeTexto($id), $jugadores)));
}

test('DA-01 (DA-36): sin ningún registro, el jugador está apto', function () {
    $m = avisosMundo();

    expect(estadoOficialDe($m['f1']))->toBe([$m['f1'] => 'apto']);
});

test('HU-11 c2 (DA-08): el puerto devuelve el estado vigente, sea cual sea', function (?string $tipo, string $esperado) {
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e1'], $tipo);

    expect(estadoOficialDe($m['f1']))->toBe([$m['f1'] => $esperado]);
})->with([
    'apto explícito' => [null, 'apto'],
    'con molestia' => ['con_molestia', 'con_molestia'],
    'lesionado' => ['lesionado', 'lesionado'],
]);

test('DA-08: un estado cerrado no cuenta: sin otro vigente, el jugador está apto', function () {
    $m = avisosMundo();
    $estado = estadoInsertar($m['f1'], $m['e1'])['estado'];
    DB::connection('propietario')->table('estados_disponibilidad')->where('id', $estado)->update(['vigente_hasta' => '2026-11-12 10:00:00+00']);

    expect(estadoOficialDe($m['f1']))->toBe([$m['f1'] => 'apto']);
});

test('DA-08: con un estado cerrado y otro vigente, cuenta el vigente', function () {
    $m = avisosMundo();
    $estado = estadoInsertar($m['f1'], $m['e1'], 'con_molestia')['estado'];
    DB::connection('propietario')->table('estados_disponibilidad')->where('id', $estado)->update(['vigente_hasta' => '2026-11-12 10:00:00+00']);
    $lesion = lesionInsertar($m['f1'], $m['e1']);
    mundoInsertar('estados_disponibilidad', [
        'jugador_id' => $m['f1'], 'estado' => 'lesionado', 'lesion_id' => $lesion, 'fecha_estimada_regreso' => '2026-11-30',
        'confirmado_por' => $m['e1'], 'vigente_desde' => '2026-11-12 10:00:00+00',
    ]);

    expect(estadoOficialDe($m['f1']))->toBe([$m['f1'] => 'lesionado']);
});

test('DA-08: varios jugadores se resuelven en una sola consulta, cada uno con su estado', function () {
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e1']);
    estadoInsertar($m['jb'], $m['e1'], 'con_molestia');
    $consultas = 0;
    DB::listen(function () use (&$consultas) {
        $consultas++;
    });

    $estados = estadoOficialDe($m['f1'], $m['j1'], $m['jb']);

    expect($estados)->toHaveCount(3)
        ->and($estados[$m['f1']])->toBe('lesionado')
        ->and($estados[$m['j1']])->toBe('apto')
        ->and($estados[$m['jb']])->toBe('con_molestia')
        ->and($consultas)->toBe(1);
});

test('DA-08: la consulta se limita a los jugadores pedidos', function () {
    $m = avisosMundo();
    $consultas = [];
    DB::listen(function ($consulta) use (&$consultas) {
        $consultas[] = $consulta;
    });

    estadoOficialDe($m['f1']);

    expect($consultas)->toHaveCount(1)
        ->and($consultas[0]->sql)->toContain('jugador_id')
        ->and($consultas[0]->bindings)->toBe([$m['f1']]);
});

test('DA-36: un identificador sin registro responde apto, también si el jugador no existe', function () {
    avisosMundo();
    $inexistente = Identificador::generar()->valor();

    expect(estadoOficialDe($inexistente))->toBe([$inexistente => 'apto']);
});

test('DA-37: el puerto no filtra por eliminado_en, igual que RepositorioDeEstados', function () {
    $m = avisosMundo();
    $estado = estadoInsertar($m['f1'], $m['e1'])['estado'];
    DB::connection('propietario')->table('estados_disponibilidad')->where('id', $estado)->update(['eliminado_en' => '2026-11-12 10:00:00+00']);

    expect(estadoOficialDe($m['f1']))->toBe([$m['f1'] => 'lesionado']);
});

test('DA-08: una lista vacía no consulta la base', function () {
    avisosMundo();
    $consultas = 0;
    DB::listen(function () use (&$consultas) {
        $consultas++;
    });

    expect(estadoOficialDe())->toBe([])
        ->and($consultas)->toBe(0);
});

test('DA-08: el adaptador es de solo lectura', function () {
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e1']);
    $antes = DB::table('estados_disponibilidad')->count();

    estadoOficialDe($m['f1'], $m['j1']);

    expect(DB::table('estados_disponibilidad')->count())->toBe($antes)
        ->and(DB::table('estados_disponibilidad_historial')->count())->toBe(0);
});
