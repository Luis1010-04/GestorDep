# Reporte de H6: Alertas y consultas

**Fecha:** 17 de noviembre de 2026 · **Especificación:** `docs/hitos/f1-h6.md` v1.15 · **Rama:** `hito/f1-h6-especificacion` (local; sin push)
**Estado: H6 NO está cerrado.** Quedan pendientes la decisión de P-16, la auditoría independiente de sistema (punto 10 de §10) y la etiqueta con la fusión (punto 11). Este reporte registra el estado de cada punto de la compuerta; no la declara cumplida.

## 1. Qué entrega H6

Por la API: las alertas abiertas de un jugador con su causa (`GET /jugadores/{jugador}/alertas`, HU-22); el listado de estatus con filtros (`GET /estatus-de-jugadores`, HU-23); la vista para convocar y la lista de convocables ordenada por desempeño (`GET /jugadores/{jugador}/vista-para-convocar` y `GET /equipos/{equipo}/jugadores/para-convocar`, HU-24 y RN-18); la vista consolidada de la convocatoria (`GET /partidos/{partido}/convocatoria/vista`, HU-12 c2); y las series de los gráficos (`GET /jugadores/{jugador}/graficos` y `GET /equipos/{equipo}/graficos`, HU-25). Más la tabla `alertas`, sus tres reglas de activación y cierre (RN-21, RN-09, RN-10), el comando `alertas:recalcular` (a las 02:00, America/Mexico_City) y el escucha de la alerta académica.

## 2. Compuerta de §10, punto por punto

| # | Punto | Estado | Evidencia |
|---|---|---|---|
| 1 | `make verificar` pasa en local y en la integración continua; `make mutacion` pasa al cerrar, con log | **Local: cumplido. Integración continua: sin comprobar** (no hay push) | `make verificar` sobre `4112157`: exit 0, 2276 pruebas, 13791 aserciones, PHPStan nivel 8, cobertura 99.6 % (Dominio 100.0, Aplicación 100.0, Infraestructura y HTTP 99.5). `make mutacion`: exit 0, 999 de 999 mutantes matados, MSI 100 %, con el árbol de `40180f2` (§5) |
| 2 | Cada historia de §2.1 tiene pruebas con su identificador; los diferidos de §2.2 anotados en `docs/trazabilidad.md` | **Cumplido** | `1b03ba7`: filas de HU-22 a HU-25, HU-12 c2, HU-29 c3 y las reglas; «Estado al cierre de H6» anota HU-26 (H7) y las alertas y notificaciones de Fase 2 |
| 3 | `openapi.yaml` incluye las rutas de §6.1 y las respuestas reales coinciden | **Cumplido** | Las siete rutas de §6.1 están en `docs/api/openapi.yaml` y cada una tiene una prueba de contrato con `ValidatorBuilder`: `ContratoDeEstatusTest` (alertas y estatus), `ContratoDeGraficosTest` (jugador y equipo), `ContratoDeVistaParaConvocarTest` (vista y convocables) y `ContratoDeVistaDeLaConvocatoriaTest` |
| 4 | `make fresh` y `migrate:rollback` funcionan sin errores | **Cumplido** | `make fresh` exit 0; `migrate:rollback` exit 0 (76 migraciones, `Ran=76` a `Ran=0`); `make migrar` exit 0 (`Ran=76 Pending=0`). Logs en `docs/reportes/logs/humo/` |
| 5 | Las tablas de §7 pasan, una prueba por fila | **Cumplido** (cruce hecho en T09, por identificador) | §7.1: `ReglasDeAlertaTest` y `RecalculoDeAlertaAcademicaTest`, filas 1 a 5. §7.2: ya la cubren las pruebas de H4 (T02 de §8). §7.3: `AcumulacionDeSancionesTest`, filas 1 a 9. §7.4: `ReglasDeAlertaTest` y `RiesgoDeBajaDeportivaTest`, filas 1 a 5. §7.5: `CalculadorDeDesempenoTest` y `OrdenPorDesempenoTest`, filas 1 a 3. §7.6: filas 1 a 5, 7 y 8 en `AlertasDelJugadorTest`, fila 6 por DA-103 en `ReglasDeAlertaTest` y `EntrenadoresDelEquipoTest`, fila 9 en `EstatusDeJugadoresTest`. §7.7: filas 1 a 4 en `VistaParaConvocarTest` y `MarcaDeDesactualizacionTest`, fila 5 en `GraficosDelJugadorTest` |
| 6 | `make humo` (H0 a H6) termina sin fallas | **Cumplido** (a la espera de que el responsable lo pruebe, punto 11) | `make humo` exit 0 (13:32:08 a 13:33:31), con `HUMO H6 OK: 18 pasos`. Hubo un intento previo fallido en el paso 3, por un defecto del guion corregido en `580a5cd` (`f1-h6-t09.md` §4) |
| 7 | Criterios propios de H6 | **Cumplido salvo un criterio sin prueba: P-16** | Tablas de RN-21, RN-09, RN-10 y RN-18 pasan (punto 5); `alertas:recalcular` está programado a las 02:00 (DA-113; `routes/console.php`) y es idempotente (§7.1 fila 2, el comando no duplica; humo paso 15); cierra las que vencen con el tiempo (§7.3, filas 8 y 9); el listado filtra por equipo, materia y tipo (`EstatusDeJugadoresTest`, HU-23 c2); la vista trae las cinco dimensiones, la disponibilidad y las fechas (`VistaParaConvocarTest`; humo paso 10). **Sin prueba:** que una alerta deportiva o de sanciones abierta se cierre cuando el jugador deja el equipo (P-16, abierto) |
| 8 | Este reporte incluye lo que pide §10 de H5, con los tiempos | **Cumplido con este documento** | §3 (por tarea), §4 (rutas fuera de lo permitido), §5 (tiempos) y §6 (decisiones). El detalle de cada tarea, en `docs/reportes/f1-h6-tNN.md` |
| 9 | DA-87 a DA-100 registradas; AB-13 y AB-07 abiertas sin bloquear | **Cumplido, con una condición** | `42ac6a3`: las catorce están en `docs/decisiones/registro-decisiones.md` §10. DA-87 y DA-89 a DA-99 quedan «pendientes de validación» hasta que el responsable confirme su aprobación; DA-88 y DA-100 ya tenían su estado. AB-13 y AB-07 siguen «por validar» y no bloquean |
| 10 | Auditoría independiente de sistema entregada, sin bloqueantes abiertos | **PENDIENTE** | Aún no se ha pedido; espera la decisión de P-16 |
| 11 | El responsable revisa, prueba `make humo` y aprueba; etiqueta `f1-h6` y fusión | **PENDIENTE** | Las ordena el responsable |

