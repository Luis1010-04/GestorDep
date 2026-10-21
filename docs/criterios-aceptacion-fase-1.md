# Criterios de aceptación de la Fase 1

**Versión:** 1.1
**Fecha:** 21 de octubre de 2026
**Fuentes:** `docs/requisitos.md` v1.1, `docs/plan-fase-1.md`, `docs/estrategia-pruebas.md`

Este documento define cuándo se acepta una tarea, un hito y la Fase 1. Los criterios de cada historia (los "Dado... cuando... entonces") viven en `docs/requisitos.md`; aquí se fija **cómo se comprueban**.

## 1. Criterios de una tarea

Una tarea está terminada solo si se cumple todo lo siguiente:

1. Hay al menos una prueba por cada criterio de aceptación de la tarea, escrita antes que el código y con el identificador en su nombre.
2. `make verificar` pasa sin errores.
3. El contrato OpenAPI refleja los cambios.
4. Solo se tocaron las rutas que la tarea permite.
5. El commit tiene el formato `F1-HN-TNN: descripción breve`.
6. El reporte incluye: archivos tocados, pruebas agregadas, resultado de `make verificar`, **lo que no se hizo** y dudas.
7. El agente se detuvo y esperó aprobación.

## 2. Criterios de un hito

Los de la compuerta de `docs/plan-fase-1.md` (sección 4), más los propios de cada hito:

| Hito | Criterios propios, además de la compuerta |
|---|---|
| H0 | `make levantar` y `make verificar` funcionan en una máquina limpia siguiendo solo el `README.md`; `make verificar` **falla** si se introduce a propósito un error de estilo, de tipos o una prueba rota; las pruebas de arquitectura **fallan** ante una violación de ejemplo; CI en verde; `GET /api/v1/salud` documentada; Docker Compose define los servicios `worker` y `scheduler`; ninguna tabla de negocio creada |
| H1 | `usuarios:crear` funciona y está probado; un usuario inicia y cierra sesión; un entrenador crea un equipo y queda asignado; un jugador duplicado responde 409 sin datos del expediente; un jugador ajeno responde 404; la búsqueda por matrícula devuelve solo nombres, apellidos y matrícula; se bloquea la cuenta tras 5 intentos fallidos |
| H2 | Las diez filas de la tabla de RN-04 pasan; el criterio propio de una materia prevalece sobre el general y el sistema indica cuál se aplicó; una calificación fuera de 0 a 10 se rechaza; el docente ve solo a sus alumnos; Infection corre sobre `Dominio/` |
| H3 | Un empalme de lugar y horario advierte sin impedir; un jugador no convocado no entra a la alineación; un partido con resultado pasa a "jugado"; la asistencia acepta presente, falta y falta justificada; las advertencias usan el formato de `docs/convenciones-api.md` |
| H4 | La captura pide solo los campos de la posición del jugador; las tablas de RN-08 y RN-10 pasan; un cambio de umbral guarda quién, cuándo y el valor anterior; el resultado del jugador en dos equipos usa solo los datos y el umbral de cada entrenador |
| H5 | Un aviso de docente o entrenador queda pendiente y no cambia el estado oficial; cualquier entrenador del jugador lo confirma y queda como registrante; el otro entrenador ve estado y fecha estimada de regreso pero no tipo, zona ni causa; el IMC se calcula sin captura; la tabla de RN-16 pasa |
| H6 | Las tablas de RN-21, RN-09, RN-10 y RN-18 pasan; una alerta que deja de cumplirse se cierra y se conserva; `alertas:recalcular` es idempotente, está programado a diario y cierra las alertas que vencen con el tiempo; el listado filtra por equipo, materia y tipo de alerta; la vista para convocar trae las cinco dimensiones, la disponibilidad y las fechas de actualización |
| H7 | `GET` de historial devuelve autor, fecha, valor anterior y nuevo según la visibilidad del usuario; el borrado lógico no aparece en vistas normales pero sí en el historial; revisión de seguridad sin hallazgos abiertos; prueba de volumen con 15 equipos, 25 usuarios y unos 375 jugadores sin errores; valores de fábrica validados o marcados como pendientes por escrito |

## 3. Criterios por historia

Cada historia se acepta cuando **todos** sus criterios de `docs/requisitos.md` tienen prueba y pasan. La tabla indica el hito, el número de criterios y dónde se prueba. El hito de cada historia proviene de `docs/plan-fase-1.md` §2; si alguna vez difieren, prevalece el plan.

