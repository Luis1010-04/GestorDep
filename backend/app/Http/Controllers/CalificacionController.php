<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeCorreccionDeCalificacion;
use App\Http\Requests\SolicitudDeNuevaCalificacion;
use App\Http\Resources\CalificacionRecurso;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Academico\Aplicacion\ComprobarInscripcionDelDocente;
use Src\Academico\Aplicacion\CorregirCalificacion;
use Src\Academico\Aplicacion\ListarCalificaciones;
use Src\Academico\Aplicacion\RegistrarCalificacion;
use Src\Academico\Dominio\TipoDeCalificacion;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\NumeroDecimal;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CalificacionController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, string $inscripcion, ComprobarInscripcionDelDocente $comprobarInscripcion, ListarCalificaciones $listarCalificaciones, UsuarioActual $usuarioActual): ColeccionPaginada
    {
        $inscripcionId = IdentificadorDeRuta::desde($inscripcion);
        if (! $comprobarInscripcion->ejecutar($inscripcionId, $usuarioActual->identificador() ?? throw new AuthenticationException)) {
            throw new NotFoundHttpException;
        }

        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = $listarCalificaciones->ejecutar($inscripcionId, $pagina, $porPagina);

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($calificacion) => new CalificacionRecurso($calificacion), $listado['calificaciones']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeNuevaCalificacion $solicitud, string $inscripcion, RegistrarCalificacion $registrarCalificacion, UsuarioActual $usuarioActual): JsonResponse
    {
        $fecha = $solicitud->validated('fecha');
        $numeroParcial = $solicitud->validated('numero_parcial');

        $calificacion = $registrarCalificacion->ejecutar(
            IdentificadorDeRuta::desde($inscripcion),
            $usuarioActual->identificador() ?? throw new AuthenticationException,
            TipoDeCalificacion::from((string) $solicitud->validated('tipo')),
            $numeroParcial === null ? null : (int) $numeroParcial,
            NumeroDecimal::desdeTexto((string) $solicitud->validated('valor')),
            $fecha === null ? null : (DateTimeImmutable::createFromFormat('!Y-m-d', (string) $fecha, new DateTimeZone('UTC')) ?: null),
        );

        return Respuesta::conDatos((new CalificacionRecurso($calificacion))->resolve($solicitud), estado: 201);
    }

    public function corregir(SolicitudDeCorreccionDeCalificacion $solicitud, string $calificacion, CorregirCalificacion $corregirCalificacion, UsuarioActual $usuarioActual): JsonResponse
    {
        $corregida = $corregirCalificacion->ejecutar(
            IdentificadorDeRuta::desde($calificacion),
            $usuarioActual->identificador() ?? throw new AuthenticationException,
            NumeroDecimal::desdeTexto((string) $solicitud->validated('valor')),
        );

        return Respuesta::conDatos((new CalificacionRecurso($corregida))->resolve($solicitud));
    }
}
