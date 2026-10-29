<?php

namespace Src\Academico\Aplicacion;

use Src\Academico\Dominio\Asignatura;
use Src\Academico\Dominio\RepositorioDeAsignaturas;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;

/** Alta de una asignatura (HU-28). */
final class CrearAsignatura
{
    public function __construct(
        private readonly RepositorioDeAsignaturas $asignaturas,
        private readonly Reloj $reloj,
    ) {}

    /** @throws AsignaturaDuplicada */
    public function ejecutar(string $clave, string $nombre, int $grado): Asignatura
    {
        if ($this->asignaturas->existeClave($clave)) {
            throw new AsignaturaDuplicada;
        }

        $asignatura = new Asignatura(Identificador::generar(), $clave, $nombre, $grado, Instante::truncadoASegundos($this->reloj->ahora()));
        $this->asignaturas->guardarNueva($asignatura);

        return $asignatura;
    }
}
