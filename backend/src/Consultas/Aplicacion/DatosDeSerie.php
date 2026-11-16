<?php

namespace Src\Consultas\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Aplicacion\AsistenciaPorMes;

/** Los puntos de una serie y la fecha de la última actualización de los registros que la forman, sin la marca de desactualizada. */
final class DatosDeSerie
{
    /**
     * @param  list<PuntoDeSerie>  $puntos  ordenados de lo más antiguo a lo más reciente
     * @param  DateTimeImmutable|null  $actualizadoEn  en UTC; nulo si la serie no tiene datos
     */
    public function __construct(
        public readonly array $puntos,
        public readonly ?DateTimeImmutable $actualizadoEn,
    ) {}

    /** El porcentaje de asistencia de cada mes, como puntos `{periodo: AAAA-MM, valor}` con dos decimales. */
    public static function deAsistencia(AsistenciaPorMes $asistencia): self
    {
        $puntos = [];
        foreach ($asistencia->porcentajes as $mes => $porcentaje) {
            $puntos[] = PuntoDeSerie::enPeriodo($mes, $porcentaje->comoCadena(2));
        }

        return new self($puntos, $asistencia->actualizadoEn);
    }
}
