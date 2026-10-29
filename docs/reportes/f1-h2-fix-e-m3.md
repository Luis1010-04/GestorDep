# Reporte F1-H2-FIX: M-3 de la auditoría del Lote E (recorrido de seguridad con alumnos compartidos)

**Commit:** `6804a9f` (`F1-H2-FIX: E M-3 recorrido de seguridad con alumnos compartidos`); este reporte va aparte, en un commit F1-DOC. **Origen:** `f1-h2-auditoria-lote-e.md`, M-3, y la instrucción del responsable del 28 de octubre de 2026. **Solo pruebas:** no cambia código de producción.

## Hallazgo

`AcademicoSeguridadTest` solo atacaba con D2 recursos de I1, la inscripción de un alumno (J1) que D2 no tiene. Faltaban en el recorrido de §0.5.4 los casos en que el atacante **sí** tiene al jugador en su alcance y solo la pertenencia de la materia lo separa del recurso:

- D2, docente de J3 en M2, sobre I3a (J3 en M1, de D1): GET de calificaciones, PATCH de una calificación y PATCH de una asistencia.
- DE, docente de J1 en M3, sobre I1 (J1 en M1, de D1).

## Cambio

En `tests/Feature/Seguridad/AcademicoSeguridadTest.php`:

- El conjunto de datos `rutas de una inscripción de H2` tiene las seis rutas cuyo recurso es una inscripción, una calificación o una asistencia: GET y POST de calificaciones, `PATCH /calificaciones/{calificacion}`, GET y POST de asistencias y `PATCH /asistencias-clase/{asistencia}`. Cada ruta es una prueba propia, para que un fallo señale la ruta.
- **`RNF-01 (§0.5.4): D2, docente de J3 en M2, recibe un 404 byte a byte idéntico sobre I3a`** (6 casos). Siembra una final y una asistencia en I3a. Como precondición, D2 ve a J3 (`GET /jugadores/{J3}` da 200). En cada ruta intenta con todos los cuerpos válidos e inválidos del recorrido existente y exige:
  - el 404 byte a byte igual al de una materia inexistente;
  - ninguna fila nueva;
  - la calificación y la asistencia sin cambios.
- **`RNF-01 (§0.5.4): DE, docente de J1 en M3, recibe un 404 byte a byte idéntico sobre I1`** (6 casos). Usa el mundo de §7.0 con el equipo ED y la materia M3. Como precondición, DE ve su propia inscripción I1m3 (200). Lo demás es igual que en la prueba de D2.
- Tres ayudantes locales del archivo: `seguridadFilasAcademicas`, `seguridadAtacarConCadaCuerpo` y el conjunto de datos. Reutilizan `seguridadRutasConRecurso`, que no cambia. Las pruebas existentes no se tocaron.

El conjunto cubre además el POST de calificaciones y asistencias de I3a. Lo pedido (GET de calificaciones, PATCH de calificación y PATCH de asistencia) está incluido.

## Evidencia

Con el código actual, las 12 pruebas nuevas pasan (`Tests: 17 passed (450 assertions)` en el archivo).

Las tres rutas de D2 y las de DE dependen del mismo filtro: `ConsultaDeInscripcionesSql::delDocente`, `->where('materias.docente_id', …)` (línea 66). `ComprobarInscripcionDelDocente`, `ComprobarCalificacionDelDocente` y `ComprobarAsistenciaDelDocente` resuelven la pertenencia ahí. La mutación quitó esa línea en una copia temporal, que se restauró después (`git status` limpio en `src/`):

```
MUT sin el filtro materias.docente_id en delDocente
  GET /inscripciones/{inscripcion}/calificaciones con []                         Failed asserting that 200 is identical to 404.
  POST /inscripciones/{inscripcion}/calificaciones con {"tipo":"final",...}      Failed asserting that 409 is identical to 404.
  PATCH /calificaciones/{calificacion} con {"valor":"9.00"}                      Failed asserting that 200 is identical to 404.
  GET /inscripciones/{inscripcion}/asistencias con []                            Failed asserting that 200 is identical to 404.
  POST /inscripciones/{inscripcion}/asistencias con {"estado":"falta"}           Failed asserting that 201 is identical to 404.
  PATCH /asistencias-clase/{asistencia} con {"estado":"falta"}                   Failed asserting that 200 is identical to 404.
  (las mismas seis líneas para DE sobre I1)
  Tests:    12 failed (49 assertions)
```

El 409 del POST de calificaciones es `calificacion_duplicada`: la mutación deja llegar al caso de uso, que choca con la final sembrada. Sigue siendo una fuga (revela que existe la final) y la prueba la detecta.

## `make verificar`

### Corrida válida (repetición autorizada por el responsable)

Antes de lanzarla:

