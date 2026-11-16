<?php

namespace Src\Alertas\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/**
 * La tabla `alertas` (DA-87): solo se abre y se cierra. No hay operación para reabrir, modificar ni borrar; el disparador
 * de la base lo rechazaría. `equipo` es nulo en la alerta académica.
 */
interface RepositorioDeAlertas
{
    public function hayAbierta(TipoDeAlerta $tipo, Identificador $jugador, ?Identificador $equipo): bool;

    /**
     * Inserta la alerta abierta. Si otro proceso ya abrió una igual entre la consulta y el INSERT, no hace nada.
     *
     * @param  array<string, mixed>  $causa
     */
    public function abrir(TipoDeAlerta $tipo, Identificador $jugador, ?Identificador $equipo, array $causa, DateTimeImmutable $ahora): void;

    /** Escribe `cerrada_en` una sola vez: no toca una alerta que ya esté cerrada. */
    public function cerrar(TipoDeAlerta $tipo, Identificador $jugador, ?Identificador $equipo, DateTimeImmutable $ahora): void;
}
