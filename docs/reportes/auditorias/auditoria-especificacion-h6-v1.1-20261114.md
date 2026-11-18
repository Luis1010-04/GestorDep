# Auditoría de la especificación de H6, v1.1

**Fecha:** 14 de noviembre de 2026
**Documento auditado:** `docs/hitos/f1-h6.md` en `6a10ce027ee84cb19d0b04eb1c9676beb3ad5caf` (`hito/f1-h6-especificacion` = `origin/hito/f1-h6-especificacion`).
**Referencia:** `docs/reportes/auditorias/auditoria-especificacion-h6-20261114.md` (auditoría de la v1.0, sobre `a5e6134`).
**Modo:** solo lectura. No se editó código ni documentación, no se ejecutó `make` y no hay commit de este reporte.
**Criterio:** el mismo de la auditoría de la v1.0. Se revisó el cambio completo `a5e6134..6a10ce0` (un archivo, 20 líneas) y se comprobó de nuevo lo que ese cambio toca en los demás documentos y en el código.

## 1. Veredicto

**No quedan bloqueantes de especificación.** B2 a B6 están corregidos. B1 no era un defecto del texto, sino una precondición sin cumplir: sigue sin cumplirse y se atiende con la tarea de DA-81, antes de T01.

Quedan abiertos los 7 mayores de la v1.0 (uno, parcialmente corregido), un mayor nuevo que nace de la corrección de DA-88, y menores. Ninguno impide T01 ni T02, con una reserva: **M1 afecta al esquema de la tabla `alertas`** y conviene decidirlo antes de T01.

## 2. Estado de los bloqueantes de la v1.0

| Id | Estado | Evidencia en v1.1 |
|---|---|---|
| B1. DA-81 sin cerrar | **Abierto, fuera de la especificación** | §0 sigue exigiéndola antes de T01; la tarea aún no existe (`docs/reportes/f1-h5-decisiones-del-agente.md`, DA-81). Se resuelve haciendo la tarea, no cambiando el texto |
| B2. Parámetros faltantes | **Corregido** | DA-100 pasa a seis claves: agrega `sanciones_faltas_umbral` = 3 y `alerta_academica_nivel` = `en_riesgo`. §5.2 dice que T01 reestructura el seeder para sembrar cada clave por separado |
| B3. T02 contra la arquitectura | **Corregido** | T02 ya no lista `src/Rendimiento/Dominio/**`, declara que no importa clases de otros contextos, deja RN-08 a H4 y pasa RN-18 y §7.5 a T05, en Consultas |
| B4. Alerta académica oculta al entrenador | **Corregido** | §3.1, DA-89, DA-93 y §5.3: el entrenador de cualquier equipo del jugador la ve, la misma alerta para ambos roles. Coincide con `docs/requisitos.md` §7 y `docs/arquitectura.md` §10 |
| B5. 404 por alerta en una ruta de lista | **Corregido** | Las cuatro filas de §7.6 esperan «200, la lista (no) la incluye»; el 404 queda para el jugador en baja |
| B6. DA-88 incoherente | **Corregido** | DA-88 es mixta: académica por escucha de los eventos de Académico (DD-46), deportivas y de sanciones por comando. T03 lo refleja. El paso 4 del humo ejecuta `alertas:recalcular` antes de consultar |

## 3. Mayores

### N1 (nuevo). Dos escuchas en cola sobre el mismo evento, sin orden definido

- **Especificación:** DA-88 y T03: la alerta académica se recalcula con un escucha de `CalificacionRegistrada`, `AsistenciaDeClaseRegistrada`, `CriterioDeMateriaModificado` y `JugadorInscrito`, en cola y `afterCommit`.
- **Código:** esos mismos cuatro eventos ya tienen un escucha en cola, `RecalcularResumenAcademico` (`backend/src/Academico/Infraestructura/AcademicoServiceProvider.php`, líneas 52 a 55), que es el que actualiza `resumenes_academicos`, de donde sale el nivel de riesgo.
- **Problema:** la alerta depende del resumen ya recalculado. Con dos trabajos independientes en la cola `database`, el de la alerta puede correr antes que el del resumen y decidir con el nivel anterior. La alerta quedaría mal hasta el comando de las 02:00. En las pruebas (driver `sync`) el orden es el de registro, así que el fallo no se vería.
- **Recomendación:** que el plan de T03 lo resuelva de forma explícita. Opciones: el escucha de Alertas recalcula el nivel él mismo en vez de leer el resumen; o Académico publica un evento nuevo al terminar el resumen y Alertas escucha ese; o un solo escucha encadena los dos pasos. Las dos últimas tocan rutas que T03 no tiene permitidas.
- **No bloquea:** T03 se detiene en su plan.

