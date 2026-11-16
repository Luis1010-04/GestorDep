<?php

namespace Src\Consultas\Aplicacion;

/**
 * Los gráficos de historial de un jugador para el usuario que consulta (HU-25, DA-97, DA-105). Cada campo es nulo si el
 * usuario no lo puede ver: el entrenador del jugador recibe las cuatro series de entrenador; el docente, las dos listas de
 * materia. Un usuario con los dos roles recibe lo que le toca por cada uno.
 */
final class GraficosDelJugador
{
    /**
     * @param  array{peso_kg: SerieDeGrafico, estatura_cm: SerieDeGrafico, imc: SerieDeGrafico}|null  $mediciones
     * @param  list<SerieDeMateria>|null  $calificaciones
     * @param  list<SerieDeMateria>|null  $asistenciaClases
     */
    public function __construct(
        public readonly ?SerieDeGrafico $rendimientoPartidos,
        public readonly ?SerieDeGrafico $asistenciaEntrenamientos,
        public readonly ?array $mediciones,
        public readonly ?SerieDeGrafico $resumenAcademico,
        public readonly ?array $calificaciones,
        public readonly ?array $asistenciaClases,
    ) {}
}
