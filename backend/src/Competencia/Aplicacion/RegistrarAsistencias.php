<?php

namespace Src\Competencia\Aplicacion;

use InvalidArgumentException;
use Src\Compartido\Aplicacion\CandadoDeJugadores;
use Src\Compartido\Aplicacion\JugadorDelEquipo;
use Src\Compartido\Aplicacion\JugadoresDelEquipo;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Competencia\Dominio\AsistenciaDeEntrenamiento;
use Src\Competencia\Dominio\EstadoDeAsistencia;
use Src\Competencia\Dominio\EstadoDeEntrenamiento;
use Src\Competencia\Dominio\RepositorioDeAsistenciasDeEntrenamiento;

/**
 * Captura por lista de la asistencia a un entrenamiento realizado (HU-11, criterio 1). Todo o nada (DA-17): si un
 * registro falla, no se guarda ninguno. La pertenencia es la de hoy: membresía vigente y sin baja (§3.4).
 */
final class RegistrarAsistencias
{
    public function __construct(
        private readonly RepositorioDeAsistenciasDeEntrenamiento $asistencias,
        private readonly BuscarEntrenamientoDelEntrenador $buscarEntrenamiento,
        private readonly JugadoresDelEquipo $jugadores,
        private readonly Reloj $reloj,
        private readonly CandadoDeJugadores $candado,
    ) {}

    /**
     * @param  list<array{jugador_id: string, estado: EstadoDeAsistencia}>  $registros
     * @return list<AsistenciaDeEntrenamiento> en el orden recibido
     *
     * @throws RecursoNoEncontrado|EntrenamientoNoRealizado|JugadorNoPerteneceAlEquipo|AsistenciaYaRegistrada
     */
    public function ejecutar(Identificador $entrenamientoId, array $registros): array
    {
        $entrenamiento = $this->buscarEntrenamiento->ejecutar($entrenamientoId);
        if ($entrenamiento->estado() !== EstadoDeEntrenamiento::Realizado) {
            throw new EntrenamientoNoRealizado;
        }

        $delEquipo = array_map(fn (JugadorDelEquipo $jugador) => $jugador->id->valor(), $this->jugadores->de($entrenamiento->equipoId()));
        $instante = Instante::truncadoASegundos($this->reloj->ahora());
        $nuevas = [];
        foreach ($registros as $indice => $registro) {
            $jugador = $this->identificador($registro['jugador_id']);
            if ($jugador === null || ! in_array($jugador->valor(), $delEquipo, true)) {
                throw new JugadorNoPerteneceAlEquipo("registros.{$indice}.jugador_id");
            }
            $nuevas[] = new AsistenciaDeEntrenamiento(Identificador::generar(), $entrenamientoId, $jugador, $registro['estado'], $instante);
        }

        $jugadores = array_map(fn (AsistenciaDeEntrenamiento $a) => $a->jugadorId(), $nuevas);
        if ($this->asistencias->hayAlgunaDe($entrenamientoId, $jugadores)) {
            throw new AsistenciaYaRegistrada;
        }
        // Con las filas de los jugadores bloqueadas: una baja simultánea se serializa con la captura (AB-31). El jugador que
        // quedó en baja responde como hoy uno en baja: 422 en su registro. Los inactivos vienen en el orden de la lista.
        $valores = array_map(fn (Identificador $jugador) => $jugador->valor(), $jugadores);
        $this->candado->conJugadoresActivos(
            $jugadores,
            fn (array $inactivos): JugadorNoPerteneceAlEquipo => new JugadorNoPerteneceAlEquipo(
                'registros.'.(int) array_search($inactivos[0]->valor(), $valores, true).'.jugador_id',
            ),
            fn () => $this->asistencias->guardarNuevas($nuevas),
        );

        return $nuevas;
    }

    private function identificador(string $texto): ?Identificador
    {
        try {
            return Identificador::desdeTexto($texto);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