## 3. Por tarea

| Tarea | Commits (primero a último) | Reporte |
|---|---|---|
| T00 Pest en paralelo (DA-101) | `2075659` | `f1-h6-plan-da101.md` (no hay `f1-h6-t00.md`) |
| T01 Tabla `alertas` y parámetros | `8fbd178` | `f1-h6-t01.md` |
| T02 Reglas de alerta en el Dominio | `9b31941` a `cf1e267` (2) | `f1-h6-t02.md` |
| T03 Recálculo de alertas | `93df2b3` a `468e68d` (14) | `f1-h6-t03.md`, `f1-h6-plan-t03.md` |
| T04 Alertas del jugador y estatus | `75fe470` | `f1-h6-t04.md` |
| T05 Vista para convocar y orden | `4677503` a `d956050` (5) | `f1-h6-t05.md`, `f1-h6-plan-t05.md` |
| T06 Vista de la convocatoria | `a2b1db1` a `d1f4772` (6) | `f1-h6-t06.md` |
| T07 Gráficos | `4339783` | `f1-h6-t07.md` |
| T08 Seguridad y baja (aprobada en `6951e33`) | `bb91031`, `8726374`, `6951e33` | `f1-h6-t08.md` |
| T09 Humo, trazabilidad y cierre documental | `1b03ba7` a `a3a8956` (7), más el de estos reportes | `f1-h6-t09.md` |

Las cifras de cada tarea (pruebas, `make verificar`, lo no hecho, fuera de alcance) están en su reporte; no las repito aquí. La suite pasó de 2271 pruebas al cierre de T08 a **2276** (13769 a 13791 aserciones) con las cinco de T09.

## 4. Rutas tocadas fuera de lo permitido

**Verificación hecha el 17/11/2026 sobre `3b8432b`** (reemplaza a la lista que antes salía de los expedientes). Script y salida: `docs/reportes/logs/verificar/f1-h6-rutas-por-tarea.sh` y `...-salida.txt`.

**Método.** Para cada tarea, los archivos de **sus** commits (los que empiezan con `F1-H6-Tnn:`), sin repetidos, comparados con las rutas permitidas de `docs/hitos/f1-h6.md` §8 (T00: `docs/reportes/f1-h6-plan-da101.md` §7). Los comandos son:

