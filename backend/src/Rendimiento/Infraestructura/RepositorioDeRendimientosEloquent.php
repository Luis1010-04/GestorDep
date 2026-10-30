<?php

namespace Src\Rendimiento\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Rendimiento\Aplicacion\RendimientoDuplicado;
use Src\Rendimiento\Dominio\DatosDeJuego;
use Src\Rendimiento\Dominio\RendimientoDePartido;
use Src\Rendimiento\Dominio\RepositorioDeRendimientos;
use Src\Rendimiento\Dominio\TipoDeDato;
use Src\Rendimiento\Dominio\ValorDeMetrica;
use stdClass;

/**
 * Escribe `rendimientos_partido` y `valores_metrica`, cada fila con su historial, en una sola transacción (RNF-02). Una
 * corrección de métricas solo toca los valores que cambian: borra lógicamente los que salen, actualiza los que cambian
 * de valor e inserta los nuevos.
 */
final class RepositorioDeRendimientosEloquent implements RepositorioDeRendimientos
{
    public const COLUMNAS = [
        'id', 'partido_id', 'jugador_id', 'equipo_id', 'registrado_por', 'posicion_id', 'minutos', 'fue_titular',
        'tarjetas_amarillas', 'tarjetas_rojas', 'calificacion_general', 'observacion', 'actualizado_en',
    ];

    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function existeDe(Identificador $partidoId, Identificador $jugadorId): bool
    {
        return $this->conexion->table('rendimientos_partido')
            ->whereNull('eliminado_en')
            ->where('partido_id', $partidoId->valor())
            ->where('jugador_id', $jugadorId->valor())
            ->exists();
    }

    public function guardarNuevo(RendimientoDePartido $rendimiento): void
    {
        $instante = $rendimiento->actualizadoEn();
        $autoria = $this->autoria($instante, nueva: true);

        try {
            $this->conexion->transaction(function () use ($rendimiento, $instante, $autoria): void {
                $this->historial->registrar(
                    'rendimientos_partido', 'rendimiento_id', $rendimiento->id(), 'crear', null, self::instantanea($rendimiento),
                    fn () => $this->conexion->table('rendimientos_partido')->insert(['id' => $rendimiento->id()->valor(), ...self::instantanea($rendimiento), ...$autoria]),
                    instante: $instante,
                );
                foreach ($rendimiento->valores() as $valor) {
                    $this->insertarValor($rendimiento->id(), $valor, $instante);
                }
            });
        } catch (UniqueConstraintViolationException) {
            throw new RendimientoDuplicado;
        }
    }

    public function buscar(Identificador $id): ?RendimientoDePartido
    {
        $fila = $this->conexion->table('rendimientos_partido')->whereNull('eliminado_en')->where('id', $id->valor())->first(self::COLUMNAS);

        return $fila === null ? null : $this->aRendimiento($fila);
    }

    public function actualizar(RendimientoDePartido $anterior, RendimientoDePartido $nuevo): void
    {
        $instante = $nuevo->actualizadoEn();

        $this->conexion->transaction(function () use ($anterior, $nuevo, $instante): void {
            $this->historial->registrar(
                'rendimientos_partido', 'rendimiento_id', $nuevo->id(), 'actualizar', self::instantanea($anterior), self::instantanea($nuevo),
                fn () => $this->conexion->table('rendimientos_partido')->where('id', $nuevo->id()->valor())->update([
                    ...self::datosDeJuego($nuevo->datos()), ...$this->autoria($instante, nueva: false),
                ]),
                instante: $instante,
            );

            if ($nuevo->valores() !== $anterior->valores()) {
                $this->reemplazarValores($nuevo, $instante);
            }
        });
    }

    /**
     * Rendimientos del partido, con sus valores. La consulta llega ya filtrada (registrante y alcance).
     *
     * @param  iterable<stdClass>  $filas
     * @return list<RendimientoDePartido>
     */
    public function aRendimientos(iterable $filas): array
    {
        $rendimientos = [];
        foreach ($filas as $fila) {
            $rendimientos[] = $this->aRendimiento($fila);
        }

        return $rendimientos;
    }

    private function reemplazarValores(RendimientoDePartido $rendimiento, DateTimeImmutable $instante): void
    {
        $actuales = $this->conexion->table('valores_metrica')
            ->whereNull('eliminado_en')
            ->where('rendimiento_id', $rendimiento->id()->valor())
            ->get(['id', 'metrica_id', 'valor'])
            ->keyBy('metrica_id');

        foreach ($rendimiento->valores() as $valor) {
            $actual = $actuales->pull($valor->metricaId()->valor());
            if ($actual === null) {
                $this->insertarValor($rendimiento->id(), $valor, $instante);
            } elseif (! NumeroDecimal::desdeTexto($actual->valor)->igualA($valor->valor())) {
                $id = Identificador::desdeTexto($actual->id);
                $this->historial->registrar(
                    'valores_metrica', 'valor_metrica_id', $id, 'actualizar',
                    self::instantaneaDeValor($rendimiento->id(), $valor->metricaId(), NumeroDecimal::desdeTexto($actual->valor)),
                    self::instantaneaDeValor($rendimiento->id(), $valor->metricaId(), $valor->valor()),
                    fn () => $this->conexion->table('valores_metrica')->where('id', $id->valor())->update([
                        'valor' => $valor->valor()->comoCadena(), ...$this->autoria($instante, nueva: false),
                    ]),
                    instante: $instante,
                );
            }
        }

        foreach ($actuales as $sale) {
            $id = Identificador::desdeTexto($sale->id);
            $metrica = Identificador::desdeTexto($sale->metrica_id);
            $this->historial->registrar(
                'valores_metrica', 'valor_metrica_id', $id, 'eliminar',
                self::instantaneaDeValor($rendimiento->id(), $metrica, NumeroDecimal::desdeTexto($sale->valor)), null,
                fn () => $this->conexion->table('valores_metrica')->where('id', $id->valor())->update([
                    'eliminado_en' => $instante->format('Y-m-d H:i:sP'),
                    'eliminado_por' => $this->usuarioActual->identificador()?->valor(),
                ]),
                instante: $instante,
            );
        }
    }

