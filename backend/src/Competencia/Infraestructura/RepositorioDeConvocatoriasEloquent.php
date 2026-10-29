<?php

namespace Src\Competencia\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Competencia\Aplicacion\JugadorYaConvocado;
use Src\Competencia\Dominio\Convocatoria;
use Src\Competencia\Dominio\RepositorioDeConvocatorias;
use Src\Competencia\Dominio\RolDeAlineacion;
use stdClass;

/** Escribe `convocatorias` y `convocatorias_historial` en la misma transacción (RNF-02). */
final class RepositorioDeConvocatoriasEloquent implements RepositorioDeConvocatorias
{
    public const COLUMNAS = ['id', 'partido_id', 'jugador_id', 'posicion_id', 'rol_alineacion', 'actualizado_en'];

    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
        private readonly Reloj $reloj,
    ) {}

    public function estaConvocado(Identificador $partidoId, Identificador $jugadorId): bool
    {
        return $this->vigentes()->where('partido_id', $partidoId->valor())->where('jugador_id', $jugadorId->valor())->exists();
    }

    public function guardarNueva(Convocatoria $convocatoria): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $instante = $convocatoria->actualizadoEn()->format('Y-m-d H:i:sP');

        try {
            $this->historial->registrar(
                'convocatorias',
                'convocatoria_id',
                $convocatoria->id(),
                'crear',
                null,
                self::instantanea($convocatoria),
                fn () => $this->conexion->table('convocatorias')->insert([
                    'id' => $convocatoria->id()->valor(),
                    ...self::instantanea($convocatoria),
                    'creado_en' => $instante,
                    'actualizado_en' => $instante,
                    'creado_por' => $autor,
                    'actualizado_por' => $autor,
                ]),
                instante: $convocatoria->actualizadoEn(),
            );
        } catch (UniqueConstraintViolationException) {
            throw new JugadorYaConvocado;
        }
    }

    public function buscar(Identificador $id): ?Convocatoria
    {
        $fila = $this->vigentes()->where('id', $id->valor())->first(self::COLUMNAS);

        return $fila === null ? null : self::aConvocatoria($fila);
    }

    public function eliminar(Convocatoria $convocatoria): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $instante = Instante::truncadoASegundos($this->reloj->ahora());
        $momento = $instante->format('Y-m-d H:i:sP');

        $this->historial->registrar(
            'convocatorias',
            'convocatoria_id',
            $convocatoria->id(),
            'eliminar',
            self::instantanea($convocatoria),
            null,
            fn () => $this->conexion->table('convocatorias')->where('id', $convocatoria->id()->valor())->update([
                'eliminado_en' => $momento,
                'eliminado_por' => $autor,
                'actualizado_en' => $momento,
                'actualizado_por' => $autor,
            ]),
            instante: $instante,
        );
    }

    /** @return array{partido_id: string, jugador_id: string, posicion_id: string|null, rol_alineacion: string|null} */
    private static function instantanea(Convocatoria $convocatoria): array
    {
        return [
            'partido_id' => $convocatoria->partidoId()->valor(),
            'jugador_id' => $convocatoria->jugadorId()->valor(),
            'posicion_id' => $convocatoria->posicionId()?->valor(),
            'rol_alineacion' => $convocatoria->rolAlineacion()?->value,
        ];
    }

    public static function aConvocatoria(stdClass $fila): Convocatoria
    {
        return new Convocatoria(
            Identificador::desdeTexto($fila->id),
            Identificador::desdeTexto($fila->partido_id),
            Identificador::desdeTexto($fila->jugador_id),
            $fila->posicion_id === null ? null : Identificador::desdeTexto($fila->posicion_id),
            $fila->rol_alineacion === null ? null : RolDeAlineacion::from($fila->rol_alineacion),
            (new DateTimeImmutable($fila->actualizado_en))->setTimezone(new DateTimeZone('UTC')),
        );
    }

    private function vigentes(): Builder
    {
        return $this->conexion->table('convocatorias')->whereNull('eliminado_en');
    }
}
