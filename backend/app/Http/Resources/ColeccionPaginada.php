<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** Coleccion paginada con `datos` y `meta` (docs/convenciones-api.md, seccion 1). */
class ColeccionPaginada extends ResourceCollection
{
    /** @var string|null */
    public static $wrap = 'datos';

    /**
     * Reemplaza los `links` y `meta` de Laravel por la meta en espanol.
     *
     * @param  array<string, mixed>  $paginado
     * @param  array<string, mixed>  $predeterminado
     * @return array<string, mixed>
     */
    public function paginationInformation(Request $request, array $paginado, array $predeterminado): array
    {
        return [
            'meta' => [
                'pagina' => $paginado['current_page'],
                'por_pagina' => $paginado['per_page'],
                'total' => $paginado['total'],
                'total_paginas' => $paginado['last_page'],
            ],
        ];
    }
}
