<?php

namespace Src\Academico\Infraestructura;

use Src\Academico\Aplicacion\FuenteDeDatosAcademicos;
use Src\Compartido\Aplicacion\JugadoresDeLaMateria;
use Src\Compartido\Dominio\Identificador;

/** Adaptador del puerto de Compartido para Alertas (DA-115): los jugadores inscritos en la materia, de la misma fuente que el recálculo de resúmenes. */
final class JugadoresDeLaMateriaAcademico implements JugadoresDeLaMateria
{
    public function __construct(private readonly FuenteDeDatosAcademicos $datos) {}

    public function de(Identificador $materia): array
    {
        return $this->datos->jugadoresDeLaMateria($materia);
    }
}
