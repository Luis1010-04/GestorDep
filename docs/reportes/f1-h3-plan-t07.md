# Plan de F1-H3-T07: alineación y resultado (HU-13)

**Rutas:** rutas base (`docs/hitos/f1-h3.md` v1.0.1 §8). El responsable también pidió actualizar `docs/trazabilidad.md` en esta tarea. **Pruebas:** §7.5 filas 4 a 8, la prueba HTTP de la fila 6 de §7.4, `jugador_no_convocado` y el alcance (404).

## Rutas

| Ruta | Qué hace |
|---|---|
| `PUT /partidos/{partido}/alineacion` | `alineacion: [{jugador_id, posicion_id, rol_alineacion}]` reemplaza la alineación completa (§3.4). Quien no está en la lista queda sin posición ni rol. Responde 200 con la convocatoria visible completa |
| `PUT /partidos/{partido}/resultado` | `goles_favor`, `goles_contra` (enteros de 0 a 99). El partido pasa a `jugado`. 200 con el partido |

**Orden de comprobación:**

- **Alineación:** 404 del partido (en `authorize()`) → 422 de forma (lista, UUID, rol, jugador repetido) → 409 `partido_no_programado` → 422 `jugador_no_convocado` en `alineacion.N.jugador_id` → 422 `posicion_inexistente`.
- **Resultado:** 404 → 422 de forma (enteros) → 409 `partido_no_programado` (§7.4 fila 6 y §7.5 fila 6) → 422 `valor_fuera_de_rango` en `goles_favor` o `goles_contra` (fila 8).

## Capas

- **Dominio:**
  - `Partido::conResultado` valida el rango de 0 a 99. Lanza `ValorFueraDeRango`, una `ReglaDeCampoViolada` que se crea en el dominio.
  - `Convocatoria::conAlineacion` para fijar o quitar posición y rol.
- **Aplicación:**
  - `DefinirAlineacion`: comprueba que cada jugador esté en la convocatoria visible y escribe solo las filas que cambian.
  - `CapturarResultado`.
  - Excepciones `JugadorNoConvocado` (campo) y `PosicionInexistente`.
- **Infraestructura:**
  - `RepositorioDeConvocatorias::actualizarAlineacion`: todo en una transacción, con historial `actualizar` por fila. Una posición inexistente la detiene la clave foránea `fk_convocatorias_posicion_id`, que se traduce a `PosicionInexistente` (DA-31).
  - El resultado usa `RepositorioDePartidos::actualizar`, ya existente.
- **HTTP:** `AlineacionController` y `ResultadoController` (o métodos en los controladores existentes), solicitudes con la pertenencia en `authorize()`, `FormatoDeError` (409 y 422 con campo), `lang/es` y `openapi.yaml`.

## Pruebas

- §7.5 filas 4 a 8 (`HU-13 (§7.5 fila N)`).
- `HU-12 (§7.4 fila 6)`: `PUT /partidos/{partido}/resultado` sobre un partido cancelado responde 409 `partido_no_programado`. Con esta prueba la fila 6 queda completa.
- Adicionales:
  - reemplazo completo (quien sale queda sin alineación);
  - alineación vacía;
  - convocado en baja como no convocado;
  - posición inexistente o con UUID v4;
  - jugador repetido;
  - un solo historial por fila que cambia;
  - goles negativos, no enteros y faltantes;
  - el resultado no se corrige (§2.3: un partido jugado da 409);
  - privacidad (404 byte a byte con cuerpos válidos e inválidos);
  - 403 y 401;
  - contrato;
  - unitarias de dominio.
- Mutaciones de privacidad.

## Decisiones

DA-30 a DA-33 en `f1-h3-decisiones-del-agente.md`.
