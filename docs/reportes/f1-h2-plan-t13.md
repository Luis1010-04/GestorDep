# Plan F1-H2-T13: asistencia a clase

**Rutas:** rutas base. **Pruebas:** §7.8 (12 filas), §7.10 fila 8 (sobre `resumenes_academicos`), §7.11 fila 16 (crear y corregir asistencia), 401 y contrato.

## Archivos

Mismo patrón que T12, reutilizando lo que T12 dejó: `ConsultaDeInscripciones::delDocente()` (pertenencia y baja), `ComprobarInscripcionDelDocente` y `JugadorEnBajaSinCaptura`.

- **Dominio:** `EstadoDeAsistencia` (enum `presente`, `falta`, `falta_justificada`), `AsistenciaDeClase`, `RepositorioDeAsistencias` (`guardarNueva`, `corregirEstado`).
- **Aplicación:** `RegistrarAsistencia`, `CorregirAsistencia`, `ListarAsistencias`, el puerto `ConsultaDeAsistencias`, `AsistenciaLeida`, `ComprobarAsistenciaDelDocente` y `AsistenciaDuplicada` (409).
- **Infraestructura:** `RepositorioDeAsistenciasEloquent` (historial e instante únicos; `lockForUpdate`; índice único → `asistencia_duplicada`), `ConsultaDeAsistenciasSql`, enlaces.
- **HTTP:** `AsistenciaController`, `SolicitudDeNuevaAsistencia`, `SolicitudDeCorreccionDeAsistencia` (404 antes de validar; cualquier campo distinto de `estado` → `prohibited`), `AsistenciaRecurso`, las rutas `GET`/`POST /inscripciones/{inscripcion}/asistencias` y `PATCH /asistencias-clase/{asistencia}`, `FormatoDeError`, `lang/es`, `openapi.yaml`.
- **Pruebas:** `tests/Feature/Academico/AsistenciasTest.php`, `tests/Unit/Academico/AsistenciaDeClaseTest.php`, ampliar `ContratoDeAcademicoTest.php`.

## Enfoque

- Se publica `AsistenciaDeClaseRegistrada` al crear y al corregir con cambio real; el escucha de T09 recalcula el porcentaje (RN-05). La asistencia no entra en el riesgo.
- Listado por `-fecha` (desempate por `id` descendente), paginado.
- Se aplican DA-13 (corregir con el jugador en baja → 409) y DA-14 (el listado sigue visible) por consistencia con T12.

## Dudas

Ninguna que obligue a detenerse.
