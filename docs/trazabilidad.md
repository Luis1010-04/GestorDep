# Trazabilidad

Relaciona cada historia (HU), regla de negocio (RN) y requisito no funcional (RNF) con las pruebas que lo cubren. Se llena en cada hito; una historia sin prueba bloquea la compuerta (`docs/estrategia-pruebas.md`, sección 6).

Origen de la columna **Hito**:

- **HU:** tabla de hitos de `docs/plan-fase-1.md` §2. Los criterios diferidos de §2.1 se verifican en el hito que allí se indica.
- **RN:** primer hito cuyas historias citan la regla en `docs/requisitos.md` o cuyos criterios la nombran en `docs/criterios-aceptacion-fase-1.md` §2; si la regla no se cita, el hito de su área. Una regla con tabla de casos en dos hitos lista ambos.
- **RNF:** tabla de verificación de RNF de `docs/criterios-aceptacion-fase-1.md` y DD-14 para lo que queda fuera de la Fase 1.

## Historias de usuario

| Elemento | Hito | Pruebas |
|---|---|---|
| HU-01 Iniciar sesión | H1 (criterio 3 y parte docente del 4 en H2) | H1: `IniciarSesionTest`, `ControlDeRolTest`, `UsuarioActualDeSesionTest`, `UsuarioTest`, `JugadoresTest`, `AsignacionDeJugadoresTest`; humo `humo-h1.sh` (pasos 2, 7, 12 y 14). **H2 (criterio 3 y parte docente del 4):** `AlcanceDelDocenteTest` (§7.4 filas 11 y 12), `PeriodosEscolaresTest` y `AsignaturasTest` (403 del entrenador y 201 del docente-entrenador), `MateriasTest`, `InscripcionesTest`, `CriterioPorMateriaTest`, `CalificacionesTest`, `AsistenciasTest` (403 y 401), `EscrituraDeUsuarioConDosRolesTest` (DA-10), `AcademicoSeguridadTest` (403 en las 17 operaciones docentes y 401 en las 19 nuevas); humo `humo-h2.sh` pasos 2, 14 y 16 |
| HU-02 Ver solo mis jugadores asignados | H1 (criterio 2 en H2) | H1: `ConsultaDeJugadoresTest`, `AlcanceDeJugadoresTest` (Integration y Arch), `JugadoresSeguridadTest`; humo pasos 8 y 11. **H2 (criterio 2):** `AlcanceDelDocenteTest` (§7.4 filas 1 a 14, inscripción y materia eliminadas), `InscripcionesTest` (§7.5 filas 1, 9 y 10), `AcademicoSeguridadTest`, `SituacionAcademicaTest` (§7.10 filas 11 a 14); `AlcanceDeJugadoresTest` (Arch: la lista cerrada no crece); humo `humo-h2.sh` paso 13. **Diferido a H5:** el lado deportivo del docente (estado de disponibilidad, §2.2) |
| HU-03 Registrar equipos y jugadores | H1 | H1: `JugadoresTest`, `EquiposTest` |
| HU-04 Asignar un jugador a varios equipos | H1 (criterio 2 en H4, criterio 3 en H5) | H1: `AsignacionDeJugadoresTest`, `ConsultaDeJugadoresTest`. **Diferido a H4:** criterio 2 (cada entrenador ve solo lo que registró), porque en H1 ningún entrenador registra datos del jugador salvo el alta; en H1 se prueba que un entrenador no ve la pertenencia a equipos ajenos. **Diferido a H5:** criterio 3 (mismo estado de disponibilidad) |
| HU-05 Mantener el estatus del jugador | H1 | H1: `ExpedienteDeJugadorTest`, `AsignacionDeJugadoresTest` |
| HU-06 Dar de alta materias y alumnos | H2 | H2: `MateriasTest` (criterio 1, §7.3), `InscripcionesTest` (criterios 2 y 3, §7.5, y §7.10 fila 23), `MateriaYAsignaturaTest`, `InscripcionTest`, `LocalizadorDeJugadoresTest` (Arch), `ContratoDeAcademicoTest`; humo `humo-h2.sh` pasos 5 y 7 |
| HU-07 Capturar calificaciones y asistencia a clases | H2 | H2: `CalificacionesTest` (criterios 1 y 2, §7.7 y §7.10 filas 2, 3, 4 y 7), `AsistenciasTest` (criterio 3, §7.8 y §7.10 fila 8), `RecalculoDelResumenTest` (recálculo por eventos, §7.10 filas 15 a 19, 22 y 24), `CalificacionTest`, `AsistenciaDeClaseTest`, `ContratoDeAcademicoTest`; humo `humo-h2.sh` pasos 8, 9 y 15 |
| HU-08 Definir criterio de aprobación por materia | H2 | H2: `CriterioPorMateriaTest` (§7.6 y §7.10 filas 5 y 6; la fila 9 «después del commit» se cubre junto con la fila 10 y `arquitectura §11: el publicador despacha después del commit` de `RecalculoDelResumenTest`, auditoría de C2 M-6), `CriterioDeAprobacionTest`, `EscalaDeCalificacionesTest`, `ContratoDeAcademicoTest`; humo `humo-h2.sh` paso 11 |
| HU-09 Ver riesgo académico calculado | H2 | H2: `SituacionAcademicaTest` (§7.10 filas 1 a 16, 20, 21 y 22 por HTTP; criterio 3 en la fila 9), `RecalculoDelResumenTest`, `ResumenAcademicoTest`, `RiesgoAcademicoTest`, `ContratoDeAcademicoTest`; humo `humo-h2.sh` pasos 10 a 13. **Diferido a H6:** el resumen en alertas, convocatoria y gráficos y la marca «posiblemente desactualizado» (§2.2) |
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
| HU-27 Cerrar sesión y cambiar contraseña | H1 | H1: `CerrarSesionYContrasenaTest`; humo paso 14 |
| HU-28 Gestionar catálogos | H1 (categorías), H2 (periodos y asignaturas), H3 (lugares, temporadas, torneos y rivales) | H1 (categorías): `CategoriasTest`, `EquiposTest`. H2 (periodos y asignaturas): `PeriodosEscolaresTest` (§7.1), `AsignaturasTest` (§7.2; «la materia lo sigue mostrando» por la API en §7.1 fila 8 y §7.2 fila 6, y en `MateriasTest` §7.3 fila 8, auditoría de C2 M-3), `PeriodoEscolarTest`, `MateriaYAsignaturaTest`, `ContratoDeAcademicoTest`; humo `humo-h2.sh` pasos 3 y 4. **Diferido a H3:** lugares, temporadas, torneos y rivales |
| HU-29 Registrar la baja de un jugador | H1 (criterio 3 en H3 y H6) | H1: `BajaDeJugadorTest`; humo paso 13. **Diferido a H3 (convocatorias) y H6 (listado y alertas):** criterio 3; en H1 solo se prueba que el jugador en baja no aparece en `GET /jugadores` |
| HU-30 Actualizar o descartar la disponibilidad | H5 | |

