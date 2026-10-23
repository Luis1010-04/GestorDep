<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Plantel\Aplicacion\EquipoConCategoria;

/**
 * @property EquipoConCategoria $resource
 */
class EquipoRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array{id: string, nombre: string, categoria: array{id: string, nombre: string}, actualizado_en: string} */
    public function toArray(Request $request): array
    {
        $equipo = $this->resource->equipo;

        return [
            'id' => $equipo->id()->valor(),
            'nombre' => $equipo->nombre(),
            'categoria' => ['id' => $equipo->categoriaId()->valor(), 'nombre' => $this->resource->categoriaNombre],
            'actualizado_en' => $equipo->actualizadoEn()->format(DateTimeInterface::ATOM),
        ];
    }
}
