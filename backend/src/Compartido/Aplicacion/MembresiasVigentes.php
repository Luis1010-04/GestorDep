<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Las parejas (jugador, equipo) con membresía vigente de jugadores sin baja, para el recálculo de alertas, que corre sin
 * usuario de sesión (DA-115). El adaptador vive en Plantel. Un jugador en baja no aparece (P-08c, DA-99); uno sin
 * membresía vigente aparece una vez con el equipo nulo, porque la alerta académica no depende del equipo.
 */
interface MembresiasVigentes
{
    /** @return list<MembresiaVigente> */
    public function todas(): array;

    /** @return list<MembresiaVigente> vacía si el jugador está en baja o no existe */
    public function delJugador(Identificador $jugador): array;
}
