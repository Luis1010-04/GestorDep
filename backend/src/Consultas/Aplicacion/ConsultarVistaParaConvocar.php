<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * `GET /jugadores/{jugador}/vista-para-convocar` (HU-24, DA-94, DA-119). El equipo del rendimiento es el pedido con
 * `filtro[equipo_id]`; sin él, el de la evaluación más reciente del consultante para el jugador. Un jugador fuera del
 * alcance, en baja o inexistente, y un equipo que el usuario no entrena o donde el jugador no está, dan el mismo nulo: la
 * API responde 404 (RNF-01, RN-23).
 */
final class ConsultarVistaParaConvocar
{
    public function __construct(
        private readonly ConsultaDeVistaParaConvocar $consulta,
        private readonly ConstructorDeLaVista $constructor,
    ) {}

    public function ejecutar(Identificador $jugador, ?Identificador $equipo = null): ?VistaParaConvocar
    {
        $datos = $this->consulta->jugador($jugador);
        if ($datos === null) {
            return null;
        }

        if ($equipo === null) {
            $equipo = $this->consulta->equipoDeLaUltimaEvaluacion($jugador);
        } elseif (! $this->consulta->jugadorEnEquipoDelUsuario($jugador, $equipo)) {
            return null;
        }

        $evaluacion = $equipo === null ? null : $this->consulta->evaluacionReciente($jugador, $equipo);

        return $this->constructor->armar($datos, $equipo, $evaluacion, $this->constructor->marca());
    }
}
