# Reporte F1-H2-FIX: M-g de la auditoría corta (el guion de cobertura falla si no puede borrar el clover)

**Commit:** `a51ee16` (`F1-H2-FIX: M-g el guion de cobertura falla si no puede borrar el clover`); este reporte va en el commit F1-DOC de la auditoría corta. **Origen:** auditoría corta del rango `1145c77..4fb2fc5` (29/10/2026), M-g, y la instrucción del responsable del mismo día.

## Hallazgo

Desde PT-02 (`b010636`), `bin/verificar-cobertura.php` borra el clover tras leerlo, pero no comprobaba si el `unlink` lo lograba. Si el borrado fallaba, el guion seguía, y el clover viejo podía aprobar la siguiente corrida: el defecto original de PT-02 volvía sin aviso.

## Cambio

- **`backend/bin/verificar-cobertura.php`:** `if (! @unlink($reporte))`, termina con código 1 y el mensaje «El reporte Clover no se pudo borrar: …», antes de validar el XML. No cambian los umbrales, las capas ni los demás mensajes.
- **`backend/tests/Unit/Herramientas/VerificarCoberturaTest.php`:** una prueba nueva, `PT-02 (M-g): si el clover no se puede borrar, falla con «El reporte Clover no se pudo borrar»`. Usa `/proc/version`, un archivo regular que se puede leer y que nadie puede borrar. El contenedor corre como root, así que quitarle permisos a una carpeta no impide el borrado. Exige el código 1 y la salida exacta.

## Pruebas primero

Con el guion anterior, la prueba nueva falla por la razón correcta, porque el guion ignoraba el fallo del borrado:

```
  ⨯ PT-02 (M-g): si…
  -'El reporte Clover no se pudo borrar: /proc/version
  +Warning: unlink(/proc/version): Operation not permitted in /var/www/html/bin/verificar-cobertura.php on line 25
  +El reporte Clover no se puede leer: /proc/version
  Tests:    1 failed, 11 passed (28 assertions)
```

Después del cambio: `Tests: 12 passed (28 assertions)`.

## Mutación

En una copia temporal completa dentro del contenedor (`/tmp/copia-mg`, con `vendor`, borrada después), se cambiaron las líneas 27 a 30 del guion por `@unlink($reporte);`:

```
MUT sin la comprobación del unlink: 4 líneas → 1
  ⨯ PT-02 (M-g): si el clover no se puede borrar, falla con «El reporte…
  Tests:    1 failed, 11 passed (28 assertions)
```

Sin la comprobación, el código de salida sigue siendo 1, porque `/proc/version` no es XML; lo que cambia es el mensaje. Por eso la prueba compara la salida exacta.

Un primer intento de mutación no aplicó la sustitución (`0 reemplazo`, por el escapado en `php -r`) y pasó 12 de 12. Se descartó y se repitió con `sed` por número de línea.

## `make verificar`

Una corrida completa, sin nada más corriendo: en los contenedores, solo los procesos permanentes (`tail -f /dev/null`, `queue:work`, `schedule:work` y PostgreSQL); en el anfitrión, ningún `php` ni `composer`.

- Pint: `PASS ... 409 files`.
- PHPStan nivel 7: `[OK] No errors`.
- Infection: 362 eliminados; MSI 100 %; MSI cubierto 100 %; `Mutación completa: 362 mutantes evaluados, ninguno omitido ni con tiempo agotado`.
- Pest: **918 pruebas** (5 560 aserciones), `Duration: 422.18s`, sin fallos.
- Cobertura: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,7 %.
- `composer audit`: sin avisos.
- **Total: 549 s (9 min 9 s), código 0.** Pasa.

## Desviaciones

- Ninguna. `bin/verificar-cobertura.php` está fuera de `tests/**`; el FIX lo pidió el responsable y se anota en `f1-h2-reporte.md` §4.
