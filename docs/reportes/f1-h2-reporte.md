# Reporte de cierre del hito F1-H2: Académico

**Fecha:** 28 de octubre de 2026 · **Rama:** `hito/f1-h2` (desde `f1-h1`) · **Especificación:** `docs/hitos/f1-h2.md` v1.0
**Estado:** construcción terminada. Falta la auditoría independiente del Lote E (§0.5) y la aprobación del responsable (§10 puntos 10 y 11). No se hizo push, PR, etiqueta ni fusión.

## 1. Resumen

Por la API ya se puede:

- dar de alta periodos escolares y asignaturas;
- crear materias e inscribir jugadores por matrícula;
- capturar y corregir calificaciones y asistencias;
- definir el criterio propio de una materia;
- consultar la situación académica (promedio, reprobadas, asistencia y nivel de riesgo), con la lectura simplificada para el entrenador.

Un docente ve solo a los inscritos en sus materias (404 fuera de eso, byte a byte igual al de un recurso inexistente). El resumen se recalcula por eventos en cola y con `alertas:recalcular`, programado a diario a las 02:00 America/Mexico_City.

| Métrica al cierre (`make verificar` de T15) | Valor |
|---|---|
| Pruebas | **897** (5 391 aserciones); H1 cerró con 441 |
| Cobertura por capa | Dominio **100,0 %** (umbral 95) · Aplicación **100,0 %** (85) · Infraestructura y HTTP **99,7 %** (70) |
| Mutación (Infection, solo `src/*/Dominio/`) | **362** mutantes, 362 eliminados; MSI **100 %**, MSI cubierto **100 %** (mínimos 80 y 85); ninguno omitido ni con tiempo agotado |
| PHPStan | Nivel **7**, sin línea base ni `ignoreErrors` |
| `composer audit` | Sin avisos (tras el FIX de `symfony/mime`, CVE-2026-45070) |
| Duración de `make verificar` | **502 s (8 min 22 s)**, medida sin nada más corriendo |
| `make humo` | H0, H1 (14 pasos) y H2 (16 pasos) con OK |

## 2. Medición de `make verificar` al cierre

Una corrida el 28 de octubre de 2026, sobre el árbol de T15, sin nada más corriendo. Antes de empezar, `docker stats` daba app 5,72 %, db 0,65 %, worker 0,24 % y scheduler 0,15 % de CPU.

| Etapa | Hasta | Duración | Resultado |
|---|---:|---:|---|
| Pint | 7 s | 7 s (incluye el arranque de `docker compose exec`) | `PASS ... 407 files` |
| PHPStan nivel 7 | 10 s | 3 s | `[OK] No errors` |
| Infection | 85 s | 75 s (`Time: 1m 13s`) | 362 generados, 362 eliminados; MSI 100 %; MSI cubierto 100 % |
| Verificación de mutación | 85 s | < 1 s | `362 mutantes evaluados, ninguno omitido ni con tiempo agotado` |
| Pest con cobertura | 475 s | 390 s (`Duration: 390.15s`) | `Tests: 897 passed (5391 assertions)` |
| Cobertura por capa | 501 s | 26 s | 100,0 / 100,0 / 99,7 % |
| `composer audit` | 502 s | 1 s | `No security vulnerability advisories found.` |
| **Total** | | **502 s (8 min 22 s)** | **pasa** |

Holgura frente al límite de 15 min de §0.4: 6 min 38 s. Con el mismo código, el entorno mostró variaciones de hasta 1,8 veces (`f1-h2-medicion-verificar.md`). Las corridas de H2 fueron de 5 min 44 s a 13 min 57 s, salvo dos anomalías ya documentadas: la primera de T04, contaminada por lecturas en paralelo (31 min 04 s, repetida en 9 min 17 s), y una de T08 (17 min 16 s, repetida en 6 min 0 s).

## 3. Por tarea

Cada tarea tiene su reporte `docs/reportes/f1-h2-tNN.md` con el detalle completo; aquí va lo esencial.

