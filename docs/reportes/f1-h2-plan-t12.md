# Plan F1-H2-T12: calificaciones

**Rutas:** rutas base. **Pruebas:** §7.7 (17 filas), §7.10 filas 2, 3, 4 y 7 (sobre `resumenes_academicos`), §7.11 fila 16 para crear y corregir, y contrato.

## Archivos

- **Dominio:** `TipoDeCalificacion` (enum `parcial`/`final`), `Calificacion` (invariantes: un parcial lleva número de 1 a 10 y la final ninguno; valor en la escala de 0 a 10), `RepositorioDeCalificaciones` (`guardarNueva`, `corregirValor`). `RepositorioDeCriterios` gana `idVigenteParaMateria()` (el propio vigente o, si no hay, el general: DD-47).
- **Aplicación:** `RegistrarCalificacion`, `CorregirCalificacion`, `ListarCalificaciones`; el puerto `ConsultaDeCalificaciones` (inscripción y calificación **del docente**, con `materiaId`, `jugadorId` y si el jugador está en baja; lectura con su criterio; listado); `CalificacionLeida`, `InscripcionDelDocente`; `ComprobarInscripcionDelDocente` y `ComprobarCalificacionDelDocente` (para el 404 en `authorize()`); excepciones `CalificacionDuplicada` (409) y `JugadorEnBajaSinCaptura` (409 `jugador_en_baja`).
- **Infraestructura:** `RepositorioDeCalificacionesEloquent` (crear y corregir con historial e instante únicos, `lockForUpdate`, índice único → `calificacion_duplicada`), `ConsultaDeCalificacionesSql`, enlaces en `AcademicoServiceProvider`.
- **HTTP:** `CalificacionController`, `SolicitudDeNuevaCalificacion`, `SolicitudDeCorreccionDeCalificacion` (404 antes de validar; cualquier campo distinto de `valor` → `prohibited`), `CalificacionRecurso`, tres rutas en `routes/api.php` dentro de `rol:docente`, `FormatoDeError`, `lang/es/errores.php`, `docs/api/openapi.yaml`.
- **Pruebas:** `tests/Feature/Academico/CalificacionesTest.php`, `tests/Unit/Academico/CalificacionTest.php`, ampliar `ContratoDeAcademicoTest.php`.

## Enfoque

- **Pertenencia (DD-50):** inscripción → materia con `docente_id` = usuario, sin consultar jugadores. **Baja (DD-52):** la inscripción del docente que no pasa `AlcanceDeJugadores::restringir()` sin `incluirBajas` es de un jugador en baja. No hace falta una lectura nueva de jugadores y la lista cerrada no crece.
- El caso de uso no recalcula: publica `CalificacionRegistrada` (`afterCommit`) al crear y al corregir con cambio real.
- Orden de comprobaciones: 404 (solicitud) → 422 de formato (solicitud) → 422 `calificacion_fuera_de_rango` → 409 `jugador_en_baja` → 409 `calificacion_duplicada`.
- Listado: parciales por número y la final al último, paginado (`pagina`, `por_pagina`).

## Decisiones propias previstas (DA)

- **DA-12:** el texto de `jugador_en_baja` se generaliza para cubrir la captura («…no se puede asignar, inscribir ni capturar calificaciones o asistencias.»). El código no cambia. Se ajusta el texto esperado en dos pruebas, sin debilitarlas.
- **DA-13:** corregir (`PATCH`) la calificación de un jugador en baja también da 409 (DD-52: «la baja detiene la captura»; es la opción más estricta).
- **DA-14:** consultar (`GET`) las calificaciones de un jugador en baja sí se permite (DD-52: sigue visible para quien ya lo tenía).

## Dudas

Ninguna que obligue a detenerse.
