<?php

namespace Src\Alertas\Aplicacion;

use DateTimeImmutable;
use Src\Alertas\Dominio\AccionSobreAlerta;
use Src\Alertas\Dominio\DecisionDeAlerta;
use Src\Compartido\Dominio\Identificador;

/** Abre, cierra o deja una alerta según si su condición se cumple y si ya hay una abierta (HU-22, DA-87). */
final class AplicarDecisionDeAlerta
{
    public function __construct(private readonly RepositorioDeAlertas $alertas) {}

    /** @param  array<string, mixed>  $causa  la que se escribe si la alerta se abre */
    public function ejecutar(TipoDeAlerta $tipo, Identificador $jugador, ?Identificador $equipo, bool $seCumple, array $causa, DateTimeImmutable $ahora): AccionSobreAlerta
    {
        $accion = DecisionDeAlerta::decidir($seCumple, $this->alertas->hayAbierta($tipo, $jugador, $equipo));

        match ($accion) {
            AccionSobreAlerta::Abrir => $this->alertas->abrir($tipo, $jugador, $equipo, $causa, $ahora),
            AccionSobreAlerta::Cerrar => $this->alertas->cerrar($tipo, $jugador, $equipo, $ahora),
            AccionSobreAlerta::Ninguna => null,
        };

        return $accion;
    }
}