| Tarea | Commit | Pruebas tras la tarea | `make verificar` | Reporte |
|---|---|---:|---|---|
| T01 PHPStan 7 e Infection | `70ceea1` | 447 | pasa; 6 min 11 s | `f1-h2-t01.md` |
| T02 `NumeroDecimal` | `2aca9ed` | 499 | pasa; 5 min 41 s | `f1-h2-t02.md` |
| T03 Migraciones y seeders | `c84f4c5` | 530 | pasa; 8 min 45 s | `f1-h2-t03.md` |
| T04 Dominio del riesgo (plan `e8d6597`) | `5db7870` | 596 | pasa; 9 min 17 s (primera corrida contaminada: 31 min 04 s) | `f1-h2-t04.md` |
| T05 Periodos escolares | `3a82618` | 623 | pasa; 11 min 49 s | `f1-h2-t05.md` |
| T06 Asignaturas | `f58b835` | 643 | pasa; 12 min 50 s | `f1-h2-t06.md` |
| T07 Materias | `d4c9bcf` | 663 | pasa; 13 min 57 s | `f1-h2-t07.md` |
| T08 Alcance del docente | `1a7b472` | 701 | pasa; 6 min 0 s | `f1-h2-t08.md` |
| T09 Recálculo del resumen | `324a977` | 720 | pasa; 5 min 51 s | `f1-h2-t09.md` |
| T10 Inscripciones | `952150b` | 776 | pasa; 11 min 7 s | `f1-h2-t10.md` |
| T11 Criterio por materia | `865c75f` | 749 | pasa; 6 min 15 s | `f1-h2-t11.md` |
| T12 Calificaciones | `74226f4` | 836 | pasa; 8 min 31 s | `f1-h2-t12.md` |
| T13 Asistencia a clase | `6184d2f` | 860 | pasa; 7 min 49 s | `f1-h2-t13.md` |
| T14 Situación académica | `859984a` | 889 | pasa; 8 min 9 s | `f1-h2-t14.md` |
| T15 Humo y cierre documental | `447adac` | 897 | pasa; 8 min 22 s | este reporte |

**Commits `F1-H2-FIX`** (cada uno con su reporte o su fila en el progreso):

- `a3f0c03`: `bcmath` en CI (DA-07).
- `15df1f6`: carpeta de `parametros_institucionales`.
- `dccd059`, `4bf34d5`: medición y resumen en el progreso.
- `e3e02be`: reinicio de pruebas por `DELETE` (`f1-h2-fix-04.md`).
- `143474e`: lotería de sesiones, PT-05 (`f1-h2-fix-02.md`).
- `752e4eb`: UUID que no es v7 en `DELETE /categorias/{id}` (`f1-h2-fix-05.md`).
- `aff1240`: `verificar` falla si Infection omite mutantes (`f1-h2-fix-06.md`).
- `8e084ae`: pruebas de privacidad M-4, M-5 y M-7 (`f1-h2-fix-07.md`).
- `9d931b6`: `symfony/mime` por CVE-2026-45070 (`f1-h2-fix-dependencias.md`).
- `1982976`: M-3 (`f1-h2-fix-m3.md`).
- `b9aa299`: M-6 (`f1-h2-fix-m6.md`).
- `bc9d01f`: M-10 (`f1-h2-fix-m10.md`).

**Commits del Lote E** (desde el resumen del Lote D, `cc66784`), en orden, con su prefijo:

| # | Hash | Prefijo | Mensaje |
|---|---|---|---|
| 1 | `9d931b6` | F1-H2-FIX | actualiza symfony/mime por CVE-2026-45070 |
| 2 | `2cfabd7` | F1-DOC | reporte del FIX de dependencias (CVE-2026-45070) |
| 3 | `1180f98` | F1-DOC | plan del FIX de la deuda menor de C2 (M-3, M-6, M-10) |
| 4 | `1982976` | F1-H2-FIX | M-3 la materia muestra el periodo y la asignatura eliminados por la API |
| 5 | `b9aa299` | F1-H2-FIX | M-6 la fila 9 de 7.6 comprueba que el evento sale ya confirmado |
| 6 | `bc9d01f` | F1-H2-FIX | M-10 pruebas de la configuracion de mutacion |
| 7 | `447adac` | F1-H2-T15 | humo de H2 y cierre documental |
| 8 | `ae277b7` | F1-DOC | correcciones del reporte de cierre de H2 |
| 9 | (último commit de la rama) | F1-DOC | hash de T15 en el reporte de cierre |

