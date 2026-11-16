<?php

namespace Src\Consultas\Aplicacion;

/** Listado de estatus de los jugadores asignados al usuario, con filtros por equipo, materia y tipo de alerta (HU-23). */
final class ListarEstatusDeJugadores
{
    public function __construct(private readonly ConsultaDeEstatus $estatus) {}

    /** @return array{elementos: list<FilaDeEstatus>, total: int} */
    public function ejecutar(FiltrosDeEstatus $filtros, int $pagina, int $porPagina): array
    {
        if ($filtros->sinResultados) {
            return ['elementos' => [], 'total' => 0];
        }

        return $this->estatus->pagina($filtros, $pagina, $porPagina);
    }
}
