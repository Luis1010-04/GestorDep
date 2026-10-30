<?php

namespace Src\Competencia\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Aplicacion\TorneoDuplicado;
use Src\Competencia\Dominio\RepositorioDeTorneos;
use Src\Competencia\Dominio\Torneo;

/** Escribe `torneos` y `torneos_historial` en la misma transacción (RNF-02). */
final class RepositorioDeTorneosEloquent implements RepositorioDeTorneos
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscrituraDeCatalogo $escritura,
    ) {}

    public function existeNombre(string $nombre, Identificador $temporadaId): bool
    {
        return $this->vigentes()->where('nombre', $nombre)->where('temporada_id', $temporadaId->valor())->exists();
    }

    public function temporadaDe(Identificador $torneoId): ?Identificador
    {
        $temporada = $this->vigentes()->where('id', $torneoId->valor())->value('temporada_id');

        return $temporada === null ? null : Identificador::desdeTexto($temporada);
    }

    public function guardarNuevo(Torneo $torneo): void
    {
        try {
            $this->escritura->crear('torneos', 'torneo_id', $torneo->id(), [
                'nombre' => $torneo->nombre(),
                'temporada_id' => $torneo->temporadaId()->valor(),
            ], $torneo->actualizadoEn());
        } catch (UniqueConstraintViolationException) {
            throw new TorneoDuplicado;
        }
    }

    public function listar(int $pagina, int $porPagina, bool $descendente): array
    {
        $torneos = $this->vigentes()
            ->orderBy('nombre', $descendente ? 'desc' : 'asc')
            ->orderBy('id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get(['id', 'nombre', 'temporada_id', 'actualizado_en'])
            ->map(fn (object $fila) => new Torneo(
                Identificador::desdeTexto($fila->id),
                $fila->nombre,
                Identificador::desdeTexto($fila->temporada_id),
                (new DateTimeImmutable($fila->actualizado_en))->setTimezone(new DateTimeZone('UTC')),
            ))
            ->all();

        return array_values($torneos);
    }

    public function contar(): int
    {
        return $this->vigentes()->count();
    }

    public function eliminar(Identificador $id): bool
    {
        return $this->escritura->eliminar('torneos', 'torneo_id', $id, ['nombre', 'temporada_id']);
    }

    private function vigentes(): Builder
    {
        return $this->conexion->table('torneos')->whereNull('eliminado_en');
    }
}
