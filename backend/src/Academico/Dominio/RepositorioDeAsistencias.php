<?php

namespace Src\Academico\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Persistencia de asistencias a clase con su historial (RNF-02). Solo ve las no eliminadas. */
interface RepositorioDeAsistencias
{
    /**
     * Escribe la asistencia y su registro `crear` de historial en una sola transacción. Si ya existe una de la misma
     * inscripción y fecha, lanza AsistenciaDuplicada (Aplicacion).
     */
    public function guardarNueva(AsistenciaDeClase $asistencia): void;

    /**
     * Cambia el estado y escribe el registro `actualizar` con la instantánea anterior y la nueva, en una transacción y con
     * un único instante. Devuelve falso, sin escribir nada, si el estado es igual al guardado.
     */
    public function corregirEstado(Identificador $asistenciaId, EstadoDeAsistencia $estado, DateTimeImmutable $instante): bool;
}