### Mayores de la v1.0 que siguen abiertos

| Id | Hallazgo | Estado en v1.1 | Tarea afectada |
|---|---|---|---|
| M1 | La tabla `alertas` no tiene entrenador, y evaluaciones y tarjetas son por entrenador (DD-17); con dos entrenadores en un equipo no se sabe de quién son los datos de la alerta | Sin cambio | **T01 (esquema)**, T03, T04 |
| M2 | §5.3 da al docente el indicador de aviso pendiente sin límite; `docs/requisitos.md` §7 dice «solo el que creó» | Sin cambio | T04 |
| M3 | Rutas permitidas incompletas: `routes/api.php` en T04 a T07; `lang/es/**` en T05 a T07; puertos de `src/Compartido/Aplicacion/**` y sus adaptadores en T03; dueño del comando `alertas:recalcular`; reportes en T02 | Parcial: §5.2 ya reconoce la reestructura del seeder, pero la línea de rutas de T01 sigue diciendo «solo las claves nuevas» | T01 a T07 |
| M4 | Formas de respuesta sin definir: detalle de materia del docente en el listado (HU-23 c3), `estado_oficial`, `situacion_academica`, fechas de actualización por dimensión, respuestas de las dos rutas de gráficos, resumen académico por periodo (HU-25 c1) | Sin cambio | T04, T05, T07 |
| M5 | `filtro_invalido` frente a la convención vigente `validacion_fallida`; id de filtro válido pero ajeno sin regla | Sin cambio | T04 |
| M6 | Humo: el paso 3 no es un paso (hacen falta 5 partidos con rendimiento); el paso 6 espera un aviso que nadie crea; no hay filtros ni alerta académica activa y cerrada, que pide `docs/estrategia-pruebas.md` §5.2 | Sin cambio, salvo el paso 4 | T09 |
| M7 | Casos sin regla: DA-92 sin evaluación o sin umbral aplicable; DA-91 sin temporada que contenga la fecha de cálculo; DA-99 deja alertas abiertas para siempre en jugadores en baja | Sin cambio | T02, T03 |

M7 toca T02: las funciones puras necesitan saber qué reciben cuando no hay evaluación. Si no se define, T02 lo tratará como `datos_insuficientes` y lo anotará como decisión del agente.

## 4. Menores

Nuevos en v1.1:

- **n1.** El encabezado sigue diciendo «Versión: 1.0 (para revisión del responsable)»; el control de cambios ya registra la 1.1.
- **n2.** El título de T02 sigue listando «RN-08, RN-18», que el cuerpo de la tarea excluye.
- **n3.** T02 dice que RN-08 «se consume desde ahí» y, a la vez, que no importa clases de otros contextos. Falta decir que la regla de RN-10 recibe el resultado como valor propio de Alertas (`pasa`, `no_pasa`, `datos_insuficientes`).
- **n4.** `alerta_academica_nivel` es un solo nivel. No dice si un nivel más grave que el configurado también activa (hoy no hay ninguno por encima de `en_riesgo`, así que no cambia el resultado).

De la v1.0, sin cambio: m1 (encabezado de §7.5), m2 (DA-95 frente a DD-12), m3 (DA-89 y el periodo vigente), m4 (OM-05 a OM-08), m5 (zona de «hoy de negocio»), m6 (orden de entrada y paginación), m7 (estilo de las claves), m8 (AB-07).

## 5. Revisado sin hallazgos en el cambio

- DD-46, que DA-88 cita, existe en el registro y describe los cuatro eventos y el escucha en cola `afterCommit` e idempotente.
- Los cuatro eventos existen en `backend/src/Compartido/Dominio/Eventos/`.
- La fila de §5.3 «Alerta académica» y las filas de §7.6 son coherentes entre sí y con el paso 5 del humo.
- §2.3 ya distingue la académica (con escucha) de las deportivas y de sanciones (por comando).
- El resto de la sección 5 del reporte de la v1.0 sigue vigente: el cambio no toca esos puntos.

## 6. No revisado

Lo mismo que en la v1.0: `docs/api/openapi.yaml` ruta por ruta, las corridas de `make` y el estado de la integración continua.
