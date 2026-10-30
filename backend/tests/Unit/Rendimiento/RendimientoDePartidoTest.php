<?php

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Rendimiento\Dominio\DatosDeJuego;
use Src\Rendimiento\Dominio\Metrica;
use Src\Rendimiento\Dominio\MetricaDeLaPosicion;
use Src\Rendimiento\Dominio\MetricaNoAplicaAPosicion;
use Src\Rendimiento\Dominio\MetricaObligatoriaFaltante;
use Src\Rendimiento\Dominio\MetricasDeLaPosicion;
use Src\Rendimiento\Dominio\ReglaDeCampoViolada;
use Src\Rendimiento\Dominio\RendimientoDePartido;
use Src\Rendimiento\Dominio\TipoDeDato;
use Src\Rendimiento\Dominio\ValorDeMetrica;
use Src\Rendimiento\Dominio\ValorFueraDeRango;

/** @param  array<string, mixed>  $cambios */
function datosDeJuegoDePrueba(array $cambios = []): DatosDeJuego
{
    $datos = ['minutos' => 90, 'fueTitular' => true, 'tarjetasAmarillas' => 0, 'tarjetasRojas' => 0, 'calificacionGeneral' => 7, 'observacion' => null, ...$cambios];

    return new DatosDeJuego(...$datos);
}

function campoRechazadoEnRendimiento(callable $operacion): ?string
{
    try {
        $operacion();
    } catch (ReglaDeCampoViolada $e) {
        return $e->codigo().' '.$e->campo();
    }

    return null;
}

function metricaDePrueba(TipoDeDato $tipo = TipoDeDato::Entero, string $minimo = '0', string $maximo = '99'): Metrica
{
    return new Metrica(Identificador::generar(), $tipo, NumeroDecimal::desdeTexto($minimo), NumeroDecimal::desdeTexto($maximo));
}

/** @return array{metrica: Identificador, valor: NumeroDecimal} */
function entradaDeMetrica(Metrica|Identificador $metrica, string $valor): array
{
    return ['metrica' => $metrica instanceof Metrica ? $metrica->id() : $metrica, 'valor' => NumeroDecimal::desdeTexto($valor)];
}

test('HU-14 (§3.3): los datos de juego aceptan los extremos de cada rango', function () {
    $minimos = datosDeJuegoDePrueba(['minutos' => 0, 'tarjetasAmarillas' => 0, 'tarjetasRojas' => 0, 'calificacionGeneral' => 1, 'observacion' => '']);
    $maximos = datosDeJuegoDePrueba(['minutos' => 150, 'fueTitular' => false, 'tarjetasAmarillas' => 2, 'tarjetasRojas' => 1, 'calificacionGeneral' => 10, 'observacion' => str_repeat('ñ', 500)]);

    expect([$minimos->minutos(), $minimos->tarjetasAmarillas(), $minimos->tarjetasRojas(), $minimos->calificacionGeneral(), $minimos->observacion()])->toBe([0, 0, 0, 1, ''])
        ->and([$maximos->minutos(), $maximos->fueTitular(), $maximos->tarjetasAmarillas(), $maximos->tarjetasRojas(), $maximos->calificacionGeneral()])->toBe([150, false, 2, 1, 10])
        ->and(mb_strlen((string) $maximos->observacion()))->toBe(500);
});

test('HU-14 (§3.3, RNF-03): un dato de juego fuera de rango lanza valor_fuera_de_rango en su campo', function (array $cambios, string $campo) {
    expect(campoRechazadoEnRendimiento(fn () => datosDeJuegoDePrueba($cambios)))->toBe("valor_fuera_de_rango $campo");
})->with([
    'minutos -1' => [['minutos' => -1], 'minutos'],
    'minutos 151' => [['minutos' => 151], 'minutos'],
    'amarillas -1' => [['tarjetasAmarillas' => -1], 'tarjetas_amarillas'],
    'amarillas 3' => [['tarjetasAmarillas' => 3], 'tarjetas_amarillas'],
    'rojas -1' => [['tarjetasRojas' => -1], 'tarjetas_rojas'],
    'rojas 2' => [['tarjetasRojas' => 2], 'tarjetas_rojas'],
    'calificación 0' => [['calificacionGeneral' => 0], 'calificacion_general'],
    'calificación 11' => [['calificacionGeneral' => 11], 'calificacion_general'],
    'observación de 501' => [['observacion' => str_repeat('ñ', 501)], 'observacion'],
    'varios: se señala el primero' => [['minutos' => 151, 'calificacionGeneral' => 11], 'minutos'],
]);

