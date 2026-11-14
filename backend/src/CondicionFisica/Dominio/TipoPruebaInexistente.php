<?php

namespace Src\CondicionFisica\Dominio;

/** Tipo de prueba inexistente, eliminado o con un UUID que no es v7: el mismo cuerpo en todos los casos (DA-61). */
final class TipoPruebaInexistente extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('tipo_prueba_inexistente', 'tipo_prueba_id');
    }
}
