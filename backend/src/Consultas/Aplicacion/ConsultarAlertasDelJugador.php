<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Alertas abiertas de un jugador que el usuario puede ver (HU-22). Quien no tiene al jugador en su alcance, o lo tiene en
 * baja (RN-23), recibe nulo: la API responde 404 igual que para un jugador inexistente (RNF-01).
 */
final class ConsultarAlertasDelJugador
{
    public function __construct(
        private readonly ConsultaDeEstatus $estatus,
        private readonly ConsultaDeAlertas $alertas,
    ) {}

    /** @return list<AlertaVisible>|null */
    public function ejecutar(Identificador $jugador): ?array
    {
        return $this->estatus->jugadorVisible($jugador) ? $this->alertas->delJugador($jugador) : null;
    }
}
