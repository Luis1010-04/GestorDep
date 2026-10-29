# Reporte F1-H2-FIX: M-c de la auditoría corta (`DELETE` de catálogos con id no v7 en el recorrido de seguridad)

**Commit:** `e02a40b` (`F1-H2-FIX: M-c DELETE de catálogos con id no v7 en el recorrido de seguridad`); este reporte va en el commit F1-DOC de la auditoría corta. **Origen:** auditoría corta del rango `1145c77..4fb2fc5` (29/10/2026), M-c, que completa M-3 de la auditoría del Lote E. **Solo pruebas:** no cambia código de producción.

## Hallazgo

M-3 del Lote E señalaba que el `DELETE` de catálogos con un id que no es v7 solo estaba en `PeriodosEscolaresTest.php:190` y `AsignaturasTest.php:125`, fuera del recorrido de seguridad. El FIX `6804a9f` no lo trató.

## Cambio

En `tests/Feature/Seguridad/AcademicoSeguridadTest.php`, una prueba con un conjunto de datos de dos casos (`periodos-escolares` y `asignaturas`):

- **`RNF-01 (§0.5.4): DELETE de un catálogo con un id inexistente o que no es v7 recibe un 404 byte a byte idéntico y no borra nada`.** Con la sesión de D2, sobre el mundo de §7.0, intenta `DELETE` con un UUID v7 inexistente y con el UUID v4 del recorrido (`UUID_V4_ACADEMICO`). Exige:
  - el 404 byte a byte igual al de referencia del recorrido (`GET /materias/{inexistente}`);
  - que el número de filas sin eliminar del catálogo no cambie.

Las pruebas existentes no se tocaron.

## Evidencia

Con el código actual: `Tests: 2 passed (15 assertions)`. Es una prueba de regresión de un comportamiento correcto, así que lo que demuestra que puede fallar son las mutaciones. Se hicieron en una copia temporal completa dentro del contenedor (`/tmp/copia-mc`, con `vendor`, borrada después), una a una, restaurando el controlador entre una y otra:

```
MUT (a) PeriodoEscolarController sin IdentificadorDeRuta (Identificador::desdeTexto directo): 1 línea
  ⨯ RNF-01 (§0.5.4): DELETE de un catálogo con un id inexistente o que…
  Failed asserting that 500 is identical to 404.
  Tests:    1 failed, 1 passed (11 assertions)
MUT (b) PeriodoEscolarController ignora el resultado del borrado: 1 línea
  Failed asserting that 204 is identical to 404.
  Tests:    1 failed, 1 passed (11 assertions)
MUT (a) AsignaturaController sin IdentificadorDeRuta: 1 línea
  Failed asserting that 500 is identical to 404.
  Tests:    1 failed, 1 passed (11 assertions)
MUT (b) AsignaturaController ignora el resultado del borrado: 1 línea
  Failed asserting that 204 is identical to 404.
  Tests:    1 failed, 1 passed (11 assertions)
```

## `make verificar`

Una corrida completa, sin nada más corriendo: en los contenedores, solo los procesos permanentes; en el anfitrión, ningún `php` ni `composer`.

- Pint: `PASS ... 409 files`.
- PHPStan nivel 7: `[OK] No errors`.
- Infection: 362 eliminados; MSI 100 %; MSI cubierto 100 %; `Mutación completa: 362 mutantes evaluados, ninguno omitido ni con tiempo agotado`.
- Pest: **920 pruebas** (5 574 aserciones), `Duration: 357.63s`, sin fallos.
- Cobertura: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,7 %.
- `composer audit`: sin avisos.
- **Total: 467 s (7 min 47 s), código 0.** Pasa.

## Desviaciones

- Ninguna.
