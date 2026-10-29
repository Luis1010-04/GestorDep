<?php

namespace Src\Academico\Dominio;

use Src\Compartido\Dominio\NumeroDecimal;

/** Resultado del cálculo académico de un jugador (RN-02, RN-04, RN-05). */
final class ResumenAcademico
{
    private function __construct(
        private readonly ?NumeroDecimal $promedio,
        private readonly int $materiasReprobadas,
        private readonly int $materiasSinCalificar,
        private readonly ?NumeroDecimal $porcentajeAsistencia,
        private readonly ?NivelDeRiesgo $nivelRiesgo,
    ) {}

    /**
     * El promedio es la media de las calificaciones de las materias que tienen una. La asistencia se copia al
     * resultado y no entra en el nivel de riesgo (RN-05).
     *
     * @param  list<MateriaEvaluada>  $materias
     */
    public static function calcular(array $materias, ConteoDeAsistencia $asistencia, ParametrosDeRiesgo $parametros): self
    {
        $calificaciones = [];
        $reprobadas = 0;
        $sinCalificar = 0;

        foreach ($materias as $materia) {
            $calificacion = $materia->calificacion();

            if ($calificacion === null) {
                $sinCalificar++;

                continue;
            }

            $calificaciones[] = $calificacion;

            if ($materia->aprobada() === false) {
                $reprobadas++;
            }
        }

        $promedio = $calificaciones === [] ? null : NumeroDecimal::media($calificaciones);

        return new self(
            $promedio,
            $reprobadas,
            $sinCalificar,
            $asistencia->porcentaje(),
            RiesgoAcademico::nivel($promedio, $reprobadas, $parametros),
        );
    }

    public function promedio(): ?NumeroDecimal
    {
        return $this->promedio;
    }

    public function materiasReprobadas(): int
    {
        return $this->materiasReprobadas;
    }

    public function materiasSinCalificar(): int
    {
        return $this->materiasSinCalificar;
    }

    /** Hay materias sin calificar, o ninguna materia tiene calificación. */
    public function datoIncompleto(): bool
    {
        return $this->materiasSinCalificar > 0 || $this->promedio === null;
    }

    public function porcentajeAsistencia(): ?NumeroDecimal
    {
        return $this->porcentajeAsistencia;
    }

    public function nivelRiesgo(): ?NivelDeRiesgo
    {
        return $this->nivelRiesgo;
    }
}
