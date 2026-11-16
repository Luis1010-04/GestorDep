<?php

namespace Src\Consultas\Aplicacion;

/**
 * La vista para convocar de un jugador (HU-24, DD-15, DA-94): las dimensiones de §6.2, cada una con la marca
 * `posiblemente_desactualizado` ya calculada al leer (DA-98). Todo es del consultante (RN-14), salvo la disponibilidad y la
 * situación académica, que son compartidas.
 */
final class VistaParaConvocar
{
    public function __construct(
        public readonly JugadorConvocable $jugador,
        public readonly DatosDeDisponibilidad $disponibilidad,
        public readonly bool $disponibilidadDesactualizada,
        public readonly ?EvaluacionReciente $rendimiento,
        public readonly bool $rendimientoDesactualizado,
        public readonly DatosDeSituacionAcademica $situacion,
        public readonly bool $situacionDesactualizada,
        public readonly DatosDeAsistencia $asistencia,
        public readonly bool $asistenciaDelEquipoDesactualizada,
        public readonly bool $asistenciaDeMisCapturasDesactualizada,
        public readonly DatosDeEstadoFisico $estadoFisico,
        public readonly bool $estadoFisicoDesactualizado,
        public readonly DatosDeSerie $progreso,
        public readonly bool $progresoDesactualizado,
    ) {}
}
