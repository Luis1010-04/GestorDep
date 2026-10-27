<?php

namespace Src\Academico\Dominio;

use Src\Compartido\Dominio\NumeroDecimal;

/** Calificación de una materia (RN-02, DD-43): la final prevalece; sin ella, la media de los parciales. */
final class CalificacionDeMateria
{
    /**
     * Redondeada a dos decimales (al medio hacia arriba). `null` si no hay final ni parciales; el cero sí es calificación.
     *
     * @param  list<NumeroDecimal>  $parciales
     */
    public static function calcular(?NumeroDecimal $final, array $parciales): ?NumeroDecimal
    {
        if ($final !== null) {
            return $final->redondear();
        }

        if ($parciales === []) {
            return null;
        }

        return NumeroDecimal::media($parciales);
    }
}
