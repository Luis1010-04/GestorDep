<?php

namespace Src\CondicionFisica\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder as ConsultaSql;
use Src\Compartido\Dominio\Identificador;
use Src\CondicionFisica\Aplicacion\ConsultaDeHistorialDeCondicionFisica;
use Src\CondicionFisica\Aplicacion\EntradaDeHistorialDeCondicionFisica;
use stdClass;

/**
 * SQL de solo lectura sobre los historiales de Condición física. Lee solo tablas del contexto y filtra en SQL por
 * `registrado_por` (RN-14), para que el total sea exacto. La entidad se une sin filtrar `eliminado_en`: el borrado lógico
 * sigue en el historial (HU-26 c2). El jugador ya lo comprobó quien llama, con la visibilidad de la ruta de lectura.
 */
final class ConsultaDeHistorialDeCondicionFisicaSql implements ConsultaDeHistorialDeCondicionFisica
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function mediciones(Identificador $jugador, Identificador $lector, int $pagina, int $porPagina): array
    {
        $consulta = $this->conexion->table('mediciones_corporales_historial as h')
            ->join('mediciones_corporales as e', 'e.id', '=', 'h.medicion_id')
            ->where('e.jugador_id', $jugador->valor())
            ->where('e.registrado_por', $lector->valor());

        return $this->pagina('mediciones', 'medicion_id', $consulta, $pagina, $porPagina);
    }

    public function pruebas(Identificador $jugador, Identificador $lector, int $pagina, int $porPagina): array
    {
        $consulta = $this->conexion->table('pruebas_fisicas_historial as h')
            ->join('pruebas_fisicas as e', 'e.id', '=', 'h.prueba_id')
            ->where('e.jugador_id', $jugador->valor())
            ->where('e.registrado_por', $lector->valor());

        return $this->pagina('pruebas-fisicas', 'prueba_id', $consulta, $pagina, $porPagina);
    }

    /** @return array{entradas: list<EntradaDeHistorialDeCondicionFisica>, total: int} */
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
            $entradas[] = new EntradaDeHistorialDeCondicionFisica(
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
