<?php

namespace Src\Compartido\Dominio;

/** El texto no es un número decimal con signo opcional, parte entera y parte fraccionaria opcional. */
final class NumeroDecimalInvalido extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('numero_decimal_invalido');
    }
}
