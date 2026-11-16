<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Aplicacion\EquiposDelEntrenador;
use Src\Compartido\Aplicacion\PorcentajeDeAsistenciaAEntrenamientos;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;

/**
 * Gráficos de historial de un equipo (HU-25 c2, DA-97): el promedio por partido de lo que registró el entrenador que consulta
 * (RN-14) y el porcentaje de asistencia del equipo por mes. Con un jugador, además, el jugador contra el promedio del equipo.
 * Quien no entrena al equipo, o pide un jugador que no es del equipo, no está en su alcance o está en baja, recibe nulo: 404.
 */
final class ConsultarGraficosDelEquipo
{
    public function __construct(
        private readonly ConsultaDeEstatus $estatus,
        private readonly ConsultaDeGraficos $graficos,
        private readonly PorcentajeDeAsistenciaAEntrenamientos $asistencia,
        private readonly EquiposDelEntrenador $equipos,
        private readonly UsuarioActual $usuarioActual,
        private readonly ParametroDeDesactualizacion $parametro,
        private readonly HoyDeNegocio $hoyDeNegocio,
    ) {}

    public function ejecutar(Identificador $equipo, ?Identificador $jugador = null): ?GraficosDelEquipo
    {
        $usuario = $this->usuarioActual->identificador();
        if ($usuario === null || ! $this->equipos->entrena($usuario, $equipo)) {
            return null;
        }
        if ($jugador !== null && ! ($this->estatus->jugadorVisible($jugador) && $this->graficos->jugadorEnEquipos($jugador, [$equipo]))) {
            return null;
        }

        $marca = new MarcaDeDesactualizacion($this->hoyDeNegocio->hoy(), $this->parametro->dias());
        $serie = fn (DatosDeSerie $datos): SerieDeGrafico => SerieDeGrafico::desde($datos, $marca);

        return new GraficosDelEquipo(
            $serie($this->graficos->rendimientoDelEquipo($equipo)),
            $serie(DatosDeSerie::deAsistencia($this->asistencia->delEquipo($equipo))),
            $jugador,
            $jugador === null ? null : $serie($this->graficos->rendimientoDelJugador($jugador, $equipo)),
            $jugador === null ? null : $serie(DatosDeSerie::deAsistencia($this->asistencia->delJugadorEnEquipos($jugador, [$equipo]))),
        );
    }
}
