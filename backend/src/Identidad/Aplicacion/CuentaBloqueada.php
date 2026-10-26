<?php

namespace Src\Identidad\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Cuenta bloqueada por intentos fallidos (RNF-10); `$segundosRestantes` alimenta `Retry-After`. */
final class CuentaBloqueada extends ReglaDeNegocioViolada
{
    public function __construct(private readonly int $segundosRestantes)
    {
        parent::__construct('cuenta_bloqueada');
    }

    public function segundosRestantes(): int
    {
        return $this->segundosRestantes;
    }
}