## Reglas de negocio

| Elemento | Hito | Pruebas |
|---|---|---|
| RN-01 Escala de calificación de 0 a 10 | H2 | H2: `EscalaDeCalificacionesTest`, `CalificacionTest`, `CalificacionesTest` (§7.7 filas 3 y 4), `CriterioPorMateriaTest` (§7.6 fila 5), `MateriasTest` (sin criterio general sembrado no se inventa un mínimo), `MigracionesDeAcademicoTest` (§7.11 filas 1 y 7), `CriterioDeAprobacionTest`, `MateriaEvaluadaTest` |
| RN-02 Promedio general y materias reprobadas | H2 | H2: `CalificacionDeMateriaTest` y `ResumenAcademicoTest` (§7.9), `NumeroDecimalTest` (§7.12 fila 4), `RecalculoDelResumenTest`, `SituacionAcademicaTest` |
| RN-03 Criterio propio de la materia | H2 | H2: `CriterioDeAprobacionTest`, `MateriaEvaluadaTest` (§7.9), `CriterioPorMateriaTest` (§7.10 filas 5 y 6), `RecalculoDelResumenTest`, `SituacionAcademicaTest`, `MigracionesDeAcademicoTest` (§7.11 filas 5 y 6) |
| RN-04 Riesgo académico | H2 | H2: `RiesgoAcademicoTest` (las 13 filas de §7.9, incluidas las diez de RN-04 y la de 7.495), `ParametrosDeRiesgoTest`, `ResumenAcademicoTest`, `NumeroDecimalTest` (§7.12 fila 3), `RecalculoDelResumenTest`, `CalificacionesTest` (§7.10 fila 7), `SituacionAcademicaTest`; humo `humo-h2.sh` pasos 10 y 11. **Diferido a H6:** RN-21 (alerta) |
| RN-05 Porcentaje de asistencia a clases | H2 | H2: `ConteoDeAsistenciaTest` (§7.9), `AsistenciasTest` (§7.10 fila 8, porcentaje tras cada asistencia), `RecalculoDelResumenTest`, `SituacionAcademicaTest`; humo `humo-h2.sh` pasos 9 y 10 |
| RN-06 Escala de calificación general de partido | H4 | |
| RN-07 Plantillas de captura y umbrales por posición | H4 | |
| RN-08 Bajo rendimiento | H4 | |
| RN-09 Acumulación de sanciones deportivas | H6 | |
| RN-10 Riesgo de baja deportiva | H4, H6 | |
| RN-11 Asistencia a entrenamientos | H3 | |
| RN-12 Carga inicial y catálogos | H1, H2, H3 | H1: ninguna prueba lleva el identificador RN-12; las categorías, que son el único catálogo de H1, las cubre HU-28 (`CategoriasTest`). H2: tampoco hay prueba con el identificador RN-12; la parte docente (periodos y asignaturas como catálogo común, materias e inscripción por docentes) la cubren HU-28 y DD-49 (`PeriodosEscolaresTest`, `AsignaturasTest`) y HU-06 (`MateriasTest`, `InscripcionesTest`) |
| RN-13 Detalle propio y resumen del resto | H2, H6 | H2: `AlcanceDelDocenteTest` (§7.4 filas 2 y 5: `equipos` vacío para el docente), `SituacionAcademicaTest` (§7.10 filas 9, 10 y 14: el entrenador ve solo el resumen); humo `humo-h2.sh` paso 12. **Diferido a H6:** el lado deportivo del docente (disponibilidad) |
| RN-14 Jugador en dos equipos | H1 (equipos propios); H3, H4 y H5 (datos que cada entrenador registra) | H1: `ConsultaDeJugadoresTest`, `ExpedienteDeJugadorTest`, `JugadoresSeguridadTest`. H2 (resumen académico visible para todos sus entrenadores; el docente sin datos deportivos): `InscripcionesTest` (§7.5 fila 10), `SituacionAcademicaTest` (§7.10 fila 14) |
| RN-15 Detalle de lesión solo para quien la registró | H5 | |
| RN-16 Aviso pendiente de confirmación | H5 | |
| RN-17 Conservación del historial y borrado lógico | H1 (borrado lógico e historial), H7 (consulta) | H1: `AlcanceDeJugadoresTest`, `BajaDeJugadorTest`, `MigracionesDePlantelTest`, `BorradoLogicoTest`, `HistorialDeTablasTest` |
| RN-18 Criterios de convocatoria y prioridad por desempeño | H3, H6 | |
| RN-19 Usuarios creados por el equipo del proyecto | H1 | H1: `CrearUsuarioComandoTest` (prefijo `CONSOLA usuarios:crear`), `UsuarioTest`, `UsuariosDemoSeederTest` |
| RN-20 Búsqueda por matrícula | H1 | H1: `ConsultaDeJugadoresTest`, `AsignacionDeJugadoresTest`, `JugadoresSeguridadTest`; humo pasos 9 y 10. H2 (búsqueda del docente e inscripción que otorga el alcance): `AlcanceDelDocenteTest` (§7.4 fila 6), `InscripcionesTest` (§7.5 filas 1 a 3), `LocalizadorDeJugadoresTest` (Arch); humo `humo-h2.sh` paso 7 |
| RN-21 Alerta de riesgo de baja académica | H6 | |
| RN-22 Creador del equipo y entrenadores adicionales | H1 | H1: `EquiposTest`, `MigracionesDePlantelTest`; humo paso 4 |
| RN-23 Baja de un jugador | H1 | H1: `BajaDeJugadorTest`, `AlcanceDeJugadoresTest`, `MigracionesDePlantelTest` |

