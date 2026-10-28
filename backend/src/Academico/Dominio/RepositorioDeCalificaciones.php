<?php

namespace Src\Academico\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;

/** Persistencia de calificaciones con su historial (RNF-02). Solo ve las no eliminadas. */
interface RepositorioDeCalificaciones
{
    /**
     * Escribe la calificación y su registro `crear` de historial en una sola transacción. Si ya existe una del mismo tipo y
     * número de parcial en la inscripción, lanza CalificacionDuplicada (Aplicacion).
     */
    public function guardarNueva(Calificacion $calificacion): void;

    /**
     * Cambia el valor y escribe el registro `actualizar` con la instantánea anterior y la nueva, en una transacción y con
     * un único instante. Devuelve falso, sin escribir nada, si el valor es igual al guardado.
     */
    public function corregirValor(Identificador $calificacionId, NumeroDecimal $valor, DateTimeImmutable $instante): bool;
}
