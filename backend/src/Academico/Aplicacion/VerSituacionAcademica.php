<?php

namespace Src\Academico\Aplicacion;

use Src\Academico\Dominio\CalificacionDeMateria;
use Src\Academico\Dominio\CriterioDeAprobacion;
use Src\Academico\Dominio\MateriaEvaluada;
use Src\Academico\Dominio\RepositorioDeCriterios;
use Src\Academico\Dominio\TipoDeCalificacion;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;

/**
 * Situación académica de un jugador en el periodo vigente (HU-09). Lee el resumen ya calculado; no recalcula. Quien no
 * tiene al jugador en su alcance recibe nulo (404). Solo quien es docente del jugador recibe el detalle de **sus propias**
 * materias del periodo vigente (DD-48, DA-15), con la calificación y el criterio vigente calculados por el dominio de T04.
 */
final class VerSituacionAcademica
{
    public function __construct(
        private readonly ConsultaDeSituacionAcademica $consulta,
        private readonly ConsultaDeCalificaciones $calificaciones,
        private readonly RepositorioDeCriterios $criterios,
        private readonly PeriodoVigente $periodoVigente,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function ejecutar(Identificador $jugadorId): ?SituacionAcademica
    {
        if (! $this->consulta->jugadorVisible($jugadorId)) {
            return null;
        }

        $periodoId = $this->periodoVigente->identificador();

        return new SituacionAcademica(
            $periodoId,
            $periodoId === null ? null : $this->consulta->nombreDelPeriodo($periodoId),
            $periodoId === null ? null : $this->consulta->resumen($jugadorId, $periodoId),
            $this->materiasDelDocente($jugadorId, $periodoId),
        );
    }

    /** @return list<MateriaDeLaSituacion>|null nula si el usuario no es docente del jugador */
    private function materiasDelDocente(Identificador $jugadorId, ?Identificador $periodoId): ?array
    {
        $docente = $this->usuarioActual->identificador();
        if ($docente === null || ! in_array('docente', $this->usuarioActual->roles(), true) || ! $this->consulta->esDocenteDelJugador($jugadorId, $docente)) {
            return null;
        }
        if ($periodoId === null) {
            return [];
        }

        $general = $this->criterios->generalVigente();

        return array_map(
            fn (MateriaDelDocente $materia): MateriaDeLaSituacion => $this->evaluar($materia, $general),
            $this->consulta->materiasDelDocente($jugadorId, $docente, $periodoId),
        );
    }

    private function evaluar(MateriaDelDocente $materia, CriterioDeAprobacion $general): MateriaDeLaSituacion
    {
        $calificaciones = $this->calificaciones->deLaInscripcion($materia->inscripcionId);
        $final = null;
        $parciales = [];
        foreach ($calificaciones as $calificacion) {
            if ($calificacion->tipo === TipoDeCalificacion::Final) {
                $final = $calificacion->valor;
            } else {
                $parciales[] = $calificacion->valor;
            }
        }

        $criterio = CriterioDeAprobacion::resolver($this->criterios->propioVigente($materia->materiaId)?->criterio(), $general);
        $evaluada = new MateriaEvaluada(CalificacionDeMateria::calcular($final, $parciales), $criterio);

        return new MateriaDeLaSituacion(
            $materia->materiaId,
            $materia->clave,
            $materia->nombre,
            $evaluada->calificacion(),
            $criterio,
            $evaluada->aprobada(),
            $calificaciones,
        );
    }
}
