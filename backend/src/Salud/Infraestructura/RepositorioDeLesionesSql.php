<?php

namespace Src\Salud\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Salud\Aplicacion\EstadoConcurrente;
use Src\Salud\Dominio\CausaDeLesion;
use Src\Salud\Dominio\Lesion;
use Src\Salud\Dominio\RepositorioDeLesiones;
use stdClass;

/** Lee y escribe `lesiones`; cada cambio lleva su historial en la misma transacción (RNF-02). */
final class RepositorioDeLesionesSql implements RepositorioDeLesiones
{
    private const COLUMNAS = [
        'id', 'jugador_id', 'registrada_por', 'tipo', 'zona', 'causa', 'fecha_inicio', 'fecha_estimada_regreso', 'fecha_alta', 'nota',
        'creado_en', 'actualizado_en',
    ];

    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function guardarNueva(Lesion $lesion): void
    {
        $momento = $lesion->creadoEn()->format('Y-m-d H:i:sP');
        $autor = $this->usuarioActual->identificador()?->valor();
        $instantanea = self::instantanea($lesion);

        $this->historial->registrar(
            'lesiones', 'lesion_id', $lesion->id(), 'crear', null, $instantanea,
            fn () => $this->conexion->table('lesiones')->insert([
                'id' => $lesion->id()->valor(), ...$instantanea,
                'creado_en' => $momento, 'actualizado_en' => $momento, 'creado_por' => $autor, 'actualizado_por' => $autor,
            ]),
            instante: $lesion->creadoEn(),
        );
    }

    /** Un choque de escritura con otra petición responde 409 conflicto, nunca 500 (DA-79). */
    public function guardarCambio(Lesion $anterior, Lesion $nueva): void
    {
        try {
            $this->historial->registrar(
                'lesiones', 'lesion_id', $nueva->id(), 'actualizar', self::instantanea($anterior), self::instantanea($nueva),
                fn () => $this->conexion->table('lesiones')->where('id', $nueva->id()->valor())->update([
                    'tipo' => $nueva->tipo(),
                    'zona' => $nueva->zona(),
                    'causa' => $nueva->causa()->value,
                    'fecha_alta' => $nueva->fechaAlta(),
                    'nota' => $nueva->nota(),
                    'actualizado_en' => $nueva->actualizadoEn()->format('Y-m-d H:i:sP'),
                    'actualizado_por' => $this->usuarioActual->identificador()?->valor(),
                ]),
                instante: $nueva->actualizadoEn(),
            );
        } catch (UniqueConstraintViolationException) {
            throw new EstadoConcurrente;
        }
    }

    public function buscar(Identificador $id): ?Lesion
    {
        $fila = $this->vigentes()->where('id', $id->valor())->first(self::COLUMNAS);

        return $fila === null ? null : self::aLesion($fila);
    }

    public function buscarParaActualizar(Identificador $id): ?Lesion
    {
        $fila = $this->vigentes()->where('id', $id->valor())->lockForUpdate()->first(self::COLUMNAS);

        return $fila === null ? null : self::aLesion($fila);
    }

    public function delRegistrante(Identificador $jugadorId, Identificador $registradaPor, int $pagina, int $porPagina): array
    {
        $consulta = $this->vigentes()->where('jugador_id', $jugadorId->valor())->where('registrada_por', $registradaPor->valor());
        $total = $consulta->getCountForPagination();
        $filas = $consulta
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->forPage($pagina, $porPagina)
            ->get(self::COLUMNAS);

        return ['elementos' => array_values($filas->map(fn (stdClass $fila) => self::aLesion($fila))->all()), 'total' => $total];
    }

    private function vigentes(): Builder
    {
        return $this->conexion->table('lesiones')->whereNull('eliminado_en');
    }

    /** @return array{jugador_id: string, registrada_por: string, tipo: string, zona: string, causa: string, fecha_inicio: string, fecha_estimada_regreso: string, fecha_alta: string|null, nota: string|null} */
    private static function instantanea(Lesion $lesion): array
    {
        return [
            'jugador_id' => $lesion->jugadorId()->valor(),
            'registrada_por' => $lesion->registradaPor()->valor(),
            'tipo' => $lesion->tipo(),
            'zona' => $lesion->zona(),
            'causa' => $lesion->causa()->value,
            'fecha_inicio' => $lesion->fechaInicio(),
            'fecha_estimada_regreso' => $lesion->fechaEstimadaRegreso(),
            'fecha_alta' => $lesion->fechaAlta(),
            'nota' => $lesion->nota(),
        ];
    }

    private static function aLesion(stdClass $fila): Lesion
    {
        $utc = new DateTimeZone('UTC');

        return new Lesion(
            Identificador::desdeTexto($fila->id),
            Identificador::desdeTexto($fila->jugador_id),
            Identificador::desdeTexto($fila->registrada_por),
            $fila->tipo,
            $fila->zona,
            CausaDeLesion::from($fila->causa),
            $fila->fecha_inicio,
            $fila->fecha_estimada_regreso,
            $fila->fecha_alta,
            $fila->nota,
            (new DateTimeImmutable($fila->creado_en))->setTimezone($utc),
            (new DateTimeImmutable($fila->actualizado_en))->setTimezone($utc),
        );
    }
}
