<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Consultas\Aplicacion\EntradaDeHistorial;

/**
 * Una entrada de historial (HU-26; docs/hitos/f1-h7.md §6.2). El autor lleva su nombre (D-3) y es nulo en los cambios
 * hechos sin sesión (DD-35).
 *
 * @property EntradaDeHistorial $resource
 */
class EntradaDeHistorialRecurso extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     recurso: string,
     *     entidad_id: string,
     *     version: int,
     *     operacion: string,
     *     autor: array{id: string, nombre: string|null}|null,
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
            'autor' => $entrada->autorId === null ? null : ['id' => $entrada->autorId->valor(), 'nombre' => $entrada->autorNombre],
            'ocurrido_en' => $entrada->ocurridoEn->format(DateTimeInterface::ATOM),
            'motivo' => $entrada->motivo,
            'datos_anteriores' => $entrada->datosAnteriores,
            'datos_nuevos' => $entrada->datosNuevos,
        ];
    }
}