**Hash final de la tarea:** `447adac` (F1-H2-T15), sobre el que se midió `make verificar` (sección 2). Las correcciones de este reporte van en un commit F1-DOC posterior que solo cambia documentación. Un commit no puede contener su propio hash: el hash final de la rama se ve en `git log -1` y se informa en la entrega. Es el commit en el que CI debe quedar en verde (sección 6, punto 1).

### T01 — PHPStan nivel 7 e Infection (`70ceea1`)

- **Archivos:**
  - `phpstan.neon` (nivel 7);
  - `composer.json` y `composer.lock` (Infection en `require-dev`, script `verificar`, `allow-plugins`);
  - `infection.json5`;
  - `tests/Ayudantes/AdaptadorPestParaInfection.php`;
  - 66 errores del nivel 7 corregidos solo con tipos, en `src/*/Infraestructura`, `FormatoDeError` y `tests` (DA-01);
  - pruebas unitarias de dominio de H1.
- **Pruebas:** §7.12 filas 1 y 2. Evidencia de que `verificar` falla ante una prueba de dominio debilitada.
- **No se hizo:** tocar el flujo de CI (no hacía falta para Infection).
- **Desviaciones:** DA-01, DA-02 y DA-03.

### T02 — `NumeroDecimal` (`2aca9ed`)

- **Archivos:** `NumeroDecimal`, `NumeroDecimalInvalido`, `Identificador` (sin `floor`, DA-06), `DecimalComoCadena`, `Dockerfile` (`bcmath`), `composer.json` y `.lock` (`ext-bcmath`).
- **Pruebas:** §7.12 filas 3 a 7 (la 7 en dos niveles, DA-04) y `DecimalesEnDominioTest` (Arch).
- **No se hizo:** reglas de Académico.
- **Desviaciones:** DA-04 y DA-06. `bcmath` en CI quedó para `a3f0c03` (DA-07).

### T03 — Migraciones y seeders (`c84f4c5`)

- **Archivos:** 15 migraciones (grupos 030, 035 y 090), `CriterioGeneralSeeder`, `ParametrosInstitucionalesSeeder` y `DatabaseSeeder`.
- **Pruebas:** §7.11 filas 1 a 15 y 17; seeders idempotentes.
- **No se hizo:** `alertas` ni endpoints. La fila 16 queda para las tareas con casos de uso.
- **Desviaciones:** DA-05. La carpeta `alertas/` se corrigió a `academico/` en `15df1f6`.

### T04 — Dominio del riesgo académico (`5db7870`; plan aprobado `e8d6597`)

- **Archivos:** `AmbitoDeCriterio`, `NivelDeRiesgo`, `CriterioDeAprobacion`, `CalificacionDeMateria`, `MateriaEvaluada`, `ConteoDeAsistencia`, `ParametrosDeRiesgo`, `RiesgoAcademico` y `ResumenAcademico`.
- **Pruebas:** las 66 de §7.9, incluidas las 13 filas de riesgo (las 10 de RN-04, la de 7.495 y las 12 y 13 con parámetros distintos).
- **No se hizo:** lecturas de base de datos o de `config`, ni constantes de umbral.
- **Desviaciones:** DA-08.

### T05 a T07 — Periodos, asignaturas y materias (`3a82618`, `f58b835`, `d4c9bcf`)

- **Archivos:** casos de uso, repositorios, controladores, solicitudes, recursos, `IdentificadorDeRuta` (no v7 → 404), rutas, `FormatoDeError`, `lang/es` y `openapi.yaml`.
- **Pruebas:** §7.1, §7.2 y §7.3, y el contrato.
- **No se hizo:** editar periodos, asignaturas o materias; importación masiva.
- **Desviaciones:**
  - DA-09 (orden de materias).
  - La evidencia de «pruebas primero» se obtuvo retirando rutas. Las pruebas de privacidad que esperan 404 se demostraron después con mutaciones (auditoría de C2, M-1; ver los reportes).
  - En la fila 6 de §7.1, el agente debió detenerse ante la contradicción del texto, corregida después por el responsable (I-1).

### T08 — Alcance del docente (`1a7b472`; plan aprobado)

