<?php

namespace Src\Salud\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Salud\Aplicacion\VisibilidadDeJugador;

/** Restringe con `AlcanceDeJugadores` una consulta sobre el identificador candidato, sin leer tablas de Plantel. */
final class VisibilidadDeJugadorSql implements VisibilidadDeJugador
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly AlcanceDeJugadores $alcance,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function esVisible(Identificador $jugadorId, bool $incluirBajas): bool
    {
        return $this->alcance->restringir($this->usuarioActual, $this->candidato($jugadorId), 'candidato.id', incluirBajas: $incluirBajas)->exists();
    }

    public function esVisibleComoEntrenador(Identificador $jugadorId): bool
    {
        return $this->alcance->restringir($this->usuarioActual, $this->candidato($jugadorId), 'candidato.id', incluirBajas: true, soloEntrenador: true)->exists();
    }

    private function candidato(Identificador $jugadorId): Builder
    {
        return $this->conexion->table('candidato')->fromRaw('(select cast(? as uuid) as id) as candidato', [$jugadorId->valor()]);
    }
}
