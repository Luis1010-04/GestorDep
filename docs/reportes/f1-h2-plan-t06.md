# Plan F1-H2-T06: asignaturas

Mismo patrón que T05 (categorías de H1): sin historial, borrado lógico.

- **Dominio:** `Asignatura` (datos, sin reglas propias: el rango de `grado` 1 a 12 es validación de entrada, DD-51), `RepositorioDeAsignaturas`.
- **Aplicación:** `CrearAsignatura`, `ListarAsignaturas`, `EliminarAsignatura`, `AsignaturaDuplicada`.
- **Infraestructura:** `RepositorioDeAsignaturasEloquent` y su enlace.
- **HTTP:** `AsignaturaController`, `SolicitudDeNuevaAsignatura`, `AsignaturaRecurso`, rutas bajo `rol:docente`, 409 en `FormatoDeError`, `lang/es`, `openapi.yaml`.
- **Pruebas:** §7.2 filas 1 a 8 y contrato, en `tests/Feature/Academico/AsignaturasTest.php`.
- **Dudas:** ninguna.
