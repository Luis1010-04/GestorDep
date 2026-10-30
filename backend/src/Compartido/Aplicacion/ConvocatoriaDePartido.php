<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Equipo, estado y convocados de un partido, para contextos que no pueden leer las tablas de Competencia
 * (docs/hitos/f1-h4.md §8, T02). No filtra por usuario: quien lo usa comprueba que entrena el equipo. El adaptador vive
 * en Competencia.
 */
interface ConvocatoriaDePartido
{
    /** Nulo si el partido no existe o está eliminado. */
    public function de(Identificador $partido): ?PartidoConConvocatoria;
}
