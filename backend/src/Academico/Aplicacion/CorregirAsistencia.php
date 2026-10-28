<?php

namespace Src\Academico\Aplicacion;

use LogicException;
use Src\Academico\Dominio\EstadoDeAsistencia;
use Src\Academico\Dominio\RepositorioDeAsistencias;
use Src\Compartido\Aplicacion\PublicadorDeEventos;
use Src\Compartido\Dominio\Eventos\AsistenciaDeClaseRegistrada;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;

/**
 * Corrige el estado de una asistencia de una inscripción del docente (HU-07). Un estado igual al guardado no escribe ni
 * publica nada; uno distinto escribe el historial y publica `AsistenciaDeClaseRegistrada`. Un jugador en baja no admite
 * correcciones (DD-52, DA-13).
 */
final class CorregirAsistencia
{
    public function __construct(
        private readonly ComprobarAsistenciaDelDocente $comprobar,
        private readonly ConsultaDeAsistencias $consulta,
        private readonly RepositorioDeAsistencias $asistencias,
        private readonly PublicadorDeEventos $eventos,
        private readonly Reloj $reloj,
    ) {}

    /** @throws JugadorEnBajaSinCaptura */
    public function ejecutar(Identificador $asistenciaId, Identificador $docente, EstadoDeAsistencia $estado): AsistenciaLeida
    {
        $inscripcion = $this->comprobar->ejecutar($asistenciaId, $docente) ?? throw new LogicException('asistencia_ajena_tras_autorizar');
        if ($inscripcion->jugadorEnBaja) {
            throw new JugadorEnBajaSinCaptura;
        }

        if ($this->asistencias->corregirEstado($asistenciaId, $estado, Instante::truncadoASegundos($this->reloj->ahora()))) {
            $this->eventos->publicar(new AsistenciaDeClaseRegistrada($inscripcion->id->valor(), $inscripcion->materiaId->valor(), $inscripcion->jugadorId->valor()));
        }

        return $this->consulta->buscar($asistenciaId) ?? throw new LogicException('asistencia_inexistente_tras_corregir');
    }
}
