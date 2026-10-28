<?php

namespace Src\Academico\Aplicacion;

use LogicException;
use Src\Academico\Dominio\EscalaDeCalificaciones;
use Src\Academico\Dominio\RepositorioDeCalificaciones;
use Src\Compartido\Aplicacion\PublicadorDeEventos;
use Src\Compartido\Dominio\Eventos\CalificacionRegistrada;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Dominio\Reloj;

/**
 * Corrige el valor de una calificación de una inscripción del docente (HU-07). El criterio guardado al capturar no cambia
 * (DD-47). Un valor igual al guardado no escribe ni publica nada; uno distinto escribe el historial y publica
 * `CalificacionRegistrada`. Un jugador en baja no admite correcciones (DD-52, DA-13).
 */
final class CorregirCalificacion
{
    public function __construct(
        private readonly ComprobarCalificacionDelDocente $comprobar,
        private readonly ConsultaDeCalificaciones $consulta,
        private readonly RepositorioDeCalificaciones $calificaciones,
        private readonly PublicadorDeEventos $eventos,
        private readonly Reloj $reloj,
    ) {}

    /** @throws CalificacionFueraDeRango|JugadorEnBajaSinCaptura */
    public function ejecutar(Identificador $calificacionId, Identificador $docente, NumeroDecimal $valor): CalificacionLeida
    {
        if (! EscalaDeCalificaciones::contiene($valor)) {
            throw new CalificacionFueraDeRango('valor');
        }
        $inscripcion = $this->comprobar->ejecutar($calificacionId, $docente) ?? throw new LogicException('calificacion_ajena_tras_autorizar');
        if ($inscripcion->jugadorEnBaja) {
            throw new JugadorEnBajaSinCaptura;
        }

        if ($this->calificaciones->corregirValor($calificacionId, $valor, Instante::truncadoASegundos($this->reloj->ahora()))) {
            $this->eventos->publicar(new CalificacionRegistrada($inscripcion->id->valor(), $inscripcion->materiaId->valor(), $inscripcion->jugadorId->valor()));
        }

        return $this->consulta->buscar($calificacionId) ?? throw new LogicException('calificacion_inexistente_tras_corregir');
    }
}
