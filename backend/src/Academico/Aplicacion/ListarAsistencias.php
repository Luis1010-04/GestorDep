<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Listado paginado de las asistencias de una inscripción del docente (HU-07). */
final class ListarAsistencias
{
    public function __construct(private readonly ConsultaDeAsistencias $consulta) {}

    /** @return array{asistencias: list<AsistenciaLeida>, total: int} */
    public function ejecutar(Identificador $inscripcionId, int $pagina, int $porPagina): array
    {
        return [
            'asistencias' => $this->consulta->listarDeLaInscripcion($inscripcionId, $pagina, $porPagina),
            'total' => $this->consulta->contarDeLaInscripcion($inscripcionId),
        ];
    }
}
