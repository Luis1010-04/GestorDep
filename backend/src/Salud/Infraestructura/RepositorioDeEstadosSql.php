<?php

namespace Src\Salud\Infraestructura;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Salud\Aplicacion\EstadoConcurrente;
use Src\Salud\Dominio\EstadoOficial;
use Src\Salud\Dominio\RepositorioDeEstados;
use Src\Salud\Dominio\TipoDeEstado;
use stdClass;

/** Lee y escribe `estados_disponibilidad`; cada cambio lleva su historial en la misma transacción (RNF-02). */
final class RepositorioDeEstadosSql implements RepositorioDeEstados
{
    private const COLUMNAS = ['id', 'jugador_id', 'estado', 'lesion_id', 'fecha_estimada_regreso', 'confirmado_por', 'vigente_desde', 'vigente_hasta', 'actualizado_en'];

    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function vigente(Identificador $jugadorId): ?EstadoOficial
    {
        $fila = $this->vigentes($jugadorId)->first(self::COLUMNAS);

        return $fila === null ? null : self::aEstado($fila);
    }

    public function vigenteParaActualizar(Identificador $jugadorId): ?EstadoOficial
    {
        $fila = $this->vigentes($jugadorId)->lockForUpdate()->first(self::COLUMNAS);

        return $fila === null ? null : self::aEstado($fila);
    }

    public function guardarNuevo(EstadoOficial $estado): void
    {
        $momento = $estado->actualizadoEn()->format('Y-m-d H:i:sP');
        $autor = $this->usuarioActual->identificador()?->valor();
        $instantanea = self::instantanea($estado);

        try {
            $this->historial->registrar(
                'estados_disponibilidad', 'estado_id', $estado->id(), 'crear', null, $instantanea,
                fn () => $this->conexion->table('estados_disponibilidad')->insert([
                    'id' => $estado->id()->valor(), ...$instantanea,
                    'creado_en' => $momento, 'actualizado_en' => $momento, 'creado_por' => $autor, 'actualizado_por' => $autor,
                ]),
                instante: $estado->vigenteDesde(),
            );
        } catch (UniqueConstraintViolationException) {
            throw new EstadoConcurrente;
        }
    }

    public function guardarCambio(EstadoOficial $anterior, EstadoOficial $nuevo): void
    {
        $instante = $nuevo->actualizadoEn();

        $this->historial->registrar(
            'estados_disponibilidad', 'estado_id', $nuevo->id(), 'actualizar', self::instantanea($anterior), self::instantanea($nuevo),
            fn () => $this->conexion->table('estados_disponibilidad')->where('id', $nuevo->id()->valor())->update([
                'fecha_estimada_regreso' => $nuevo->fechaEstimadaRegreso(),
                'vigente_hasta' => $nuevo->vigenteHasta()?->format(DateTimeInterface::ATOM),
                'actualizado_en' => $instante->format('Y-m-d H:i:sP'),
                'actualizado_por' => $this->usuarioActual->identificador()?->valor(),
            ]),
            instante: $instante,
        );
    }

    private function vigentes(Identificador $jugadorId): Builder
    {
        return $this->conexion->table('estados_disponibilidad')->where('jugador_id', $jugadorId->valor())->whereNull('vigente_hasta');
    }

    /** @return array{jugador_id: string, estado: string, lesion_id: string|null, fecha_estimada_regreso: string|null, confirmado_por: string, vigente_desde: string, vigente_hasta: string|null} */
    private static function instantanea(EstadoOficial $estado): array
    {
        return [
            'jugador_id' => $estado->jugadorId()->valor(),
            'estado' => $estado->tipo()->value,
            'lesion_id' => $estado->lesionId()?->valor(),
            'fecha_estimada_regreso' => $estado->fechaEstimadaRegreso(),
            'confirmado_por' => $estado->confirmadoPor()->valor(),
            'vigente_desde' => $estado->vigenteDesde()->format(DateTimeInterface::ATOM),
            'vigente_hasta' => $estado->vigenteHasta()?->format(DateTimeInterface::ATOM),
        ];
    }

    private static function aEstado(stdClass $fila): EstadoOficial
    {
        $utc = new DateTimeZone('UTC');

        return new EstadoOficial(
            Identificador::desdeTexto($fila->id),
            Identificador::desdeTexto($fila->jugador_id),
            TipoDeEstado::from($fila->estado),
            $fila->lesion_id === null ? null : Identificador::desdeTexto($fila->lesion_id),
            $fila->fecha_estimada_regreso,
            Identificador::desdeTexto($fila->confirmado_por),
            (new DateTimeImmutable($fila->vigente_desde))->setTimezone($utc),
            $fila->vigente_hasta === null ? null : (new DateTimeImmutable($fila->vigente_hasta))->setTimezone($utc),
            (new DateTimeImmutable($fila->actualizado_en))->setTimezone($utc),
        );
    }
}
