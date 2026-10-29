<?php

namespace Src\Academico\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Academico\Aplicacion\AsignaturaDuplicada;
use Src\Academico\Dominio\Asignatura;
use Src\Academico\Dominio\RepositorioDeAsignaturas;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;

/**
 * Escribe `asignaturas`. La tabla no lleva historial (no está marcada (H) en docs/hitos/f1-h2.md §5.1):
 * el rastro de RNF-02 son sus columnas de auditoría, incluidas `eliminado_en` y `eliminado_por`.
 */
final class RepositorioDeAsignaturasEloquent implements RepositorioDeAsignaturas
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

    public function existeClave(string $clave): bool
    {
        return $this->vigentes()->where('clave', $clave)->exists();
    }

    public function guardarNueva(Asignatura $asignatura): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $instante = $asignatura->actualizadoEn()->format('Y-m-d H:i:sP');

        try {
            $this->conexion->table('asignaturas')->insert([
                'id' => $asignatura->id()->valor(),
                'clave' => $asignatura->clave(),
                'nombre' => $asignatura->nombre(),
                'grado' => $asignatura->grado(),
                'creado_en' => $instante,
                'actualizado_en' => $instante,
                'creado_por' => $autor,
                'actualizado_por' => $autor,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new AsignaturaDuplicada;
        }
    }

    public function listar(int $pagina, int $porPagina, bool $descendente): array
    {
        $asignaturas = $this->vigentes()
            ->orderBy('clave', $descendente ? 'desc' : 'asc')
            ->orderBy('id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get(['id', 'clave', 'nombre', 'grado', 'actualizado_en'])
            ->map(fn (object $fila) => new Asignatura(
                Identificador::desdeTexto($fila->id),
                $fila->clave,
                $fila->nombre,
                (int) $fila->grado,
                (new DateTimeImmutable($fila->actualizado_en))->setTimezone(new DateTimeZone('UTC')),
            ))
            ->all();

        return array_values($asignaturas);
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

    private function vigentes(): Builder
    {
        return $this->conexion->table('asignaturas')->whereNull('eliminado_en');
    }
}
