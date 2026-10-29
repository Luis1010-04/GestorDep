# Plan F1-H2-T03: migraciones de Académico y seeders

**Enfoque**
- Una migración por tabla (arquitectura §8.1), fecha `2026_10_26`, con `up()` y `down()`:
  - `database/migrations/academico/` grupo 030: `periodos_escolares`, `asignaturas`, `materias`, `inscripciones`, `criterios_aprobacion`, `calificaciones`, `asistencias_clase`, `resumenes_academicos`; grupo 090: historiales de `materias`, `inscripciones`, `criterios_aprobacion`, `calificaciones` y `asistencias_clase`.
  - `database/migrations/alertas/` grupo 035: `parametros_institucionales` y, en el 090, su historial. La carpeta es `alertas` porque la arquitectura §7.9 asigna esa tabla a "Alertas y parámetros" (H6 agrega ahí `alertas`); `AppServiceProvider` registra cada subcarpeta con `glob`, sin cambios.
- Restricciones: como §5.1. Los `CHECK` de longitud (`nombre` ≤ 100, `clave` ≤ 20) van aparte porque `citext` no admite longitud. Los índices únicos parciales llevan `WHERE eliminado_en IS NULL`, salvo `parametros_institucionales`, que usa exactamente `WHERE vigente_hasta IS NULL` como pide la especificación. El de calificaciones usa `NULLS NOT DISTINCT` (PostgreSQL 16).
- Columna de entidad en cada historial (la fija `Schema::historial`, igual que en H1): `materia_id`, `inscripcion_id`, `criterio_aprobacion_id`, `calificacion_id`, `asistencia_clase_id`, `parametro_institucional_id`. Las tareas T07 a T13 deben usar estos nombres con `EscritorDeHistorial`.
- Seeders `CriterioGeneralSeeder` (7.00, vigente desde 2026-01-01) y `ParametrosInstitucionalesSeeder` (`riesgo_academico` de §5.3), idempotentes (no pisan una fila vigente existente) y llamados desde `DatabaseSeeder` también en producción. No escriben historial.
- `EsquemaDePrueba` ya reinicia por introspección de `pg_tables`: no necesita cambios para conocer las tablas nuevas; la fila 17 se prueba sin tocar `tests/Pest.php`.

**Archivos**: las migraciones anteriores, los dos seeders, `DatabaseSeeder.php`, `tests/Integration/Academico/MigracionesDeAcademicoTest.php`, reportes.

**Pruebas**: filas 1 a 15 y 17 de §7.11 y los seeders; la fila 14 incluye `migrate:rollback` y `migrate:fresh`.

**Dudas**: ninguna. Las columnas de historial y la carpeta de `parametros_institucionales` son decisiones menores anotadas en DA-05.
