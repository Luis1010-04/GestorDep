# Reporte F1-H2-FIX: M-10 (resumen de mutación vigente, Errored/Ignored y composer.lock)

**Commit:** `F1-H2-FIX: M-10 pruebas de la configuracion de mutacion` (ver `git log`). **Plan:** `f1-h2-plan-fix-deuda-c2.md`. **Solo pruebas:** no cambia `bin/verificar-mutacion.php`, ni el script `verificar`, ni `infection.json5`, ni ningún umbral.

## Hallazgo

- (a) `bin/verificar-mutacion.php` no comprueba que `build/infection/resumen.log` sea de la corrida actual: si Infection dejara de escribirlo, un resumen viejo limpio pasaría.
- (b) `Errored` e `Ignored` no hacen fallar la verificación (criterio de Infection, aceptable; conviene documentarlo).
- (c) `composer.lock` cambió en T01 y T02 sin estar listado.

## Cambio

Tres pruebas nuevas en `tests/Unit/Herramientas/VerificarMutacionTest.php`:

- **(a) `HERRAMIENTAS mutación (M-10 a)`.** Exige que `infection.json5` escriba `logs.summary` en `build/infection/resumen.log` y que, en el script `verificar` de `composer.json`, el paso **inmediatamente posterior** a Infection sea `@php bin/verificar-mutacion.php build/infection/resumen.log`. Mientras eso se cumpla, cada corrida de Infection sobrescribe el resumen que lee la verificación, y un resumen viejo no puede leerse. Si alguien quita el resumen o cambia la ruta, la prueba falla.
  - Se descartaron dos alternativas porque cambiaban el comportamiento del script o de la herramienta, y la autorización era solo para cambios sin efecto de comportamiento: borrar el resumen antes de Infection y comparar fechas.
- **(b) dos pruebas `M-10 b`.**
  - Documentan el criterio vigente: un resumen con `Errored: 3` o con `Ignored: 4` sale con código 0 (Infection cuenta los errores como eliminados).
  - La segunda exige además que `infection.json5` no tenga `mutators` ni ningún `ignore`. Así `Ignored` solo puede dejar de ser 0 con un cambio visible de configuración, que esta prueba detiene.
- **(c)** Solo documentación: `composer.lock` cambió en T01 (por `require-dev` de Infection) y en T02 (por `ext-bcmath`), y también en el FIX de dependencias `9d931b6`. Es consecuencia inevitable de cambios permitidos de `composer.json`. Se incluirá en la lista de rutas fuera de la especificación del reporte de cierre de H2 (T15).

## Evidencia de que las pruebas nuevas pueden fallar

En copias temporales:

```
MUT (a1) sin logs.summary en infection.json5
  ⨯ HERRAMIENTAS mutación (M-10 a): Infection escribe el resumen en la…
  Tests:    1 failed, 2 passed
MUT (a2) el script lee otra ruta
  ⨯ HERRAMIENTAS mutación (M-10 a): Infection escribe el resumen en la…
  Tests:    1 failed, 2 passed
MUT (b) un mutador ignorado (JSON válido)
  ✓ HERRAMIENTAS mutación (M-10 a) …
  ✓ HERRAMIENTAS mutación (M-10 b): los mutantes con error …
  ⨯ HERRAMIENTAS mutación (M-10 b): los ignorados no hacen fallar, y la…
  Tests:    1 failed, 2 passed
```

Un primer intento de la mutación (b) produjo JSON inválido e hizo fallar también la prueba (a) por una razón ajena. Se repitió con JSON válido, verificado con `json_decode(..., JSON_THROW_ON_ERROR)`, y falla solo la prueba que corresponde.

## `make verificar`

Una corrida completa, sin nada más corriendo:

- Pint: `PASS ... 406 files`.
- PHPStan nivel 7: sin errores.
- Infection: 362 eliminados; MSI 100 %; MSI cubierto 100 %; ninguno omitido.
- Pest: **892 pruebas** (5372 aserciones), `Duration: 378.08s`.
- Cobertura: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,7 %.
- Auditoría: sin avisos.
- **Total: 488 s (8 min 8 s).** Pasa.

## Desviaciones

- Ninguna.
