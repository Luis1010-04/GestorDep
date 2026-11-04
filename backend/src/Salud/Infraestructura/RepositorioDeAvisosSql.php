<?php

namespace Src\Salud\Infraestructura;

use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Salud\Dominio\AvisoDeLesion;
use Src\Salud\Dominio\EstadoDeAviso;
use Src\Salud\Dominio\RepositorioDeAvisos;
use stdClass;

/** Lee y escribe `avisos_lesion`; cada cambio lleva su historial en la misma transacción (RNF-02). */
final class RepositorioDeAvisosSql implements RepositorioDeAvisos
{
    private const COLUMNAS = [
        'id', 'jugador_id', 'creado_por', 'reportado_por_jugador', 'nota', 'estado', 'fecha_aviso', 'confirmado_por',
        'descartado_por', 'nota_descarte', 'creado_en', 'actualizado_en',
    ];

    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function guardarNuevo(AvisoDeLesion $aviso): void
    {
        $momento = $aviso->creadoEn()->format('Y-m-d H:i:sP');
        $instantanea = self::instantanea($aviso);

        $this->historial->registrar(
            'avisos_lesion', 'aviso_id', $aviso->id(), 'crear', null, $instantanea,
            fn () => $this->conexion->table('avisos_lesion')->insert([
                'id' => $aviso->id()->valor(), ...$instantanea,
                'creado_en' => $momento, 'actualizado_en' => $momento, 'actualizado_por' => $aviso->creadoPor()->valor(),
            ]),
            instante: $aviso->creadoEn(),
        );
    }

    public function guardarDescarte(AvisoDeLesion $anterior, AvisoDeLesion $descartado): void
    {
        $this->historial->registrar(
            'avisos_lesion', 'aviso_id', $descartado->id(), 'actualizar', self::instantanea($anterior), self::instantanea($descartado),
            fn () => $this->conexion->table('avisos_lesion')->where('id', $descartado->id()->valor())->update([
                'estado' => $descartado->estado()->value,
                'descartado_por' => $descartado->descartadoPor()?->valor(),
                'nota_descarte' => $descartado->notaDescarte(),
                'actualizado_en' => $descartado->actualizadoEn()->format('Y-m-d H:i:sP'),
                'actualizado_por' => $this->usuarioActual->identificador()?->valor(),
            ]),
            instante: $descartado->actualizadoEn(),
        );
    }

    public function buscar(Identificador $id): ?AvisoDeLesion
    {
        $fila = $this->vigentes()->where('id', $id->valor())->first(self::COLUMNAS);

        return $fila === null ? null : self::aAviso($fila);
    }

    public function buscarParaActualizar(Identificador $id): ?AvisoDeLesion
    {
        $fila = $this->vigentes()->where('id', $id->valor())->lockForUpdate()->first(self::COLUMNAS);

        return $fila === null ? null : self::aAviso($fila);
    }

    public function delJugador(Identificador $jugadorId, ?Identificador $creadoPor, int $pagina, int $porPagina): array
    {
        $consulta = $this->vigentes()
            ->where('jugador_id', $jugadorId->valor())
            ->when($creadoPor, fn (Builder $q, Identificador $autor) => $q->where('creado_por', $autor->valor()));
        $total = $consulta->getCountForPagination();
        $filas = $consulta
            ->orderByDesc('fecha_aviso')
            ->orderByDesc('id')
            ->forPage($pagina, $porPagina)
            ->get(self::COLUMNAS);

        return ['elementos' => array_values($filas->map(fn (stdClass $fila) => self::aAviso($fila))->all()), 'total' => $total];
    }

    private function vigentes(): Builder
    {
        return $this->conexion->table('avisos_lesion')->whereNull('eliminado_en');
    }

    /** @return array{jugador_id: string, creado_por: string, reportado_por_jugador: bool, nota: string, estado: string, fecha_aviso: string, confirmado_por: string|null, descartado_por: string|null, nota_descarte: string|null} */
    private static function instantanea(AvisoDeLesion $aviso): array
    {
        return [
            'jugador_id' => $aviso->jugadorId()->valor(),
            'creado_por' => $aviso->creadoPor()->valor(),
            'reportado_por_jugador' => $aviso->reportadoPorJugador(),
            'nota' => $aviso->nota(),
            'estado' => $aviso->estado()->value,
            'fecha_aviso' => $aviso->fechaAviso(),
            'confirmado_por' => $aviso->confirmadoPor()?->valor(),
            'descartado_por' => $aviso->descartadoPor()?->valor(),
            'nota_descarte' => $aviso->notaDescarte(),
        ];
    }

    private static function aAviso(stdClass $fila): AvisoDeLesion
    {
        return new AvisoDeLesion(
            Identificador::desdeTexto($fila->id),
            Identificador::desdeTexto($fila->jugador_id),
            Identificador::desdeTexto($fila->creado_por),
            $fila->reportado_por_jugador,
            $fila->nota,
            EstadoDeAviso::from($fila->estado),
            $fila->fecha_aviso,
            $fila->confirmado_por === null ? null : Identificador::desdeTexto($fila->confirmado_por),
            $fila->descartado_por === null ? null : Identificador::desdeTexto($fila->descartado_por),
            $fila->nota_descarte,
            new DateTimeImmutable($fila->creado_en),
            new DateTimeImmutable($fila->actualizado_en),
        );
    }
}
