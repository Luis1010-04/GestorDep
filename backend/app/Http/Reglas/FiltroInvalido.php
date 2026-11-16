<?php

namespace App\Http\Reglas;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/**
 * Regla de un valor de `filtro[campo]` (docs/hitos/f1-h6.md §6.3, P-05). El `codigo` del error de campo es el nombre de la
 * regla que falló en `snake_case` (`App\Exceptions\FormatoDeError`), así que esta clase fija `filtro_invalido`.
 */
final class FiltroInvalido implements ValidationRule
{
    /** @param  list<string>|null  $valores  los únicos valores admitidos; nulo si el filtro es un UUID */
    private function __construct(private readonly ?array $valores) {}

    public static function uuid(): self
    {
        return new self(null);
    }

    /** @param  list<string>  $valores */
    public static function unoDe(array $valores): self
    {
        return new self($valores);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $valido = $this->valores === null
            ? is_string($value) && Str::isUuid($value)
            : is_string($value) && in_array($value, $this->valores, true);

        if (! $valido) {
            $fail('validation.filtro_invalido')->translate();
        }
    }
}