| Historia | Hito | Criterios | Pruebas requeridas | Criterio diferido |
|---|---|---|---|---|
| HU-01 Iniciar sesión | H1 | 4 | Feature, Seguridad | |
| HU-02 Ver solo mis jugadores | H1 | 3 | Feature, Seguridad, Arch | c2 en H2 |
| HU-27 Cerrar sesión y cambiar contraseña | H1 | 3 | Feature | |
| HU-03 Registrar equipos y jugadores | H1 | 3 | Feature, Integración | |
| HU-04 Asignar a varios equipos | H1 | 3 | Feature, Seguridad | c3 en H5 |
| HU-05 Mantener el estatus | H1 | 2 | Feature, Integración | |
| HU-29 Registrar la baja | H1 | 3 | Feature | c3 en H3 y H6 |
| HU-28 Gestionar catálogos | H1, H2, H3 | 4 por catálogo | Feature, Integración | Categorías en H1; periodos y asignaturas en H2; lugares, temporadas, torneos y rivales en H3 |
| HU-06 Dar de alta materias y alumnos | H2 | 3 | Feature | |
| HU-07 Capturar calificaciones y asistencia | H2 | 3 | Unit, Feature | |
| HU-08 Criterio por materia | H2 | 3 | Unit, Feature | |
| HU-09 Ver riesgo académico | H2 | 3 | Unit, Feature | |
| HU-10 Programar entrenamientos | H3 | 3 | Feature, Integración | |
| HU-11 Asistencia a entrenamientos | H3 | 3 | Feature | c2 en H5 |
| HU-12 Programar partidos y convocar | H3 | 3 | Feature | c2 en H6; c3 en H5 |
| HU-13 Alineación y resultado | H3 | 3 | Feature, Integración | |
| HU-14 Capturar rendimiento | H4 | 3 | Feature | |
| HU-15 Plantillas y umbrales | H4 | 3 | Unit, Feature | |
| HU-16 Pasa o no pasa | H4 | 3 | Unit, Feature | |
| HU-17 Medidas periódicas | H5 | 3 | Unit, Feature | |
| HU-18 Pruebas físicas | H5 | 3 | Feature | |
| HU-19 Crear aviso | H5 | 3 | Feature, Seguridad | |
| HU-20 Confirmar estado oficial | H5 | 3 | Feature, Seguridad | |
| HU-21 Compartir disponibilidad | H5 | 3 | Feature, Seguridad | |
| HU-30 Actualizar o descartar | H5 | 3 | Feature | |
| HU-22 Ver alertas | H6 | 3 | Unit, Feature, Consola | |
| HU-23 Listado de estatus | H6 | 4 | Feature | |
| HU-24 Vista para convocar | H6 | 3 | Feature | |
| HU-25 Gráficos (datos) | H6 | 3 | Feature, Seguridad | |
| HU-26 Historial de cambios | H7 | 3 | Feature, Seguridad | |

El historial se escribe desde H0 (componente de la tarea T08); H7 entrega solo su **consulta**.

## 4. Criterios por regla y requisito no funcional

| Elemento | Cómo se acepta |
|---|---|
| RN-01 a RN-23 | Prueba unitaria de dominio o de integración por regla, con el identificador en el nombre. Las que tienen tabla en `docs/estrategia-pruebas.md` (RN-04, RN-08, RN-09, RN-10, RN-16, RN-18, RN-21) tienen una prueba por fila |
| RNF-01 Confidencialidad | Pruebas de seguridad por hito y prueba de arquitectura de `AlcanceDeJugadores` |
| RNF-02 Trazabilidad | Prueba de integración: el cambio y su historial se guardan en la misma transacción |
| RNF-03 Integridad | Pruebas de integración de restricciones `CHECK` e índices parciales |
| RNF-04 Vigencia de datos | Los recursos incluyen su fecha de última actualización; prueba de los 30 días [Propuesta] |
| RNF-05 Usabilidad | **Fuera de la Fase 1** (se verifica en el frontend); la API solo pide los campos de la posición (H4) |
| RNF-06 Capacidad | Prueba de volumen en H7 |
| RNF-07 Disponibilidad | **Fuera de la Fase 1** (despliegue y respaldos) |
| RNF-08 Idioma | Ningún texto visible está escrito en el código; prueba que recorre las respuestas de error y confirma que salen de `lang/es` |
| RNF-09 Extensibilidad | Umbrales, criterios y plantillas se leen de tablas; una prueba cambia el valor y el cálculo cambia |
| RNF-10 Seguridad de acceso | Pruebas de bloqueo, caducidad de sesión y política de contraseña |
| RNF-11 Mantenibilidad y pruebas | `make verificar` con los umbrales de cobertura de `docs/estrategia-pruebas.md` |

## 5. Pruebas que protegen el alcance

Para que el agente no construya lo que no está pedido, estas pruebas forman parte de `make verificar` desde el hito indicado:

| Prueba | Qué impide | Desde |
|---|---|---|
| Rutas documentadas | Una ruta de `/api/v1` que no esté en `docs/api/openapi.yaml`, o una ruta documentada que no exista | H0 (T09) |
| Arquitectura | Dominio con dependencias del framework, Aplicación que usa Infraestructura, un contexto que importa el dominio de otro (salvo Compartido), una consulta de jugadores que no pasa por `AlcanceDeJugadores` | H0 (T05) |
| Borrado | Uso de `delete()` o `forceDelete()` físico sobre tablas de negocio | H1 |
| Historial | Tabla con `(H)` en `docs/arquitectura.md` sin su `{tabla}_historial` | H1 |
| Textos | Texto visible escrito en el código fuera de `lang/es` | H0 (T09) |

## 6. Criterios de la Fase 1 completa

La Fase 1 se da por completa cuando:

1. Los hitos H0 a H7 están cerrados y etiquetados (`f1-h0` a `f1-h7`).
2. Las 30 historias tienen todos sus criterios probados, incluidos los diferidos.
3. Las 23 reglas de negocio tienen pruebas; los valores [Propuesta] y [Decisión por defecto] están validados o registrados como pendientes en `docs/decisiones/registro-decisiones.md`.
4. `make verificar` y la integración continua pasan en la rama principal con los umbrales de cobertura vigentes.
5. `docs/api/openapi.yaml` describe todas las rutas y las respuestas reales coinciden.
6. `docs/trazabilidad.md` relaciona las 30 HU, las 23 RN y los RNF aplicables con sus pruebas.
7. El responsable demostró los flujos principales con `make humo` y los aprobó.
8. Nada de la lista "La Fase 1 no hace" de `docs/alcance-fases.md` fue construido.
