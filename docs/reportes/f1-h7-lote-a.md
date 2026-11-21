# Resumen del lote A de H7 (T01 a T04)

Encargo 49, autorizado por Luis el 21/11/2026 a las 09:34. Cierra el lote A, como pide `docs/hitos/f1-h7.md` §0. Base: `main` en `f257f59`.

Las horas están en hora de Ciudad de México (UTC−6). Los datos de PR, puntas, fusiones y CI se verificaron el 21/11/2026 con `gh pr view` y `gh run list`. Las auditorías se citan como las pasó Luis en el encargo 49. El estado de cada hallazgo se comprobó en el repositorio.

## 1. Estado final

| Tarea | Estado | PR | Punta al fusionar | Fusión | Fecha de fusión | CI de `main` |
|---|---|---|---|---|---|---|
| T01: parámetro de conservación del historial (AB-03) | Fusionada | [#25](https://github.com/Luis1010-04/GestorDep/pull/25) | `c3f3870` | `9a5fe19` | 19/11/2026 22:16 | [verde](https://github.com/Luis1010-04/GestorDep/actions/runs/44673302010) |
| T02: historial de calificaciones y asistencia a clase (HU-26) | Fusionada | [#27](https://github.com/Luis1010-04/GestorDep/pull/27) | `6d23f5d` | `f254728` | 20/11/2026 10:37 | [verde](https://github.com/Luis1010-04/GestorDep/actions/runs/44775218123) |
| T03: historial de avisos, lesiones y estados (HU-26, RN-15) | Fusionada | [#29](https://github.com/Luis1010-04/GestorDep/pull/29) | `29c26bc` | `8e87e5f` | 20/11/2026 15:06 | [verde](https://github.com/Luis1010-04/GestorDep/actions/runs/44817351434) |
| T04: historial de mediciones y pruebas físicas (HU-26, RN-14) | Fusionada | [#30](https://github.com/Luis1010-04/GestorDep/pull/30) | `b4d89ab` | `c98da6d` | 20/11/2026 15:52 | [verde](https://github.com/Luis1010-04/GestorDep/actions/runs/44824157295) |

Las cuatro tareas del lote están en `main`. Las siete rutas de historial de §6.1 existen, y cada una está documentada en `docs/api/openapi.yaml`.

## 2. Fusiones del lote (#24 a #31)

Los ocho PR están fusionados. No hay ninguno abierto ni cerrado sin fusionar.

| PR | Contenido | Rama | Punta | Fusión | Fecha de fusión | CI de `main` |
|---|---|---|---|---|---|---|
| [#24](https://github.com/Luis1010-04/GestorDep/pull/24) | Especificación v0.3 | `docs/f1-h7-especificacion` | `3bfb169` | `11790a0` | 19/11/2026 16:14 | [verde](https://github.com/Luis1010-04/GestorDep/actions/runs/44626712621) |
| [#25](https://github.com/Luis1010-04/GestorDep/pull/25) | T01 | `tecnico/f1-h7-t01` | `c3f3870` | `9a5fe19` | 19/11/2026 22:16 | [verde](https://github.com/Luis1010-04/GestorDep/actions/runs/44673302010) |
| [#26](https://github.com/Luis1010-04/GestorDep/pull/26) | Especificación v0.4: fila 11 de §7.1 y DA-124 | `docs/f1-h7-fila-11` | `051ccdf` | `6a117a1` | 19/11/2026 23:22 | [verde](https://github.com/Luis1010-04/GestorDep/actions/runs/44680647706) |
| [#27](https://github.com/Luis1010-04/GestorDep/pull/27) | T02 | `tecnico/f1-h7-t02` | `6d23f5d` | `f254728` | 20/11/2026 10:37 | [verde](https://github.com/Luis1010-04/GestorDep/actions/runs/44775218123) |
| [#28](https://github.com/Luis1010-04/GestorDep/pull/28) | Especificación v0.5 (DA-18, DA-125 y UTC) y resumen de la noche | `docs/f1-h7-v05-da18` | `16a0492` | `da40969` | 20/11/2026 09:58 | [verde](https://github.com/Luis1010-04/GestorDep/actions/runs/44768690633) |
| [#29](https://github.com/Luis1010-04/GestorDep/pull/29) | T03 | `tecnico/f1-h7-t03` | `29c26bc` | `8e87e5f` | 20/11/2026 15:06 | [verde](https://github.com/Luis1010-04/GestorDep/actions/runs/44817351434) |
| [#30](https://github.com/Luis1010-04/GestorDep/pull/30) | T04 | `tecnico/f1-h7-t04` | `b4d89ab` | `c98da6d` | 20/11/2026 15:52 | [verde](https://github.com/Luis1010-04/GestorDep/actions/runs/44824157295) |
| [#31](https://github.com/Luis1010-04/GestorDep/pull/31) | Nota del riesgo 3 de A3 en el reporte de T04 | `tecnico/f1-h7-t04-riesgo3` | `3bbe9eb` | `f257f59` | 20/11/2026 16:14 | [verde](https://github.com/Luis1010-04/GestorDep/actions/runs/44827289274) |

- **#28 se fusionó antes que #27.** Por eso T02 entró a `main` después de la v0.5, aunque su reporte está escrito sobre la v0.4 (hallazgo 6 de la auditoría de T02).
- La versión de la especificación en cada fusión se comprobó en §13 de `docs/hitos/f1-h7.md`: 0.3 en `11790a0`, 0.4 en `6a117a1` y 0.5 en `da40969`.

## 3. Auditorías

### T01

No hay auditoría independiente registrada en el repositorio.

### T02 (PR #27): sesión independiente

Recomendó fusionar.

| # | Hallazgo | Estado |
|---|---|---|
| 1 | Sin prueba de `autor: null` sin sesión (DD-35) | **Corregido** en `6d23f5d` |
| 2 | Sin prueba de RN-17 (jugador en baja) | **Corregido** en `6d23f5d` |
| 3 | Paginación parcial: faltan `por_pagina=101`, el valor por defecto y el desempate por `id` | **Pendiente**, para T05b |
| 4 | El esquema `EntradaDeHistorial` solo sirve a H2 | **Resuelto**: T03 y T04 tienen su propio esquema |
| 5 | Alias en la rama del docente de `AlcanceDeJugadores` | **Pendiente**, para T05b |
| 6 | El reporte de T02 estaba escrito sobre la v0.4 | **No corregido** (ver abajo) |

Lo que se comprobó del hallazgo 6:
- `docs/reportes/f1-h7-t02.md` sigue citando la v0.4 en la línea 3 y en la línea 83 («Ninguna con la especificación v0.4»).
- El único commit que lo toca es `faa3449`. `6d23f5d` solo cambió la prueba y agregó el log `f1-h7-t02-verificar-2.log`.
- Por eso el reporte tampoco menciona las pruebas de los hallazgos 1 y 2.

### T03 (PR #29): auditoría A2, sesión independiente

Recomendó fusionar.

| # | Hallazgo | Estado |
|---|---|---|
| 1 | 404 antes de 422 sin prueba | **Corregido** |
| 2 | Filtro `comoEntrenador` sin prueba | **Corregido** |
| 3 | Omisión de `lesion_id` nulo sin prueba | **Corregido** |

- Las correcciones se hicieron en `d3f3c18`, ese commit se enmendó a `af234a7` y se subió como `29c26bc`, la punta fusionada.
- La omisión de `lesion_id` nulo la ratificó Luis el 20/11/2026 a las 14:58. Está registrada en `docs/reportes/f1-h7-t03.md` (línea 107).

### T04 (PR #30): auditoría A3, sesión independiente

Recomendó fusionar. Solo encontró hallazgos de severidad baja.

| # | Hallazgo | Estado |
|---|---|---|
| 1 | El esquema `EntradaDeHistorialDeCondicionFisica` de OpenAPI acepta cualquier objeto en `datos_anteriores` y `datos_nuevos`. El contrato no impide que vuelva a salir `registrado_por`, y solo lo detectan las pruebas «HU-26 c1 (DA-18)» | **Pendiente** |
| 2 | No hay prueba del borrado lógico en el historial de T04, y la especificación se contradice sobre c2: §2.1 se lo asigna a T04, y §8 y §11 solo a T02 | **Pendiente** de decisión documental |
| Riesgo 3 | RN-14 en el historial depende de que `registrado_por` no cambie. Lo protegen el código del dominio y de la aplicación, no una restricción de la base de datos | **Registrado, pendiente de decisión de Luis** (PR #31, `docs/reportes/f1-h7-t04.md`, «Riesgos registrados») |

## 4. Decisiones de Luis que siguen abiertas

1. **Riesgo 3 de A3:** `registrado_por` está protegido solo por código.
2. **Contradicción sobre c2 en T04:** §2.1 asigna HU-26 c1 a c3 a T04. §8 (pruebas de T04) y §11 asignan c2 solo a T02.
3. **RN-13 y RN-23 en T05:** pendientes de decisión.
   - §2.1 asigna RN-13, RN-14 y RN-15 a T05, pero ni §8 ni §11 nombran pruebas para ellas, y RN-13 no está en §4.2.
   - §11 no menciona RN-23, aunque §8 sí le nombra una prueba.
   - El encargo 47 indica que no se les escriba prueba hasta que se decida.
4. **Hallazgo 5 y paginación de T02:** van a una tarea correctiva aparte, T05b, todavía no autorizada.
5. **Trailers de los commits:**
   - El commit `3bbe9eb` (PR #31) dice `Co-Authored-By: Claude Opus 5.5`, el modelo que lo hizo, y no lleva `Claude-Session`. La diferencia con la convención se anotó en el cuerpo del PR #31.
   - Falta decidir la convención: qué modelo se nombra y cuándo se incluye `Claude-Session` si no se puede verificar.

## 5. Otros pendientes

- **AB-03 (6 meses tras la baja):** sigue siendo un dato provisional, pendiente de la institución. Ningún proceso lee `conservacion_historial` (D-4).
- **Hallazgo 6 de la auditoría de T02:** el reporte de T02 sigue escrito sobre la v0.4 y no recoge las pruebas de `6d23f5d`. Corregirlo requiere su propio encargo.
- **`docs/reportes/f1-h7-decisiones-del-agente.md` no existe.** §0 indica que ahí van las decisiones nuevas del agente.

## 6. Siguiente lote

El lote B empieza con T05. Su plan está aprobado (encargo 47), con estas condiciones:
- sin el hallazgo 5 ni la paginación de T02;
- RN-13 y RN-23 pendientes de decisión;
- mutaciones solo en copias de `/tmp/mut`.

T05 quedó detenida en su paso 1 porque faltaba este resumen.
