<?php

namespace Src\Plantel\Aplicacion;

/** Búsqueda por matrícula (RN-20): cualquier usuario con sesión localiza a un jugador no asignado. */
final class BuscarJugadorPorMatricula
{
    public function __construct(private readonly BuscadorDeJugadorPorMatricula $buscador) {}

    /** @throws JugadorNoEncontrado */
    public function ejecutar(string $matricula): JugadorBuscado
    {
        return $this->buscador->buscar(trim($matricula)) ?? throw new JugadorNoEncontrado;
    }
}
