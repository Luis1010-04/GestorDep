<?php

namespace Src\Salud\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder as ConsultaSql;
use Src\Compartido\Dominio\Identificador;
use Src\Salud\Aplicacion\ConsultaDeHistorialDeSalud;
use Src\Salud\Aplicacion\EntradaDeHistorialDeSalud;
use stdClass;

/**
 * SQL de solo lectura sobre los historiales de Salud. Lee solo tablas de Salud y filtra en SQL, para que el total sea
 * exacto. La entidad se une sin filtrar `eliminado_en`: el borrado lógico sigue en el historial (HU-26 c2). El jugador ya
 * lo comprobó quien llama, con la visibilidad de la ruta de lectura.
 */
final class ConsultaDeHistorialDeSaludSql implements ConsultaDeHistorialDeSalud
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function avisos(Identificador $jugador, Identificador $lector, bool $comoEntrenador, int $pagina, int $porPagina): array
    {
        $consulta = $this->conexion->table('avisos_lesion_historial as h')
            ->join('avisos_lesion as e', 'e.id', '=', 'h.aviso_id')
            ->where('e.jugador_id', $jugador->valor())
            ->where(fn (ConsultaSql $fila) => $fila
                ->where('e.creado_por', $lector->valor())
                ->when($comoEntrenador, fn (ConsultaSql $confirmado) => $confirmado->orWhere('e.confirmado_por', $lector->valor())));

        return $this->pagina('avisos-lesion', 'aviso_id', $consulta, $pagina, $porPagina);
    }

    public function lesiones(Identificador $jugador, Identificador $lector, int $pagina, int $porPagina): array
    {
        $consulta = $this->conexion->table('lesiones_historial as h')
            ->join('lesiones as e', 'e.id', '=', 'h.lesion_id')
            ->where('e.jugador_id', $jugador->valor())
            ->where('e.registrada_por', $lector->valor());

        return $this->pagina('lesiones', 'lesion_id', $consulta, $pagina, $porPagina);
    }

    public function estados(Identificador $jugador, int $pagina, int $porPagina): array
    {
        $consulta = $this->conexion->table('estados_disponibilidad_historial as h')
            ->join('estados_disponibilidad as e', 'e.id', '=', 'h.estado_id')
            ->where('e.jugador_id', $jugador->valor());

        return $this->pagina('estados-disponibilidad', 'estado_id', $consulta, $pagina, $porPagina);
    }

    public function lesionesRegistradasPor(Identificador $jugador, Identificador $lector): array
    {
        /** @var list<string> */
        return $this->conexion->table('lesiones')
            ->where('jugador_id', $jugador->valor())
            ->where('registrada_por', $lector->valor())
            ->pluck('id')
            ->all();
    }

    /** @return array{entradas: list<EntradaDeHistorialDeSalud>, total: int} */
    private function pagina(string $recurso, string $columna, ConsultaSql $consulta, int $pagina, int $porPagina): array
    {
        $total = (clone $consulta)->count();
        $filas = $consulta
            ->orderByDesc('h.ocurrido_en')
            ->orderByDesc('h.id')
            ->forPage($pagina, $porPagina)
            ->get(["h.$columna as entidad_id", 'h.version', 'h.operacion', 'h.ocurrido_en', 'h.motivo', 'h.datos_anteriores', 'h.datos_nuevos']);

        $entradas = [];
        foreach ($filas as $fila) {
            /** @var stdClass $fila */
            $entradas[] = new EntradaDeHistorialDeSalud(
                $recurso,
                Identificador::desdeTexto($fila->entidad_id),
                (int) $fila->version,
                $fila->operacion,
                (new DateTimeImmutable($fila->ocurrido_en))->setTimezone(new DateTimeZone('UTC')),
                $fila->motivo,
                self::datos($fila->datos_anteriores),
                self::datos($fila->datos_nuevos),
            );
        }

        return ['entradas' => $entradas, 'total' => $total];
    }

    /** @return array<string, mixed>|null */
    private static function datos(?string $json): ?array
    {
        if ($json === null) {
            return null;
        }

        /** @var array<string, mixed> */
        return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    }
}
