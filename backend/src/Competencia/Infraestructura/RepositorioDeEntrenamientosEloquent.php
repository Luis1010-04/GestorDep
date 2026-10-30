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
use Src\Competencia\Dominio\Entrenamiento;
use Src\Competencia\Dominio\EstadoDeEntrenamiento;
use Src\Competencia\Dominio\RepositorioDeEntrenamientos;
use stdClass;

/** Escribe `entrenamientos` y `entrenamientos_historial` en la misma transacción (RNF-02). Los instantes van en UTC (RNF-04). */
final class RepositorioDeEntrenamientosEloquent implements RepositorioDeEntrenamientos
{
    private const COLUMNAS = ['id', 'equipo_id', 'lugar_id', 'inicia_en', 'termina_en', 'responsable_id', 'estado', 'actualizado_en'];

    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function guardarNuevo(Entrenamiento $entrenamiento): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $instante = $entrenamiento->actualizadoEn()->format('Y-m-d H:i:sP');

        $this->historial->registrar(
            'entrenamientos',
            'entrenamiento_id',
            $entrenamiento->id(),
            'crear',
            null,
            $this->instantanea($entrenamiento),
            fn () => $this->conexion->table('entrenamientos')->insert([
                'id' => $entrenamiento->id()->valor(),
                ...$this->columnas($entrenamiento),
                'creado_en' => $instante,
                'actualizado_en' => $instante,
                'creado_por' => $autor,
                'actualizado_por' => $autor,
            ]),
            instante: $entrenamiento->actualizadoEn(),
        );
    }

    public function actualizar(Entrenamiento $anterior, Entrenamiento $nuevo): void
    {
        $this->historial->registrar(
            'entrenamientos',
            'entrenamiento_id',
            $nuevo->id(),
            'actualizar',
            $this->instantanea($anterior),
            $this->instantanea($nuevo),
            fn () => $this->conexion->table('entrenamientos')->where('id', $nuevo->id()->valor())->update([
                ...$this->columnas($nuevo),
                'actualizado_en' => $nuevo->actualizadoEn()->format('Y-m-d H:i:sP'),
                'actualizado_por' => $this->usuarioActual->identificador()?->valor(),
            ]),
            instante: $nuevo->actualizadoEn(),
        );
    }

    public function buscar(Identificador $id): ?Entrenamiento
    {
        $fila = $this->conexion->table('entrenamientos')->where('id', $id->valor())->first(self::COLUMNAS);

        return $fila === null ? null : $this->aEntrenamiento($fila);
    }

    public function listarDelEquipo(Identificador $equipoId, ?DateTimeImmutable $desde, ?DateTimeImmutable $hasta, int $pagina, int $porPagina, bool $descendente): array
    {
        $entrenamientos = $this->delEquipo($equipoId, $desde, $hasta)
            ->orderBy('inicia_en', $descendente ? 'desc' : 'asc')
            ->orderBy('id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get(self::COLUMNAS)
            ->map(fn (stdClass $fila) => $this->aEntrenamiento($fila))
            ->all();

        return array_values($entrenamientos);
    }

    public function contarDelEquipo(Identificador $equipoId, ?DateTimeImmutable $desde, ?DateTimeImmutable $hasta): int
    {
        return $this->delEquipo($equipoId, $desde, $hasta)->count();
    }

    public function hayEmpalme(Entrenamiento $entrenamiento): bool
    {
        return $this->conexion->table('entrenamientos')
            ->where('lugar_id', $entrenamiento->lugarId()->valor())
            ->where('id', '<>', $entrenamiento->id()->valor())
            ->where('estado', '<>', EstadoDeEntrenamiento::Cancelado->value)
            ->where('inicia_en', '<', $this->enUtc($entrenamiento->terminaEn()))
            ->where('termina_en', '>', $this->enUtc($entrenamiento->iniciaEn()))
            ->exists();
    }

    private function delEquipo(Identificador $equipoId, ?DateTimeImmutable $desde, ?DateTimeImmutable $hasta): Builder
    {
        $consulta = $this->conexion->table('entrenamientos')->where('equipo_id', $equipoId->valor());
        if ($desde !== null) {
            $consulta->where('inicia_en', '>=', $this->enUtc($desde));
        }
        if ($hasta !== null) {
            $consulta->where('inicia_en', '<', $this->enUtc($hasta));
        }

        return $consulta;
    }

    /** @return array<string, string> */
    private function columnas(Entrenamiento $entrenamiento): array
    {
        return [
            'equipo_id' => $entrenamiento->equipoId()->valor(),
            'lugar_id' => $entrenamiento->lugarId()->valor(),
            'inicia_en' => $this->enUtc($entrenamiento->iniciaEn()),
            'termina_en' => $this->enUtc($entrenamiento->terminaEn()),
            'responsable_id' => $entrenamiento->responsableId()->valor(),
            'estado' => $entrenamiento->estado()->value,
        ];
    }

    /** @return array<string, string> instantánea completa para el historial, con los instantes en ISO 8601 UTC */
    private function instantanea(Entrenamiento $entrenamiento): array
    {
        $utc = new DateTimeZone('UTC');

        return [
            ...$this->columnas($entrenamiento),
            'inicia_en' => $entrenamiento->iniciaEn()->setTimezone($utc)->format(DateTimeInterface::ATOM),
            'termina_en' => $entrenamiento->terminaEn()->setTimezone($utc)->format(DateTimeInterface::ATOM),
        ];
    }

    private function enUtc(DateTimeImmutable $instante): string
    {
        return $instante->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:sP');
    }

    private function aEntrenamiento(stdClass $fila): Entrenamiento
    {
        $utc = new DateTimeZone('UTC');

        return new Entrenamiento(
            Identificador::desdeTexto($fila->id),
            Identificador::desdeTexto($fila->equipo_id),
            Identificador::desdeTexto($fila->lugar_id),
            (new DateTimeImmutable($fila->inicia_en))->setTimezone($utc),
            (new DateTimeImmutable($fila->termina_en))->setTimezone($utc),
            Identificador::desdeTexto($fila->responsable_id),
            EstadoDeEntrenamiento::from($fila->estado),
            (new DateTimeImmutable($fila->actualizado_en))->setTimezone($utc),
        );
    }
}
