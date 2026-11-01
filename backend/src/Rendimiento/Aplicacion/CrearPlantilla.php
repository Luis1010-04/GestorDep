<?php

namespace Src\Rendimiento\Aplicacion;

use InvalidArgumentException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Dominio\Reloj;
use Src\Rendimiento\Dominio\CriterioDeEvaluacion;
use Src\Rendimiento\Dominio\PlantillaDeEvaluacion;
use Src\Rendimiento\Dominio\RepositorioDePlantillas;
use Src\Rendimiento\Dominio\ValorFueraDeRango;

/**
 * Crea una plantilla propia del entrenador de la sesión para una posición (HU-15, RN-07). La posición es la de la
 * plantilla: un entrenador no tiene posición (DA-29). Comprueba en este orden: posición con UUID v7 (422), rangos
 * (422), nombre libre para él o frente a una de fábrica (409); la existencia de la posición la comprueba la clave
 * foránea al escribir, como DA-31 de H3.
 */
final class CrearPlantilla
{
    public function __construct(
        private readonly RepositorioDePlantillas $plantillas,
        private readonly UsuarioActual $usuarioActual,
        private readonly Reloj $reloj,
    ) {}

    /** @throws RecursoNoEncontrado|PosicionInexistente|ValorFueraDeRango|PlantillaDuplicada */
    public function ejecutar(string $nombre, string $posicionId, string $calificacionMinima, int $ventanaPartidos): PlantillaDeEvaluacion
    {
        $autor = $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;
        try {
            $posicion = Identificador::desdeTexto($posicionId);
        } catch (InvalidArgumentException) {
            throw new PosicionInexistente;
        }

        $criterio = new CriterioDeEvaluacion(NumeroDecimal::desdeTexto($calificacionMinima), $ventanaPartidos);
        if ($this->plantillas->nombreOcupado($nombre, $posicion, $autor)) {
            throw new PlantillaDuplicada;
        }

        $plantilla = PlantillaDeEvaluacion::propia(Identificador::generar(), $nombre, $posicion, $autor, $criterio);
        $this->plantillas->guardarNueva($plantilla, Instante::truncadoASegundos($this->reloj->ahora()));

        return $plantilla;
    }
}