```
git log --reverse --format=%h --grep="^F1-H6-Tnn:"          # los commits de la tarea
git show --name-only --format= <commit>                      # sus archivos; se juntan y se ordenan sin repetidos
git rev-parse --short <primer commit>~1                      # el commit anterior al primero (en la salida)
```

No usé `git diff --name-only <anterior>..<último>` porque los commits de una tarea están intercalados con los de otras y con los F1-DOC (T03, por ejemplo, va de `93df2b3` a `468e68d` con otros commits en medio), y un diff de rango atribuiría a la tarea archivos que no son suyos. Para cada tarea, la salida dice el commit anterior al primero.

**Supuestos.** (1) Los reportes y sus logs (`docs/reportes/**`) se dan por permitidos en todas las tareas, porque §0 pide un `f1-h6-tNN.md` por tarea aunque §8 no lo repita. (2) Las rutas se comparan por nombre de archivo: las restricciones «solo …» dentro de una ruta permitida (por ejemplo, «solo los adaptadores de DA-115») no se pueden verificar por nombre; revisé a mano los archivos de T03 que caen en esas rutas y todos son adaptadores, puertos o proveedores nombrados en §8.

| Tarea | Commits | Archivos distintos | Fuera de las rutas permitidas |
|---|---|---|---|
| T00 | 1 (`2075659`; anterior `33a9910`) | 41 | ninguno |
| T01 | 1 (`8fbd178`; anterior `8606ba3`) | 6 | ninguno |
| T02 | 2 (`9b31941` a `cf1e267`; anterior `8fbd178`) | 20 | ninguno |
| T03 | 14 (`93df2b3` a `468e68d`; anterior `d50969c`) | 74 | **13 archivos** (abajo) |
| T04 | 1 (`75fe470`; anterior `cf1e267`) | 27 | ninguno |
| T05 | 5 (`4677503` a `d956050`; anterior `155d6a0`) | 39 | **3 archivos** (abajo) |
| T06 | 6 (`a2b1db1` a `d1f4772`; anterior `222df9d`) | 21 | ninguno |
| T07 | 1 (`4339783`; anterior `75fe470`) | 38 | ninguno |
| T08 | 3 (`bb91031` a `6951e33`; anterior `d1f4772`) | 31 | **2 archivos** (abajo) |
| T09 | 8 (`1b03ba7` a `3b8432b`; anterior `6951e33`) | 20 | ninguno |

**Discrepancias** (todas en archivos que no están en la lista de §8 de su tarea). Las tres tareas tienen la discrepancia cubierta por una aprobación y declarada en su reporte; la correspondencia archivo por archivo de T03 y T08 está en un addendum de cada reporte. (En una versión anterior de este apartado dije que el reporte de T03 no declaraba los archivos del renombrado y que no constaba su aprobación: era inexacto; ver abajo.)

- **T03, todo en el commit `468e68d` (13 archivos): los 13 están cubiertos.** Correspondencia archivo por archivo en `docs/reportes/f1-h6-t03.md` §9 (addendum del 17/11/2026):
  - *Renombrado a `resumenes:conciliar` (DA-113):* cubierto por `docs/reportes/f1-h6-plan-t03.md` §9, «Aprobado por el responsable el 16/11/2026: 23 líneas en 5 archivos de H2 …, 3 líneas de código …». Son `ConciliarResumenes.php` (plan §9.3, fila 2), las cinco pruebas de Académico y Seguridad (plan §9.1; 1 + 3 + 12 + 6 + 1 = 23 líneas, igual que la aprobación) y los cuatro documentos `docs/estrategia-pruebas.md` línea 68, `docs/criterios-aceptacion-fase-1.md`, `docs/CAMBIOS-v1.2.md` y DD-21 del registro (plan §9.2, filas 1 a 4: «Se corrige durante la implementación de T03»). El reporte de T03 los declara por grupo en su §1 («Renombrado aprobado»).
  - *DA-121 (aprobada el 16/11/2026):* `tests/Arch/AlcanceDeJugadoresTest.php` (DA-121 lo nombra), `tests/Feature/Academico/AlcanceDelDocenteTest.php` y `tests/Ayudantes/DeteccionDeConsultasDeJugadores.php` (el detector donde se registra el uso autorizado). Declarados por nombre en el reporte de T03, §1 y §7 punto 3.
  - *Sin archivo pendiente de decisión.* Dos notas, sin tratarlas como aprobadas: (1) el plan §9.2 proponía además anotar DD-46 y DD-32 del registro y `468e68d` no lo hizo; (2) el plan §9.4 proponía tres pasos (renombrado primero, código de T03 en commits propios, documentos en un F1-DOC aparte) y `468e68d` es un solo commit con todo; no consta que esa agrupación se haya ordenado; **el responsable la aceptó como desviación declarada el 17/11/2026, sin reescribir el historial**.
