<?php

namespace Src\Academico\Aplicacion;

use Src\Academico\Dominio\CriterioVigente;
use Src\Academico\Dominio\EscalaDeCalificaciones;
use Src\Academico\Dominio\RepositorioDeCriterios;
use Src\Compartido\Aplicacion\PublicadorDeEventos;
use Src\Compartido\Dominio\Eventos\CriterioDeMateriaModificado;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;

/**
 * Define el criterio de aprobación propio de una materia del docente (HU-08, RN-03). Un valor igual al vigente no escribe
 * ni publica nada; uno distinto cierra el vigente y crea el nuevo, y publica `CriterioDeMateriaModificado`.
 * La pertenencia de la materia al docente la comprueba la solicitud HTTP antes de llegar aquí.
 */
final class DefinirCriterioDeMateria
{
    public function __construct(
        private readonly RepositorioDeCriterios $criterios,
        private readonly HoyDeNegocio $hoyDeNegocio,
        private readonly PublicadorDeEventos $eventos,
    ) {}

    /** @throws CalificacionFueraDeRango */
    public function ejecutar(Identificador $materia, NumeroDecimal $minimo): CriterioVigente
    {
        if (! EscalaDeCalificaciones::contiene($minimo)) {
            throw new CalificacionFueraDeRango('calificacion_minima');
        }

        $actual = $this->criterios->propioVigente($materia);
        if ($actual !== null && $actual->criterio()->calificacionMinima()->igualA($minimo)) {
            return $actual;
        }

        $nuevo = $this->criterios->reemplazarPropio($materia, $minimo, $this->hoyDeNegocio->hoy());
        $this->eventos->publicar(new CriterioDeMateriaModificado($materia->valor()));

        return $nuevo;
    }
}
