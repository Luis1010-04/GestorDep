# Trazabilidad

Relaciona cada historia (HU), regla de negocio (RN) y requisito no funcional (RNF) con las pruebas que lo cubren. Se llena en cada hito; una historia sin prueba bloquea la compuerta (`docs/estrategia-pruebas.md`, sección 6).

Origen de la columna **Hito**:

- **HU:** tabla de hitos de `docs/plan-fase-1.md` §2. Los criterios diferidos de §2.1 se verifican en el hito que allí se indica.
- **RN:** primer hito cuyas historias citan la regla en `docs/requisitos.md` o cuyos criterios la nombran en `docs/criterios-aceptacion-fase-1.md` §2; si la regla no se cita, el hito de su área. Una regla con tabla de casos en dos hitos lista ambos.
- **RNF:** tabla de verificación de RNF de `docs/criterios-aceptacion-fase-1.md` y DD-14 para lo que queda fuera de la Fase 1.

## Historias de usuario

| Elemento | Hito | Pruebas |
|---|---|---|
| HU-01 Iniciar sesión | H1 (criterio 3 y parte docente del 4 en H2) | |
| HU-02 Ver solo mis jugadores asignados | H1 (criterio 2 en H2) | |
| HU-03 Registrar equipos y jugadores | H1 | |
| HU-04 Asignar un jugador a varios equipos | H1 (criterio 2 en H4, criterio 3 en H5) | |
| HU-05 Mantener el estatus del jugador | H1 | |
| HU-06 Dar de alta materias y alumnos | H2 | |
| HU-07 Capturar calificaciones y asistencia a clases | H2 | |
| HU-08 Definir criterio de aprobación por materia | H2 | |
| HU-09 Ver riesgo académico calculado | H2 | |
| HU-10 Programar entrenamientos | H3 | |
| HU-11 Registrar asistencia a entrenamientos | H3 (criterio 2 en H5) | |
| HU-12 Programar partidos y armar convocatoria | H3 (criterio 3 en H5, criterio 2 en H6) | |
| HU-13 Registrar alineación y resultado | H3 | |
| HU-14 Capturar rendimiento después del partido | H4 | |
| HU-15 Gestionar plantillas y umbrales | H4 | |
| HU-16 Evaluar si el jugador pasa o no pasa | H4 | |
| HU-17 Registrar medidas periódicas | H5 | |
| HU-18 Registrar pruebas físicas | H5 | |
| HU-19 Crear un aviso de molestia o lesión | H5 | |
| HU-20 Confirmar el estado oficial de disponibilidad | H5 | |
| HU-21 Compartir la disponibilidad entre entrenadores | H5 | |
| HU-22 Ver alertas del jugador | H6 | |
| HU-23 Ver listado de estatus | H6 | |
| HU-24 Consultar la vista consolidada del jugador para convocar | H6 | |
| HU-25 Ver gráficos de historial individual y grupal | H6 | |
| HU-26 Consultar el historial de cambios | H7 | |
| HU-27 Cerrar sesión y cambiar contraseña | H1 | |
| HU-28 Gestionar catálogos | H1 (categorías), H2 (periodos y asignaturas), H3 (lugares, temporadas, torneos y rivales) | |
| HU-29 Registrar la baja de un jugador | H1 (criterio 3 en H3 y H6) | |
| HU-30 Actualizar o descartar la disponibilidad | H5 | |

## Reglas de negocio

| Elemento | Hito | Pruebas |
|---|---|---|
| RN-01 Escala de calificación de 0 a 10 | H2 | |
| RN-02 Promedio general y materias reprobadas | H2 | |
| RN-03 Criterio propio de la materia | H2 | |
| RN-04 Riesgo académico | H2 | |
| RN-05 Porcentaje de asistencia a clases | H2 | |
| RN-06 Escala de calificación general de partido | H4 | |
| RN-07 Plantillas de captura y umbrales por posición | H4 | |
| RN-08 Bajo rendimiento | H4 | |
| RN-09 Acumulación de sanciones deportivas | H6 | |
| RN-10 Riesgo de baja deportiva | H4, H6 | |
| RN-11 Asistencia a entrenamientos | H3 | |
| RN-12 Carga inicial y catálogos | H1, H2, H3 | |
| RN-13 Detalle propio y resumen del resto | H2, H6 | |
| RN-14 Jugador en dos equipos | H1 (equipos propios); H3, H4 y H5 (datos que cada entrenador registra) | |
| RN-15 Detalle de lesión solo para quien la registró | H5 | |
| RN-16 Aviso pendiente de confirmación | H5 | |
| RN-17 Conservación del historial y borrado lógico | H1 (borrado lógico e historial), H7 (consulta) | |
| RN-18 Criterios de convocatoria y prioridad por desempeño | H3, H6 | |
| RN-19 Usuarios creados por el equipo del proyecto | H1 | |
| RN-20 Búsqueda por matrícula | H1 | |
| RN-21 Alerta de riesgo de baja académica | H6 | |
| RN-22 Creador del equipo y entrenadores adicionales | H1 | |
| RN-23 Baja de un jugador | H1 | |

## Requisitos no funcionales

| Elemento | Hito | Pruebas |
|---|---|---|
| RNF-01 Confidencialidad | H1 a H7 | |
| RNF-02 Trazabilidad | H0 a H7 | H0: `EscritorDeHistorialTest`, `MacrosDeEsquemaTest` |
| RNF-03 Integridad | H1 a H7 | |
| RNF-04 Vigencia de datos | H6 | |
| RNF-05 Usabilidad | H4 (solo los campos de la posición); el resto, fuera de la Fase 1 | |
| RNF-06 Capacidad | H7 | |
| RNF-07 Disponibilidad | Fuera de la Fase 1 | |
| RNF-08 Idioma | H0 a H7 | H0: `TextosEnLangTest`, `FormatoDeErrorTest` |
| RNF-09 Extensibilidad | H2, H4 | |
| RNF-10 Seguridad de acceso | H1 | |
| RNF-11 Mantenibilidad y pruebas | H0 a H7 | |
