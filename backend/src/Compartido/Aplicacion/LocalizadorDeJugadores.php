<?php

namespace Src\Compartido\Aplicacion;

/**
 * Localiza a un jugador por matrícula sin pasar por `AlcanceDeJugadores`: es la excepción de visibilidad de RN-20 que
 * necesita la inscripción de un docente, que otorga el alcance (docs/hitos/f1-h2.md §5.4). Solo la usa el caso de uso
 * `InscribirJugador` de Académico (lo verifica `tests/Arch/LocalizadorDeJugadoresTest.php`) y devuelve únicamente el
 * identificador y si el jugador está en baja.
 */
interface LocalizadorDeJugadores
{
    /** La matrícula llega recortada; se compara sin distinguir mayúsculas (DD-37). Nulo si no hay un expediente vigente con ella. */
    public function porMatricula(string $matricula): ?JugadorLocalizado;
}
