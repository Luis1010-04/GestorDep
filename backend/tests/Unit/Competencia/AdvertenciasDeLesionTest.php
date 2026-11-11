<?php

use Src\Compartido\Aplicacion\EstadoDeDisponibilidadOficial;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Aplicacion\AdvertenciasDeLesion;

/**
 * Puerto falso: responde con los estados dados y cuenta las consultas.
 *
 * @param  array<string, string>  $estados  estado por identificador; sin entrada, `apto`
 * @return EstadoDeDisponibilidadOficial&object{consultas: list<list<string>>}
 */
function advertenciasPuerto(array $estados): EstadoDeDisponibilidadOficial
{
    return new class($estados) implements EstadoDeDisponibilidadOficial
    {
        /** @var list<list<string>> */
        public array $consultas = [];

        /** @param  array<string, string>  $estados */
        public function __construct(private readonly array $estados) {}

        public function de(array $jugadores): array
        {
            $ids = array_map(fn (Identificador $jugador) => $jugador->valor(), $jugadores);
            $this->consultas[] = $ids;

            return array_combine($ids, array_map(fn (string $id) => $this->estados[$id] ?? 'apto', $ids));
        }
    };
}

test('DA-15: en la asistencia, una advertencia por cada lesionado, con su posición en la petición desde 0', function () {
    [$j1, $f1, $j2] = [Identificador::generar(), Identificador::generar(), Identificador::generar()];
    $puerto = advertenciasPuerto([$j1->valor() => 'lesionado', $j2->valor() => 'lesionado']);

    $advertencias = (new AdvertenciasDeLesion($puerto))->deAsistencia([$j1, $f1, $j2]);

    expect($advertencias)->toBe([
        ['codigo' => 'jugador_lesionado', 'campo' => 'registros.0.jugador_id'],
        ['codigo' => 'jugador_lesionado', 'campo' => 'registros.2.jugador_id'],
    ]);
});

test('DA-15: solo lesionado advierte; con_molestia y apto no', function () {
    [$molestia, $apto] = [Identificador::generar(), Identificador::generar()];
    $puerto = advertenciasPuerto([$molestia->valor() => 'con_molestia', $apto->valor() => 'apto']);

    expect((new AdvertenciasDeLesion($puerto))->deAsistencia([$molestia, $apto]))->toBe([]);
});

test('Una sola consulta al puerto por petición, con la lista completa en orden', function () {
    $jugadores = [Identificador::generar(), Identificador::generar(), Identificador::generar()];
    $puerto = advertenciasPuerto([]);

    (new AdvertenciasDeLesion($puerto))->deAsistencia($jugadores);

    expect($puerto->consultas)->toBe([array_map(fn (Identificador $jugador) => $jugador->valor(), $jugadores)]);
});

test('Sin jugadores no hay advertencias ni consulta al puerto', function () {
    $puerto = advertenciasPuerto([]);

    expect((new AdvertenciasDeLesion($puerto))->deAsistencia([]))->toBe([])
        ->and($puerto->consultas)->toBe([]);
});

test('DA-15: en la convocatoria, la advertencia del lesionado señala jugador_id', function () {
    $f1 = Identificador::generar();
    $puerto = advertenciasPuerto([$f1->valor() => 'lesionado']);

    expect((new AdvertenciasDeLesion($puerto))->deConvocatoria($f1))->toBe([['codigo' => 'jugador_lesionado', 'campo' => 'jugador_id']])
        ->and($puerto->consultas)->toBe([[$f1->valor()]]);
});

test('DA-15: en la convocatoria, un jugador que no está lesionado no advierte', function (string $estado) {
    $f1 = Identificador::generar();

    expect((new AdvertenciasDeLesion(advertenciasPuerto([$f1->valor() => $estado])))->deConvocatoria($f1))->toBe([]);
})->with(['apto', 'con_molestia']);
