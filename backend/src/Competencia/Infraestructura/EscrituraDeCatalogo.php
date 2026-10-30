<?php

namespace Src\Competencia\Infraestructura;

use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;

/**
 * Alta y borrado lógico de una fila de catálogo de Competencia con su historial en la misma transacción
 * (docs/hitos/f1-h3.md §3.4: el historial también aplica a los catálogos).
 */
final class EscrituraDeCatalogo
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
        private readonly Reloj $reloj,
    ) {}

    /** @param  array<string, string>  $datos  columnas de la entidad; también son la instantánea del historial */
    public function crear(string $tabla, string $columnaEntidad, Identificador $id, array $datos, DateTimeImmutable $instante): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $momento = $instante->format('Y-m-d H:i:sP');

        $this->historial->registrar(
            $tabla,
            $columnaEntidad,
            $id,
            'crear',
            null,
            $datos,
            fn () => $this->conexion->table($tabla)->insert([
                'id' => $id->valor(),
                ...$datos,
                'creado_en' => $momento,
                'actualizado_en' => $momento,
                'creado_por' => $autor,
                'actualizado_por' => $autor,
            ]),
            instante: $instante,
        );
    }

    /**
     * @param  list<string>  $columnas  columnas de la instantánea anterior
     * @return bool falso si la fila no existe o ya estaba eliminada
     */
    public function eliminar(string $tabla, string $columnaEntidad, Identificador $id, array $columnas): bool
    {
        return $this->conexion->transaction(function () use ($tabla, $columnaEntidad, $id, $columnas): bool {
            $fila = $this->conexion->table($tabla)->whereNull('eliminado_en')->where('id', $id->valor())->lockForUpdate()->first($columnas);
            if ($fila === null) {
                return false;
            }

            $autor = $this->usuarioActual->identificador()?->valor();
            $instante = Instante::truncadoASegundos($this->reloj->ahora());
            $momento = $instante->format('Y-m-d H:i:sP');

            $this->historial->registrar(
                $tabla,
                $columnaEntidad,
                $id,
                'eliminar',
                (array) $fila,
                null,
                fn () => $this->conexion->table($tabla)->where('id', $id->valor())->update([
                    'eliminado_en' => $momento,
                    'eliminado_por' => $autor,
                    'actualizado_en' => $momento,
                    'actualizado_por' => $autor,
                ]),
                instante: $instante,
            );

            return true;
        });
    }
}