- **Archivos:**
  - rama del docente en `AlcanceDeJugadoresSql`;
  - parámetro `soloEntrenador` en `AlcanceDeJugadores`;
  - `buscarParaEscribir` y `ComprobarJugadorEscribible` en Plantel;
  - ayudantes de prueba;
  - descripciones en `openapi.yaml`.
- **Pruebas:** §7.4 filas 1 a 14, `EscrituraDeUsuarioConDosRolesTest` y `DisparadoresTrasReinicioTest`.
- **No se hizo:** ampliar la lista cerrada de usos sin alcance; datos deportivos para el docente.
- **Desviaciones:** DA-10 (aprobada). `openapi.yaml` estaba fuera de las rutas de T08 (sección 4).

### T09 — Recálculo del resumen (`324a977`)

- **Archivos:**
  - los cuatro eventos;
  - `PublicadorDeEventos` (`afterCommit`);
  - el escucha `RecalcularResumenAcademico`;
  - `RecalcularResumenDeJugador`, `ConciliarResumenes` y `alertas:recalcular`;
  - la programación en `routes/console.php`.
- **Pruebas:** §7.10 filas 15 a 19, 22 y 24, y §7.12 fila 8.
- **No se hizo:** alertas; texto en consola (T09 no permitía `lang/es`).
- **Desviaciones:** ninguna DA.

### T10 — Inscripciones (`952150b`)

- **Archivos:**
  - el puerto `LocalizadorDeJugadores` en Compartido y su adaptador en Plantel (opción A del responsable);
  - `InscribirJugador` y `ListarInscripciones`;
  - consulta y repositorio;
  - HTTP y `openapi.yaml`.
- **Pruebas:** §7.5 y §7.10 fila 23; Arch del localizador.
- **No se hizo:** quitar inscripciones.
- **Desviaciones:** DA-11 (confirmada).

### T11 — Criterio por materia (`865c75f`)

- **Archivos:** `DefinirCriterioDeMateria`, `EscalaDeCalificaciones`, `CriterioVigente`, el repositorio de criterios (cierre y alta con historial) y HTTP.
- **Pruebas:** §7.6 y §7.10 filas 5 y 6.
- **No se hizo:** editar el criterio general ni volver a «general».
- **Desviaciones:** ninguna DA.

### T12 — Calificaciones (`74226f4`)

- **Archivos:**
  - `Calificacion`, `TipoDeCalificacion`, `RepositorioDeCalificaciones`;
  - registrar, corregir y listar;
  - `ConsultaDeInscripciones::delDocente` (pertenencia y baja sin leer jugadores);
  - HTTP y `openapi.yaml`.
- **Pruebas:** §7.7 filas 1 a 17, §7.10 filas 2, 3, 4 y 7, §7.11 fila 16 y el contrato. Privacidad comprobada con mutación.
- **No se hizo:** borrar calificaciones; recalcular en el caso de uso.
- **Desviaciones:** DA-12, DA-13 y DA-14.

### T13 — Asistencia a clase (`6184d2f`)

- **Archivos:** `AsistenciaDeClase`, `EstadoDeAsistencia`, `RepositorioDeAsistencias`; registrar, corregir y listar; HTTP y `openapi.yaml`.
- **Pruebas:** §7.8 filas 1 a 12, §7.10 fila 8 (porcentaje tras cada captura), §7.11 fila 16 y el contrato. Privacidad comprobada con mutación.
- **No se hizo:** borrar asistencias; incluir la asistencia en el riesgo.
- **Desviaciones:** se aplican DA-13 y DA-14.

### T14 — Situación académica (`859984a`)

- **Archivos:** `VerSituacionAcademica`, `ConsultaDeSituacionAcademica` y su SQL (jugador por `AlcanceDeJugadores` con bajas), el recurso y `openapi.yaml`.
- **Pruebas:**
  - §7.10 filas 1 a 16, 20, 21 y 22 por HTTP;
  - el recorrido de seguridad `AcademicoSeguridadTest`;
  - el contrato.
  - Privacidad comprobada con tres mutaciones.
