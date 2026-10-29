# Reporte F1-H2-FIX: PT-02 (`verificar-cobertura.php` no aprueba con un clover viejo)

**Commit:** `b010636` (`F1-H2-FIX: PT-02 el guion borra el clover tras leerlo`); este reporte va aparte, en un commit F1-DOC. **Origen:** PT-02 del registro de decisiones (pendiente técnico de H1) y la auditoría del Lote E. **Decisión del responsable (28/10):** opción A, sin cambios en `composer.json`.

## Problema

`bin/verificar-cobertura.php` leía `build/coverage/clover.xml` sin saber si era de la corrida actual. En H1 se vio que evaluaba un reporte viejo cuando Pest fallaba por falta de memoria: un clover antiguo que aprobaba podía dar por buena una corrida que no lo había regenerado.

## Cambio

- **`backend/bin/verificar-cobertura.php`:** después de comprobar que el reporte existe, lo lee completo en memoria y **lo borra** antes de validar su contenido (`simplexml_load_string`) y de comparar umbrales. Lo borra también si el XML no se puede leer o si una capa queda bajo su umbral. La siguiente verificación sin un clover nuevo falla con «El reporte Clover no existe: …». No cambian los umbrales, las capas, los mensajes ni `composer.json`.
- **`backend/tests/Unit/Herramientas/VerificarCoberturaTest.php`,** cuatro pruebas nuevas:
  - `PT-02: si el clover no existe, falla con «El reporte Clover no existe»`: código 1 y el mensaje exacto.
  - `PT-02: el guion borra el clover tras leerlo, apruebe o no los umbrales` (2 casos).
  - `PT-02: un clover que no se puede leer también se borra`.
  - `PT-02: una segunda verificación sin regenerar el clover falla, aunque la primera aprobara`.

## Pruebas primero

Con el guion anterior, las cuatro del borrado fallan. La del mensaje exacto ya pasaba, porque ese comportamiento existía:

```
  ✓ PT-02: si el clover no existe, falla con «El reporte Clover no exis…
  ⨯ PT-02: el guion borra el clover tras leerlo, apruebe o no los umbra… (aprueba)
  ⨯ PT-02: el guion borra el clover tras leerlo, apruebe o no los umbra… (bajo el umbral)
  ⨯ PT-02: un clover que no se puede leer también se borra
  ⨯ PT-02: una segunda verificación sin regenerar el clover falla, aunq…
  Tests:    4 failed, 7 passed (25 assertions)
```

Después del cambio: `Tests: 11 passed (26 assertions)`.

## Mutaciones

Cada una en una copia temporal completa, borrada después, restaurando el guion entre una y otra:

```
MUT (a) el guion no borra el clover: 1 líneas cambiadas
  ⨯ PT-02: el guion borra el clover tras leerlo, apruebe o no los umbra… (×2)
  ⨯ PT-02: un clover que no se puede leer también se borra
  ⨯ PT-02: una segunda verificación sin regenerar el clover falla, aunq…
  Tests:    4 failed, 7 passed (25 assertions)
MUT (b1) un reporte ausente sale con 0: 2 líneas cambiadas
  ⨯ HERRAMIENTAS cobertura: falla si el reporte no existe
  ⨯ PT-02: si el clover no existe, falla con «El reporte Clover no exis…
  ⨯ PT-02: una segunda verificación sin regenerar el clover falla, aunq…
  Tests:    3 failed, 8 passed (23 assertions)
MUT (b2) sin la comprobación de que el reporte exista: 4 líneas cambiadas
  ⨯ HERRAMIENTAS cobertura: falla si el reporte no existe
  ⨯ PT-02: si el clover no existe, falla con «El reporte Clover no exis…
  ⨯ PT-02: una segunda verificación sin regenerar el clover falla, aunq…
  Tests:    3 failed, 8 passed (26 assertions)
```

## Nadie más lee el clover

- `git grep -i clover` (sin `vendor` ni `docs`) solo encuentra el guion y el script `verificar` de `composer.json`, que genera el clover (Pest) y lo lee en el paso siguiente.
- La integración continua (`.github/workflows/verificar.yml`) termina en `composer verificar`: no sube ni lee el clover después.
- El `Makefile` solo invoca `composer verificar`.
- Ningún guion de `scripts/` lo usa.
- Tras `make verificar`, el informe HTML sigue en `build/coverage/` (`index.html` y sus carpetas) y `clover.xml` ya no está.

## `make verificar`

Una corrida completa, sin nada más corriendo. Antes de lanzarla: ningún proceso de prueba vivo en los contenedores ni en el anfitrión, y `docker stats` por debajo del 1 %.

- Pint: `PASS ... 409 files`.
- PHPStan nivel 7: sin errores.
- Infection: 362 eliminados; MSI 100 %; MSI cubierto 100 %; ninguno omitido.
- Pest: **917 pruebas** (5558 aserciones), `Duration: 378.97s`, sin fallos.
- Cobertura: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,7 %, leída del clover recién generado, que el guion borró después.
- `composer audit`: sin avisos.
- **Total: 486 s (8 min 6 s).** Pasa.

## Desviaciones

- Ninguna.
