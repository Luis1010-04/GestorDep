<?php

namespace Src\Plantel\Infraestructura;

use Src\Compartido\Aplicacion\JugadorLocalizado;
use Src\Compartido\Aplicacion\LocalizadorDeJugadores;
use Src\Plantel\Dominio\RepositorioDeJugadores;

/** Adaptador del puerto de Compartido: delega en `buscarParaAsignar`, la búsqueda por matrícula que Plantel ya tiene autorizada. */
final class LocalizadorDeJugadoresPlantel implements LocalizadorDeJugadores
{
    public function __construct(private readonly RepositorioDeJugadores $jugadores) {}

    public function porMatricula(string $matricula): ?JugadorLocalizado
    {
        $jugador = $this->jugadores->buscarParaAsignar($matricula);

        return $jugador === null ? null : new JugadorLocalizado($jugador->id, $jugador->enBaja);
    }
}
