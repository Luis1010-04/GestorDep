<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Rendimiento\Dominio\RendimientoDePartido;

/** Rendimientos de un partido que registró un entrenador, de jugadores en su alcance (RN-14; el alcance va antes, §3.3). */
interface ConsultaDeRendimientos
{
    /** @return list<RendimientoDePartido> */
    public function delPartidoPor(Identificador $partidoId, Identificador $registradoPor, int $pagina, int $porPagina): array;

    public function contarDelPartidoPor(Identificador $partidoId, Identificador $registradoPor): int;
}