- **T05 (3 archivos):** `Makefile` (`b971282`), `docs/hitos/f1-h6.md` y `docs/decisiones/pendientes.md` (`465118f`). Declarados en `docs/reportes/f1-h6-t05.md` §9, «Ajustes aprobados el 16/11/2026», con su commit; el `Makefile` ya figuraba como duda en §8 del mismo reporte. No se añadió nada: ya constan el reporte y el encabezado de la aprobación.
- **T08 (2 archivos):** `backend/tests/Ayudantes/MundoDeAlertas.php` (`8726374`) y `docs/decisiones/pendientes.md` (`bb91031` y `6951e33`). Declarados en `docs/reportes/f1-h6-t08.md` §1 y §8; la **orden que cada uno autorizó** se añadió en el addendum §10 de ese reporte.

Quedan **sin discrepancia** T00, T01, T02, T04, T06, T07 y T09. En T00 `backend/docker/db/init/02-bases-por-proceso.sh` es «el guion idempotente junto a» `01-roles-y-bases.sh` de su plan §7.

## 5. Tiempos

| | Duración |
|---|---|
| `make verificar` (final, sobre `4112157`) | 7 min 4 s en total (13:34:50 a 13:41:54); 327.20 s las pruebas |
| `make verificar` (primera de T09, sobre `40180f2`) | 7 min 28 s (448 s); pruebas 297.52 s |
| `make mutacion` | 4 min 5 s (247 s), con 4 hilos; 999 mutantes |
| `make pruebas` (serie, T08) | 732.67 s a 792.11 s en siete corridas |
| `make humo` (H0 a H6) | 83 s (13:32:08 a 13:33:31) |

**Aclaración sobre la mutación:** corrió con `40180f2` como HEAD (13:22:22 a 13:26:29). `a42820b` solo agregó logs, y los commits posteriores solo cambiaron `scripts/humo/humo-h6.sh` y `GuionDeHumoTest.php`, fuera de `Dominio/`. No se repitió. DA-101 fijó 450 s como meta de `make verificar`, no como bloqueo: la corrida final tardó 424 s.

## 6. Decisiones

- **DA-87 a DA-100:** registradas en `docs/decisiones/registro-decisiones.md` §10 en T09 (`42ac6a3`). DA-87 y DA-89 a DA-99, pendientes de validación.
- **DA-101 a DA-121:** ya registradas durante H6.
- **Decisiones del agente:** `docs/reportes/f1-h6-decisiones-del-agente.md` (hasta la número 25 al cierre de T08).

## 7. Lo que queda abierto

| Qué | Estado | ¿Bloquea según §10? |
|---|---|---|
| **P-16** alertas abiertas de un equipo que el jugador dejó | **Decisión del responsable pendiente.** Deja sin prueba un criterio del punto 7 (ver arriba) | El texto de §10 no la nombra; el responsable decide si el punto 7 la cubre. Espera su decisión antes de la auditoría |
| **P-15** jugadores en baja en los agregados de los gráficos del equipo | Pendiente (opciones A y B) | §10 no la nombra |
| Deuda: orden de la convocatoria (`creado_en` e `id`) | Pendiente | §10 no la nombra |
| Deuda: orden de las alertas (`activada_en` e `id`) | Pendiente; el desempate y si la especificación exige un orden quedan abiertos | §10 no la nombra |
| Deuda: nombres `CalculoDeDesempeno` y `CalculadorDeDesempeno` | Pendiente; `pendientes.md` dice «resolver en H7» | No |
| AB-13 (ventana de RN-09) y AB-07 (reglamento de elegibilidad) | Por validar con la institución | No: §10 punto 9 dice «sin bloquear H6» |
| Limitación del humo: no verifica el worker real | El humo corre con `QUEUE_CONNECTION=sync` por decisión del responsable; el escucha en cola solo tiene una prueba de declaración (`RecalculoDeAlertaAcademicaTest`, línea 147) | No |
| Integración continua sin comprobar | No hay push | Punto 1: la parte «en la integración continua» queda sin verificar |
| Etiqueta `f1-h6` y fusión | Las ordena el responsable | Punto 11 |

## 8. Siguiente paso

Detenerse. El responsable decide P-16; después se pide la auditoría independiente de sistema (`docs/reportes/auditorias/`); con ella entregada y sin bloqueantes, el responsable revisa, prueba `make humo` y ordena la etiqueta.
