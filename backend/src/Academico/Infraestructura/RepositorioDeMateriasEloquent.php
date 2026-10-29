<?php

namespace Src\Academico\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Academico\Aplicacion\MateriaDuplicada;
use Src\Academico\Dominio\Materia;
use Src\Academico\Dominio\RepositorioDeMaterias;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;

/** Escribe `materias` y `materias_historial` en la misma transacción (RNF-02, arquitectura §9). */
final class RepositorioDeMateriasEloquent implements RepositorioDeMaterias
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function existe(Identificador $asignaturaId, Identificador $periodoId, Identificador $docenteId): bool
    {
        return $this->conexion->table('materias')
            ->whereNull('eliminado_en')
            ->where('asignatura_id', $asignaturaId->valor())
            ->where('periodo_id', $periodoId->valor())
            ->where('docente_id', $docenteId->valor())
            ->exists();
    }

    public function guardarNueva(Materia $materia): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $instante = $materia->actualizadoEn()->format('Y-m-d H:i:sP');
        $datos = [
            'asignatura_id' => $materia->asignaturaId()->valor(),
            'periodo_id' => $materia->periodoId()->valor(),
            'docente_id' => $materia->docenteId()->valor(),
        ];

        try {
            $this->historial->registrar(
                'materias',
                'materia_id',
                $materia->id(),
                'crear',
                null,
                $datos,
                fn () => $this->conexion->table('materias')->insert([
                    'id' => $materia->id()->valor(),
                    ...$datos,
                    'creado_en' => $instante,
                    'actualizado_en' => $instante,
                    'creado_por' => $autor,
                    'actualizado_por' => $autor,
                ]),
                instante: $materia->actualizadoEn(),
            );
        } catch (UniqueConstraintViolationException) {
            throw new MateriaDuplicada;
        }
    }
}
