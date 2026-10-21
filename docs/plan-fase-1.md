# Plan de la Fase 1 (API del MVP)

**Versión:** 1.2
**Fecha:** 21 de octubre de 2026
**Origen:** sección 5 del Documento de Arranque v1.0, con las dependencias corregidas por la auditoría (IC-04, IC-05).

## 1. Tecnologías y metodología

### 1.1 Tecnologías

| Elemento | Decisión |
|---|---|
| Lenguaje y framework | PHP 8.4 y Laravel 13 |
| Base de datos | PostgreSQL 16 o superior (`uuid`, `jsonb`, `timestamptz`, índices parciales, `CHECK`, `citext`) |
| Identificadores | UUID v7 |
| Autenticación | Laravel Sanctum en modo SPA (sesión con cookies) |
| Entorno local | Docker Compose propio en la raíz del monorepo; todo se ejecuta con `make` |
| Pruebas y calidad | Pest, Pest Arch, Laravel Pint, Larastan (nivel por hito en `docs/estrategia-pruebas.md` §2), Infection desde H2, cobertura de Pest, `composer audit` |
| Colas y tareas programadas | Cola `database` con servicio `worker` y planificador con servicio `scheduler` en Docker Compose (`docs/arquitectura.md` §11) |
| Contrato | OpenAPI en `docs/api/openapi.yaml` |
| Integración continua | GitHub Actions con PostgreSQL como servicio |
| Agente de programación | Claude Code (con Laravel Boost) o Codex. Las reglas del agente se escriben en `backend/.ai/guidelines/proyecto.md`; Boost las copia a `backend/CLAUDE.md` y `backend/AGENTS.md` al ejecutar `boost:install` o `boost:update`, y esos dos **nunca se editan a mano**. El `CLAUDE.md` y el `AGENTS.md` de la raíz son punteros cortos escritos a mano |

### 1.2 Metodología

| Aspecto | Cómo se trabaja |
|---|---|
| Organización | **Fase → Hito → Tarea** (`F1-H2-T05`). Cada hito es un corte vertical que se puede demostrar |
| Priorización | MoSCoW: todo lo del MVP es Must salvo indicación contraria |
| Desarrollo | Primero la prueba (TDD) a partir de los criterios de aceptación, y luego la implementación mínima |
| Arquitectura | Monolito modular con DDD: un contexto por carpeta, dominio puro en PHP, eventos de dominio entre contextos |
| Control de versiones | Una rama por hito, un commit por tarea con el formato `F1-H0-T03: descripción breve` |
| Revisión | Una sola persona, el responsable del proyecto; cada tarea y cada compuerta se aprueban manualmente |
| Árbitro de "terminado" | `make verificar` (estilo, análisis estático, pruebas con cobertura y auditoría de dependencias) |
| Especificación | Cada hito tiene su especificación en `docs/hitos/f1-hN.md`, escrita justo antes de empezarlo |
| Trazabilidad | `docs/trazabilidad.md` relaciona HU, RN y RNF con sus pruebas |

## 2. Hitos

Cada hito es un corte vertical: entrega algo que se puede ejecutar y probar a través de la API. No se inicia un hito hasta aprobar la compuerta del anterior.

| Hito | Nombre | Entregable demostrable | Historias | Depende de |
|---|---|---|---|---|
| H0 | Cimientos | Entorno reproducible, ruta de salud, verificación y CI en verde, estructura DDD, historial y formato de errores | Transversal | N/A |
| H1 | Identidad y Plantel | Usuarios por consola, sesión, equipos, jugadores, categorías, asignaciones, baja y visibilidad por asignación | HU-01 a HU-05, HU-27, HU-29, HU-28 (categorías) | H0 |
| H2 | Académico | Periodos y asignaturas, materias, inscripciones, calificaciones, criterios y riesgo académico calculado | HU-06 a HU-09, HU-28 (periodos y asignaturas) | H1 |
| H3 | Competencia | Lugares, temporadas, torneos y rivales, entrenamientos, asistencia, partidos, convocatorias y alineación | HU-10 a HU-13, HU-28 (lugares, temporadas, torneos y rivales) | H1 |
| H4 | Rendimiento | Captura por posición, plantillas, umbrales y resultado pasa, no pasa o datos insuficientes | HU-14 a HU-16 | H3 |
| H5 | Condición física y Salud | Mediciones, pruebas, avisos, lesiones y disponibilidad | HU-17 a HU-21, HU-30 | H1, H2 |
| H6 | Alertas y consultas | Alertas, listado de estatus, vista para convocar y datos para gráficos | HU-22 a HU-25 | H2, H3, H4, H5 |
| H7 | Cierre | Historial consultable, revisión de seguridad y rendimiento, y validación final de los valores de fábrica | HU-26 y transversal | H6 |

