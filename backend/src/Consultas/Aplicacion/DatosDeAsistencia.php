<?php

namespace Src\Consultas\Aplicacion;

/** Las dos asistencias a entrenamientos de DA-111: la de los equipos del consultante y la de los registros que capturó él. */
final class DatosDeAsistencia
{
    public function __construct(
        public readonly PorcentajeDeAsistencia $equipo,
        public readonly PorcentajeDeAsistencia $deMisCapturas,
    ) {}
}
