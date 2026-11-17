<?php

namespace App\Http\Controllers;

use App\Http\Reglas\FiltroInvalido;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\VistaParaConvocarRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Consultas\Aplicacion\ConsultarVistaDeLaConvocatoria;
use Src\Consultas\Aplicacion\ConsultarVistaParaConvocar;
use Src\Consultas\Aplicacion\ListarJugadoresParaConvocar;
use Src\Consultas\Aplicacion\VistaParaConvocar;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VistaParaConvocarController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el listado de estatus; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    /**
     * Un jugador fuera del alcance, en baja, inexistente o con un id que no es UUID v7 responde el mismo 404 (RNF-01, RN-23);
     * también un `filtro[equipo_id]` de un equipo que el usuario no entrena o donde el jugador no está (DA-119).
     */
    public function delJugador(Request $solicitud, string $jugador, ConsultarVistaParaConvocar $consultar): JsonResponse
    {
        $datos = $solicitud->validate([
            'filtro' => ['array'],
            'filtro.equipo_id' => [FiltroInvalido::uuid()],
        ]);
        /** @var array{equipo_id?: string} $filtro */
        $filtro = $datos['filtro'] ?? [];

        $vista = $consultar->ejecutar(
            IdentificadorDeRuta::desde($jugador),
            isset($filtro['equipo_id']) ? IdentificadorDeRuta::desde($filtro['equipo_id']) : null,
        ) ?? throw new NotFoundHttpException;

        return Respuesta::conDatos((new VistaParaConvocarRecurso($vista))->resolve($solicitud));
    }

    /**
     * La vista de cada convocado de un partido (HU-12 c2, DA-96), paginada como la ruta de convocatoria de H3. Un partido
     * inexistente, eliminado, con un id que no es UUID v7 o de un equipo que el usuario no entrena responde 404.
     */
    public function delPartido(Request $solicitud, string $partido, ConsultarVistaDeLaConvocatoria $consultar): ColeccionPaginada
    {
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $convocatoria = $consultar->ejecutar(IdentificadorDeRuta::desde($partido), $pagina, $porPagina) ?? throw new NotFoundHttpException;

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn (VistaParaConvocar $vista): VistaParaConvocarRecurso => new VistaParaConvocarRecurso($vista), $convocatoria['elementos']),
            $convocatoria['total'],
            $porPagina,
            $pagina,
        ));
    }

    /** Un equipo que el usuario no entrena, inexistente o con un id que no es UUID v7 responde 404. */
    public function delEquipo(Request $solicitud, string $equipo, ListarJugadoresParaConvocar $listar): ColeccionPaginada
    {
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $lista = $listar->ejecutar(IdentificadorDeRuta::desde($equipo), $pagina, $porPagina) ?? throw new NotFoundHttpException;

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn (VistaParaConvocar $vista): VistaParaConvocarRecurso => new VistaParaConvocarRecurso($vista), $lista['elementos']),
            $lista['total'],
            $porPagina,
            $pagina,
        ));
    }
}
