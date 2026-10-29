# Plan de F1-H3-T06: convocatoria (HU-12, HU-29 criterio 3)

**Rutas:** rutas base (`docs/hitos/f1-h3.md` v1.0.1 §8). **Pruebas:** §7.5 filas 1, 2, 3, 9 y 10. **Fuera de T06:** la alineación y el resultado (T07), la vista consolidada (H6) y la advertencia por lesionado (H5).

## Rutas

| Ruta | Qué hace |
|---|---|
| `GET /partidos/{partido}/convocatoria` | Convocados no eliminados del partido, restringidos por `AlcanceDeJugadores` (`incluirBajas` falso, solo entrenador): un jugador en baja no aparece (HU-29 criterio 3, fila 9). Paginado, en orden de convocatoria |
| `POST /partidos/{partido}/convocatoria` | `jugador_id`. 201 |
| `DELETE /convocatorias/{convocatoria}` | Borrado lógico con historial (`eliminar`); la fila conserva su posición y su rol, pero deja de contar para la alineación (fila 10). 204 |

Orden del alta: 404 del partido (en `authorize()`) → 422 de forma → 409 `partido_no_programado` → 404 del jugador fuera del alcance o del equipo (fila 3) → 409 `jugador_ya_convocado` en `jugador_id` (fila 2). El borrado: 404 de la convocatoria (en `authorize()`) → 409 `partido_no_programado`.

**«Filtros de la convocatoria».** §6.1 no da parámetros de filtro para `GET /partidos/{partido}/convocatoria`, y agregar uno sería un campo que la especificación no nombra. Entiendo «filtros» como los que decide qué convocatorias aparecen: las eliminadas no salen, las de un jugador en baja no salen y el alcance del entrenador. Cada uno lleva su prueba.

## Capas

- **Dominio:** `Convocatoria` (con `posicion_id` y `rol_alineacion` nulos hasta T07), `RolDeAlineacion`, `RepositorioDeConvocatorias`. `Partido::exigirProgramado()` pasa a ser público.
- **Aplicación:**
  - `ConvocarJugador`, `QuitarDeLaConvocatoria`, `ListarConvocatoria`, `BuscarConvocatoriaDelEntrenador`;
  - `JugadorYaConvocado` (409 con campo);
  - el puerto `ConsultaDeConvocatoria`, que hace el listado con el alcance.
- **Infraestructura:**
  - `RepositorioDeConvocatoriasEloquent`, con historial, y la UQ parcial que convierte una carrera en 409;
  - `ConsultaDeConvocatoriaSql`, que pasa por `AlcanceDeJugadores` sobre `convocatorias.jugador_id`.
- **HTTP:** `ConvocatoriaController`, `SolicitudDeConvocatoria` (pertenencia en `authorize()`), `ConvocatoriaRecurso`, `FormatoDeError`, `lang/es` y `openapi.yaml`.

## Pruebas

- §7.5 filas 1, 2, 3, 9 y 10 (`HU-12 (§7.5 fila N)`; la 9, también `HU-29`).
- Filtros: convocatoria eliminada, jugador en baja, y E3 ve la convocatoria de A.
- Privacidad, con 404 byte a byte: E2 convoca en un partido de A (§7.6 fila 3), consulta la lista o quita un convocado; un jugador de otro equipo; un UUID v4.
- Partido cancelado o jugado: 409 al convocar y al quitar.
- Historial de alta y de borrado; contrato.
- La prueba que faltaba de T05: un `filtro[temporada_id]` con un UUID válido que no es v7 da una lista vacía (DA-26).
- Unitarias de `Convocatoria` (Infection).
- Mutaciones de privacidad, como en T04 y T05.

## Decisiones

DA-28 y DA-29 en `f1-h3-decisiones-del-agente.md`.
