<?php

namespace Src\Academico\Aplicacion;

use InvalidArgumentException;
use Src\Academico\Dominio\CriterioDeAprobacion;
use Src\Academico\Dominio\Materia;
use Src\Academico\Dominio\RepositorioDeAsignaturas;
use Src\Academico\Dominio\RepositorioDeCriterios;
use Src\Academico\Dominio\RepositorioDeMaterias;
use Src\Academico\Dominio\RepositorioDePeriodos;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;

/** Alta de una materia del docente que la crea (HU-06). */
final class CrearMateria
{
    public function __construct(
        private readonly RepositorioDeMaterias $materias,
        private readonly RepositorioDeAsignaturas $asignaturas,
        private readonly RepositorioDePeriodos $periodos,
        private readonly RepositorioDeCriterios $criterios,
        private readonly ConsultaDeMaterias $consulta,
        private readonly Reloj $reloj,
    ) {}

    /** @throws AsignaturaInexistente|PeriodoInexistente|MateriaDuplicada */
    public function ejecutar(Identificador $docente, string $asignaturaId, string $periodoId): MateriaConCriterio
    {
        $asignatura = $this->identificadorVigente($asignaturaId, fn (Identificador $id) => $this->asignaturas->existe($id), new AsignaturaInexistente);
        $periodo = $this->identificadorVigente($periodoId, fn (Identificador $id) => $this->periodos->existe($id), new PeriodoInexistente);

        if ($this->materias->existe($asignatura, $periodo, $docente)) {
            throw new MateriaDuplicada;
        }

        $materia = new Materia(Identificador::generar(), $asignatura, $periodo, $docente, Instante::truncadoASegundos($this->reloj->ahora()));
        $this->materias->guardarNueva($materia);

        $detalle = $this->consulta->buscarDelDocente($materia->id(), $docente);
        assert($detalle !== null);

        return new MateriaConCriterio($detalle, CriterioDeAprobacion::resolver(null, $this->criterios->generalVigente()));
    }

    /**
     * @param  callable(Identificador): bool  $existe
     */
    private function identificadorVigente(string $texto, callable $existe, AsignaturaInexistente|PeriodoInexistente $inexistente): Identificador
    {
        try {
            $id = Identificador::desdeTexto($texto);
        } catch (InvalidArgumentException) {
            throw $inexistente;
        }

        return $existe($id) ? $id : throw $inexistente;
    }
}
