<?php

namespace Src\Plantel\Aplicacion;

use DateTimeImmutable;
use LogicException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Dominio\RepositorioDeJugadores;

/**
 * Registra la baja de un jugador del alcance del usuario (HU-29, RN-23). La baja es global (DD-30): cambia ambos estatus
 * y no cierra las membresías (RN-17). El alcance lo comprueba la solicitud HTTP antes de llegar aquí.
 */
final class DarDeBajaAJugador
{
    public function __construct(
        private readonly RepositorioDeJugadores $jugadores,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /** @throws MotivoObligatorio|JugadorNoEncontrado|JugadorYaEnBaja */
    public function ejecutar(Identificador $jugadorId, string $motivo, DateTimeImmutable $fecha): Baja
    {
        $this->usuarioActual->identificador() ?? throw new LogicException('usuario_no_identificado');

        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new MotivoObligatorio;
        }

        return $this->jugadores->darDeBaja($jugadorId, $motivo, $fecha);
    }
}
