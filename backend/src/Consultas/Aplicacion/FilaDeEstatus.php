<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Lo que Consultas lee por sí misma de un jugador del listado de estatus (HU-23): sus datos de identificación, el
 * indicador de aviso pendiente (HU-23 c4, DA-108) y las alertas que el usuario puede ver. El estado oficial y la situación
 * académica los aportan Salud y Académico, que ya aplican sus reglas de visibilidad.
 */
final class FilaDeEstatus
{
    /** @param  list<AlertaVisible>  $alertas */
    public function __construct(
        public readonly Identificador $jugadorId,
        public readonly string $nombres,
        public readonly string $apellidos,
        public readonly string $matricula,
        public readonly bool $avisoPendiente,
        public readonly array $alertas,
    ) {}
}
