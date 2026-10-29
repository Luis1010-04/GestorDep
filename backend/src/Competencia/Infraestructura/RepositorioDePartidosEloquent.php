<?php

namespace Src\Competencia\Infraestructura;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Competencia\Dominio\CondicionDePartido;
use Src\Competencia\Dominio\EstadoDePartido;
use Src\Competencia\Dominio\Partido;
use Src\Competencia\Dominio\RepositorioDePartidos;
use Src\Competencia\Dominio\TipoDePartido;
use stdClass;

/** Escribe `partidos` y `partidos_historial` en la misma transacción (RNF-02). Los instantes van en UTC (RNF-04). */
final class RepositorioDePartidosEloquent implements RepositorioDePartidos
{
    private const COLUMNAS = ['id', 'equipo_id', 'temporada_id', 'torneo_id', 'rival_id', 'lugar_id', 'inicia_en', 'tipo', 'condicion', 'estado', 'goles_favor', 'goles_contra', 'actualizado_en'];

    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function guardarNuevo(Partido $partido): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $instante = $partido->actualizadoEn()->format('Y-m-d H:i:sP');

        $this->historial->registrar(
            'partidos',
            'partido_id',
            $partido->id(),
            'crear',
            null,
            $this->instantanea($partido),
            fn () => $this->conexion->table('partidos')->insert([
                'id' => $partido->id()->valor(),
                ...$this->columnas($partido),
                'creado_en' => $instante,
                'actualizado_en' => $instante,
                'creado_por' => $autor,
                'actualizado_por' => $autor,
            ]),
            instante: $partido->actualizadoEn(),
        );
    }

    public function actualizar(Partido $anterior, Partido $nuevo): void
    {
        $this->historial->registrar(
            'partidos',
            'partido_id',
            $nuevo->id(),
            'actualizar',
            $this->instantanea($anterior),
            $this->instantanea($nuevo),
            fn () => $this->conexion->table('partidos')->where('id', $nuevo->id()->valor())->update([
                ...$this->columnas($nuevo),
                'actualizado_en' => $nuevo->actualizadoEn()->format('Y-m-d H:i:sP'),
                'actualizado_por' => $this->usuarioActual->identificador()?->valor(),
            ]),
            instante: $nuevo->actualizadoEn(),
        );
    }

    public function buscar(Identificador $id): ?Partido
    {
        $fila = $this->conexion->table('partidos')->where('id', $id->valor())->first(self::COLUMNAS);

        return $fila === null ? null : $this->aPartido($fila);
    }

    public function listarDelEquipo(Identificador $equipoId, ?Identificador $temporadaId, ?EstadoDePartido $estado, int $pagina, int $porPagina, bool $descendente): array
    {
        $partidos = $this->delEquipo($equipoId, $temporadaId, $estado)
            ->orderBy('inicia_en', $descendente ? 'desc' : 'asc')
            ->orderBy('id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get(self::COLUMNAS)
            ->map(fn (stdClass $fila) => $this->aPartido($fila))
            ->all();

        return array_values($partidos);
    }

    public function contarDelEquipo(Identificador $equipoId, ?Identificador $temporadaId, ?EstadoDePartido $estado): int
    {
        return $this->delEquipo($equipoId, $temporadaId, $estado)->count();
    }

    private function delEquipo(Identificador $equipoId, ?Identificador $temporadaId, ?EstadoDePartido $estado): Builder
    {
        $consulta = $this->conexion->table('partidos')->where('equipo_id', $equipoId->valor());
        if ($temporadaId !== null) {
            $consulta->where('temporada_id', $temporadaId->valor());
        }
        if ($estado !== null) {
            $consulta->where('estado', $estado->value);
        }

        return $consulta;
    }

    /** @return array<string, string|int|null> */
    private function columnas(Partido $partido): array
    {
        return [
            'equipo_id' => $partido->equipoId()->valor(),
            'temporada_id' => $partido->temporadaId()->valor(),
            'torneo_id' => $partido->torneoId()?->valor(),
            'rival_id' => $partido->rivalId()->valor(),
            'lugar_id' => $partido->lugarId()->valor(),
            'inicia_en' => $partido->iniciaEn()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:sP'),
            'tipo' => $partido->tipo()->value,
            'condicion' => $partido->condicion()->value,
            'estado' => $partido->estado()->value,
            'goles_favor' => $partido->golesFavor(),
            'goles_contra' => $partido->golesContra(),
        ];
    }

    /** @return array<string, string|int|null> instantánea completa para el historial, con `inicia_en` en ISO 8601 UTC */
    private function instantanea(Partido $partido): array
    {
        return [
            ...$this->columnas($partido),
            'inicia_en' => $partido->iniciaEn()->setTimezone(new DateTimeZone('UTC'))->format(DateTimeInterface::ATOM),
        ];
    }

    private function aPartido(stdClass $fila): Partido
    {
        $utc = new DateTimeZone('UTC');

        return new Partido(
            Identificador::desdeTexto($fila->id),
            Identificador::desdeTexto($fila->equipo_id),
            Identificador::desdeTexto($fila->temporada_id),
            $fila->torneo_id === null ? null : Identificador::desdeTexto($fila->torneo_id),
            Identificador::desdeTexto($fila->rival_id),
            Identificador::desdeTexto($fila->lugar_id),
            (new DateTimeImmutable($fila->inicia_en))->setTimezone($utc),
            TipoDePartido::from($fila->tipo),
            CondicionDePartido::from($fila->condicion),
            EstadoDePartido::from($fila->estado),
            $fila->goles_favor === null ? null : (int) $fila->goles_favor,
            $fila->goles_contra === null ? null : (int) $fila->goles_contra,
            (new DateTimeImmutable($fila->actualizado_en))->setTimezone($utc),
        );
    }
}
