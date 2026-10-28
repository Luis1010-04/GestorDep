# Reporte F1-H2-FIX: M-3 (la materia sigue mostrando el catálogo eliminado, por la API)

**Commit:** `F1-H2-FIX: M-3 la materia muestra el periodo y la asignatura eliminados por la API` (ver `git log`). **Plan:** `f1-h2-plan-fix-deuda-c2.md`. Solo pruebas: ningún cambio de código de producción.

## Hallazgo

§7.1 fila 8 y §7.2 fila 6 comprobaban «una materia que lo usa lo sigue mostrando» con un `join` en la base, no por la API (`PeriodosEscolaresTest.php:168`, `AsignaturasTest.php:113`).

## Cambio

- `tests/Feature/Academico/PeriodosEscolaresTest.php` (§7.1 fila 8): tras borrar P0, `GET /materias/{materia}` responde 200 con `periodo` = `{id: P0, nombre: "2025-2"}`, y `GET /materias?filtro[periodo_id]=P0` también lo muestra.
- `tests/Feature/Academico/AsignaturasTest.php` (§7.2 fila 6): tras borrar la asignatura, `GET /materias/{materia}` y `GET /materias` muestran `asignatura` = `{id, clave: "MAT-101", nombre: "Matemáticas"}`.
- Ambas siembran el criterio general (`CriterioGeneralSeeder`), que `GET /materias/{materia}` necesita. Se conservó la comprobación por la base: no se quitó ninguna aserción.

## Evidencia de que la prueba nueva puede fallar

En una copia temporal, la consulta de materias filtra los catálogos eliminados (`whereNull('periodos_escolares.eliminado_en')` y `whereNull('asignaturas.eliminado_en')`):

```
  ⨯ HU-28 (§7.1 fila 8): borrar P0 da 204, deja de listarse y una mater… 0.36s
  ⨯ HU-28 (§7.2 fila 6): borrar da 204, se puede crear la misma clave y… 0.82s
  Expected response status code [200] but received 404.
  Expected response status code [200] but received 404.
  Tests:    2 failed, 2 passed (26 assertions)
```

Con la versión anterior (solo el `join`), esa mutación pasaba sin ser detectada.

## `make verificar`

Una corrida completa, sin nada más corriendo:

- Pint: `PASS ... 406 files`.
- PHPStan nivel 7: sin errores.
- Infection: 362 eliminados; MSI 100 %; MSI cubierto 100 %; ninguno omitido.
- Pest: **889 pruebas** (5365 aserciones; 7 más que antes), `Duration: 419.01s`.
- Cobertura: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,7 %.
- Auditoría: sin avisos.
- **Total: 547 s (9 min 7 s).** Pasa.

## Desviaciones

- Ninguna.
