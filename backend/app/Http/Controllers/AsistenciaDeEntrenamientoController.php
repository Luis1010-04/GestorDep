<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeAsistenciasDeEntrenamiento;
use App\Http\Requests\SolicitudDeCorreccionDeAsistenciaDeEntrenamiento;
use App\Http\Resources\AsistenciaDeEntrenamientoRecurso;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\ConteoDeAsistenciaRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Competencia\Aplicacion\BuscarEntrenamientoDelEntrenador;
use Src\Competencia\Aplicacion\CorregirAsistencia;
use Src\Competencia\Aplicacion\ListarAsistencias;
use Src\Competencia\Aplicacion\RegistrarAsistencias;
use Src\Competencia\Aplicacion\VerAsistenciaDelEquipo;
use Src\Competencia\Aplicacion\VerAsistenciaDelJugador;
use Src\Competencia\Dominio\EstadoDeAsistencia;

class AsistenciaDeEntrenamientoController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, string $entrenamiento, BuscarEntrenamientoDelEntrenador $buscarEntrenamiento, ListarAsistencias $listarAsistencias): ColeccionPaginada
    {
        $entrenamientoId = IdentificadorDeRuta::desde($entrenamiento);
        $buscarEntrenamiento->ejecutar($entrenamientoId);
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = $listarAsistencias->ejecutar($entrenamientoId, $pagina, $porPagina);

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($asistencia) => new AsistenciaDeEntrenamientoRecurso($asistencia), $listado['elementos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeAsistenciasDeEntrenamiento $solicitud, string $entrenamiento, RegistrarAsistencias $registrarAsistencias): JsonResponse
    {
        /** @var list<array{jugador_id: string, estado: string}> $registros */
        $registros = $solicitud->validated('registros');

        $asistencias = $registrarAsistencias->ejecutar(
            IdentificadorDeRuta::desde($entrenamiento),
            array_map(fn (array $registro) => ['jugador_id' => $registro['jugador_id'], 'estado' => EstadoDeAsistencia::from($registro['estado'])], $registros),
        );

        return Respuesta::conDatos(
            array_map(fn ($asistencia) => (new AsistenciaDeEntrenamientoRecurso($asistencia))->resolve($solicitud), $asistencias),
            estado: 201,
        );
    }

    public function corregir(SolicitudDeCorreccionDeAsistenciaDeEntrenamiento $solicitud, string $asistencia, CorregirAsistencia $corregirAsistencia): JsonResponse
    {
        $corregida = $corregirAsistencia->ejecutar(IdentificadorDeRuta::desde($asistencia), EstadoDeAsistencia::from((string) $solicitud->validated('estado')));

        return Respuesta::conDatos((new AsistenciaDeEntrenamientoRecurso($corregida))->resolve($solicitud));
    }

    public function delEquipo(string $equipo, VerAsistenciaDelEquipo $verAsistencia): JsonResponse
    {
        $asistencia = $verAsistencia->ejecutar(IdentificadorDeRuta::desde($equipo));

        return Respuesta::conDatos([
            'equipo_id' => $asistencia->equipoId->valor(),
            ...ConteoDeAsistenciaRecurso::de($asistencia->conteo),
            'por_jugador' => array_map(fn (array $fila) => [
                'jugador_id' => $fila['jugador']->id->valor(),
                'nombres' => $fila['jugador']->nombres,
                'apellidos' => $fila['jugador']->apellidos,
                ...ConteoDeAsistenciaRecurso::de($fila['conteo']),
            ], $asistencia->porJugador),
        ]);
    }

    public function delJugador(string $jugador, VerAsistenciaDelJugador $verAsistencia): JsonResponse
    {
        $jugadorId = IdentificadorDeRuta::desde($jugador);

        return Respuesta::conDatos(['jugador_id' => $jugadorId->valor(), ...ConteoDeAsistenciaRecurso::de($verAsistencia->ejecutar($jugadorId))]);
    }
}
