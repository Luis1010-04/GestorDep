<?php

namespace Src\Compartido\Dominio;

use DomainException;
use InvalidArgumentException;

/**
 * Violacion de una regla de negocio. El codigo es estable y en snake_case
 * (docs/convenciones-api.md); el mensaje visible se resuelve en lang/es.
 */
class ReglaDeNegocioViolada extends DomainException
{
    public function __construct(private readonly string $codigo)
    {
        if (preg_match('/^[a-z][a-z0-9]*(_[a-z0-9]+)*$/', $codigo) !== 1) {
            throw new InvalidArgumentException('codigo_de_regla_invalido');
        }

        parent::__construct($codigo);
    }

    public function codigo(): string
    {
        return $this->codigo;
    }
}