    private function insertarValor(Identificador $rendimientoId, ValorDeMetrica $valor, DateTimeImmutable $instante): void
    {
        $id = Identificador::generar();
        $instantanea = self::instantaneaDeValor($rendimientoId, $valor->metricaId(), $valor->valor());

        $this->historial->registrar(
            'valores_metrica', 'valor_metrica_id', $id, 'crear', null, $instantanea,
            fn () => $this->conexion->table('valores_metrica')->insert(['id' => $id->valor(), ...$instantanea, ...$this->autoria($instante, nueva: true)]),
            instante: $instante,
        );
    }

    /** @return array<string, string|null> */
    private function autoria(DateTimeImmutable $instante, bool $nueva): array
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $momento = $instante->format('Y-m-d H:i:sP');

        return $nueva
            ? ['creado_en' => $momento, 'actualizado_en' => $momento, 'creado_por' => $autor, 'actualizado_por' => $autor]
            : ['actualizado_en' => $momento, 'actualizado_por' => $autor];
    }

    /** @return array<string, mixed> */
    private static function instantanea(RendimientoDePartido $rendimiento): array
    {
        return [
            'partido_id' => $rendimiento->partidoId()->valor(),
            'jugador_id' => $rendimiento->jugadorId()->valor(),
            'equipo_id' => $rendimiento->equipoId()->valor(),
            'registrado_por' => $rendimiento->registradoPor()->valor(),
            'posicion_id' => $rendimiento->posicionId()->valor(),
            ...self::datosDeJuego($rendimiento->datos()),
        ];
    }

    /** @return array{minutos: int, fue_titular: bool, tarjetas_amarillas: int, tarjetas_rojas: int, calificacion_general: int, observacion: string|null} */
    private static function datosDeJuego(DatosDeJuego $datos): array
    {
        return [
            'minutos' => $datos->minutos(),
            'fue_titular' => $datos->fueTitular(),
            'tarjetas_amarillas' => $datos->tarjetasAmarillas(),
            'tarjetas_rojas' => $datos->tarjetasRojas(),
            'calificacion_general' => $datos->calificacionGeneral(),
            'observacion' => $datos->observacion(),
        ];
    }

    /** @return array{rendimiento_id: string, metrica_id: string, valor: string} */
    private static function instantaneaDeValor(Identificador $rendimientoId, Identificador $metricaId, NumeroDecimal $valor): array
    {
        return ['rendimiento_id' => $rendimientoId->valor(), 'metrica_id' => $metricaId->valor(), 'valor' => $valor->comoCadena()];
    }

    private function aRendimiento(stdClass $fila): RendimientoDePartido
    {
        $valores = $this->conexion->table('valores_metrica')
            ->join('metricas', 'metricas.id', '=', 'valores_metrica.metrica_id')
            ->leftJoin('metricas_posicion', function ($union) use ($fila): void {
                $union->on('metricas_posicion.metrica_id', '=', 'valores_metrica.metrica_id')->where('metricas_posicion.posicion_id', '=', $fila->posicion_id);
            })
            ->whereNull('valores_metrica.eliminado_en')
            ->where('valores_metrica.rendimiento_id', $fila->id)
            ->orderBy('metricas_posicion.orden')
            ->orderBy('metricas.codigo')
            ->get(['valores_metrica.metrica_id', 'valores_metrica.valor', 'metricas.tipo_dato'])
            ->map(fn (stdClass $valor) => new ValorDeMetrica(
                Identificador::desdeTexto($valor->metrica_id), NumeroDecimal::desdeTexto($valor->valor), TipoDeDato::from($valor->tipo_dato),
            ))
            ->all();

        return new RendimientoDePartido(
            Identificador::desdeTexto($fila->id),
            Identificador::desdeTexto($fila->partido_id),
            Identificador::desdeTexto($fila->jugador_id),
            Identificador::desdeTexto($fila->equipo_id),
            Identificador::desdeTexto($fila->registrado_por),
            Identificador::desdeTexto($fila->posicion_id),
            new DatosDeJuego($fila->minutos, $fila->fue_titular, $fila->tarjetas_amarillas, $fila->tarjetas_rojas, $fila->calificacion_general, $fila->observacion),
            array_values($valores),
            (new DateTimeImmutable($fila->actualizado_en))->setTimezone(new DateTimeZone('UTC')),
        );
    }
}
