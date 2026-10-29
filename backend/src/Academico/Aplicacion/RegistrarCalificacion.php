<?php

namespace Src\Academico\Aplicacion;

use DateTimeImmutable;
use LogicException;
use Src\Academico\Dominio\Calificacion;
use Src\Academico\Dominio\EscalaDeCalificaciones;
use Src\Academico\Dominio\RepositorioDeCalificaciones;
use Src\Academico\Dominio\RepositorioDeCriterios;
use Src\Academico\Dominio\TipoDeCalificacion;
use Src\Compartido\Aplicacion\PublicadorDeEventos;
use Src\Compartido\Dominio\Eventos\CalificacionRegistrada;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Dominio\Reloj;

/**
 * Captura una calificación parcial o final de una inscripción del docente (HU-07). Guarda el criterio vigente al capturar
 * (DD-47), escribe su historial y publica `CalificacionRegistrada`; no recalcula (lo hace el escucha). La pertenencia de la
 * inscripción al docente la comprueba la solicitud HTTP antes de llegar aquí.
 */
final class RegistrarCalificacion
{
    public function __construct(
        private readonly ConsultaDeInscripciones $inscripciones,
        private readonly ConsultaDeCalificaciones $consulta,
        private readonly RepositorioDeCalificaciones $calificaciones,
        private readonly RepositorioDeCriterios $criterios,
        private readonly PublicadorDeEventos $eventos,
        private readonly HoyDeNegocio $hoyDeNegocio,
        private readonly Reloj $reloj,
    ) {}

    /** @throws CalificacionFueraDeRango|JugadorEnBajaSinCaptura|CalificacionDuplicada */
    public function ejecutar(
        Identificador $inscripcionId,
        Identificador $docente,
        TipoDeCalificacion $tipo,
        ?int $numeroParcial,
        NumeroDecimal $valor,
        ?DateTimeImmutable $fecha,
    ): CalificacionLeida {
        if (! EscalaDeCalificaciones::contiene($valor)) {
            throw new CalificacionFueraDeRango('valor');
        }
        $inscripcion = $this->inscripciones->delDocente($inscripcionId, $docente) ?? throw new LogicException('inscripcion_ajena_tras_autorizar');
        if ($inscripcion->jugadorEnBaja) {
            throw new JugadorEnBajaSinCaptura;
        }

        $calificacion = new Calificacion(
            Identificador::generar(),
            $inscripcion->id,
            $tipo,
            $numeroParcial,
            $valor,
            $this->criterios->idVigenteParaMateria($inscripcion->materiaId),
            $fecha ?? $this->hoyDeNegocio->hoy(),
            Instante::truncadoASegundos($this->reloj->ahora()),
        );
        $this->calificaciones->guardarNueva($calificacion);
        $this->eventos->publicar(new CalificacionRegistrada($inscripcion->id->valor(), $inscripcion->materiaId->valor(), $inscripcion->jugadorId->valor()));

        return $this->consulta->buscar($calificacion->id()) ?? throw new LogicException('calificacion_inexistente_tras_guardar');
    }
}