test('HU-14: corregir los datos de juego cambia solo lo indicado y vuelve a validar', function () {
    $datos = datosDeJuegoDePrueba(['observacion' => 'Bien']);

    $corregidos = $datos->con(['calificacion_general' => 9, 'observacion' => null]);

    expect([$corregidos->minutos(), $corregidos->fueTitular(), $corregidos->calificacionGeneral(), $corregidos->observacion()])->toBe([90, true, 9, null])
        ->and($datos->calificacionGeneral())->toBe(7)
        ->and($datos->con(['minutos' => 10, 'fue_titular' => false, 'tarjetas_amarillas' => 1, 'tarjetas_rojas' => 1])->tarjetasRojas())->toBe(1)
        ->and(campoRechazadoEnRendimiento(fn () => $datos->con(['tarjetas_rojas' => 2])))->toBe('valor_fuera_de_rango tarjetas_rojas');
});

test('HU-14 (DA-07): una métrica admite valores dentro de su mínimo y máximo, enteros si es entera', function () {
    $entera = metricaDePrueba();
    $decimal = metricaDePrueba(TipoDeDato::Decimal, '0', '10');

    expect($entera->admite(NumeroDecimal::desdeTexto('0')))->toBeTrue()
        ->and($entera->admite(NumeroDecimal::desdeTexto('99')))->toBeTrue()
        ->and($entera->admite(NumeroDecimal::desdeTexto('99.00')))->toBeTrue()
        ->and($entera->admite(NumeroDecimal::desdeTexto('100')))->toBeFalse()
        ->and($entera->admite(NumeroDecimal::desdeTexto('-1')))->toBeFalse()
        ->and($entera->admite(NumeroDecimal::desdeTexto('2.5')))->toBeFalse()
        ->and($decimal->admite(NumeroDecimal::desdeTexto('2.5')))->toBeTrue()
        ->and($decimal->admite(NumeroDecimal::desdeTexto('10.01')))->toBeFalse()
        ->and($entera->tipo())->toBe(TipoDeDato::Entero);
});

test('HU-14: con las obligatorias dentro de rango, devuelve un valor por métrica en el orden recibido', function () {
    $goles = metricaDePrueba();
    $atajadas = metricaDePrueba();
    $opcional = metricaDePrueba(TipoDeDato::Decimal);
    $posicion = new MetricasDeLaPosicion([new MetricaDeLaPosicion($goles, true), new MetricaDeLaPosicion($atajadas, true), new MetricaDeLaPosicion($opcional, false)]);

    $valores = $posicion->valores([entradaDeMetrica($atajadas, '3'), entradaDeMetrica($goles, '1')]);

    expect(array_map(fn (ValorDeMetrica $valor) => [$valor->metricaId()->valor(), $valor->valor()->comoCadena(), $valor->tipo()], $valores))->toBe([
        [$atajadas->id()->valor(), '3.00', TipoDeDato::Entero],
        [$goles->id()->valor(), '1.00', TipoDeDato::Entero],
    ])
        ->and(count($posicion->valores([entradaDeMetrica($atajadas, '3'), entradaDeMetrica($goles, '1'), entradaDeMetrica($opcional, '2.5')])))->toBe(3);
});

test('HU-14 (§3.3): una métrica que no es de la posición lanza metrica_no_aplica_a_posicion en su índice', function () {
    $goles = metricaDePrueba();
    $posicion = new MetricasDeLaPosicion([new MetricaDeLaPosicion($goles, true)]);

    expect(campoRechazadoEnRendimiento(fn () => $posicion->valores([entradaDeMetrica($goles, '1'), entradaDeMetrica(Identificador::generar(), '1')])))->toBe('metrica_no_aplica_a_posicion metricas.1.metrica_id')
        ->and(campoRechazadoEnRendimiento(fn () => $posicion->valores([entradaDeMetrica(Identificador::generar(), '1')])))->toBe('metrica_no_aplica_a_posicion metricas.0.metrica_id');
});

test('HU-14 (§3.3): si falta una obligatoria lanza metrica_obligatoria_faltante en metricas', function () {
    $goles = metricaDePrueba();
    $asistencias = metricaDePrueba();
    $posicion = new MetricasDeLaPosicion([new MetricaDeLaPosicion($goles, true), new MetricaDeLaPosicion($asistencias, true)]);

    expect(campoRechazadoEnRendimiento(fn () => $posicion->valores([entradaDeMetrica($goles, '1')])))->toBe('metrica_obligatoria_faltante metricas')
        ->and(campoRechazadoEnRendimiento(fn () => $posicion->valores([])))->toBe('metrica_obligatoria_faltante metricas');
});

