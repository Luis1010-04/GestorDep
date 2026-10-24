<?php

namespace Src\Plantel\Aplicacion;

/** Única lectura de jugadores fuera del alcance (RN-20). */
interface BuscadorDeJugadorPorMatricula
{
    /** Compara sin distinguir mayúsculas (DD-37) y encuentra también a los jugadores en baja (DD-38). */
    public function buscar(string $matricula): ?JugadorBuscado;
}
