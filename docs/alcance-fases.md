# Alcance por fase: lo que se completa en la Fase 1 y lo que se espera en la Fase 2

**Proyecto:** Sistema de seguimiento académico y deportivo de futbolistas
**Versión:** 1.1
**Fecha:** 21 de octubre de 2026
**Fuentes:** `docs/requisitos.md` v1.1, `docs/arquitectura.md` v1.1 y el PDF original "Proceso general del sistema web"

## 1. Para qué sirve este documento

Fija, sin ambigüedad, qué se construye en la Fase 1 y qué queda para la Fase 2. Es la referencia para decidir si una tarea está dentro o fuera de alcance: **lo que no aparece como "Fase 1" aquí no se construye en la Fase 1**, aunque el PDF lo mencione.

La Fase 2 no está planificada. Lo que se lista como "Fase 2" es lo que el PDF espera y el MVP deja fuera; se prioriza y se planifica al cerrar H7, con su propio descubrimiento.

## 2. Qué es la Fase 1

La Fase 1 es el MVP en forma de **API de backend** (sin interfaz de usuario). Entrega:

- 30 historias de usuario (HU-01 a HU-30), 23 reglas de negocio (RN-01 a RN-23) y 11 requisitos no funcionales (RNF-01 a RNF-11), construidos en los hitos H0 a H7 de `docs/plan-fase-1.md`.
- Dos perfiles de usuario: docente y entrenador.
- Un contrato OpenAPI (`docs/api/openapi.yaml`) contra el que se construirá el frontend.
- Pruebas automáticas, pruebas de consola y una integración continua en verde (`docs/estrategia-pruebas.md`).

Un hito se da por completado solo si cumple los criterios de `docs/criterios-aceptacion-fase-1.md`.

## 3. Alcance punto por punto del PDF

| Punto del PDF | Se completa en la Fase 1 | Se espera en la Fase 2 |
|---|---|---|
| Acceso y perfiles | Inicio y cierre de sesión, cambio de contraseña (HU-01, HU-27); perfiles docente y entrenador; usuarios creados por consola (RN-19) | Perfiles de administrador, coordinador, directivo y futbolista; alta de usuarios y permisos por el administrador |
| 1. Registro de usuarios y futbolistas | Equipos, jugadores y asignaciones; nombres, apellidos, fecha de nacimiento, matrícula, categoría (del equipo), equipo, posición, estatus académico y deportivo, historial y baja (HU-03 a HU-05, HU-26, HU-29) | Datos personales y de contacto, fotografía, grado o periodo académico, documentación; registro de usuarios por el administrador |
| 2. Expediente académico | Asignaturas, calificaciones parciales y finales, promedio, porcentaje de asistencia, materias reprobadas, periodos escolares y nivel de riesgo académico (HU-06 a HU-09) | Retardos como dato, actividades académicas, observaciones docentes; la asistencia como parte del riesgo académico |
| 3. Rendimiento deportivo | Minutos, titularidad, tarjetas, calificación general, observación breve; goles, asistencias y estadísticas por posición (atajadas, goles recibidos, recuperaciones, faltas cometidas, pases completados, balones perdidos, tiros a portería); convocatorias; umbrales y resultado pasa o no pasa (HU-14 a HU-16) | Pases realizados, faltas recibidas, distancia recorrida, velocidad promedio y máxima, evaluaciones técnicas, tácticas, físicas y psicológicas, disciplina deportiva; indicadores colectivos |
| 4. Programación de entrenamientos | Fecha, hora de inicio y fin, lugar, equipo, responsable, lista de jugadores y asistencia (presente, falta, falta justificada) (HU-10, HU-11) | Tipo de entrenamiento, objetivos, actividades, puntualidad, participación, nivel de esfuerzo, cumplimiento de ejercicios, rendimiento técnico, táctico y físico, observaciones individuales y grupales; programación por coordinadores |
| 5. Programación de partidos | Fecha, hora, lugar, rival, condición (local o visitante), torneo, tipo, convocatoria, titulares, suplentes, posiciones y resultado (HU-12, HU-13) | Árbitros, incidencias del encuentro, estadísticas colectivas del partido |
| 6. Evaluaciones físicas | Peso, estatura, índice de masa corporal, velocidad, resistencia, tiempo en pruebas y resultados por periodo con su evolución (HU-17, HU-18) | Fuerza, potencia, flexibilidad, agilidad, coordinación; responsable de preparación física como perfil |
| 7. Generación de alertas | 3 de las 13 alertas, visibles dentro del sistema: riesgo de baja académica, riesgo de baja deportiva y acumulación de sanciones (HU-22). Consulta por docentes y entrenadores | Las demás alertas (ver sección 4), consulta por coordinadores y directivos, notificaciones activas |
| 8. Análisis estadístico y analítico | Gráficos de historial individual y grupal: calificaciones, rendimiento en partidos, asistencia y mediciones físicas; jugador contra el promedio de su equipo (HU-25); listado con filtros por equipo, materia y tipo de alerta (HU-23); vista consolidada para convocar (HU-24) | Comparaciones entre periodos, futbolistas, posiciones, torneos y temporadas; porcentaje de aprobación; relación entre rendimiento académico y deportivo; futbolistas con mayor progreso; fortalezas y áreas de oportunidad; tendencias por temporada; filtros por categoría, posición, periodo, torneo y temporada |
| 9. Generación de reportes | Nada | Todos los reportes y su exportación a PDF, Excel y CSV |
| 10. Consulta por el futbolista | Nada. Un jugador que quiere reportar una molestia lo informa a un docente o entrenador (HU-19) | Perfil de futbolista con consulta de su información, con acceso controlado |
| Lesiones (no está en el PDF) | Avisos, confirmación del estado oficial, disponibilidad compartida entre entrenadores y su actualización (HU-19 a HU-21, HU-30) | Perfil de futbolista que reporta sus propios avisos |