test('HU-14 (§3.3): un valor fuera del rango de su métrica lanza valor_fuera_de_rango en metricas.N.valor', function () {
    $goles = metricaDePrueba();
    $pases = metricaDePrueba(maximo: '300');
    $posicion = new MetricasDeLaPosicion([new MetricaDeLaPosicion($goles, true), new MetricaDeLaPosicion($pases, true)]);

    expect(campoRechazadoEnRendimiento(fn () => $posicion->valores([entradaDeMetrica($goles, '1'), entradaDeMetrica($pases, '301')])))->toBe('valor_fuera_de_rango metricas.1.valor')
        ->and(campoRechazadoEnRendimiento(fn () => $posicion->valores([entradaDeMetrica($goles, '1.5'), entradaDeMetrica($pases, '300')])))->toBe('valor_fuera_de_rango metricas.0.valor')
        ->and(campoRechazadoEnRendimiento(fn () => $posicion->valores([entradaDeMetrica($goles, '0'), entradaDeMetrica($pases, '300')])))->toBeNull();
});

test('HU-14: primero se señala la métrica que no aplica, luego la obligatoria que falta y después el rango', function () {
    $goles = metricaDePrueba();
    $asistencias = metricaDePrueba();
    $posicion = new MetricasDeLaPosicion([new MetricaDeLaPosicion($goles, true), new MetricaDeLaPosicion($asistencias, true)]);

    expect(campoRechazadoEnRendimiento(fn () => $posicion->valores([entradaDeMetrica($goles, '100'), entradaDeMetrica(Identificador::generar(), '1')])))->toBe('metrica_no_aplica_a_posicion metricas.1.metrica_id')
        ->and(campoRechazadoEnRendimiento(fn () => $posicion->valores([entradaDeMetrica($goles, '100')])))->toBe('metrica_obligatoria_faltante metricas');
});

test('HU-14: corregir un rendimiento conserva su identidad y reemplaza los valores solo si se indican', function () {
    $valor = new ValorDeMetrica(Identificador::generar(), NumeroDecimal::desdeTexto('1'), TipoDeDato::Entero);
    $utc = new DateTimeZone('UTC');
    $rendimiento = new RendimientoDePartido(
        Identificador::generar(), Identificador::generar(), Identificador::generar(), Identificador::generar(), Identificador::generar(),
        Identificador::generar(), datosDeJuegoDePrueba(), [$valor], new DateTimeImmutable('2026-09-20 15:00:00', $utc),
    );
    $instante = new DateTimeImmutable('2026-09-21 10:00:00', $utc);
    $otro = new ValorDeMetrica(Identificador::generar(), NumeroDecimal::desdeTexto('4'), TipoDeDato::Entero);

    $sinValores = $rendimiento->corregido(datosDeJuegoDePrueba(['calificacionGeneral' => 9]), null, $instante);
    $conValores = $rendimiento->corregido($rendimiento->datos(), [$otro], $instante);

    expect($sinValores->id())->toBe($rendimiento->id())
        ->and([$sinValores->partidoId(), $sinValores->jugadorId(), $sinValores->equipoId(), $sinValores->registradoPor(), $sinValores->posicionId()])
        ->toBe([$rendimiento->partidoId(), $rendimiento->jugadorId(), $rendimiento->equipoId(), $rendimiento->registradoPor(), $rendimiento->posicionId()])
        ->and($sinValores->datos()->calificacionGeneral())->toBe(9)
        ->and($sinValores->valores())->toBe([$valor])
        ->and($sinValores->actualizadoEn())->toBe($instante)
        ->and($conValores->valores())->toBe([$otro])
        ->and($rendimiento->datos()->calificacionGeneral())->toBe(7);
});

test('HU-14: las excepciones de campo llevan su código estable', function () {
    expect((new MetricaObligatoriaFaltante)->campo())->toBe('metricas')
        ->and((new MetricaNoAplicaAPosicion('metricas.2.metrica_id'))->codigo())->toBe('metrica_no_aplica_a_posicion')
        ->and((new ValorFueraDeRango('minutos'))->codigo())->toBe('valor_fuera_de_rango');
});