- **No se hizo:** calificaciones individuales al entrenador; alertas; «posiblemente desactualizado».
- **Desviaciones:**
  - El caso de uso vive en `src/Academico` (rutas base), no en `src/Consultas`: la arquitectura prohíbe a Consultas importar dominio ajeno.
  - DA-15, pendiente de confirmación.

### T15 — Humo de H2 y cierre documental (`447adac`; plan `f1-h2-plan-t15.md`)

- **Archivos:**
  - `scripts/humo/humo-h2.sh` (nuevo; `lib.sh` sin cambios);
  - `database/seeders/UsuariosDemoSeeder.php` (segundo docente, `docente2@example.test`);
  - `Makefile` (una línea en `humo`);
  - `docs/trazabilidad.md`;
  - `docs/decisiones/registro-decisiones.md`;
  - pruebas `tests/Unit/Herramientas/GuionDeHumoTest.php` (nueva) y `tests/Integration/Identidad/UsuariosDemoSeederTest.php` (ampliada);
  - este reporte, el plan y el progreso.
- **Pruebas:**
  - Prueba de consola del seeder (`CONSOLA (F1-H2-T15)`).
  - Prueba del seeder ampliada a 4 usuarios.
  - Mecanismo de parada del humo ante un código o un cuerpo distinto.
  - `humo-h2.sh` con `set -euo pipefail`, cola `sync` y los pasos 1 a 16.
- **Fallo previo:** las del seeder (`Undefined constant … CORREO_DOCENTE_2`, `Failed asserting that 3 is identical to 4`) y la del guion (el archivo no existía). Las tres del mecanismo de parada pasaban desde el inicio porque `lib.sh` es de H1.
- **Evidencia de que el humo falla ante un paso roto:** una segunda corrida sin `make fresh` termina con código 1:

  ```
  OK     paso 2: el docente 1 inicia sesión -> HTTP 200
  FALLA  paso 3: crea un periodo escolar que contiene hoy y sale vigente -> HTTP 409 (se esperaba 201)
         cuerpo: {"codigo":"periodo_duplicado","mensaje":"Ya existe un periodo escolar con ese nombre.","errores":[]}
  segunda EXIT=1
  ```

- **No se hizo:** push, PR ni etiquetas.
- **Fuera de alcance:** ninguno nuevo.
- **Desviaciones:**
  - La credencial del segundo docente se lee en `humo-h2.sh` con `php -r` de la constante del seeder, sin modificar `humo_credencial` de `lib.sh`, que solo admitía agregar funciones.
  - La trazabilidad y el registro de decisiones incluyen las DA pertinentes, a pedido del responsable.

## 4. Rutas tocadas que la especificación no permitía

| Commit | Ruta | Estado |
|---|---|---|
| `70ceea1` (T01) | `app/Exceptions/FormatoDeError.php`, `src/*/Infraestructura/**`, `tests/Feature/**`, `tests/Integration/**`, `tests/Ayudantes/**` (tipos del nivel 7) | Autorizado por el responsable (DA-01) |
| `70ceea1` (T01) | `composer.json` (`allow-plugins`) y `tests/Ayudantes/AdaptadorPestParaInfection.php` | Anotado en DA-02 |
| `70ceea1`, `2aca9ed` (T01, T02) y `9d931b6` (FIX) | `composer.lock` | Consecuencia inevitable de cambios permitidos de `composer.json` o de la actualización autorizada (auditoría de C2, M-10 c) |
| `c84f4c5` (T03) | `database/migrations/alertas/` | Corregido a `academico/` en `15df1f6` por decisión del responsable |
| `a3f0c03` (FIX) | `.github/workflows/verificar.yml` | Autorizado (DA-07) |
| `752e4eb`, `aff1240`, `9d931b6` (FIX) | `CategoriaController.php`; `bin/verificar-mutacion.php`, `composer.json`, `infection.json5`; `composer.lock` | FIX pedidos por el responsable, con su reporte |
| `1a7b472` (T08) | `docs/api/openapi.yaml` (descripciones de rutas de H1) | **Aceptado por el responsable como omisión de la especificación** (auditoría de C2, M-2) |
| `1a7b472` (T08) | Casos de uso de escritura de `src/Plantel/Aplicacion` (más allá de «solo lo que las lecturas necesiten») | Autorizado (DA-10) |
| `952150b` (T10) | `src/Compartido/Aplicacion/**`, `src/Plantel/Infraestructura/**` | Autorizado por el responsable (opción A) |

