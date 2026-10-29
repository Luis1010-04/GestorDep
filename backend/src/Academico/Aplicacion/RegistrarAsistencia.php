<?php

namespace Src\Academico\Aplicacion;

use DateTimeImmutable;
use LogicException;
use Src\Academico\Dominio\AsistenciaDeClase;
use Src\Academico\Dominio\EstadoDeAsistencia;
use Src\Academico\Dominio\RepositorioDeAsistencias;
use Src\Compartido\Aplicacion\PublicadorDeEventos;
use Src\Compartido\Dominio\Eventos\AsistenciaDeClaseRegistrada;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;

/**
 * Registra la asistencia a clase de una inscripción del docente (HU-07 criterio 3). Escribe su historial y publica
 * `AsistenciaDeClaseRegistrada`; no recalcula (lo hace el escucha). La pertenencia de la inscripción al docente la
 * comprueba la solicitud HTTP antes de llegar aquí.
 */
final class RegistrarAsistencia
{
    public function __construct(
        private readonly ConsultaDeInscripciones $inscripciones,
        private readonly ConsultaDeAsistencias $consulta,
        private readonly RepositorioDeAsistencias $asistencias,
        private readonly PublicadorDeEventos $eventos,
        private readonly HoyDeNegocio $hoyDeNegocio,
        private readonly Reloj $reloj,
    ) {}

    /** @throws JugadorEnBajaSinCaptura|AsistenciaDuplicada */
    public function ejecutar(Identificador $inscripcionId, Identificador $docente, EstadoDeAsistencia $estado, ?DateTimeImmutable $fecha): AsistenciaLeida
    {
        $inscripcion = $this->inscripciones->delDocente($inscripcionId, $docente) ?? throw new LogicException('inscripcion_ajena_tras_autorizar');
        if ($inscripcion->jugadorEnBaja) {
            throw new JugadorEnBajaSinCaptura;
        }

        $asistencia = new AsistenciaDeClase(
            Identificador::generar(),
            $inscripcion->id,
            $fecha ?? $this->hoyDeNegocio->hoy(),
            $estado,
            Instante::truncadoASegundos($this->reloj->ahora()),
        );
        $this->asistencias->guardarNueva($asistencia);
        $this->eventos->publicar(new AsistenciaDeClaseRegistrada($inscripcion->id->valor(), $inscripcion->materiaId->valor(), $inscripcion->jugadorId->valor()));

        return $this->consulta->buscar($asistencia->id()) ?? throw new LogicException('asistencia_inexistente_tras_guardar');
    }
}