H2 y H3 solo dependen de H1, por lo que su orden puede cambiar. H5 depende también de H2 porque el docente crea avisos (HU-19) y su alcance nace con las inscripciones. Se mantiene el orden de la tabla.

### 2.1 Criterios que se verifican en un hito posterior

Algunas historias tienen criterios que necesitan datos de otro hito. Se implementan cuando existe ese hito y la historia no se da por completa hasta entonces. Cada hito lo anota en su especificación.

| Historia y criterio | Se verifica en | Motivo |
|---|---|---|
| HU-02, criterio 2 (docente ve solo a sus alumnos) | H2 | Necesita materias e inscripciones |
| HU-04, criterio 3 (mismo estado de disponibilidad para los dos entrenadores) | H5 | Necesita el estado de disponibilidad |
| HU-29, criterio 3 (el jugador en baja no aparece en convocatorias) | H3 | Necesita convocatorias |
| HU-29, criterio 3 (el jugador en baja no aparece en listados ni alertas) | H6 | Necesita listado y alertas |
| HU-11, criterio 2 (se indica al jugador lesionado al registrar asistencia) | H5 | Necesita el estado oficial |
| HU-12, criterio 3 (advertencia por jugador lesionado) | H5 | Necesita el estado oficial |
| HU-12, criterio 2 (la convocatoria muestra la vista consolidada) | H6 | Necesita HU-24 |
| HU-19, criterios del docente | H5 (tras H2) | Necesita el alcance del docente |

## 3. Validaciones que deben estar listas antes de cada hito

| Antes de | Debe estar resuelto |
|---|---|
| H1 | DD-01, DD-02, DD-04 (categorías), DD-05, DD-13, DD-16; AB-06 (aplicación móvil) |
| H2 | DD-06, DD-11; AB-01 en lo académico (calificación mínima 7.0 y niveles de riesgo); AB-08 (retícula) |
| H3 | DD-07, DD-17, DD-18; AB-07 (reglamento de elegibilidad) |
| H4 | DD-09, DD-10; AB-01 en lo deportivo (umbral 6.0 con ventana de 5, escala de partido) |
| H5 | DD-03 |
| H6 | DD-08, DD-12, DD-15 |
| H7 | AB-02, AB-03; cierre del registro de decisiones |

Si una decisión no está validada, el agente usa el valor por defecto como dato de fábrica con un comentario que lo indique (`backend/.ai/guidelines/proyecto.md` §5) y el hito se marca como "con valores por validar".

## 4. Compuerta de cada hito

Un hito se da por cerrado cuando se cumple todo lo siguiente:

1. `make verificar` pasa en el entorno local y en la integración continua.
2. Cada historia del hito tiene pruebas con el identificador en su nombre, los criterios diferidos están anotados y `docs/trazabilidad.md` lo refleja (HU, RN y RNF).
3. `docs/api/openapi.yaml` incluye los endpoints del hito y las respuestas reales coinciden con él.
4. Las migraciones se ejecutan desde cero (`make fresh`) y se revierten sin errores.
5. Las reglas de negocio del hito se demuestran con las tablas de casos de ejemplo de `docs/estrategia-pruebas.md`.
6. El guion de humo del hito (`make humo`) termina sin fallas.
7. El hito cumple sus criterios de aceptación de `docs/criterios-aceptacion-fase-1.md`.
8. El agente entregó el reporte de **fuera de alcance** (`backend/.ai/guidelines/proyecto.md` §6) y no hay trabajo no pedido.
9. El responsable revisa los reportes de las tareas, prueba manualmente los flujos principales y aprueba.
10. Se crea la etiqueta de git `f1-hN` y se fusiona la rama del hito.

## 5. Qué se escribe y cuándo

- La especificación de cada hito (`docs/hitos/f1-hN.md`) se redacta **justo antes** de empezarlo, con lo aprendido en el anterior. Solo H0 está escrita.
- Cada especificación de hito incluye: tareas con las rutas que se pueden tocar, criterios de aceptación por tarea, tablas de casos de ejemplo de las reglas del hito, criterios diferidos y el guion de humo.
- **Cada especificación de hito es autocontenida.** Copia el texto de las HU y RN del hito, sus tablas de casos y las tablas de datos de `docs/arquitectura.md` que usa. Así el agente trabaja con la especificación y consulta los documentos completos solo ante una duda, lo que ahorra contexto en cada tarea.
- **Cada tarea se trabaja en una sesión nueva del agente**, que termina con el reporte y espera aprobación.
- La Fase 2 se planifica al cerrar H7, con su propio descubrimiento y las condiciones de `docs/alcance-fases.md`.
