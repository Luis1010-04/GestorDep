<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Listado paginado de las calificaciones de una inscripción del docente (HU-07). */
final class ListarCalificaciones
{
    public function __construct(private readonly ConsultaDeCalificaciones $consulta) {}

    /** @return array{calificaciones: list<CalificacionLeida>, total: int} */
    public function ejecutar(Identificador $inscripcionId, int $pagina, int $porPagina): array
    {
        return [
            'calificaciones' => $this->consulta->listarDeLaInscripcion($inscripcionId, $pagina, $porPagina),
            'total' => $this->consulta->contarDeLaInscripcion($inscripcionId),
        ];
    }
}
