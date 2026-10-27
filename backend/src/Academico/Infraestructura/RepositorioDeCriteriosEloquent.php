<?php

namespace Src\Academico\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use LogicException;
use Src\Academico\Dominio\AmbitoDeCriterio;
use Src\Academico\Dominio\CriterioDeAprobacion;
use Src\Academico\Dominio\CriterioVigente;
use Src\Academico\Dominio\RepositorioDeCriterios;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;

/** Lee `criterios_aprobacion` y escribe el criterio propio de una materia con su historial en la misma transacción (RNF-02). */
final class RepositorioDeCriteriosEloquent implements RepositorioDeCriterios
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
        private readonly Reloj $reloj,
    ) {}

    public function generalVigente(): CriterioDeAprobacion
    {
        $minimo = $this->conexion->table('criterios_aprobacion')
            ->where('ambito', 'general')
            ->whereNull('vigente_hasta')
            ->whereNull('eliminado_en')
            ->value('calificacion_minima');

        if ($minimo === null) {
            throw new LogicException('criterio_general_inexistente');
        }

        return new CriterioDeAprobacion(AmbitoDeCriterio::General, NumeroDecimal::desdeTexto((string) $minimo));
    }

    public function propiosVigentes(array $materias): array
    {
        if ($materias === []) {
            return [];
        }

        $propios = [];
        $filas = $this->propios()
            ->whereIn('materia_id', array_map(fn (Identificador $materia): string => $materia->valor(), $materias))
            ->get(['materia_id', 'calificacion_minima']);
        foreach ($filas as $fila) {
            $propios[$fila->materia_id] = new CriterioDeAprobacion(AmbitoDeCriterio::Materia, NumeroDecimal::desdeTexto((string) $fila->calificacion_minima));
        }

        return $propios;
    }

    public function propioVigente(Identificador $materia): ?CriterioVigente
    {
        $fila = $this->propios()->where('materia_id', $materia->valor())->first(['calificacion_minima', 'vigente_desde']);

        return $fila === null ? null : new CriterioVigente(
            new CriterioDeAprobacion(AmbitoDeCriterio::Materia, NumeroDecimal::desdeTexto((string) $fila->calificacion_minima)),
            new DateTimeImmutable($fila->vigente_desde, new DateTimeZone('UTC')),
        );
    }

    public function reemplazarPropio(Identificador $materia, NumeroDecimal $minimo, DateTimeImmutable $hoy): CriterioVigente
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $momento = Instante::truncadoASegundos($this->reloj->ahora());
        $instante = $momento->format('Y-m-d H:i:sP');
        $fecha = $hoy->format('Y-m-d');

        $this->conexion->transaction(function () use ($materia, $minimo, $autor, $momento, $instante, $fecha): void {
            $anterior = $this->propios()->where('materia_id', $materia->valor())->lockForUpdate()->first(['id', 'calificacion_minima', 'vigente_desde']);

            if ($anterior !== null) {
                $datos = fn (?string $hasta): array => [
                    'ambito' => 'materia',
                    'materia_id' => $materia->valor(),
                    'calificacion_minima' => $anterior->calificacion_minima,
                    'vigente_desde' => $anterior->vigente_desde,
                    'vigente_hasta' => $hasta,
                ];
                $this->historial->registrar(
                    'criterios_aprobacion',
                    'criterio_aprobacion_id',
                    Identificador::desdeTexto($anterior->id),
                    'actualizar',
                    $datos(null),
                    $datos($fecha),
                    fn () => $this->conexion->table('criterios_aprobacion')->where('id', $anterior->id)->update([
                        'vigente_hasta' => $fecha,
                        'actualizado_en' => $instante,
                        'actualizado_por' => $autor,
                    ]),
                    instante: $momento,
                );
            }

            $nuevoId = Identificador::generar();
            $this->historial->registrar(
                'criterios_aprobacion',
                'criterio_aprobacion_id',
                $nuevoId,
                'crear',
                null,
                [
                    'ambito' => 'materia',
                    'materia_id' => $materia->valor(),
                    'calificacion_minima' => $minimo->comoCadena(2),
                    'vigente_desde' => $fecha,
                    'vigente_hasta' => null,
                ],
                fn () => $this->conexion->table('criterios_aprobacion')->insert([
                    'id' => $nuevoId->valor(),
                    'ambito' => 'materia',
                    'materia_id' => $materia->valor(),
                    'calificacion_minima' => $minimo->comoCadena(2),
                    'vigente_desde' => $fecha,
                    'creado_en' => $instante,
                    'actualizado_en' => $instante,
                    'creado_por' => $autor,
                    'actualizado_por' => $autor,
                ]),
                instante: $momento,
            );
        });

        return new CriterioVigente(new CriterioDeAprobacion(AmbitoDeCriterio::Materia, $minimo->redondear(2)), $hoy);
    }

    private function propios(): Builder
    {
        return $this->conexion->table('criterios_aprobacion')
            ->where('ambito', 'materia')
            ->whereNull('vigente_hasta')
            ->whereNull('eliminado_en');
    }
}