- **Procesos:** en `app`, `worker` y `scheduler` solo estaban los permanentes (`tail -f /dev/null`, `queue:work --tries=3`, `schedule:work`), sin `pest`, `composer`, `phpstan` ni `infection`. En `db`, una única sesión en reposo del `worker` sobre la base de desarrollo `gestordep`. En el anfitrión (`tasklist`), ningún proceso `php`, `composer`, `phpstan` ni `infection`.
- **Base de pruebas:** `phpunit.xml` fuerza `DB_DATABASE=gestordep_pruebas` (`env` y `server`, `force="true"`) y el contenedor `app` usa `DB_DATABASE=gestordep`. Son distintas.
- **`docker stats`:** en reposo, todos los contenedores estaban por debajo del 1 %. Hubo picos aislados y periódicos (`worker` 7,62 % y 23,81 %; `app` 6,05 % junto con `db` 11,69 %). Son el sondeo de `queue:work` (0 trabajos en cola, 0 fallidos, 0 reinicios) y los `healthcheck` de Docker cada 5 s: `app` abre una conexión PDO a la base de desarrollo y `db` ejecuta `pg_isready`. Es la misma carga de fondo de todas las corridas anteriores.

Resultado, una corrida completa sin nada más corriendo:

- Pint: `PASS ... 407 files`.
- PHPStan nivel 7: sin errores.
- Infection: 362 eliminados; MSI 100 %; MSI cubierto 100 %; ninguno omitido (`Time: 1m 41s`).
- Pest: **909 pruebas** (5535 aserciones), `Duration: 559.34s`, sin fallos.
- Cobertura: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,7 %.
- `composer audit`: sin avisos.
- **Total: 752 s (12 min 32 s).** Pasa, por debajo de los 15 min de §0.4, aunque más lenta que las corridas habituales (7 a 9 min): Pest tardó 559 s frente a 380 a 440 s.

### Corrida fallida anterior (descartada; contaminada)

La primera corrida de este FIX en esta sesión **falló**: `Tests: 570 failed, 339 passed (1191 assertions)`, código 2, **1641 s (27 min 21 s)**, por encima del límite de 15 min. No se hizo commit y el responsable autorizó una sola repetición. Al lanzarla, el contenedor `app` ya estaba al 76,59 % de CPU sin que hubiera ningún proceso visible después, y PHPStan tardó 446 s, cuando normalmente tarda de 2 a 3. Todos los fallos son de esquema en la base de pruebas `gestordep_pruebas`: otro proceso estaba creando y borrando tablas en ella al mismo tiempo.

**Causa probable.** Un intento previo de lanzar `make verificar` en esa misma sesión falló al instante porque el archivo de registro apuntaba a una carpeta inexistente. El cliente de `docker compose exec` murió, pero `composer verificar` siguió corriendo dentro del contenedor, porque Docker no termina el proceso interno cuando muere el cliente. Ambas corridas compartieron la base de pruebas. No se pudo confirmar: al terminar no quedaba ningún proceso de prueba vivo.

Las tres primeras causas distintas de fallo, con su mensaje completo:

1. `MigracionesDeAcademicoTest` (`QueryException`):
   ```
   SQLSTATE[42P01]: Undefined table: 7 ERROR:  relation "migrations" does not exist
   LINE 1: insert into "migrations" ("migration", "batch") values ($1, ...
                       ^ (Connection: propietario, Host: db, Port: 5432, Database: gestordep_pruebas, SQL: insert into "migrations" ("migration", "batch") values (2026_10_21_020300_crear_tabla_jugadores, 1))
   ```
   La tabla `migrations` desapareció entre la creación de una tabla y el registro de su migración. Se repitió 337 veces más 9 variantes con `at character`.
2. `MigracionesDeAcademicoTest` (`QueryException`):
   ```
   SQLSTATE[42P07]: Duplicate table: 7 ERROR:  relation "trabajos" already exists (Connection: propietario, Host: db, Port: 5432, Database: gestordep_pruebas, SQL: create table "trabajos" ("id" bigserial not null primary key, "queue" varchar(255) not null, "payload" text not null, "attempts" smallint not null, "reserved_at" integer null, "available_at" integer not null, "created_at" integer not null))
   ```
   Se repitió 141 veces; `trabajos_fallidos`, 11 veces.
3. ```
   SQLSTATE[42P07]: Duplicate table: 7 ERROR:  relation "sessions" already exists (Connection: propietario, Host: db, Port: 5432, Database: gestordep_pruebas, SQL: create table "sessions" ("id" varchar(255) not null, "user_id" uuid null, "ip_address" varchar(45) null, "user_agent" text null, "payload" text not null, "last_activity" integer not null))
   ```
   Se repitió 4 veces.

En el resto de los fallos aparecen `relation "usuarios" does not exist` (19), choques de unicidad en `uq_usuarios_correo` (4) y en el catálogo `pg_type_typname_nsp_index` (4): todos son síntomas de dos reconstrucciones del esquema a la vez.

## Desviaciones

- Ninguna.
