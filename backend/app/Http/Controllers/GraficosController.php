<?php

namespace App\Http\Controllers;

use App\Http\Reglas\FiltroInvalido;
use App\Http\Resources\GraficosDelEquipoRecurso;
use App\Http\Resources\GraficosDelJugadorRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Src\Compartido\Dominio\Identificador;
use Src\Consultas\Aplicacion\ConsultarGraficosDelEquipo;
use Src\Consultas\Aplicacion\ConsultarGraficosDelJugador;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GraficosController extends Controller
{
    /** Un jugador fuera del alcance, en baja, inexistente o con un id que no es UUID v7 responde el mismo 404 (RNF-01, RN-23). */
    public function delJugador(Request $solicitud, string $jugador, ConsultarGraficosDelJugador $consultar): JsonResponse
    {
        $graficos = $consultar->ejecutar(IdentificadorDeRuta::desde($jugador)) ?? throw new NotFoundHttpException;

        return Respuesta::conDatos((new GraficosDelJugadorRecurso($graficos))->resolve($solicitud));
    }

    /**
     * Un equipo que el usuario no entrena responde 404; con `filtro[jugador_id]`, un jugador que no es del equipo, que no está
     * en el alcance o que está en baja responde el mismo 404.
     */
    public function delEquipo(Request $solicitud, string $equipo, ConsultarGraficosDelEquipo $consultar): JsonResponse
    {
        // El 404 va antes que el 422 (A6-10, DD-50).
        if (! $consultar->puedeVer(IdentificadorDeRuta::desde($equipo))) {
            throw new NotFoundHttpException;
        }
        $datos = $solicitud->validate([
            'filtro' => ['array'],
            'filtro.jugador_id' => [FiltroInvalido::uuid()],
        ]);
        /** @var array{jugador_id?: string} $filtro */
        $filtro = $datos['filtro'] ?? [];

        $graficos = $consultar->ejecutar(IdentificadorDeRuta::desde($equipo), $this->jugador($filtro['jugador_id'] ?? null)) ?? throw new NotFoundHttpException;

        return Respuesta::conDatos((new GraficosDelEquipoRecurso($graficos))->resolve($solicitud));
    }

    /** Un UUID que no es v7 no puede ser de ningún jugador: 404, igual que uno inexistente. */
    private function jugador(?string $texto): ?Identificador
    {
        if ($texto === null) {
            return null;
        }

        try {
            return Identificador::desdeTexto($texto);
        } catch (InvalidArgumentException) {
            throw new NotFoundHttpException;
        }
    }
}
