<?php

namespace Src\Competencia\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Aplicacion\VisibilidadDeJugador;

/**
 * Restringe con `AlcanceDeJugadores` una consulta sobre el identificador candidato, sin leer tablas de Plantel desde
 * Competencia (§5.3): solo la rama del entrenador; con bajas para el historial (DA-20) y sin ellas para la
 * alineación (DA-34).
 */
final class VisibilidadDeJugadorSql implements VisibilidadDeJugador
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly AlcanceDeJugadores $alcance,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function esVisibleParaEntrenador(Identificador $jugadorId, bool $incluirBajas = true): bool
    {
        $candidato = $this->conexion->table('candidato')->fromRaw('(select cast(? as uuid) as id) as candidato', [$jugadorId->valor()]);

        return $this->alcance->restringir($this->usuarioActual, $candidato, 'candidato.id', incluirBajas: $incluirBajas, soloEntrenador: true)->exists();
    }
}