## Requisitos no funcionales

| Elemento | Hito | Pruebas |
|---|---|---|
| RNF-01 Confidencialidad | H1 a H7 | H1: `AlcanceDeJugadoresTest`, `ConsultaDeJugadoresTest`, `JugadoresSeguridadTest`. H2: `AcademicoSeguridadTest` (recorrido de cada ruta nueva: ajeno, rol, sin sesión, ids inexistentes y v4, 404 byte a byte), `AlcanceDelDocenteTest`, `InscripcionesTest`, `CriterioPorMateriaTest`, `CalificacionesTest`, `AsistenciasTest`, `SituacionAcademicaTest`, `EscrituraDeUsuarioConDosRolesTest` (DA-10), `LocalizadorDeJugadoresTest` (Arch); humo `humo-h2.sh` paso 13 |
| RNF-02 Trazabilidad | H0 a H7 | H0: `EscritorDeHistorialTest`, `MacrosDeEsquemaTest`; H1: `MigracionesDeIdentidadTest`, `MigracionesDePlantelTest`, `HistorialDeTablasTest`, `ActualizadoEnCoherenteTest`, `JugadoresTest`, `EquiposTest`, `AsignacionDeJugadoresTest`, `ExpedienteDeJugadorTest`, `BajaDeJugadorTest`. H2: `MigracionesDeAcademicoTest` (§7.11 filas 12, 14 y 17), `DisparadoresTrasReinicioTest`, `MateriasTest`, `InscripcionesTest`, `CriterioPorMateriaTest`, `CalificacionesTest`, `AsistenciasTest` (historial en la misma transacción y fallo real del historial, §7.11 fila 16) |
| RNF-03 Integridad | H1 a H7 | H1: `MigracionesDeIdentidadTest`, `MigracionesDePlantelTest`, `JugadoresTest`, `UsuarioActualDeSesionTest`. H2: `MigracionesDeAcademicoTest` (§7.11 filas 1 a 15), `CalificacionesTest` y `AsistenciasTest` (carreras por el índice único) |
| RNF-04 Vigencia de datos | H6 | H1 (solo la zona de las fechas de negocio): `HoyDeNegocioTest`, `HoyDeNegocioEnContenedorTest`. H2 (decimales exactos y `calculado_en`): `NumeroDecimalTest` (§7.12 filas 5 a 7), `DecimalesEnDominioTest` (Arch), `SituacionAcademicaTest` (§7.10 fila 22). **Diferido a H6:** la marca «posiblemente desactualizado» (§2.2) |
| RNF-05 Usabilidad | H4 (solo los campos de la posición); el resto, fuera de la Fase 1 | |
| RNF-06 Capacidad | H7 | |
| RNF-07 Disponibilidad | Fuera de la Fase 1 | |
| RNF-08 Idioma | H0 a H7 | H0: `TextosEnLangTest`, `FormatoDeErrorTest` |
| RNF-09 Extensibilidad | H2, H4 | H2 (parámetros en tablas, no en el código): `RecalculoDelResumenTest` (sin `riesgo_academico` el recálculo falla), `RiesgoAcademicoTest` (§7.9 filas 12 y 13), `ParametrosDeRiesgoTest`, `MigracionesDeAcademicoTest` (§7.11 fila 15) |
| RNF-10 Seguridad de acceso | H1 | H1: `IniciarSesionTest`, `CerrarSesionYContrasenaTest`, `PoliticaDeContrasenaTest`, `UsuarioTest`, `UsuarioActualDeSesionTest`, `MigracionesDeIdentidadTest` (tabla `sessions`) |
| RNF-11 Mantenibilidad y pruebas | H0 a H7 | H1: `VerificarCoberturaTest` (umbral por capa). H2: PHPStan nivel 7 e Infection sobre `Dominio/` en `make verificar` (§7.12 filas 1 y 2: evidencia en `docs/reportes/f1-h2-t01.md`), `VerificarMutacionTest` (mutantes omitidos o con tiempo agotado y configuración del resumen, auditoría de C2 M-10), `DecimalesEnDominioTest` (Arch), `GuionDeHumoTest` (el humo se detiene ante un paso roto), `ConfiguracionDePhpstanTest` (§7.12 fila 2: nivel 7, sin `ignoreErrors`, sin línea base ni exclusiones; sus tres pruebas llevan el identificador `RNF-11 (M-1)`; auditoría del Lote E, M-1), `VerificarCoberturaTest` (PT-02: el guion borra el clover tras leerlo y no aprueba con uno viejo). Las demás pruebas de herramientas usan el prefijo `HERRAMIENTAS`, `PT-02` o `F1-H2-T15` |

