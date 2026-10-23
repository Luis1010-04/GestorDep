<?php

namespace Src\Plantel\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Plantel\Aplicacion\CategoriaInexistente;
use Src\Plantel\Aplicacion\EquipoConCategoria;
use Src\Plantel\Aplicacion\EquipoDuplicado;
use Src\Plantel\Dominio\Equipo;
use Src\Plantel\Dominio\RepositorioDeEquipos;

/** Escribe `equipos` y `entrenador_equipo`, cada uno con su historial, todo en una transacción (docs/arquitectura.md, sección 9.2). */
final class RepositorioDeEquiposEloquent implements RepositorioDeEquipos
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
        private readonly HoyDeNegocio $hoyDeNegocio,
    ) {}

    public function existeEquipo(string $nombre, Identificador $categoriaId): bool
    {
        return $this->conexion->table('equipos')
            ->whereNull('eliminado_en')
            ->where('nombre', $nombre)
            ->where('categoria_id', $categoriaId->valor())
            ->exists();
    }

    public function guardarNuevo(Equipo $equipo, Identificador $entrenador): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $instante = $equipo->actualizadoEn()->format('Y-m-d H:i:s.uP');
        $desde = $this->hoyDeNegocio->hoy()->format('Y-m-d');
        $asignacionId = Identificador::generar();

        try {
            $this->conexion->transaction(function () use ($equipo, $entrenador, $autor, $instante, $desde, $asignacionId): void {
                $this->historial->registrar(
                    'equipos',
                    'equipo_id',
                    $equipo->id(),
                    'crear',
                    null,
                    ['nombre' => $equipo->nombre(), 'categoria_id' => $equipo->categoriaId()->valor(), 'activo' => true],
                    fn () => $this->conexion->table('equipos')->insert([
                        'id' => $equipo->id()->valor(),
                        'nombre' => $equipo->nombre(),
                        'categoria_id' => $equipo->categoriaId()->valor(),
                        'creado_en' => $instante,
                        'actualizado_en' => $instante,
                        'creado_por' => $autor,
                        'actualizado_por' => $autor,
                    ]),
                );

                $this->historial->registrar(
                    'entrenador_equipo',
                    'entrenador_equipo_id',
                    $asignacionId,
                    'crear',
                    null,
                    ['usuario_id' => $entrenador->valor(), 'equipo_id' => $equipo->id()->valor(), 'desde' => $desde, 'hasta' => null],
                    fn () => $this->conexion->table('entrenador_equipo')->insert([
                        'id' => $asignacionId->valor(),
                        'usuario_id' => $entrenador->valor(),
                        'equipo_id' => $equipo->id()->valor(),
                        'desde' => $desde,
                        'creado_en' => $instante,
                        'actualizado_en' => $instante,
                        'creado_por' => $autor,
                        'actualizado_por' => $autor,
                    ]),
                );
            });
        } catch (UniqueConstraintViolationException) {
            throw new EquipoDuplicado;
        } catch (QueryException $e) {
            // SQLSTATE 23503: la categoría dejó de existir entre la comprobación y la escritura.
            throw $e->getCode() === '23503' && str_contains($e->getMessage(), 'fk_equipos_categoria_id') ? new CategoriaInexistente : $e;
        }
    }

    public function listarDelEntrenador(Identificador $entrenador, int $pagina, int $porPagina, bool $descendente): array
    {
        return $this->delEntrenador($entrenador)
            ->orderBy('equipos.nombre', $descendente ? 'desc' : 'asc')
            ->orderBy('equipos.id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get(['equipos.id', 'equipos.nombre', 'equipos.categoria_id', 'equipos.actualizado_en', 'categorias.nombre as categoria_nombre'])
            ->map(fn (object $fila) => new EquipoConCategoria(
                new Equipo(
                    Identificador::desdeTexto($fila->id),
                    $fila->nombre,
                    Identificador::desdeTexto($fila->categoria_id),
                    (new DateTimeImmutable($fila->actualizado_en))->setTimezone(new DateTimeZone('UTC')),
                ),
                $fila->categoria_nombre,
            ))
            ->all();
    }

    public function contarDelEntrenador(Identificador $entrenador): int
    {
        return $this->delEntrenador($entrenador)->count();
    }

    /** La categoría se une sin filtrar `eliminado_en`: un equipo sigue mostrando la suya aunque se haya eliminado (HU-28). */
    private function delEntrenador(Identificador $entrenador): Builder
    {
        return $this->conexion->table('equipos')
            ->join('categorias', 'categorias.id', '=', 'equipos.categoria_id')
            ->join('entrenador_equipo as asignacion', 'asignacion.equipo_id', '=', 'equipos.id')
            ->whereNull('equipos.eliminado_en')
            ->where('asignacion.usuario_id', $entrenador->valor())
            ->whereNull('asignacion.hasta')
            ->whereNull('asignacion.eliminado_en');
    }
}
