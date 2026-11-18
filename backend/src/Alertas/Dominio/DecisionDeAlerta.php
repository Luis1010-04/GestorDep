<?php

namespace Src\Alertas\Dominio;

/**
 * Activación y cierre de una alerta (HU-22, DA-87). Hay a lo sumo una alerta abierta: si la condición se cumple y ya
 * hay una, no se duplica; si deja de cumplirse, la abierta se cierra y se conserva.
 */
final class DecisionDeAlerta
{
    public static function decidir(bool $seCumple, bool $hayAbierta): AccionSobreAlerta
    {
        if ($seCumple) {
            return $hayAbierta ? AccionSobreAlerta::Ninguna : AccionSobreAlerta::Abrir;
        }

        return $hayAbierta ? AccionSobreAlerta::Cerrar : AccionSobreAlerta::Ninguna;
    }
}
