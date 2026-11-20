<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Salud\Aplicacion\EntradaDeHistorialDeSalud;

/**
 * Una entrada de historial de Salud (HU-26; docs/hitos/f1-h7.md §6.2). Sin la clave `autor`: en los historiales de H5 se
 * omite, no va en nulo (DA-18, DA-125).
 *
 * @property EntradaDeHistorialDeSalud $resource
 */
class EntradaDeHistorialDeSaludRecurso extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     recurso: string,
     *     entidad_id: string,
     *     version: int,
     *     operacion: string,
     *     ocurrido_en: string,
     *     motivo: string|null,
     *     datos_anteriores: array<string, mixed>|null,
     *     datos_nuevos: array<string, mixed>|null
     * }
     */
    public function toArray(Request $request): array
    {
        $entrada = $this->resource;

        return [
            'recurso' => $entrada->recurso,
            'entidad_id' => $entrada->entidadId->valor(),
            'version' => $entrada->version,
            'operacion' => $entrada->operacion,
            'ocurrido_en' => $entrada->ocurridoEn->format(DateTimeInterface::ATOM),
            'motivo' => $entrada->motivo,
            'datos_anteriores' => $entrada->datosAnteriores,
            'datos_nuevos' => $entrada->datosNuevos,
        ];
    }
}
