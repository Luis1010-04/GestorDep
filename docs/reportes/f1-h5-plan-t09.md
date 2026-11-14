# Plan de F1-H5-T09: humo de H5 y cierre del hito

**Fecha:** 13 de noviembre de 2026
**Rama:** `hito/f1-h5`, desde `e51cb46` · **Especificación:** `docs/hitos/f1-h5.md` v1.0.8 (sube a v1.0.9 en este plan), §8 (T09), §9 y §10 · **Origen:** auditoría de estado del 13/11/2026 (`docs/reportes/auditorias/auditoria-estado-h5-20261113.md`)
**Estado:** Aprobado por Luis el 13/11/2026 (alcance completo).

## 1. Alcance

T09 cierra lo que la auditoría de estado dejó para esta tarea, más los puntos que Luis decidió meter en ella:

| Punto | Qué se hace | Origen |
|---|---|---|
| 1 | Especificación v1.0.9: `rango_no_configurado` en §6.3 (DA-70) y DA-76 en §3.4 y §3.5 | Auditoría M1 |
| 2 | OpenAPI: solo la descripción de la confirmación, para DA-76. Sin cambiar códigos ni esquemas | Auditoría M2 |
| 3 | Respaldo 409 en lesiones: `RepositorioDeLesionesSql::guardarCambio` convierte el choque de índice único en `EstadoConcurrente` (409 `conflicto`), como avisos, estados, mediciones y pruebas | Auditoría B6, DA-79 |
| 4 | Pruebas de contrato del 409 `conflicto` del descarte y del 422 `rango_no_configurado` | Auditoría B7 |
| 5 | Prueba combinada de DA-33: fechas inválidas frente a `conflicto` | Pendiente 13 |
| 6 | RN-22: una prueba en mediciones y otra en pruebas físicas con dos entrenadores del mismo equipo | Auditoría §2 |
| 7 | Humo de H5 (§9, 18 pasos): `scripts/humo/humo-h5.sh`, la línea en `make humo` y `GuionDeHumoTest` | Pendiente 3 |
| 8 | Documentos de cierre: progreso al estado final, trazabilidad con la tabla de equivalencias final, `f1-h5-reporte.md` con la disposición de los 27 pendientes | Pendientes 2 y 4, §10 punto 8 |

Fuera de T09: la auditoría independiente del Lote C (§10 punto 10), la etiqueta `f1-h5`, el PR y la fusión, que esperan la orden de Luis. Tampoco se toca la migración `2026_11_03_060100` (B1) ni `docs/hitos/` fuera de `f1-h5.md`.

## 2. Permisos

§8 permite a T09 `scripts/humo/humo-h5.sh`, `Makefile` (una línea en `humo`), `tests/Unit/Herramientas/**`, `docs/trazabilidad.md`, `docs/decisiones/registro-decisiones.md` y reportes. La orden de Luis del 13/11/2026 amplía las rutas a:

| Ruta | Para |
|---|---|
| `docs/hitos/f1-h5.md` | Punto 1 |
| `docs/api/openapi.yaml` (solo la descripción de la confirmación) | Punto 2 |
| `backend/src/Salud/Infraestructura/RepositorioDeLesionesSql.php` | Punto 3 |
| `backend/tests/Integration/Salud/**`, `backend/tests/Feature/Salud/**`, `backend/tests/Feature/CondicionFisica/**` | Puntos 3 a 6 |
| `docs/reportes/f1-h5-progreso.md`, `docs/reportes/f1-h5-reporte.md` | Punto 8 |

Ninguna otra ruta. No se agregan tablas, rutas, códigos, claves de `lang/es`, dependencias ni configuración de Docker.

## 3. Fases

Un commit por punto, en este orden. PHPStan después de cada commit con pruebas.

