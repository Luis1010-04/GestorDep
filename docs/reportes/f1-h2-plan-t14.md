# Plan F1-H2-T14: situación académica

**Rutas:** rutas base (y `src/Consultas/**`, que no se usa: ver «Ubicación»). **Pruebas:** §7.10 filas 1 a 16, 20, 21 y 22 **por HTTP**; recorrido de seguridad en `tests/Feature/Seguridad/` con cada ruta nueva de H2; contrato.

## Ubicación

La arquitectura (§5, línea 83) permite a Consultas leer tablas de otros contextos, pero **no importar sus clases de dominio**. La situación del docente necesita el dominio de T04: la calificación de cada materia (`CalificacionDeMateria`), el criterio vigente (`CriterioDeAprobacion::resolver`) y `aprobada` (`MateriaEvaluada`). Por eso el caso de uso y su lectura viven en `src/Academico` (rutas base). La lectura del jugador pasa por `AlcanceDeJugadores::restringir(..., incluirBajas: true)`, como `ConsultaDeInscripcionesSql`. La lista cerrada de usos sin alcance no crece.

## Archivos

- **Aplicación:** `VerSituacionAcademica` (nula si el jugador no está en el alcance → 404), el puerto `ConsultaDeSituacionAcademica` (jugador visible, nombre del periodo, resumen del periodo, ¿es docente del jugador?, sus materias del periodo con la inscripción), `SituacionAcademica`, `ResumenLeido`, `MateriaDeLaSituacion`; `ConsultaDeCalificaciones` gana `deLaInscripcion()` (todas, sin paginar).
- **Infraestructura:** `ConsultaDeSituacionAcademicaSql`, enlace.
- **HTTP:** `SituacionAcademicaController`, `SituacionAcademicaRecurso` (reutiliza `CalificacionRecurso` para las calificaciones de cada materia), la ruta `GET /jugadores/{jugador}/situacion-academica` con `whereUuid`, sin control de rol (cualquier sesión con alcance), `openapi.yaml`.
- **Pruebas:** `tests/Feature/Academico/SituacionAcademicaTest.php`, `tests/Feature/Seguridad/AcademicoSeguridadTest.php`, ampliar `ContratoDeAcademicoTest.php`.

## Enfoque

- **Periodo vigente** con `PeriodoVigente` (T05). Sin periodo vigente: `periodo` nulo, todo nulo, contadores en 0, `dato_incompleto` verdadero y `calculado_en` nulo. Con periodo y sin fila de resumen: el periodo, nulos, contadores en 0 y `calculado_en` nulo.
- `dato_incompleto` = sin resumen, o promedio nulo, o alguna materia sin calificar (DD-43).
- `materias` **solo** si el usuario tiene el rol docente y una inscripción vigente del jugador en una materia suya; si no, la clave no aparece (entrenador, o docente-entrenador que solo lo ve por su equipo). Cada materia lleva `materia_id`, `asignatura` (`clave`, `nombre`), `calificacion`, `criterio` (el vigente), `aprobada` y `calificaciones` (las de su inscripción, con el formato del recurso de calificación).

## Decisión propia prevista

- **DA-15:** `materias` lista solo las materias del docente **del periodo vigente** (el mismo periodo del resumen); sin periodo vigente, arreglo vacío. La especificación dice «sus propias materias» sin periodo. Es la opción que revela menos y es coherente con el resumen.

## Dudas

Ninguna que obligue a detenerse.
