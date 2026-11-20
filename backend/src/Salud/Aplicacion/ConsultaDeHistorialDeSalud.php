<?php

namespace Src\Salud\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Historial de avisos, lesiones y estados de un jugador (HU-26; docs/hitos/f1-h7.md §5.3). Quien llama comprueba antes que
 * el jugador está en su alcance. Cada lista va de la entrada más reciente a la más antigua, con el total exacto.
 */
interface ConsultaDeHistorialDeSalud
{
    /**
     * Solo los avisos cuya fila completa ve el lector (D-2): los que creó y, si entrena al jugador, los que confirmó.
     *
     * @return array{entradas: list<EntradaDeHistorialDeSalud>, total: int}
     */
    public function avisos(Identificador $jugador, Identificador $lector, bool $comoEntrenador, int $pagina, int $porPagina): array;

    /**
     * Solo las lesiones que registró el lector (RN-15).
     *
     * @return array{entradas: list<EntradaDeHistorialDeSalud>, total: int}
     */
    public function lesiones(Identificador $jugador, Identificador $lector, int $pagina, int $porPagina): array;

    /** @return array{entradas: list<EntradaDeHistorialDeSalud>, total: int} */
    public function estados(Identificador $jugador, int $pagina, int $porPagina): array;

    /**
     * Identificadores de las lesiones del jugador que registró el lector (DA-125: solo él ve `lesion_id`).
     *
     * @return list<string>
     */
    public function lesionesRegistradasPor(Identificador $jugador, Identificador $lector): array;
}
