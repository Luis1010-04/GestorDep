<?php

namespace Src\Plantel\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Plantel\Aplicacion\CategoriaDuplicada;
use Src\Plantel\Dominio\Categoria;
use Src\Plantel\Dominio\RepositorioDeCategorias;

/**
 * Escribe `categorias`. La tabla no lleva historial (no está marcada (H) en docs/arquitectura.md):
 * el rastro de RNF-02 son sus columnas de auditoría, incluidas `eliminado_en` y `eliminado_por`.
 */
final class RepositorioDeCategoriasEloquent implements RepositorioDeCategorias
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly UsuarioActual $usuarioActual,
        private readonly Reloj $reloj,
    ) {}

    public function existeNombre(string $nombre): bool
    {
        return $this->vigentes()->where('nombre', $nombre)->exists();
    }

    public function guardarNueva(Categoria $categoria): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $instante = $categoria->actualizadoEn()->format('Y-m-d H:i:s.uP');

        try {
            $this->conexion->table('categorias')->insert([
                'id' => $categoria->id()->valor(),
                'nombre' => $categoria->nombre(),
                'creado_en' => $instante,
                'actualizado_en' => $instante,
                'creado_por' => $autor,
                'actualizado_por' => $autor,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new CategoriaDuplicada;
        }
    }

    public function listar(int $pagina, int $porPagina, bool $descendente): array
    {
        return $this->vigentes()
            ->orderBy('nombre', $descendente ? 'desc' : 'asc')
            ->orderBy('id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get(['id', 'nombre', 'actualizado_en'])
            ->map(fn (object $fila) => new Categoria(
                Identificador::desdeTexto($fila->id),
                $fila->nombre,
                (new DateTimeImmutable($fila->actualizado_en))->setTimezone(new DateTimeZone('UTC')),
            ))
            ->all();
    }

    public function contar(): int
    {
        return $this->vigentes()->count();
    }

    public function eliminar(Identificador $id): bool
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $instante = $this->reloj->ahora()->format('Y-m-d H:i:s.uP');

        return $this->vigentes()->where('id', $id->valor())->update([
            'eliminado_en' => $instante,
            'eliminado_por' => $autor,
            'actualizado_en' => $instante,
            'actualizado_por' => $autor,
        ]) > 0;
    }

    private function vigentes(): Builder
    {
        return $this->conexion->table('categorias')->whereNull('eliminado_en');
    }
}
