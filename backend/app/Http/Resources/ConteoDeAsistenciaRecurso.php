<?php

namespace App\Http\Resources;

use Src\Competencia\Dominio\ConteoDeAsistenciaAEntrenamientos;

/** Porcentaje como cadena con dos decimales (DD-51), o nulo sin registros, y los conteos como enteros (DA-22). */
final class ConteoDeAsistenciaRecurso
{
    /** @return array{porcentaje: string|null, presentes: int, faltas: int, faltas_justificadas: int} */
    public static function de(ConteoDeAsistenciaAEntrenamientos $conteo): array
    {
        return [
            'porcentaje' => DecimalComoCadena::de($conteo->porcentaje()),
            'presentes' => $conteo->presentes(),
            'faltas' => $conteo->faltas(),
            'faltas_justificadas' => $conteo->faltasJustificadas(),
        ];
    }
}
