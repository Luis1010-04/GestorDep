<?php

namespace Src\Consultas\Aplicacion;

/**
 * Partidos jugados de los equipos del entrenador con al menos un convocado visible sin rendimiento (HU-14, §3.3). Vive
 * en Consultas porque cruza Competencia y Rendimiento (docs/arquitectura.md §4.3).
 */
interface ConsultaDePendientesDeRendimiento
{
    /** @return array{elementos: list<PartidoPendienteDeRendimiento>, total: int} del más reciente al más antiguo */
    public function delUsuario(int $pagina, int $porPagina): array;
}
