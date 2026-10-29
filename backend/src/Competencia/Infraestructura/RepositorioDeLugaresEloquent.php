<?php

namespace Src\Competencia\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Aplicacion\LugarDuplicado;
use Src\Competencia\Dominio\Lugar;
use Src\Competencia\Dominio\RepositorioDeLugares;

/** Escribe `lugares` y `lugares_historial` en la misma transacción (RNF-02). */
final class RepositorioDeLugaresEloquent implements RepositorioDeLugares
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

    public function guardarNuevo(Lugar $lugar): void
    {
        try {
            $this->escritura->crear('lugares', 'lugar_id', $lugar->id(), ['nombre' => $lugar->nombre()], $lugar->actualizadoEn());
        } catch (UniqueConstraintViolationException) {
            throw new LugarDuplicado;
        }
    }

    public function listar(int $pagina, int $porPagina, bool $descendente): array
    {
        $lugares = $this->vigentes()
            ->orderBy('nombre', $descendente ? 'desc' : 'asc')
            ->orderBy('id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get(['id', 'nombre', 'actualizado_en'])
            ->map(fn (object $fila) => new Lugar(
                Identificador::desdeTexto($fila->id),
                $fila->nombre,
                (new DateTimeImmutable($fila->actualizado_en))->setTimezone(new DateTimeZone('UTC')),
            ))
            ->all();

        return array_values($lugares);
    }

    public function contar(): int
    {
        return $this->vigentes()->count();
    }

    public function eliminar(Identificador $id): bool
    {
        return $this->escritura->eliminar('lugares', 'lugar_id', $id, ['nombre']);
    }

    private function vigentes(): Builder
    {
        return $this->conexion->table('lugares')->whereNull('eliminado_en');
    }
}
