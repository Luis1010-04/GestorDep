<?php

namespace Src\Competencia\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Aplicacion\RivalDuplicado;
use Src\Competencia\Dominio\RepositorioDeRivales;
use Src\Competencia\Dominio\Rival;

/** Escribe `rivales` y `rivales_historial` en la misma transacción (RNF-02). */
final class RepositorioDeRivalesEloquent implements RepositorioDeRivales
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

    public function guardarNuevo(Rival $rival): void
    {
        try {
            $this->escritura->crear('rivales', 'rival_id', $rival->id(), ['nombre' => $rival->nombre()], $rival->actualizadoEn());
        } catch (UniqueConstraintViolationException) {
            throw new RivalDuplicado;
        }
    }

    public function listar(int $pagina, int $porPagina, bool $descendente): array
    {
        $rivales = $this->vigentes()
            ->orderBy('nombre', $descendente ? 'desc' : 'asc')
            ->orderBy('id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get(['id', 'nombre', 'actualizado_en'])
            ->map(fn (object $fila) => new Rival(
                Identificador::desdeTexto($fila->id),
                $fila->nombre,
                (new DateTimeImmutable($fila->actualizado_en))->setTimezone(new DateTimeZone('UTC')),
            ))
            ->all();

        return array_values($rivales);
    }

    public function contar(): int
    {
        return $this->vigentes()->count();
    }

    public function eliminar(Identificador $id): bool
    {
        return $this->escritura->eliminar('rivales', 'rival_id', $id, ['nombre']);
    }

    private function vigentes(): Builder
    {
        return $this->conexion->table('rivales')->whereNull('eliminado_en');
    }
}
