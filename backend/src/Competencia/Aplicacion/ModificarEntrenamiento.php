<?php

namespace Src\Competencia\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Aplicacion\JugadoresDelEquipo;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;
use Src\Compartido\Dominio\Reloj;
use Src\Competencia\Dominio\EntrenamientoNoProgramado;
use Src\Competencia\Dominio\EstadoDeEntrenamiento;
use Src\Competencia\Dominio\RepositorioDeEntrenamientos;
use Src\Competencia\Dominio\TransicionNoPermitida;

/**
 * Cambio de hora, lugar, responsable o estado de un entrenamiento (HU-10, criterio 2). Los datos se aplican antes que
 * el estado, y todo queda en una sola fila de historial. Si sigue sin cancelar, se advierte el empalme (DA-09).
 */
final class ModificarEntrenamiento
{
    public function __construct(
        private readonly RepositorioDeEntrenamientos $entrenamientos,
        private readonly BuscarEntrenamientoDelEntrenador $buscar,
        private readonly ReferenciasDeEntrenamiento $referencias,
        private readonly JugadoresDelEquipo $jugadores,
        private readonly Reloj $reloj,
    ) {}

    /** @throws RecursoNoEncontrado|LugarInexistente|ResponsableNoAsignado|EntrenamientoNoProgramado|TransicionNoPermitida|ReglaDeNegocioViolada */
    public function ejecutar(
        Identificador $id,
        ?DateTimeImmutable $iniciaEn,
        ?DateTimeImmutable $terminaEn,
        ?string $lugarId,
        ?string $responsableId,
        ?EstadoDeEntrenamiento $estado,
    ): EntrenamientoConJugadores {
        $anterior = $this->buscar->ejecutar($id);
        $instante = Instante::truncadoASegundos($this->reloj->ahora());
        $nuevo = $anterior;

        if ($iniciaEn !== null || $terminaEn !== null || $lugarId !== null || $responsableId !== null) {
            $nuevo = $nuevo->conDatos(
                $iniciaEn,
                $terminaEn,
                $lugarId === null ? null : $this->referencias->lugar($lugarId),
                $responsableId === null ? null : $this->referencias->responsable($responsableId, $anterior->equipoId()),
                $instante,
            );
        }
        if ($estado !== null) {
            $nuevo = $nuevo->conEstado($estado, $instante);
        }

        $this->entrenamientos->actualizar($anterior, $nuevo);

        return new EntrenamientoConJugadores(
            $nuevo,
            $this->jugadores->de($nuevo->equipoId()),
            $nuevo->cuentaParaEmpalmes() && $this->entrenamientos->hayEmpalme($nuevo),
        );
    }
}