| Fase | Commit | Pruebas primero |
|---|---|---|
| 0 | Push de `e51cb46` (hecho) y estado de Actions | — |
| 1 | `F1-H5-T09: plan de cierre de H5` (este archivo) | — |
| 2.1 | `F1-H5-T09: especificación v1.0.9` | — |
| 2.2 | `F1-H5-T09: descripción de OpenAPI para DA-76` | Las de contrato existentes siguen en verde |
| 2.3 | `F1-H5-T09: respaldo 409 en lesiones (DA-79)` | `DA-79` en `RepositorioDeEstadosYLesionesTest` o `DetalleDeLesion…`: disparador que lanza `unique_violation` en `lesiones_historial` durante `PATCH /lesiones/{lesion}`. Debe fallar primero con 500 |
| 2.4 | `F1-H5-T09: contrato de conflicto y rango no configurado` | En `ContratoDeAvisosTest` y `ContratoDeMedicionesTest`, validadas contra OpenAPI |
| 2.5 | `F1-H5-T09: prueba de DA-33` | En `ConfirmacionYEstadoTest`: con el disparador activo, fechas inválidas dan 422 `rango_de_fechas_invalido`; con fechas válidas, 409 `conflicto` |
| 2.6 | `F1-H5-T09: pruebas de RN-22 en mediciones y pruebas` | En `MedicionesTest` y `PruebasFisicasTest`: E3, del mismo equipo que E1, no ve lo que registró E1 |
| 2.7 | `F1-H5-T09: humo de H5` | `GuionDeHumoTest`: `humo-h5.sh` se detiene ante cualquier error y recorre los 18 pasos de §9. Falla primero porque el guion no existe |
| 3 | Corridas: `make verificar` (tiempo), `make mutacion`, `make fresh` y `make humo` de H0 a H5 | — |
| 4 | `F1-H5-T09: reporte de cierre de H5` | — |

## 4. Criterio de cierre

- Los siete commits de la fase 2 y el de la fase 4, cada uno con su mensaje.
- `make verificar` pasa en 900 s o menos. Si tarda más, me detengo y lo reporto.
- `make mutacion` pasa los umbrales de DD-53 sobre `src/*/Dominio`, sin regresiones.
- `make fresh` y `make humo` de H0 a H5 terminan sin fallas.
- Logs de las corridas que respaldan el reporte en `docs/reportes/logs/`; los demás en `backend/build/`.
- Sin push, sin PR y sin etiqueta. Entrego ids, tiempos, resultados y lo que no cerré.

## 5. Riesgos

| Riesgo | Qué hago |
|---|---|
| `make verificar` cerca del límite: la medición de DA-57 dio 742 s; otras corridas, 755 s y 868 s. Las pruebas nuevas suman tiempo | Corro sin otros comandos en paralelo. Si pasa de 900 s, me detengo y lo reporto; no quito pruebas |
| Las pruebas de DA-33 y RN-22 fijan un comportamiento que el código ya tiene, así que pasan en su primera corrida | Lo digo en el reporte y demuestro que pueden fallar con una mutación en una copia temporal dentro del contenedor, con control antes y después, como en T03 §15 |
| El disparador de las pruebas de choque se crea con la conexión `propietario`, fuera de la transacción de la prueba | Se borra en `finally`, como en `RepositorioDeAvisosTest` |
| La prueba de contrato del 422 `rango_no_configurado` necesita quitar una fila sembrada | Se borra dentro de la transacción de la prueba, como en `RangoFaltanteTest`; no cambia el seeder |
| `make levantar` no reconstruye la imagen con la versión nueva de Docker Compose | Levanto con `docker compose up -d --no-build --wait`; no cambio la configuración de Docker |
| `make humo` exige una base recién sembrada | `make fresh` antes de `make humo`. Un humo fallido por no haber hecho `make fresh` no cuenta: su log va a `backend/build/` |
| Una corrida falla por algo ajeno a T09 | Me detengo y lo reporto; no lo ajusto |
| El humo de H5 choca con datos de humos anteriores (categoría, matrícula, temporada, periodo vigente) | Datos propios (`Sub-10`, matrículas `HUMO-H5-00n`) y la misma salida de H4 cuando la temporada o el periodo ya existen |
