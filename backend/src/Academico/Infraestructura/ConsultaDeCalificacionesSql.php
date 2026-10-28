<?php

namespace Src\Academico\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Academico\Aplicacion\CalificacionLeida;
use Src\Academico\Aplicacion\ConsultaDeCalificaciones;
use Src\Academico\Dominio\AmbitoDeCriterio;
use Src\Academico\Dominio\CriterioDeAprobacion;
use Src\Academico\Dominio\TipoDeCalificacion;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use stdClass;

/** Lecturas de calificaciones no eliminadas con el criterio guardado al capturarlas (DD-47). No leen jugadores. */
final class ConsultaDeCalificacionesSql implements ConsultaDeCalificaciones
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function buscar(Identificador $calificacionId): ?CalificacionLeida
    {
        $fila = $this->vigentes()->where('calificaciones.id', $calificacionId->valor())->first($this->columnas());

        return $fila === null ? null : $this->aCalificacion($fila);
    }

    public function inscripcionDe(Identificador $calificacionId): ?Identificador
    {
        $inscripcion = $this->conexion->table('calificaciones')
            ->where('id', $calificacionId->valor())
            ->whereNull('eliminado_en')
            ->value('inscripcion_id');

        return $inscripcion === null ? null : Identificador::desdeTexto((string) $inscripcion);
    }

    public function listarDeLaInscripcion(Identificador $inscripcionId, int $pagina, int $porPagina): array
    {
        $filas = $this->vigentes()
            ->where('calificaciones.inscripcion_id', $inscripcionId->valor())
            ->orderByRaw("case when calificaciones.tipo = 'final' then 1 else 0 end")
            ->orderBy('calificaciones.numero_parcial')
            ->orderBy('calificaciones.id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get($this->columnas())
            ->map(fn (stdClass $fila): CalificacionLeida => $this->aCalificacion($fila))
            ->all();

        return array_values($filas);
    }

    public function contarDeLaInscripcion(Identificador $inscripcionId): int
    {
        return $this->vigentes()->where('calificaciones.inscripcion_id', $inscripcionId->valor())->count();
    }

    private function vigentes(): Builder
    {
        return $this->conexion->table('calificaciones')
            ->join('criterios_aprobacion', 'criterios_aprobacion.id', '=', 'calificaciones.criterio_id')
            ->whereNull('calificaciones.eliminado_en');
    }

    /** @return list<string> */
    private function columnas(): array
    {
        return [
            'calificaciones.id', 'calificaciones.inscripcion_id', 'calificaciones.tipo', 'calificaciones.numero_parcial',
            'calificaciones.valor', 'calificaciones.fecha', 'calificaciones.actualizado_en',
            'criterios_aprobacion.ambito', 'criterios_aprobacion.calificacion_minima',
        ];
    }

    private function aCalificacion(stdClass $fila): CalificacionLeida
    {
        return new CalificacionLeida(
            Identificador::desdeTexto($fila->id),
            Identificador::desdeTexto($fila->inscripcion_id),
            TipoDeCalificacion::from($fila->tipo),
            $fila->numero_parcial === null ? null : (int) $fila->numero_parcial,
            NumeroDecimal::desdeTexto((string) $fila->valor),
            new CriterioDeAprobacion(AmbitoDeCriterio::from($fila->ambito), NumeroDecimal::desdeTexto((string) $fila->calificacion_minima)),
            new DateTimeImmutable($fila->fecha, new DateTimeZone('UTC')),
            (new DateTimeImmutable($fila->actualizado_en))->setTimezone(new DateTimeZone('UTC')),
        );
    }
}
