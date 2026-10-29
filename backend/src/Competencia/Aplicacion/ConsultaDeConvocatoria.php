<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\Convocatoria;

/**
 * Convocados no eliminados de un partido, restringidos por `AlcanceDeJugadores` sin bajas: un jugador dado de baja
 * no aparece (HU-29 criterio 3, DA-29).
 */
interface ConsultaDeConvocatoria
{
    /** @return list<Convocatoria> en orden de convocatoria */
    public function delPartido(Identificador $partidoId, int $pagina, int $porPagina): array;

    public function contarDelPartido(Identificador $partidoId): int;
}
