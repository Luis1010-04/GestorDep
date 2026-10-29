# Plan F1-H2-T02: `NumeroDecimal` en Compartido

**Enfoque**
- `src/Compartido/Dominio/NumeroDecimal.php`: valor inmutable sobre `bcmath` (cadena normalizada). Firmas: `desdeTexto(string)`, `media(list<NumeroDecimal>, int $escala = 2)`, `sumar`, `restar`, `multiplicar`, `dividir(NumeroDecimal, int $escala)`, `redondear(int $escala = 2)`, `comparar`, `igualA`, `menorQue`, `menorOIgualQue`, `mayorQue`, `mayorOIgualQue`, `decimales()`, `comoCadena(int $decimales = 2)`.
- Redondeo HALF_UP con `bcround(..., RoundingMode::HalfAwayFromZero)` (PHP 8.4); la división calcula un decimal más y redondea con él, de modo que 1 ÷ 3 y 2 ÷ 3 dan 0.33 y 0.67 sin `float`.
- `NumeroDecimalInvalido` (extiende `ReglaDeNegocioViolada`, código `numero_decimal_invalido`) para texto inválido. Sin entrada en `lang/es` (la ruta no está permitida en T02): el HTTP nunca lo alcanza porque valida antes; si llegara, usa el mensaje genérico.
- `app/Http/Resources/DecimalComoCadena.php`: serializa `?NumeroDecimal` a cadena de dos decimales o nulo.
- `Dockerfile` (`bcmath` no estaba instalada: `php -m` no la lista) y `composer.json` (`ext-bcmath`).

**Cómo se prohíbe `float` en el dominio (propuesta ya implementada en `tests/Arch/DecimalesEnDominioTest.php`):** una prueba lee con `token_get_all` cada archivo de `src/*/Dominio/` y falla ante tipos `float`/`double`, conversión `(float)`, literales decimales o con exponente (`T_DNUMBER`) y las funciones `floatval`, `doubleval`, `is_float`, `round`, `floor`, `ceil` y `number_format`. Los comentarios y cadenas no cuentan. Además una regla `arch()` de Pest prohíbe esas cuatro funciones de redondeo.

**Fila 7 de §7.12:** el dominio expone `decimales()` y acepta más precisión; el 422 por más de dos decimales vive en la capa HTTP y se prueba en T07/T11/T12 (§7.6 fila 6 y §7.7 fila 5). Ver DA-04.

**Pruebas:** filas 3 a 7 de §7.12 (`tests/Unit/Compartido/NumeroDecimalTest.php`, `tests/Arch/DecimalesEnDominioTest.php`).

**Dudas:** el flujo de CI (`.github/workflows/verificar.yml`) necesita `bcmath` en `extensions`; la ruta no está permitida en T02, así que se deja anotado (ver reporte).
