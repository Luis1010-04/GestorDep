<?php

namespace Src\Salud\Aplicacion;

use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;
use Src\Salud\Dominio\RepositorioDeEstados;

/** El estado oficial vigente de un jugador que el usuario ya ve (HU-21, HU-04 c3). */
final class ConsultarEstado
{
    public function __construct(
        private readonly RepositorioDeEstados $estados,
        private readonly UsuarioActual $usuarioActual,
        private readonly HoyDeNegocio $hoyDeNegocio,
    ) {}

    /** @throws RecursoNoEncontrado */
    public function ejecutar(Identificador $jugadorId): EstadoVisible
    {
        $lector = $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;
        $estado = $this->estados->vigente($jugadorId);

        return $estado === null ? EstadoVisible::aptoImplicito() : EstadoVisible::para($estado, $lector, $this->hoyDeNegocio->hoy()->format('Y-m-d'));
    }
}
