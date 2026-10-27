<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeCriterioDeMateria;
use App\Http\Requests\SolicitudDeNuevaMateria;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\CriterioRecurso;
use App\Http\Resources\MateriaRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Academico\Aplicacion\CrearMateria;
use Src\Academico\Aplicacion\DefinirCriterioDeMateria;
use Src\Academico\Aplicacion\ListarMaterias;
use Src\Academico\Aplicacion\VerMateria;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MateriaController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, ListarMaterias $listarMaterias, UsuarioActual $usuarioActual): ColeccionPaginada
    {
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
            'filtro' => ['array'],
            'filtro.periodo_id' => ['string', 'uuid'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = $listarMaterias->ejecutar($this->docente($usuarioActual), $datos['filtro']['periodo_id'] ?? null, $pagina, $porPagina);

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($materia) => new MateriaRecurso($materia), $listado['materias']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeNuevaMateria $solicitud, CrearMateria $crearMateria, UsuarioActual $usuarioActual): JsonResponse
    {
        $materia = $crearMateria->ejecutar(
            $this->docente($usuarioActual),
            (string) $solicitud->input('asignatura_id'),
            (string) $solicitud->input('periodo_id'),
        );

        return Respuesta::conDatos((new MateriaRecurso($materia))->resolve($solicitud), estado: 201);
    }

    public function ver(Request $solicitud, string $materia, VerMateria $verMateria, UsuarioActual $usuarioActual): JsonResponse
    {
        $encontrada = $verMateria->ejecutar(IdentificadorDeRuta::desde($materia), $this->docente($usuarioActual))
            ?? throw new NotFoundHttpException;

        return Respuesta::conDatos((new MateriaRecurso($encontrada))->resolve($solicitud));
    }

    public function definirCriterio(SolicitudDeCriterioDeMateria $solicitud, string $materia, DefinirCriterioDeMateria $definirCriterio): JsonResponse
    {
        $criterio = $definirCriterio->ejecutar(
            IdentificadorDeRuta::desde($materia),
            NumeroDecimal::desdeTexto((string) $solicitud->validated('calificacion_minima')),
        );

        return Respuesta::conDatos((new CriterioRecurso($criterio))->resolve($solicitud));
    }

    private function docente(UsuarioActual $usuarioActual): Identificador
    {
        return $usuarioActual->identificador() ?? throw new AuthenticationException;
    }
}
