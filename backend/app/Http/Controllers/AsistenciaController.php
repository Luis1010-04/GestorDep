<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeCorreccionDeAsistencia;
use App\Http\Requests\SolicitudDeNuevaAsistencia;
use App\Http\Resources\AsistenciaRecurso;
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
use Src\Academico\Aplicacion\CorregirAsistencia;
use Src\Academico\Aplicacion\ListarAsistencias;
use Src\Academico\Aplicacion\RegistrarAsistencia;
use Src\Academico\Dominio\EstadoDeAsistencia;
use Src\Compartido\Aplicacion\UsuarioActual;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AsistenciaController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, string $inscripcion, ComprobarInscripcionDelDocente $comprobarInscripcion, ListarAsistencias $listarAsistencias, UsuarioActual $usuarioActual): ColeccionPaginada
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

        $listado = $listarAsistencias->ejecutar($inscripcionId, $pagina, $porPagina);

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($asistencia) => new AsistenciaRecurso($asistencia), $listado['asistencias']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeNuevaAsistencia $solicitud, string $inscripcion, RegistrarAsistencia $registrarAsistencia, UsuarioActual $usuarioActual): JsonResponse
    {
        $fecha = $solicitud->validated('fecha');

        $asistencia = $registrarAsistencia->ejecutar(
            IdentificadorDeRuta::desde($inscripcion),
            $usuarioActual->identificador() ?? throw new AuthenticationException,
            EstadoDeAsistencia::from((string) $solicitud->validated('estado')),
            $fecha === null ? null : (DateTimeImmutable::createFromFormat('!Y-m-d', (string) $fecha, new DateTimeZone('UTC')) ?: null),
        );

        return Respuesta::conDatos((new AsistenciaRecurso($asistencia))->resolve($solicitud), estado: 201);
    }

    public function corregir(SolicitudDeCorreccionDeAsistencia $solicitud, string $asistencia, CorregirAsistencia $corregirAsistencia, UsuarioActual $usuarioActual): JsonResponse
    {
        $corregida = $corregirAsistencia->ejecutar(
            IdentificadorDeRuta::desde($asistencia),
            $usuarioActual->identificador() ?? throw new AuthenticationException,
            EstadoDeAsistencia::from((string) $solicitud->validated('estado')),
        );

        return Respuesta::conDatos((new AsistenciaRecurso($corregida))->resolve($solicitud));
    }
}
