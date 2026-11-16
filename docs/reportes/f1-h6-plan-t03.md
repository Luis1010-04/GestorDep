# F1-H6-T03: plan de recálculo de alertas

**Fecha:** 16 de noviembre de 2026 · **Especificación:** `docs/hitos/f1-h6.md` v1.11 · **Estado:** plan para aprobación (revisión de plan). No se escribió código, pruebas ni migraciones, y no se ejecutó `make`.
**Nota:** este archivo sustituye al plan de T03 del 15 y 16 de noviembre; esa versión sigue en el historial de git.

## 1. Alcance y reglas aplicadas

T03 construye el recálculo de las tres alertas (HU-22, RN-21, RN-09, RN-10, ADR-07 con DA-88) sobre las reglas puras que T02 dejó en `src/Alertas/Dominio/`. No crea rutas HTTP, tablas, migraciones ni eventos (la tabla `alertas` y su disparador son de T01).

| Decisión | Qué manda a T03 | Dónde está en este plan |
|---|---|---|
| DA-88 | La alerta académica se recalcula por escucha de `CalificacionRegistrada`, `AsistenciaDeClaseRegistrada`, `CriterioDeMateriaModificado` y `JugadorInscrito`, en cola `afterCommit` e idempotente. Las deportivas y las de sanciones, con el comando diario | §4, §5 |
| DA-112 | Alertas lee otros contextos solo por puertos de `src/Compartido/Aplicacion/`, con adaptador en el contexto dueño. Cuatro puertos nuevos: `TarjetasPorPartido` (Rendimiento), `FaltasDeEntrenamiento` (Competencia), `TemporadaDeFecha` (Competencia) y `EvaluacionesVigentesDelEquipo` (Rendimiento). Falta construir el del nivel de riesgo académico (P-09, adaptador en Académico) | §3 |
| DA-113 | `alertas:recalcular` vive en `src/Alertas/Infraestructura/Consola/` y se programa a las 02:00 (America/Mexico_City) en `routes/console.php`. El comando de resúmenes de Académico pasa a `resumenes:conciliar`, también a las 02:00 | §4, §8, §9 |
| DA-102 y DA-103 | Las alertas deportivas y de sanciones cuentan datos de todos los entrenadores del equipo; basta un `no_pasa`; por partido, jugador y tipo de tarjeta cuenta el mayor número registrado por un entrenador. Ya están en el Dominio (T02) | §3, §7 |
| DA-107 | Sin evaluación guardada, el resultado es `datos_insuficientes` | §3 (puerto de evaluaciones), §7 |
| DA-91 y P-08b | Las tarjetas cuentan por temporada del partido; si ninguna temporada contiene la fecha de cálculo, no se cuentan tarjetas, pero sí faltas | §3 (`TemporadaDeFecha`), §7 |
| DA-92 | Se toma la última evaluación de cada entrenador del equipo en `evaluaciones_rendimiento` | §3 |
| DA-114 | No hay ruta para asignar entrenadores adicionales; las pruebas de DA-102 crean las filas de `entrenador_equipo` directamente | §7 |
| P-09 (A) | El escucha calcula el nivel de riesgo por un puerto, sin leer el resumen guardado | §3, §5 |
| P-08c (A) y DA-99 | Las alertas abiertas de un jugador en baja se conservan y no se muestran; T03 no abre alertas nuevas a un jugador en baja | §4, §6 |
| DA-87, DA-100, DA-105 | Tabla `alertas`, claves de `parametros_institucionales` y forma de `causa` | §6 |

Fuera de T03: las rutas y consultas de alertas y del listado (T04), la vista para convocar y los gráficos (T05, T07), el guion de humo de H6 (T09).

## 2. Estado de partida

[pendiente]

## 3. Puertos

[pendiente]

## 4. Comando y programación

[pendiente]

## 5. Escuchas de la alerta académica

[pendiente]

## 6. Cierre y apertura

[pendiente]

## 7. Pruebas previstas

[pendiente]

## 8. Archivos

[pendiente]

## 9. Cambios de renombrado fuera de rutas permitidas

[pendiente]

## 10. Preguntas abiertas para Luis

[pendiente]
