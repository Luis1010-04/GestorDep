<?php

namespace Src\Competencia\Aplicacion;

use InvalidArgumentException;
use Src\Compartido\Aplicacion\JugadorDelEquipo;
use Src\Compartido\Aplicacion\JugadoresDelEquipo;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Competencia\Dominio\Convocatoria;
use Src\Competencia\Dominio\PartidoNoProgramado;
use Src\Competencia\Dominio\RepositorioDeConvocatorias;

/**
 * Convoca a un jugador vigente y sin baja del equipo del partido (HU-12, §3.4, DA-28). No valida elegibilidad
 * (RN-18, AB-07). Un jugador fuera del equipo, en baja o inexistente da el mismo 404 (fila 3).
 */
final class ConvocarJugador
{
    public function __construct(
        private readonly RepositorioDeConvocatorias $convocatorias,
        private readonly BuscarPartidoDelEntrenador $buscarPartido,
        private readonly JugadoresDelEquipo $jugadores,
        private readonly Reloj $reloj,
    ) {}

    /** @throws RecursoNoEncontrado|PartidoNoProgramado|JugadorYaConvocado */
    public function ejecutar(Identificador $partidoId, string $jugadorId): Convocatoria
    {
        $partido = $this->buscarPartido->ejecutar($partidoId);
        $partido->exigirProgramado();

        $jugador = $this->delEquipo($jugadorId, $partido->equipoId()) ?? throw new RecursoNoEncontrado;
        if ($this->convocatorias->estaConvocado($partidoId, $jugador)) {
            throw new JugadorYaConvocado;
        }

        $convocatoria = Convocatoria::nueva($partidoId, $jugador, Instante::truncadoASegundos($this->reloj->ahora()));
        $this->convocatorias->guardarNueva($convocatoria);

        return $convocatoria;
    }

    private function delEquipo(string $jugadorId, Identificador $equipoId): ?Identificador
    {
        try {
            $jugador = Identificador::desdeTexto($jugadorId);
        } catch (InvalidArgumentException) {
            return null;
        }

        foreach ($this->jugadores->de($equipoId) as $delEquipo) {
            /** @var JugadorDelEquipo $delEquipo */
            if ($delEquipo->id->igualA($jugador)) {
                return $jugador;
            }
        }

        return null;
    }
}
