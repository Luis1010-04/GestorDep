<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Rendimiento\Dominio\RepositorioDePlantillas;
use Src\Rendimiento\Dominio\RepositorioDeUmbrales;

/** Umbral de un jugador en un equipo para el entrenador de la sesión; sin umbral propio, la plantilla de fábrica de su posición (HU-15, criterio 1). */
final class ConsultarUmbral
{
    public function __construct(
        private readonly ComprobarJugadorDelEquipo $comprobar,
        private readonly RepositorioDeUmbrales $umbrales,
        private readonly RepositorioDePlantillas $plantillas,
    ) {}

    /** @throws RecursoNoEncontrado también si la posición no tiene plantilla de fábrica (DA-26) */
    public function ejecutar(Identificador $equipo, Identificador $jugador): UmbralAplicable
    {
        ['entrenador' => $entrenador, 'posicion' => $posicion] = $this->comprobar->ejecutar($equipo, $jugador);

        $umbral = $this->umbrales->vigenteDe($jugador, $equipo, $entrenador);
        if ($umbral !== null) {
            return UmbralAplicable::desdeUmbral($umbral);
        }

        return UmbralAplicable::desdePlantilla($this->plantillas->deFabricaDe($posicion) ?? throw new RecursoNoEncontrado);
    }
}