T05 a T07, T09, T11 a T15 y los FIX de pruebas (`e3e02be`, `143474e`, `8e084ae`, `1982976`, `b9aa299`, `bc9d01f`) no tocaron rutas fuera de las permitidas. T14 no usó `src/Consultas/**` (permitida); trabajó dentro de las rutas base.

## 5. Decisiones del agente (DA-nn)

Detalle en `f1-h2-decisiones-del-agente.md`; registradas en `docs/decisiones/registro-decisiones.md` §4.

- **Herramientas y configuración:** DA-01 a DA-07 (rutas de T01, adaptador de Pest, `--with-uncovered`, fila 7 de §7.12 en dos niveles, carpeta de `parametros_institucionales`, `Identificador` sin `floor`, `bcmath` en CI).
- **DA-08:** respuestas del plan de T04 (aceptada). **DA-09:** orden de materias.
- **DA-10:** escrituras de plantel solo con el alcance de entrenador. Restringe permisos; aprobada.
- **DA-11:** 404 `jugador_no_registrado`; confirmada.
- **DA-12:** texto de `jugador_en_baja` que cubre la captura.
- **DA-13:** corregir con el jugador en baja → 409.
- **DA-14 (lectura):** el docente dueño sigue viendo calificaciones y asistencias de un alumno en baja. **Aceptada en revisión del 28/10; sujeta a confirmación del responsable.**
- **DA-15 (lectura, restrictiva):** la situación lista solo las materias del docente del periodo vigente. **Pendiente de confirmación del responsable** (AB-27).

M-8 de la auditoría de C2 (el 409 al inscribir revela a un docente que una matrícula está en baja) queda registrado como **comportamiento exigido por DD-52**, con su revisión en H7 (AB-26).

## 6. Compuerta de H2 (§10), punto por punto

