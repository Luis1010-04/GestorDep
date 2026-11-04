<?php

namespace Src\Salud\Aplicacion;

use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Salud\Dominio\AvisoDeLesion;
use Src\Salud\Dominio\RepositorioDeAvisos;

/**
 * Crea un aviso pendiente (HU-19). La solicitud HTTP ya comprobó el alcance y validó el cuerpo, así que aquí solo falta
 * ver si el jugador está en baja: visible con bajas y no visible sin ellas (DD-60, DA-24). No toca el estado oficial.
 */
final class CrearAviso
{
    public function __construct(
        private readonly RepositorioDeAvisos $avisos,
        private readonly VisibilidadDeJugador $visibilidad,
        private readonly UsuarioActual $usuarioActual,
        private readonly Reloj $reloj,
    ) {}

    /** @throws RecursoNoEncontrado|JugadorEnBajaSinAviso */
    public function ejecutar(Identificador $jugadorId, string $nota, string $fecha, bool $reportadoPorJugador): AvisoVisible
    {
        $autor = $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;
        if (! $this->visibilidad->esVisible($jugadorId, incluirBajas: false)) {
            throw new JugadorEnBajaSinAviso;
        }

        $aviso = AvisoDeLesion::crear(
            Identificador::generar(), $jugadorId, $autor, $reportadoPorJugador, $nota, $fecha,
            Instante::truncadoASegundos($this->reloj->ahora()),
        );
        $this->avisos->guardarNuevo($aviso);

        return AvisoVisible::para($aviso, $autor);
    }
}
