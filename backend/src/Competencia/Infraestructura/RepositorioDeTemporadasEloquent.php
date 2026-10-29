<?php

namespace Src\Competencia\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Aplicacion\TemporadaDuplicada;
use Src\Competencia\Aplicacion\TemporadaSolapada;
use Src\Competencia\Dominio\RepositorioDeTemporadas;
use Src\Competencia\Dominio\Temporada;

/**
 * Escribe `temporadas` y `temporadas_historial` en la misma transacción (RNF-02). El alta toma un candado
 * consultivo de transacción para que la comprobación del cruce y la escritura no se intercalen con otra alta (DD-07).
 */
final class RepositorioDeTemporadasEloquent implements RepositorioDeTemporadas
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscrituraDeCatalogo $escritura,
    ) {}

    public function existe(Identificador $id): bool
    {
        return $this->vigentes()->where('id', $id->valor())->exists();
    }

    public function existeNombre(string $nombre): bool
    {
        return $this->vigentes()->where('nombre', $nombre)->exists();
    }

    public function guardarNueva(Temporada $temporada): void
    {
        $inicio = $temporada->fechaInicio()->format('Y-m-d');
        $fin = $temporada->fechaFin()->format('Y-m-d');

        try {
            $this->conexion->transaction(function () use ($temporada, $inicio, $fin): void {
                $this->conexion->select("select pg_advisory_xact_lock(hashtext('temporadas'))");

                // Dos intervalos de días con extremos incluidos se cruzan si cada uno empieza antes o el día en que termina el otro.
                if ($this->vigentes()->where('fecha_inicio', '<=', $fin)->where('fecha_fin', '>=', $inicio)->exists()) {
                    throw new TemporadaSolapada;
                }

                $this->escritura->crear('temporadas', 'temporada_id', $temporada->id(), [
                    'nombre' => $temporada->nombre(),
                    'fecha_inicio' => $inicio,
                    'fecha_fin' => $fin,
                ], $temporada->actualizadoEn());
            });
        } catch (UniqueConstraintViolationException) {
            throw new TemporadaDuplicada;
        }
    }

    public function listar(int $pagina, int $porPagina, bool $descendente): array
    {
        $utc = new DateTimeZone('UTC');
        $temporadas = $this->vigentes()
            ->orderBy('fecha_inicio', $descendente ? 'desc' : 'asc')
            ->orderBy('id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get(['id', 'nombre', 'fecha_inicio', 'fecha_fin', 'actualizado_en'])
            ->map(fn (object $fila) => new Temporada(
                Identificador::desdeTexto($fila->id),
                $fila->nombre,
                new DateTimeImmutable($fila->fecha_inicio, $utc),
                new DateTimeImmutable($fila->fecha_fin, $utc),
                (new DateTimeImmutable($fila->actualizado_en))->setTimezone($utc),
            ))
            ->all();

        return array_values($temporadas);
    }

    public function contar(): int
    {
        return $this->vigentes()->count();
    }

    public function eliminar(Identificador $id): bool
    {
        return $this->escritura->eliminar('temporadas', 'temporada_id', $id, ['nombre', 'fecha_inicio', 'fecha_fin']);
    }

    private function vigentes(): Builder
    {
        return $this->conexion->table('temporadas')->whereNull('eliminado_en');
    }
}
