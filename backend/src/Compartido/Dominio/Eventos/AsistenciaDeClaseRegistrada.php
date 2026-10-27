<?php

namespace Src\Compartido\Dominio\Eventos;

/** Se registró o corrigió una asistencia a clase (HU-07 criterio 3). Solo identificadores. */
final class AsistenciaDeClaseRegistrada
{
    public function __construct(
        public readonly string $inscripcionId,
        public readonly string $materiaId,
        public readonly string $jugadorId,
    ) {}
}
