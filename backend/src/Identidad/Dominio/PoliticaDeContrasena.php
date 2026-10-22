<?php

namespace Src\Identidad\Dominio;

use InvalidArgumentException;

/** Política de contraseña de RNF-10: solo una longitud mínima, sin reglas de complejidad. */
final class PoliticaDeContrasena
{
    public function __construct(private readonly int $longitudMinima)
    {
        if ($longitudMinima < 1) {
            throw new InvalidArgumentException('longitud_minima_invalida');
        }
    }

    public function cumple(string $contrasena): bool
    {
        return mb_strlen($contrasena) >= $this->longitudMinima;
    }
}