## Estado al cierre de H1

- Las pruebas se buscan por el identificador (`HU-xx`, `RN-xx`, `RNF-xx`) en su nombre; la columna lista los archivos que las contienen, no los títulos. Total de la suite en el cierre de H1: **441 pruebas, 2 405 aserciones** (`make verificar`).
- Criterios diferidos de `docs/hitos/f1-h1.md` §2.2: HU-01 c3 y parte docente del c4 (H2), HU-02 c2 (H2), HU-04 c2 (H4) y c3 (H5), HU-29 c3 (H3 y H6) y los catálogos de HU-28 distintos de categorías (H2 y H3). Ninguno se prueba en H1.
- RN-12 queda sin prueba con su identificador en H1 (ver su fila).

## Estado al cierre de H2

- Total de la suite al cierre de H2: **920 pruebas, 5 574 aserciones** (`make verificar` del FIX M-c, `e02a40b`, tras las correcciones de la auditoría corta; en T15 eran 897 y 5 391). Ver `docs/reportes/f1-h2-reporte.md`.
- Criterios diferidos de `docs/hitos/f1-h2.md` §2.2: RN-21 (H6); HU-02 c2, lado deportivo del docente (H5); el resumen y el detalle en alertas, vista para convocar y gráficos (H6); HU-24 / RNF-04, marca «posiblemente desactualizado» (H6); historial de calificaciones y asistencia consultable por la API (H7, H2 solo lo escribe); HU-28, lugares, temporadas, torneos y rivales (H3). Ninguno se prueba en H2.
- RN-12 sigue sin prueba con su identificador en H2 (ver su fila); su parte docente la cubren HU-28, DD-49 y HU-06.
- RNF-11 tiene prueba con su identificador en H2 desde el FIX de M-1 del Lote E: las tres de `ConfiguracionDePhpstanTest` (`RNF-11 (M-1): …`). El resto lo cubren las pruebas `HERRAMIENTAS` y `PT-02` de `tests/Unit/Herramientas/` y la evidencia de §7.12 fila 1 en `docs/reportes/f1-h2-t01.md` (auditoría del Lote E, M-2).
- Varias pruebas de H2 llevan en el nombre el identificador de la decisión que verifican (`DD-45`, `DD-47` a `DD-52`, `DD-55`, `DA-10`, `DA-15`) en lugar del de la historia; la historia correspondiente es la de su tabla de casos (§7 de la especificación).
