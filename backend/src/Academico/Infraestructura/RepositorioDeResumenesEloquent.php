<?php

namespace Src\Academico\Infraestructura;

use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Src\Academico\Dominio\RepositorioDeResumenes;
use Src\Academico\Dominio\ResumenAcademico;
use Src\Compartido\Dominio\Identificador;

final class RepositorioDeResumenesEloquent implements RepositorioDeResumenes
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function guardar(Identificador $jugadorId, Identificador $periodoId, ResumenAcademico $resumen, DateTimeImmutable $calculadoEn): void
    {
        $this->conexion->table('resumenes_academicos')->upsert(
            [[
                'id' => Identificador::generar()->valor(),
                'jugador_id' => $jugadorId->valor(),
                'periodo_id' => $periodoId->valor(),
                'promedio' => $resumen->promedio()?->comoCadena(2),
                'materias_reprobadas' => $resumen->materiasReprobadas(),
                'materias_sin_calificar' => $resumen->materiasSinCalificar(),
                'porcentaje_asistencia' => $resumen->porcentajeAsistencia()?->comoCadena(2),
                'nivel_riesgo' => $resumen->nivelRiesgo()?->value,
                'calculado_en' => $calculadoEn->format('Y-m-d H:i:sP'),
            ]],
            ['jugador_id', 'periodo_id'],
            ['promedio', 'materias_reprobadas', 'materias_sin_calificar', 'porcentaje_asistencia', 'nivel_riesgo', 'calculado_en'],
        );
    }
}