| # | Punto | Estado | Evidencia |
|---|---|---|---|
| 1 | `make verificar` pasa en local y en la integración continua, con umbrales por capa, PHPStan 7 e Infection (MSI ≥ 80, cubierto ≥ 85) | **Local: cumplido. CI: pendiente; el cierre depende de él** | Local: sección 2. CI: el responsable informó la falla de `composer audit` en `cc66784`, corregida en `9d931b6`; desde entonces no hubo push (regla vigente), así que el estado en CI de los commits posteriores no se ha observado. **El cierre de este punto depende de que CI quede en verde en el commit final de la rama, después del push del responsable** |
| 2 | Cada historia del hito tiene pruebas con su identificador; los diferidos de §2.2 anotados en la trazabilidad | Cumplido, con la nota de RN-12 | `docs/trazabilidad.md`: HU-01, 02, 06, 07, 08, 09 y 28 y RN-01 a 05, 13, 14 y 20 con sus pruebas; «Estado al cierre de H2» con los diferidos de §2.2. **RN-12** no tiene prueba con su identificador (igual que en H1); su parte docente la cubren HU-28, DD-49 y HU-06 |
| 3 | `openapi.yaml` incluye las rutas de §6.1 y las respuestas reales coinciden | Cumplido | Las 19 operaciones de §6.1 están en `openapi.yaml` (comprobado por ruta y método) y en `route:list` (19). `ContratoDeAcademicoTest` valida respuestas reales de todas: 7 pruebas |
| 4 | `make fresh` y `migrate:rollback` sin errores | Cumplido | `make fresh` con código 0 (36 migraciones y 5 seeders). Sobre la base de desarrollo, recién reconstruida: **antes** del rollback, `migrate:status` daba 36 migraciones ejecutadas y 0 pendientes. `migrate:rollback --database=propietario --force` salió con código 0 y **revirtió las 36** (36 líneas `DONE`, ninguna con error; `make fresh` las deja todas en el lote 1). **Después**, `migrate:status` dio 0 ejecutadas y 36 pendientes, y en el esquema solo quedó la tabla `migrations`. `make fresh` las volvió a ejecutar (36 ejecutadas, 0 pendientes). En la suite, §7.11 fila 14 (`MigracionesDeAcademicoTest`) |
| 5 | Las tablas de §7 pasan, una prueba por fila (las diez de RN-04 y las filas 11 a 13) | Cumplido | §7.1 a §7.12 con una prueba por fila (reportes de T01 a T14; contraste fila por fila en la auditoría de C2 para §7.1 a §7.6 y §7.10). Las 13 filas de riesgo de §7.9, entre ellas las diez de RN-04, en `RiesgoAcademicoTest`. Las filas 11 a 13 de §7.9 y de §7.10 tienen prueba propia |
| 6 | `make humo` sin fallas | Cumplido | Tras `make fresh`: `HUMO H0 OK`, `HUMO H1 OK: 14 pasos`, `HUMO H2 OK: 16 pasos`, código 0 (salida en la sección 7) |
| 7 | Criterios propios de H2 | Cumplido | El criterio propio prevalece e indica cuál se aplicó: `CriterioPorMateriaTest`, §7.9 RN-03, humo paso 11. Calificación fuera de 0 a 10 rechazada: §7.7 fila 3, humo paso 15. El docente ve solo a sus alumnos: §7.4, `AcademicoSeguridadTest`, humo paso 13. El entrenador no ve calificaciones individuales: §7.10 fila 9, humo paso 12. Recálculo tras cada captura: §7.10 filas 2 a 8 y 23. `alertas:recalcular` idempotente y programado: §7.10 filas 17 y 24 |
| 8 | El reporte incluye por tarea commit, archivos, pruebas, `verificar`, lo no hecho, fuera de alcance y desviaciones; cobertura y mutación; rutas fuera de la especificación; DA | Cumplido | Secciones 1 a 5 de este reporte y los reportes por tarea |
| 9 | DD-42 a DD-55 registradas y las validaciones pendientes anotadas | Cumplido | `registro-decisiones.md` §1 (DD-42 a DD-55) y §2 (AB-22 a AB-27: RN-01, RN-04, DD-43, DD-44, DD-20, AB-08, la revisión de seguridad de DD-52 y DA-15) |
| 10 | Las dos auditorías independientes entregadas, sin hallazgos bloqueantes abiertos | **Parcial** | Auditoría de C2: `f1-h2-auditoria-lote-c2.md`, 0 bloqueantes. I-1 resuelto en la especificación, M-1 a M-10 resueltos o registrados (`f1-h2-progreso.md`). **Falta la auditoría del Lote E** (§0.5) |
| 11 | El responsable revisa, prueba `make humo` y aprueba; etiqueta `f1-h2` y fusión | **Pendiente del responsable** | No se creó la etiqueta ni se fusionó (regla vigente) |

## 7. Salida completa de `make humo` al cierre

Salida literal de `make humo` (código de salida 0), ejecutado justo después de `make fresh` el 28 de octubre de 2026. Después se volvió a ejecutar `make fresh` para dejar la base de desarrollo limpia.

