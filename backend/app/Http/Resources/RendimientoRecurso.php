<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Rendimiento\Dominio\RendimientoDePartido;
use Src\Rendimiento\Dominio\TipoDeDato;
use Src\Rendimiento\Dominio\ValorDeMetrica;

/**
 * Un rendimiento capturado (HU-14). No incluye `registrado_por`: solo lo ve quien lo registró (RN-14). Cada valor de
 * métrica viaja como número si la métrica es entera y como cadena con dos decimales si es decimal (DD-22).
 *
 * @property RendimientoDePartido $resource
 */
class RendimientoRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $datos = $this->resource->datos();

        return [
            'id' => $this->resource->id()->valor(),
            'partido_id' => $this->resource->partidoId()->valor(),
            'jugador_id' => $this->resource->jugadorId()->valor(),
            'equipo_id' => $this->resource->equipoId()->valor(),
            'posicion_id' => $this->resource->posicionId()->valor(),
            'minutos' => $datos->minutos(),
            'fue_titular' => $datos->fueTitular(),
            'tarjetas_amarillas' => $datos->tarjetasAmarillas(),
            'tarjetas_rojas' => $datos->tarjetasRojas(),
            'calificacion_general' => $datos->calificacionGeneral(),
            'observacion' => $datos->observacion(),
            'metricas' => array_map(fn (ValorDeMetrica $valor) => [
                'metrica_id' => $valor->metricaId()->valor(),
                'valor' => self::numero($valor->valor(), $valor->tipo()),
            ], $this->resource->valores()),
            'actualizado_en' => $this->resource->actualizadoEn()->format(DateTimeInterface::ATOM),
        ];
    }

    /** Un número de una métrica: entero JSON si la métrica es entera; cadena con dos decimales si es decimal (DD-22). */
    public static function numero(NumeroDecimal $numero, TipoDeDato $tipo): int|string
    {
        return $tipo === TipoDeDato::Entero ? (int) $numero->comoCadena(0) : (string) DecimalComoCadena::de($numero);
    }
}
