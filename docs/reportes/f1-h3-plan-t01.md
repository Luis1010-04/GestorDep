# Plan de F1-H3-T01: migraciones de Competencia

**Rutas:** `database/migrations/competencia/**`, `tests/Integration/Competencia/**`, reportes.

## Archivos

Grupo 040 (fecha `2026_10_29`, arquitectura §8.1), una migración por tabla, en `database/migrations/competencia/`:

| Archivo | Tabla | Restricciones |
|---|---|---|
| `…_040100_crear_tabla_lugares` | `lugares` (nombre `citext`) | `ck_lugares_nombre` (hasta 100), `uq_lugares_nombre` parcial |
| `…_040200_crear_tabla_rivales` | `rivales` | igual |
| `…_040300_crear_tabla_temporadas` | `temporadas` (nombre, fecha_inicio, fecha_fin) | nombre como arriba; `ck_temporadas_fechas` (`fecha_fin > fecha_inicio`) |
| `…_040400_crear_tabla_torneos` | `torneos` (nombre, temporada_id) | `uq_torneos_nombre_temporada` parcial; FK a `temporadas` |
| `…_040500_crear_tabla_entrenamientos` | `entrenamientos` | FK a `equipos`, `lugares`, `usuarios`; `ck_entrenamientos_horario`; `ck_entrenamientos_estado`; IX (`equipo_id`, `inicia_en`) e IX (`lugar_id`, `inicia_en`) |
| `…_040600_crear_tabla_asistencias_entrenamiento` | `asistencias_entrenamiento` | FK; `ck_…_estado`; UQ parcial (R-2); IX `jugador_id` |
| `…_040700_crear_tabla_partidos` | `partidos` | FK (`torneo_id` nulo); `ck_partidos_tipo`, `_condicion`, `_estado`, `_goles` (0 a 99), `_resultado` (DA-02); IX (`equipo_id`, `inicia_en`); UQ (`id`, `equipo_id`) |
| `…_040800_crear_tabla_convocatorias` | `convocatorias` | FK (`posicion_id` nulo, DA-03); `ck_…_rol_alineacion`; UQ parcial (`partido_id`, `jugador_id`); IX `jugador_id` |
| `…_090901` a `…_090908` | los ocho `{tabla}_historial` | macro `Schema::historial()` (DA-01) |

Todas con `auditoriaBasica()`, claves `restrictOnDelete()` y `down()` con `dropIfExists`. Instantes en `timestampTz`. Sin seeders.

## Pruebas (`tests/Integration/Competencia/MigracionesDeCompetenciaTest.php`)

Patrón de `MigracionesDeAcademicoTest` (inserción como propietario, SQLSTATE esperado):

- RNF-03: nombres de catálogo sin distinguir mayúsculas y hasta 100; UQ parcial liberado tras el borrado lógico (lugares, rivales, temporadas, torneos por temporada).
- RNF-03: `CHECK` de fechas de temporada y de horario de entrenamiento.
- RNF-03: valores cerrados (estados, tipo, condición, rol).
- RNF-03: goles de 0 a 99 y resultado solo en `jugado` (DA-02).
- RNF-03: asistencia y convocatoria duplicadas chocan; la convocatoria eliminada libera su índice.
- RNF-03: UQ (`id`, `equipo_id`) de partidos y claves foráneas sin cascada.
- RNF-02: cada historial es de solo inserción; `EscritorDeHistorial` sobre una tabla de Competencia escribe la entidad y el historial juntos o ninguno.
- RNF-03: `migrate:rollback` y `migrate` de nuevo; `migrate:fresh`; el reinicio rápido de pruebas deja las tablas vacías.

## Dudas

Ninguna abierta tras R-1 y R-2. DA-01 a DA-03 registradas.
