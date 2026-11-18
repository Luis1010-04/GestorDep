<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;

/** Arma la vista para convocar de un jugador con sus seis dimensiones y las marcas de desactualizada (DA-94, DA-98). */
final class ConstructorDeLaVista
{
    public function __construct(
        private readonly ConsultaDeVistaParaConvocar $consulta,
        private readonly ConsultaDeGraficos $graficos,
        private readonly ParametroDeDesactualizacion $parametro,
        private readonly HoyDeNegocio $hoyDeNegocio,
    ) {}

    /** La marca del día de negocio de esta lectura; se crea una vez por consulta y se pasa a `armar`. */
    public function marca(): MarcaDeDesactualizacion
    {
        return new MarcaDeDesactualizacion($this->hoyDeNegocio->hoy(), $this->parametro->dias());
    }

    /**
     * @param  Identificador|null  $equipo  el equipo del rendimiento y del progreso; nulo si el jugador no tiene evaluación del usuario
     * @param  EvaluacionReciente|null  $evaluacion  la ya leída para ese equipo, o nula si no hay
     */
    public function armar(JugadorConvocable $jugador, ?Identificador $equipo, ?EvaluacionReciente $evaluacion, MarcaDeDesactualizacion $marca): VistaParaConvocar
    {
        $disponibilidad = $this->consulta->disponibilidad($jugador->id);
        $situacion = $this->consulta->situacionAcademica($jugador->id);
        $asistencia = $this->consulta->asistencia($jugador->id);
        $estadoFisico = $this->consulta->estadoFisico($jugador->id);
        $progreso = $this->graficos->rendimientoDelJugador($jugador->id, $equipo);

        return new VistaParaConvocar(
            $jugador,
            $disponibilidad,
            $marca->de($disponibilidad->actualizadoEn),
            $evaluacion,
            $marca->de($evaluacion?->calculadoEn),
            $situacion,
            $marca->de($situacion->calculadoEn),
            $asistencia,
            $marca->de($asistencia->equipo->actualizadoEn),
            $marca->de($asistencia->deMisCapturas->actualizadoEn),
            $estadoFisico,
            $marca->de($estadoFisico->actualizadoEn),
            $progreso,
            $marca->de($progreso->actualizadoEn),
        );
    }
}
