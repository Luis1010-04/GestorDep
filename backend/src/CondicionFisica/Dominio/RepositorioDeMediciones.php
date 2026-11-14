<?php

namespace Src\CondicionFisica\Dominio;

use Src\Compartido\Dominio\Identificador;

interface RepositorioDeMediciones
{
    public function guardarNueva(MedicionCorporal $medicion): void;

    public function guardarCorreccion(MedicionCorporal $anterior, MedicionCorporal $corregida, string $motivo): void;

    /** La del jugador en el periodo que registró ese entrenador, con la fila bloqueada hasta el fin de la transacción (DD-59). */
    public function delPeriodoParaActualizar(Identificador $jugadorId, Identificador $registradoPor, string $periodo): ?MedicionCorporal;

    /**
     * Las que registró ese entrenador, por periodo ascendente (DD-59, DA-06).
     *
     * @return array{elementos: list<MedicionCorporal>, total: int}
     */
    public function delRegistrante(Identificador $jugadorId, Identificador $registradoPor, int $pagina, int $porPagina): array;
}
