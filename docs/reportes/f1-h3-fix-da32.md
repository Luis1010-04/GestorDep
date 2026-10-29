# Reporte del FIX de DA-32 (alineación con jugador fuera de alcance) y revisión de DA-31

**Commit:** `478330d` (`F1-H3-FIX: alineación con jugador fuera de alcance responde 404 (DA-32)`). **Decisión:** DA-34 en `f1-h3-decisiones-del-agente.md`, indicada por el responsable el 29/10/2026.

## Qué cambia

`PUT /partidos/{partido}/alineacion`:

- Un jugador **fuera del alcance de entrenador sin bajas** responde 404 `recurso_no_encontrado`, con el mismo cuerpo que un jugador inexistente. Son fuera del alcance:
  - el inexistente;
  - el de otro equipo, por ejemplo J4 para E1;
  - el que está en baja, aunque haya estado convocado;
  - el que tiene un UUID que no es v7.
- **422 `jugador_no_convocado`** queda solo para un jugador visible que no está convocado. Por ejemplo, un jugador del equipo cuya convocatoria se quitó, o J4 para E3, que entrena A y B.
- El orden no cambia: el 409 `partido_no_programado` sigue antes que la comprobación por jugador.

## Archivos

- `src/Competencia/Aplicacion/VisibilidadDeJugador.php`: `esVisibleParaEntrenador(jugador, incluirBajas = true)`.
- `src/Competencia/Infraestructura/VisibilidadDeJugadorSql.php`: pasa `incluirBajas` a `AlcanceDeJugadores`.
- `src/Competencia/Aplicacion/DefinirAlineacion.php`: si el jugador no está convocado, consulta la visibilidad sin bajas y devuelve 404 o 422.
- `docs/api/openapi.yaml`: descripciones del 404 y del 422 de la alineación.
- `tests/Feature/Competencia/AlineacionYResultadoTest.php`: la prueba de DA-32 se reemplazó por tres.
- `tests/Integration/Competencia/AlineacionClaveForaneaTest.php` (nuevo): revisión de DA-31.

## Pruebas

- `HU-13 (DA-34)`: un jugador visible cuya convocatoria se quitó da 422 `jugador_no_convocado` en `alineacion.0.jugador_id`.
- `RNF-01 (DA-34)`: J5 (en baja y convocado), J4 (de otro equipo) y un UUID v4 dan 404 byte a byte igual al de un jugador inexistente, sin cambios ni historial.
- `HU-13 (DA-34)`: para E3, J4 es visible y no está convocado, así que da 422.
- `HU-13 (DA-31)`: una posición que no está en el catálogo se convierte en `PosicionInexistente`, sin cambios.
- `HU-13 (DA-31)`: otra clave foránea inválida (`fk_convocatorias_actualizado_por`, con un autor inexistente) llega como `QueryException` 23503 y **no** como `PosicionInexistente`.

**Falla previa por la razón correcta:**

```
FAILED  Tests\Feature\Competencia\AlineacionYResultadoTest > RNF-01 (DA-3…
Expected response status code [404] but received 422.
Failed asserting that 422 is identical to 404.
Tests:    1 failed, 29 passed
```

Las dos pruebas de DA-31 pasaron desde el inicio: el `catch` ya estaba limitado (ver la revisión).

**Comprobación con mutación** (sobre una copia de respaldo; el archivo se restauró y las 29 pasan):

| Mutación | Resultado |
|---|---|
| Considerar visible a cualquier jugador (`return true`) | `RNF-01 (DA-34)` falla |
| `incluirBajas: true` en la comprobación | `RNF-01 (DA-34)` falla (J5 daría 422) |

## Revisión de DA-31

El `catch` de `RepositorioDeConvocatoriasEloquent::actualizarAlineacion` ya era restringido: solo convierte el SQLSTATE `23503` cuyo mensaje contiene `fk_convocatorias_posicion_id` y vuelve a lanzar cualquier otra excepción. No hubo que cambiarlo. La prueba nueva lo fija: un autor inexistente viola `fk_convocatorias_actualizado_por`, y el repositorio no lo convierte en `posicion_inexistente`.

## Verificación

No se corrió `make verificar` en este commit: el responsable pidió una sola corrida al final (`f1-h3-fix-gate.md`), que lo incluye. Antes del commit pasaron Pint, PHPStan y las 275 pruebas de `Feature/Competencia`, `Integration/Competencia` y `Unit/Competencia`.
