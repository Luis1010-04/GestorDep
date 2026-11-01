<?php

namespace Src\Rendimiento\Dominio;

use InvalidArgumentException;

/**
 * Categoría de texto de la calificación general de un partido (RN-06, DD-09): de 1 a 4 deficiente, de 5 a 6 regular,
 * de 7 a 8 bueno y de 9 a 10 sobresaliente. Es una constante del dominio, sin tabla (v1.0.4, DA-24); no cambia el
 * número que se guarda.
 */
enum CategoriaDeCalificacion: string
{
    case Deficiente = 'deficiente';
    case Regular = 'regular';
    case Bueno = 'bueno';
    case Sobresaliente = 'sobresaliente';

    public static function de(int $calificacion): self
    {
        if ($calificacion < 1 || $calificacion > 10) {
            throw new InvalidArgumentException('calificacion_general_invalida');
        }

        return match (true) {
            $calificacion <= 4 => self::Deficiente,
            $calificacion <= 6 => self::Regular,
            $calificacion <= 8 => self::Bueno,
            default => self::Sobresaliente,
        };
    }
}
