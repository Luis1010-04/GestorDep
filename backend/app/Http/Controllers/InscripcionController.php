<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeNuevaInscripcion;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\InscripcionRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Academico\Aplicacion\ComprobarMateriaDelDocente;
use Src\Academico\Aplicacion\InscribirJugador;
use Src\Academico\Aplicacion\ListarInscripciones;
use Src\Compartido\Aplicacion\UsuarioActual;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InscripcionController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, string $materia, ComprobarMateriaDelDocente $comprobarMateria, ListarInscripciones $listarInscripciones, UsuarioActual $usuarioActual): ColeccionPaginada
    {
        $materiaId = IdentificadorDeRuta::desde($materia);
        if (! $comprobarMateria->ejecutar($materiaId, $usuarioActual->identificador() ?? throw new AuthenticationException)) {
            throw new NotFoundHttpException;
        }

        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = $listarInscripciones->ejecutar($materiaId, $pagina, $porPagina);

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($inscripcion) => new InscripcionRecurso($inscripcion), $listado['inscripciones']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeNuevaInscripcion $solicitud, string $materia, InscribirJugador $inscribirJugador): JsonResponse
    {
        $inscripcion = $inscribirJugador->ejecutar(IdentificadorDeRuta::desde($materia), (string) $solicitud->validated('matricula'));

        return Respuesta::conDatos((new InscripcionRecurso($inscripcion))->resolve($solicitud), estado: 201);
    }
}
