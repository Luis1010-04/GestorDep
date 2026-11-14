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
use Src\CondicionFisica\Aplicacion\MedicionConcurrente;
use Src\CondicionFisica\Dominio\MedicionCorporal;
use Src\CondicionFisica\Dominio\RepositorioDeMediciones;
use stdClass;

/** Lee y escribe `mediciones_corporales`; cada cambio lleva su historial en la misma transacción (RNF-02). */
final class RepositorioDeMedicionesSql implements RepositorioDeMediciones
{
    private const COLUMNAS = ['id', 'jugador_id', 'equipo_id', 'registrado_por', 'fecha', 'peso_kg', 'estatura_cm', 'creado_en', 'actualizado_en'];

    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function guardarNueva(MedicionCorporal $medicion): void
    {
        $momento = $medicion->creadoEn()->format('Y-m-d H:i:sP');
        $autor = $this->usuarioActual->identificador()?->valor();
        $instantanea = self::instantanea($medicion);

        try {
            $this->historial->registrar(
                'mediciones_corporales', 'medicion_id', $medicion->id(), 'crear', null, $instantanea,
                fn () => $this->conexion->table('mediciones_corporales')->insert([
                    'id' => $medicion->id()->valor(), ...$instantanea,
                    'creado_en' => $momento, 'actualizado_en' => $momento, 'creado_por' => $autor, 'actualizado_por' => $autor,
                ]),
                instante: $medicion->creadoEn(),
            );
        } catch (UniqueConstraintViolationException) {
            throw new MedicionConcurrente;
        }
    }

    public function guardarCorreccion(MedicionCorporal $anterior, MedicionCorporal $corregida, string $motivo): void
    {
        $this->historial->registrar(
            'mediciones_corporales', 'medicion_id', $corregida->id(), 'actualizar', self::instantanea($anterior), self::instantanea($corregida),
            fn () => $this->conexion->table('mediciones_corporales')->where('id', $corregida->id()->valor())->update([
                'equipo_id' => $corregida->equipoId()->valor(),
                'fecha' => $corregida->fecha(),
                'peso_kg' => $corregida->pesoKg()->comoCadena(2),
                'estatura_cm' => $corregida->estaturaCm()->comoCadena(1),
                'actualizado_en' => $corregida->actualizadoEn()->format('Y-m-d H:i:sP'),
                'actualizado_por' => $this->usuarioActual->identificador()?->valor(),
            ]),
            motivo: $motivo,
            instante: $corregida->actualizadoEn(),
        );
    }

    public function delPeriodoParaActualizar(Identificador $jugadorId, Identificador $registradoPor, string $periodo): ?MedicionCorporal
    {
        $fila = $this->vigentes()
            ->where('jugador_id', $jugadorId->valor())
            ->where('registrado_por', $registradoPor->valor())
            ->where('periodo', $periodo)
            ->lockForUpdate()
            ->first(self::COLUMNAS);

        return $fila === null ? null : self::aMedicion($fila);
    }

    public function delRegistrante(Identificador $jugadorId, Identificador $registradoPor, int $pagina, int $porPagina): array
    {
        $consulta = $this->vigentes()->where('jugador_id', $jugadorId->valor())->where('registrado_por', $registradoPor->valor());
        $total = $consulta->getCountForPagination();
        $filas = $consulta
            ->orderBy('periodo')
            ->orderBy('id')
            ->forPage($pagina, $porPagina)
            ->get(self::COLUMNAS);

        return ['elementos' => array_values($filas->map(fn (stdClass $fila) => self::aMedicion($fila))->all()), 'total' => $total];
    }

    private function vigentes(): Builder
    {
        return $this->conexion->table('mediciones_corporales')->whereNull('eliminado_en');
    }

    /** @return array{jugador_id: string, equipo_id: string, registrado_por: string, fecha: string, periodo: string, peso_kg: string, estatura_cm: string} */
    private static function instantanea(MedicionCorporal $medicion): array
    {
        return [
            'jugador_id' => $medicion->jugadorId()->valor(),
            'equipo_id' => $medicion->equipoId()->valor(),
            'registrado_por' => $medicion->registradoPor()->valor(),
            'fecha' => $medicion->fecha(),
            'periodo' => $medicion->periodo(),
            'peso_kg' => $medicion->pesoKg()->comoCadena(2),
            'estatura_cm' => $medicion->estaturaCm()->comoCadena(1),
        ];
    }

    private static function aMedicion(stdClass $fila): MedicionCorporal
    {
        $utc = new DateTimeZone('UTC');

        return MedicionCorporal::reconstruir(
            Identificador::desdeTexto($fila->id),
            Identificador::desdeTexto($fila->jugador_id),
            Identificador::desdeTexto($fila->equipo_id),
            Identificador::desdeTexto($fila->registrado_por),
            $fila->fecha,
            NumeroDecimal::desdeTexto($fila->peso_kg),
            NumeroDecimal::desdeTexto($fila->estatura_cm),
            (new DateTimeImmutable($fila->creado_en))->setTimezone($utc),
            (new DateTimeImmutable($fila->actualizado_en))->setTimezone($utc),
        );
    }
}
