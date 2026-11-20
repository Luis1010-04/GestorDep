<?php

namespace Src\CondicionFisica\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Historial de mediciones y pruebas físicas de un jugador (HU-26; docs/hitos/f1-h7.md §5.3). Quien llama comprueba antes
 * que entrena al jugador. Solo entran los registros del lector (RN-14). Cada lista va de la entrada más reciente a la más
 * antigua, con el total exacto.
 */
interface ConsultaDeHistorialDeCondicionFisica
{
    /** @return array{entradas: list<EntradaDeHistorialDeCondicionFisica>, total: int} */
    public function mediciones(Identificador $jugador, Identificador $lector, int $pagina, int $porPagina): array;

    /** @return array{entradas: list<EntradaDeHistorialDeCondicionFisica>, total: int} */
    public function pruebas(Identificador $jugador, Identificador $lector, int $pagina, int $porPagina): array;
}
