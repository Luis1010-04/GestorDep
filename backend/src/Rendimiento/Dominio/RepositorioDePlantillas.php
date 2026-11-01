<?php

namespace Src\Rendimiento\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

interface RepositorioDePlantillas
{
    /** Inserta la plantilla con su historial en una transacción; un nombre que choca con el índice único lanza PlantillaDuplicada y una posición inexistente, PosicionInexistente (Aplicacion). */
    public function guardarNueva(PlantillaDeEvaluacion $plantilla, DateTimeImmutable $instante): void;

    /** Si el entrenador ya tiene una plantilla con ese nombre en la posición, o hay una de fábrica (v1.0.4 §6.3). */
    public function nombreOcupado(string $nombre, Identificador $posicion, Identificador $entrenador): bool;

    /** Una plantilla de fábrica o propia del entrenador; la de otro entrenador es nula, como una inexistente. */
    public function buscarVisible(Identificador $id, Identificador $entrenador): ?PlantillaDeEvaluacion;

    /** La plantilla de fábrica de la posición; si hubiera varias, la más antigua (DA-26). */
    public function deFabricaDe(Identificador $posicion): ?PlantillaDeEvaluacion;
}
