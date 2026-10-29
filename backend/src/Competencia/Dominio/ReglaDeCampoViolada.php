<?php

namespace Src\Competencia\Dominio;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Regla de negocio que se atribuye a un campo de la petición: la respuesta lo señala en `errores` (docs/convenciones-api.md §3). */
abstract class ReglaDeCampoViolada extends ReglaDeNegocioViolada
{
    public function __construct(string $codigo, private readonly string $campo)
    {
        parent::__construct($codigo);
    }

    public function campo(): string
    {
        return $this->campo;
    }
}
