<?php

namespace Src\Competencia\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Aplicacion\VisibilidadDeJugador;

/**
 * Restringe con `AlcanceDeJugadores` una consulta sobre el identificador candidato, sin leer tablas de Plantel desde
 * Competencia (§5.3): solo la rama del entrenador y con bajas, porque el entrenador conserva el historial (DA-20).
 */
final class VisibilidadDeJugadorSql implements VisibilidadDeJugador
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly AlcanceDeJugadores $alcance,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function esVisibleParaEntrenador(Identificador $jugadorId): bool
    {
        $candidato = $this->conexion->table('candidato')->fromRaw('(select cast(? as uuid) as id) as candidato', [$jugadorId->valor()]);

        return $this->alcance->restringir($this->usuarioActual, $candidato, 'candidato.id', incluirBajas: true, soloEntrenador: true)->exists();
    }
}
