# Reporte F1-H2-FIX: M-1 de la auditoría del Lote E (prueba de la configuración de PHPStan)

**Commit:** `ad8738b` (`F1-H2-FIX: E M-1 prueba de la configuracion de PHPStan`); este reporte va aparte, en un commit F1-DOC. **Origen:** M-1 de la auditoría del Lote E y el plan del punto 3 de `f1-h2-progreso.md`. **Solo pruebas:** no cambia `phpstan.neon` ni código de producción.

## Hallazgo

DD-53 (PHPStan en nivel 7, «nunca se baja», sin línea base ni `ignoreErrors`) dependía solo de la corrida de PHPStan dentro de `make verificar`. Nada impedía debilitar la configuración y que el análisis siguiera pasando: bajar el nivel, ignorar errores, añadir una línea base o sacar carpetas del análisis.

## Cambio

Nuevo `tests/Unit/Herramientas/ConfiguracionDePhpstanTest.php`, con tres pruebas que leen `phpstan.neon` como texto. El proyecto no instala un analizador de NEON y la configuración es corta. Antes de leer, se quitan los comentarios `#` y las líneas en blanco, para que un comentario no oculte ni simule una opción.

- `RNF-11 (M-1): PHPStan analiza en nivel 7 o más`.
- `RNF-11 (M-1): PHPStan no ignora errores, no excluye rutas ni usa línea base`: comprueba que no haya
  - `ignoreErrors`;
  - `excludePaths`;
  - `reportUnmatchedIgnoredErrors: false`;
  - ninguna mención a `baseline`;
  - ningún archivo `phpstan-baseline*`.
- `RNF-11 (M-1): PHPStan solo incluye configuración de vendor/ y analiza app, bootstrap, routes, src y tests`: `includes` no vacío, todo bajo `vendor/`, y `paths` exactamente igual a la lista vigente.

Están en `tests/Unit`, así que también corren dentro de la suite que usa Infection; no cubren `Dominio/` y no cambian el número de mutantes. Antes de `make verificar`, PHPStan señaló un acceso posiblemente indefinido (`$nivel[1]`) en la propia prueba, que se corrigió.

## Evidencia de que pueden fallar

Con la configuración actual: `Tests: 3 passed (11 assertions)`. En una copia temporal completa (borrada después), cada mutación por separado, restaurando `phpstan.neon` entre una y otra:

```
MUT nivel 6
  ⨯ RNF-11 (M-1): PHPStan analiza en nivel 7 o más
  Tests:    1 failed, 2 passed (11 assertions)
MUT ignoreErrors
  ⨯ RNF-11 (M-1): PHPStan no ignora errores, no excluye rutas ni usa lí…
  Tests:    1 failed, 2 passed (7 assertions)
MUT includes con phpstan-baseline.neon
  ⨯ RNF-11 (M-1): PHPStan no ignora errores, no excluye rutas ni usa lí…
  ⨯ RNF-11 (M-1): PHPStan solo incluye configuración de vendor/ y analiza app, bootstrap, routes, src y tests
  Tests:    2 failed, 1 passed (9 assertions)
MUT archivo phpstan-baseline.neon presente
  ⨯ RNF-11 (M-1): PHPStan no ignora errores, no excluye rutas ni usa lí…
  Tests:    1 failed, 2 passed (11 assertions)
MUT paths sin tests
  ⨯ RNF-11 (M-1): PHPStan solo incluye configuración de vendor/ y anali…
  Tests:    1 failed, 2 passed (11 assertions)
MUT excludePaths
  ⨯ RNF-11 (M-1): PHPStan no ignora errores, no excluye rutas ni usa lí…
  Tests:    1 failed, 2 passed (8 assertions)
```

## `make verificar`

Una corrida completa, sin nada más corriendo. Antes de lanzarla: ningún proceso de prueba vivo en los contenedores ni en el anfitrión, y `docker stats` por debajo del 1 %.

- Pint: `PASS ... 409 files`.
- PHPStan nivel 7: sin errores.
- Infection: 362 eliminados; MSI 100 %; MSI cubierto 100 %; ninguno omitido.
- Pest: **912 pruebas** (5546 aserciones), `Duration: 370.17s`, sin fallos.
- Cobertura: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,7 %.
- `composer audit`: sin avisos.
- **Total: 473 s (7 min 53 s).** Pasa.

## Desviaciones

- Ninguna.