## 4. Las 13 alertas del PDF

| # | Alerta del PDF | Fase 1 | Fase 2 |
|---|---|---|---|
| 1 | Bajo promedio académico | Se refleja en el nivel de riesgo académico (RN-04); sin alerta propia | Alerta propia |
| 2 | Materias reprobadas | Se refleja en el nivel de riesgo académico (RN-04); sin alerta propia | Alerta propia |
| 3 | Faltas académicas frecuentes | No | Alerta |
| 4 | Retardos constantes | No (los retardos no se capturan) | Alerta |
| 5 | Inasistencias a entrenamientos | Cuenta dentro de la acumulación de sanciones (RN-09); sin alerta propia | Alerta propia |
| 6 | Bajo rendimiento deportivo | Cuenta dentro del riesgo de baja deportiva (RN-10); sin alerta propia | Alerta propia |
| 7 | Disminución del rendimiento físico | No | Alerta |
| 8 | Falta de participación en partidos | No | Alerta |
| 9 | Acumulación de sanciones | **Sí** (RN-09, HU-22) | Ajustes |
| 10 | Falta de evaluaciones académicas o deportivas | No | Alerta |
| 11 | Documentación incompleta | No | Alerta |
| 12 | Riesgo de baja académica | **Sí** (RN-21, HU-22) | Ajustes |
| 13 | Riesgo de baja deportiva | **Sí** (RN-10, HU-22) | Ajustes |

## 5. Lo que la Fase 1 no hace

Está prohibido construirlo en la Fase 1, aunque parezca fácil o útil:

- Cualquier cosa dentro de `frontend/`: pantallas, componentes, estilos.
- Reportes y exportación (PDF, Excel, CSV).
- Notificaciones (correo, mensajes, notificaciones push).
- Perfiles de administrador, coordinador, directivo, futbolista o preparador físico, y cualquier pantalla o endpoint para administrar usuarios.
- Árbitros, incidencias de partido y estadísticas colectivas.
- Análisis comparativo y avanzado de la sección 3, punto 8, de la columna Fase 2.
- Campos, métricas y pruebas físicas de la columna Fase 2 (por ejemplo, fuerza, distancia recorrida o evaluaciones técnicas).
- Alertas distintas de las tres de la sección 4.
- Carga o subida de archivos (fotografía, documentación).
- Servicios externos de cualquier tipo.
- Una aplicación móvil o el modo de tokens de Sanctum.
- Optimizaciones no pedidas (particiones, cachés, colas externas), salvo que una prueba de rendimiento del hito H7 las exija y se apruebe. La cola con driver `database` y el planificador de Laravel **sí** están permitidos: no son servicios externos y la arquitectura los exige (`docs/arquitectura.md` §11).

## 6. Cómo se prepara el terreno para la Fase 2 sin construirla

- El catálogo de métricas (`metricas` y `metricas_posicion`) y los tipos de prueba física (`tipos_prueba`) permiten agregar la mayoría de los datos del PDF como filas, sin migración de esquema (ADR-08). Esas filas no se agregan en la Fase 1.
- Los umbrales, criterios y plantillas son tablas editables (ADR-09, RNF-09), listas para un futuro perfil de administración.
- La separación en contextos y los eventos de dominio (ADR-01, ADR-12) permiten agregar alertas nuevas sin tocar las existentes.

Nada de esto autoriza a anticipar tablas, endpoints o campos de la Fase 2.

## 7. Condiciones para iniciar la planificación de la Fase 2

1. La compuerta de H7 está aprobada y la etiqueta `f1-h7` creada.
2. Los valores marcados [Propuesta] y [Decisión por defecto] están validados por la institución (`docs/decisiones/registro-decisiones.md`).
3. Se decidió quién construye el frontend y cuándo, porque la Fase 2 depende de él para los perfiles nuevos, los reportes y los gráficos.
4. Se priorizó con MoSCoW cada elemento de la columna Fase 2, empezando por los de mayor impacto en la decisión de convocar o dar de baja.
5. Se hizo un nuevo descubrimiento con los usuarios del MVP en operación.

## 8. Qué historias entran en cada hito

La relación entre hitos, historias y entregables vive **solo** en `docs/plan-fase-1.md` §2, para que no haya dos versiones que se desfasen.
