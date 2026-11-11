<?php

namespace Src\CondicionFisica\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\CondicionFisica\Aplicacion\PruebaConcurrente;
use Src\CondicionFisica\Dominio\PruebaFisica;
use Src\CondicionFisica\Dominio\RepositorioDePruebasFisicas;
use stdClass;

/** Lee y escribe `pruebas_fisicas`; cada cambio lleva su historial en la misma transacción (RNF-02). */
final class RepositorioDePruebasFisicasSql implements RepositorioDePruebasFisicas
{
    private const COLUMNAS = [
        'pruebas_fisicas.id', 'pruebas_fisicas.jugador_id', 'pruebas_fisicas.equipo_id', 'pruebas_fisicas.registrado_por',
        'pruebas_fisicas.tipo_prueba_id', 'pruebas_fisicas.fecha', 'pruebas_fisicas.resultado', 'pruebas_fisicas.creado_en',
        'pruebas_fisicas.actualizado_en',
    ];

    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function guardarNueva(PruebaFisica $prueba): void
    {
        $momento = $prueba->creadoEn()->format('Y-m-d H:i:sP');
        $autor = $this->usuarioActual->identificador()?->valor();
        $instantanea = self::instantanea($prueba);

        try {
            $this->historial->registrar(
                'pruebas_fisicas', 'prueba_id', $prueba->id(), 'crear', null, $instantanea,
                fn () => $this->conexion->table('pruebas_fisicas')->insert([
                    'id' => $prueba->id()->valor(), ...$instantanea,
                    'creado_en' => $momento, 'actualizado_en' => $momento, 'creado_por' => $autor, 'actualizado_por' => $autor,
                ]),
                instante: $prueba->creadoEn(),
            );
        } catch (UniqueConstraintViolationException) {
            throw new PruebaConcurrente;
        }
    }

    public function guardarCorreccion(PruebaFisica $anterior, PruebaFisica $corregida, string $motivo): void
    {
        $this->historial->registrar(
            'pruebas_fisicas', 'prueba_id', $corregida->id(), 'actualizar', self::instantanea($anterior), self::instantanea($corregida),
            fn () => $this->conexion->table('pruebas_fisicas')->where('id', $corregida->id()->valor())->update([
                'equipo_id' => $corregida->equipoId()->valor(),
                'fecha' => $corregida->fecha(),
                'resultado' => $corregida->resultado()->comoCadena(2),
                'actualizado_en' => $corregida->actualizadoEn()->format('Y-m-d H:i:sP'),
                'actualizado_por' => $this->usuarioActual->identificador()?->valor(),
            ]),
            motivo: $motivo,
            instante: $corregida->actualizadoEn(),
        );
    }

    public function delPeriodoParaActualizar(Identificador $jugadorId, Identificador $tipoPruebaId, Identificador $registradoPor, string $periodo): ?PruebaFisica
    {
        $fila = $this->delRegistranteEn($jugadorId, $registradoPor)
            ->where('pruebas_fisicas.tipo_prueba_id', $tipoPruebaId->valor())
            ->where('pruebas_fisicas.periodo', $periodo)
            ->lockForUpdate()
            ->first(self::COLUMNAS);

        return $fila === null ? null : self::aPrueba($fila);
    }

    public function delRegistrante(Identificador $jugadorId, Identificador $registradoPor, ?Identificador $tipoPruebaId, int $pagina, int $porPagina): array
    {
        $consulta = $this->delRegistranteEn($jugadorId, $registradoPor);
        if ($tipoPruebaId !== null) {
            $consulta->where('pruebas_fisicas.tipo_prueba_id', $tipoPruebaId->valor());
        }
        $total = $consulta->getCountForPagination();
        $filas = $consulta
            ->join('tipos_prueba', 'tipos_prueba.id', '=', 'pruebas_fisicas.tipo_prueba_id')
            ->orderBy('pruebas_fisicas.periodo')
            ->orderBy('tipos_prueba.codigo')
            ->orderBy('pruebas_fisicas.id')
            ->forPage($pagina, $porPagina)
            ->get(self::COLUMNAS);

        return ['elementos' => array_values($filas->map(fn (stdClass $fila) => self::aPrueba($fila))->all()), 'total' => $total];
    }

    public function delPeriodo(Identificador $jugadorId, Identificador $registradoPor, string $periodo): array
    {
        $filas = $this->delRegistranteEn($jugadorId, $registradoPor)->where('pruebas_fisicas.periodo', $periodo)->get(self::COLUMNAS);

        return array_values($filas->map(fn (stdClass $fila) => self::aPrueba($fila))->all());
    }

    private function delRegistranteEn(Identificador $jugadorId, Identificador $registradoPor): Builder
    {
        return $this->conexion->table('pruebas_fisicas')
            ->whereNull('pruebas_fisicas.eliminado_en')
            ->where('pruebas_fisicas.jugador_id', $jugadorId->valor())
            ->where('pruebas_fisicas.registrado_por', $registradoPor->valor());
    }

    /** @return array{jugador_id: string, equipo_id: string, registrado_por: string, tipo_prueba_id: string, fecha: string, periodo: string, resultado: string} */
    private static function instantanea(PruebaFisica $prueba): array
    {
        return [
            'jugador_id' => $prueba->jugadorId()->valor(),
            'equipo_id' => $prueba->equipoId()->valor(),
            'registrado_por' => $prueba->registradoPor()->valor(),
            'tipo_prueba_id' => $prueba->tipoPruebaId()->valor(),
            'fecha' => $prueba->fecha(),
            'periodo' => $prueba->periodo(),
            'resultado' => $prueba->resultado()->comoCadena(2),
        ];
    }

    private static function aPrueba(stdClass $fila): PruebaFisica
    {
        $utc = new DateTimeZone('UTC');

        return PruebaFisica::reconstruir(
            Identificador::desdeTexto($fila->id),
            Identificador::desdeTexto($fila->jugador_id),
            Identificador::desdeTexto($fila->equipo_id),
            Identificador::desdeTexto($fila->registrado_por),
            Identificador::desdeTexto($fila->tipo_prueba_id),
            $fila->fecha,
            NumeroDecimal::desdeTexto($fila->resultado),
            (new DateTimeImmutable($fila->creado_en))->setTimezone($utc),
            (new DateTimeImmutable($fila->actualizado_en))->setTimezone($utc),
        );
    }
}
