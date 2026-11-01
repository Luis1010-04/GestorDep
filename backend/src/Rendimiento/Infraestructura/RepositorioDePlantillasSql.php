<?php

namespace Src\Rendimiento\Infraestructura;

use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Rendimiento\Aplicacion\ConsultaDePlantillas;
use Src\Rendimiento\Aplicacion\PlantillaDuplicada;
use Src\Rendimiento\Aplicacion\PosicionInexistente;
use Src\Rendimiento\Dominio\CriterioDeEvaluacion;
use Src\Rendimiento\Dominio\PlantillaDeEvaluacion;
use Src\Rendimiento\Dominio\RepositorioDePlantillas;
use stdClass;

/** Lee y escribe `plantillas_evaluacion`; cada alta lleva su historial en la misma transacción (RNF-02). */
final class RepositorioDePlantillasSql implements ConsultaDePlantillas, RepositorioDePlantillas
{
    private const COLUMNAS = ['id', 'nombre', 'posicion_id', 'creada_por', 'calificacion_minima', 'ventana_partidos'];

    /** Clave foránea violada: la única que puede fallar al insertar es la de la posición. */
    private const CLAVE_FORANEA_VIOLADA = '23503';

    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function guardarNueva(PlantillaDeEvaluacion $plantilla, DateTimeImmutable $instante): void
    {
        $momento = $instante->format('Y-m-d H:i:sP');
        $autor = $this->usuarioActual->identificador()?->valor();
        $instantanea = self::instantanea($plantilla);

        try {
            $this->historial->registrar(
                'plantillas_evaluacion', 'plantilla_id', $plantilla->id(), 'crear', null, $instantanea,
                fn () => $this->conexion->table('plantillas_evaluacion')->insert([
                    'id' => $plantilla->id()->valor(), ...$instantanea,
                    'creado_en' => $momento, 'actualizado_en' => $momento, 'creado_por' => $autor, 'actualizado_por' => $autor,
                ]),
                instante: $instante,
            );
        } catch (UniqueConstraintViolationException) {
            throw new PlantillaDuplicada;
        } catch (QueryException $e) {
            throw $e->getCode() === self::CLAVE_FORANEA_VIOLADA ? new PosicionInexistente : $e;
        }
    }

    public function nombreOcupado(string $nombre, Identificador $posicion, Identificador $entrenador): bool
    {
        return $this->visibles($entrenador)
            ->where('nombre', $nombre)
            ->where('posicion_id', $posicion->valor())
            ->exists();
    }

    public function buscarVisible(Identificador $id, Identificador $entrenador): ?PlantillaDeEvaluacion
    {
        $fila = $this->visibles($entrenador)->where('id', $id->valor())->first(self::COLUMNAS);

        return $fila === null ? null : self::aPlantilla($fila);
    }

    public function deFabricaDe(Identificador $posicion): ?PlantillaDeEvaluacion
    {
        $fila = $this->conexion->table('plantillas_evaluacion')
            ->whereNull('eliminado_en')
            ->whereNull('creada_por')
            ->where('posicion_id', $posicion->valor())
            ->orderBy('creado_en')
            ->orderBy('id')
            ->first(self::COLUMNAS);

        return $fila === null ? null : self::aPlantilla($fila);
    }

    public function visiblesDelUsuario(?Identificador $posicion, int $pagina, int $porPagina): array
    {
        $entrenador = $this->usuarioActual->identificador();
        if ($entrenador === null) {
            return ['elementos' => [], 'total' => 0];
        }

        $consulta = $this->visibles($entrenador)->when($posicion, fn (Builder $q, Identificador $p) => $q->where('posicion_id', $p->valor()));
        $total = $consulta->getCountForPagination();
        $filas = $consulta
            ->orderByRaw('creada_por is null desc')
            ->orderBy('nombre')
            ->orderBy('id')
            ->forPage($pagina, $porPagina)
            ->get(self::COLUMNAS);

        return ['elementos' => array_values($filas->map(fn (stdClass $fila) => self::aPlantilla($fila))->all()), 'total' => $total];
    }

    /** Las de fábrica y las del entrenador, no eliminadas (§5.3). */
    private function visibles(Identificador $entrenador): Builder
    {
        return $this->conexion->table('plantillas_evaluacion')
            ->whereNull('eliminado_en')
            ->where(fn (Builder $q) => $q->whereNull('creada_por')->orWhere('creada_por', $entrenador->valor()));
    }

    /** @return array{nombre: string, posicion_id: string, creada_por: string|null, calificacion_minima: string, ventana_partidos: int, es_de_fabrica: bool} */
    private static function instantanea(PlantillaDeEvaluacion $plantilla): array
    {
        return [
            'nombre' => $plantilla->nombre(),
            'posicion_id' => $plantilla->posicionId()->valor(),
            'creada_por' => $plantilla->creadaPor()?->valor(),
            'calificacion_minima' => $plantilla->criterio()->calificacionMinima()->comoCadena(),
            'ventana_partidos' => $plantilla->criterio()->ventanaPartidos(),
            'es_de_fabrica' => $plantilla->esDeFabrica(),
        ];
    }

    private static function aPlantilla(stdClass $fila): PlantillaDeEvaluacion
    {
        return new PlantillaDeEvaluacion(
            Identificador::desdeTexto($fila->id),
            $fila->nombre,
            Identificador::desdeTexto($fila->posicion_id),
            $fila->creada_por === null ? null : Identificador::desdeTexto($fila->creada_por),
            new CriterioDeEvaluacion(NumeroDecimal::desdeTexto($fila->calificacion_minima), $fila->ventana_partidos),
        );
    }
}
