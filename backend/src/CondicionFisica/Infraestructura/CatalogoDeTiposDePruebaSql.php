<?php

namespace Src\CondicionFisica\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\CondicionFisica\Dominio\CatalogoDeTiposDePrueba;
use Src\CondicionFisica\Dominio\RangoDeMedida;
use Src\CondicionFisica\Dominio\TipoDePrueba;
use stdClass;

/** Lee `tipos_prueba` en cada operación: un rango cambiado en la base se aplica sin tocar el código (RNF-09, DA-16). */
final class CatalogoDeTiposDePruebaSql implements CatalogoDeTiposDePrueba
{
    private const COLUMNAS = ['id', 'codigo', 'nombre', 'unidad', 'mejor_resultado', 'minimo', 'maximo'];

    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function buscar(Identificador $id): ?TipoDePrueba
    {
        $fila = $this->vigentes()->where('id', $id->valor())->first(self::COLUMNAS);

        return $fila === null ? null : self::aTipo($fila);
    }

    public function todos(): array
    {
        return array_values($this->vigentes()->orderBy('codigo')->get(self::COLUMNAS)->map(fn (stdClass $fila) => self::aTipo($fila))->all());
    }

    private function vigentes(): Builder
    {
        return $this->conexion->table('tipos_prueba')->whereNull('eliminado_en');
    }

    private static function aTipo(stdClass $fila): TipoDePrueba
    {
        return new TipoDePrueba(
            Identificador::desdeTexto($fila->id),
            $fila->codigo,
            $fila->nombre,
            $fila->unidad,
            $fila->mejor_resultado,
            new RangoDeMedida(NumeroDecimal::desdeTexto($fila->minimo), NumeroDecimal::desdeTexto($fila->maximo)),
        );
    }
}
