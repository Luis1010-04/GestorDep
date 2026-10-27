<?php

namespace App\Http\Respuestas;

use InvalidArgumentException;
use Src\Compartido\Dominio\Identificador;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** Identificador de un parámetro de ruta: un UUID válido que no es v7 es un recurso inexistente (404), nunca un 500. */
final class IdentificadorDeRuta
{
    public static function desde(string $texto): Identificador
    {
        try {
            return Identificador::desdeTexto($texto);
        } catch (InvalidArgumentException) {
            throw new NotFoundHttpException;
        }
    }
}
