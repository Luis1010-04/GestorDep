<?php

namespace Src\Academico\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Academico\Aplicacion\PeriodoDuplicado;
use Src\Academico\Dominio\PeriodoEscolar;
use Src\Academico\Dominio\RepositorioDePeriodos;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;

/**
 * Escribe `periodos_escolares`. La tabla no lleva historial (no está marcada (H) en docs/hitos/f1-h2.md §5.1):
 * el rastro de RNF-02 son sus columnas de auditoría, incluidas `eliminado_en` y `eliminado_por`.
 */
final class RepositorioDePeriodosEloquent implements RepositorioDePeriodos
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly UsuarioActual $usuarioActual,
        private readonly Reloj $reloj,
    ) {}

    public function existe(Identificador $id): bool
    {
        return $this->vigentes()->where('id', $id->valor())->exists();
    }

    public function existeNombre(string $nombre): bool
    {
        return $this->vigentes()->where('nombre', $nombre)->exists();
    }

    public function guardarNuevo(PeriodoEscolar $periodo): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $instante = $periodo->actualizadoEn()->format('Y-m-d H:i:sP');

        try {
            $this->conexion->table('periodos_escolares')->insert([
                'id' => $periodo->id()->valor(),
                'nombre' => $periodo->nombre(),
                'fecha_inicio' => $periodo->fechaInicio()->format('Y-m-d'),
                'fecha_fin' => $periodo->fechaFin()->format('Y-m-d'),
                'creado_en' => $instante,
                'actualizado_en' => $instante,
                'creado_por' => $autor,
                'actualizado_por' => $autor,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new PeriodoDuplicado;
        }
    }

    public function listar(int $pagina, int $porPagina, bool $descendente): array
    {
        $periodos = $this->vigentes()
            ->orderBy('fecha_inicio', $descendente ? 'desc' : 'asc')
            ->orderBy('id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get(['id', 'nombre', 'fecha_inicio', 'fecha_fin', 'actualizado_en'])
            ->map(fn (object $fila) => new PeriodoEscolar(
                Identificador::desdeTexto($fila->id),
                $fila->nombre,
                new DateTimeImmutable($fila->fecha_inicio, new DateTimeZone('UTC')),
                new DateTimeImmutable($fila->fecha_fin, new DateTimeZone('UTC')),
                (new DateTimeImmutable($fila->actualizado_en))->setTimezone(new DateTimeZone('UTC')),
            ))
            ->all();

        return array_values($periodos);
    }

    public function contar(): int
    {
        return $this->vigentes()->count();
    }

    public function eliminar(Identificador $id): bool
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $instante = Instante::truncadoASegundos($this->reloj->ahora())->format('Y-m-d H:i:sP');

        return $this->vigentes()->where('id', $id->valor())->update([
            'eliminado_en' => $instante,
            'eliminado_por' => $autor,
            'actualizado_en' => $instante,
            'actualizado_por' => $autor,
        ]) > 0;
    }

    public function identificadorVigenteEn(DateTimeImmutable $dia): ?Identificador
    {
        $id = $this->vigentes()
            ->where('fecha_inicio', '<=', $dia->format('Y-m-d'))
            ->where('fecha_fin', '>=', $dia->format('Y-m-d'))
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->value('id');

        return $id === null ? null : Identificador::desdeTexto($id);
    }

    private function vigentes(): Builder
    {
        return $this->conexion->table('periodos_escolares')->whereNull('eliminado_en');
    }
}