```
docker compose exec -T app bash -s < scripts/humo/humo-h0.sh
HUMO H0 OK: GET /api/v1/salud -> 200
docker compose exec -T app bash /scripts/humo/humo-h1.sh
OK     paso 1: GET /api/v1/salud -> HTTP 200
OK     paso 2: el entrenador 1 inicia sesión -> HTTP 200
OK     paso 3: crea la categoría Sub-17 -> HTTP 201
OK     paso 4: crea el equipo Tigres Sub-17 -> HTTP 201
OK     paso 4: el equipo aparece en GET /equipos -> HTTP 200
OK     paso 5: registra un jugador en ese equipo -> HTTP 201
OK     paso 6: la misma matrícula responde 409 sin datos del primero -> HTTP 409
OK     paso 7: el entrenador 2 inicia sesión con otro archivo de cookies -> HTTP 200
OK     paso 8: el jugador del entrenador 1 responde 404 al entrenador 2 -> HTTP 404
OK     paso 9: la búsqueda por matrícula devuelve solo nombres, apellidos y matrícula -> HTTP 200
OK     paso 10: el entrenador 2 crea su equipo -> HTTP 201
OK     paso 10: asigna al jugador por matrícula -> HTTP 201
OK     paso 11: GET /jugadores del entrenador 2 devuelve al jugador con solo su equipo -> HTTP 200
OK     paso 12: el docente inicia sesión -> HTTP 200
OK     paso 12: el docente recibe 403 al crear un equipo -> HTTP 403
OK     paso 12: el docente recibe una lista vacía en GET /jugadores -> HTTP 200
OK     paso 13: el entrenador 1 da de baja al jugador -> HTTP 201
OK     paso 13: deja de aparecer en GET /jugadores del entrenador 1 -> HTTP 200
OK     paso 13: deja de aparecer en GET /jugadores del entrenador 2 -> HTTP 200
OK     paso 14: el entrenador 1 cierra sesión -> HTTP 204
OK     paso 14: la misma cookie responde 401 -> HTTP 401
HUMO H1 OK: 14 pasos
docker compose exec -T app bash /scripts/humo/humo-h2.sh
Servidor del guion con QUEUE_CONNECTION=sync
OK     paso 1: GET /api/v1/salud -> HTTP 200
OK     paso 2: el docente 1 inicia sesión -> HTTP 200
OK     paso 3: crea un periodo escolar que contiene hoy y sale vigente -> HTTP 201
OK     paso 4: crea la asignatura MAT-101 -> HTTP 201
OK     paso 5: crea la materia con esa asignatura y ese periodo -> HTTP 201
OK     paso 6: el entrenador 1 inicia sesión (otro archivo de cookies) -> HTTP 200
OK     paso 6: crea su categoría Sub-15 -> HTTP 201
OK     paso 6: crea su equipo -> HTTP 201
OK     paso 6: registra un jugador -> HTTP 201
OK     paso 7: el docente 1 busca la matrícula: solo nombres, apellidos y matrícula -> HTTP 200
OK     paso 7: lo inscribe por matrícula -> HTTP 201
OK     paso 8: captura la calificación parcial 8.50 -> HTTP 201
OK     paso 8: captura la calificación final 8.00 -> HTTP 201
OK     paso 9: captura la asistencia presente del 2026-10-26 -> HTTP 201
OK     paso 9: captura la asistencia presente del 2026-10-27 -> HTTP 201
OK     paso 9: captura la asistencia falta del 2026-10-28 -> HTTP 201
OK     paso 10: situación como docente: 8.00, sin_riesgo, 66.67 y con materias -> HTTP 200
OK     paso 11: define el criterio propio 8.50 -> HTTP 200
OK     paso 11: la situación pasa a 1 reprobada y en_observacion -> HTTP 200
OK     paso 12: el entrenador 1 ve el mismo nivel, sin materias -> HTTP 200
OK     paso 13: el docente 2 inicia sesión -> HTTP 200
OK     paso 13: el docente 2 recibe 404 en la situación -> HTTP 404
OK     paso 13: el docente 2 recibe una lista vacía en GET /jugadores -> HTTP 200
OK     paso 14: el entrenador 1 recibe 403 al crear un periodo -> HTTP 403
OK     paso 14: el docente 1 recibe 403 al crear un equipo -> HTTP 403
OK     paso 15: un valor fuera de rango responde calificacion_fuera_de_rango -> HTTP 422
OK     paso 16: el docente 1 cierra sesión -> HTTP 204
OK     paso 16: la misma cookie responde 401 -> HTTP 401
HUMO H2 OK: 16 pasos
```

## 8. Pendientes que H2 deja

- **Para el responsable:**
  - confirmar DA-15 y DA-14 (esta, aceptada en revisión del 28/10);
  - abrir la auditoría independiente del Lote E (§0.5);
  - hacer el push y comprobar que CI queda en verde en el commit final (condición del punto 1 de la compuerta);
  - aprobar, etiquetar `f1-h2` y fusionar.
- **Para la revisión de seguridad de H7:** AB-17 a AB-21 de H1, AB-26 (M-8 y la captura académica sin bloqueo de la fila del jugador, §11) y PT-04.
- **Con la institución (no bloquean H2):** AB-22 a AB-25 (RN-01, RN-04, DD-43, DD-44, DD-20 y AB-08).
- **Para H6:** `alertas:recalcular` solo concilia resúmenes; H6 agrega las alertas, la clave `alerta_academica_nivel` y la marca «posiblemente desactualizado».
