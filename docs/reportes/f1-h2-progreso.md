# Progreso de H2

| Tarea | Estado | Commit | `make verificar` |
|---|---|---|---|
| T01 Calidad (PHPStan 7 e Infection) | hecha | `70ceea1` | pasa; 447 pruebas; 6 min 11 s |
| T02 `NumeroDecimal` | hecha | `2aca9ed` | pasa; 499 pruebas; 5 min 41 s |
| T03 Migraciones y seeders | hecha | `c84f4c5` | pasa; 530 pruebas; 8 min 45 s |
| T04 Dominio del riesgo | hecha | `5db7870` | pasa; 596 pruebas; 9 min 17 s sin carga ajena (la corrida con carga paralela tardó 31 min 04 s; ver `f1-h2-medicion-verificar.md`) |
| T05 Periodos escolares | hecha | `3a82618` | pasa; 623 pruebas; 11 min 49 s |
| T06 Asignaturas | hecha | `f58b835` | pasa; 643 pruebas; 12 min 50 s |
| T07 Materias | hecha | `d4c9bcf` | pasa; 663 pruebas; 13 min 57 s |
| FIX reinicio de pruebas por `DELETE` | hecho | `e3e02be` | pasa; 667 pruebas; 7 min 5 s (`f1-h2-fix-04.md`) |
| FIX lotería de sesiones (PT-05) | hecho | `143474e` | pasa; 669 pruebas; 6 min 58 s (`f1-h2-fix-02.md`) |
| FIX UUID que no es v7 en `DELETE /categorias/{id}` | hecho | `752e4eb` | pasa; 676 pruebas; 6 min 20 s (`f1-h2-fix-05.md`) |
| T08 Alcance del docente | hecha | `1a7b472` | pasa; 701 pruebas; 6 min 0 s (una primera corrida lenta tardó 17 min 16 s por una ralentización transitoria; ver `f1-h2-t08.md`) |
| T09 Recálculo del resumen académico | hecha | `324a977` | pasa; 720 pruebas; 5 min 51 s |
| T10 Inscripciones | hecha (después de T11) | `952150b` | pasa; 776 pruebas; 11 min 7 s (primera corrida tras reiniciar el equipo, en frío) |
| T11 Criterio por materia | hecha | `865c75f` | pasa; 749 pruebas; 6 min 15 s |
| FIX `verificar` falla si Infection omite mutantes por tiempo | hecho | `aff1240` | pasa; 783 pruebas; 6 min 21 s (`f1-h2-fix-06.md`) |
| FIX pruebas de privacidad de la auditoría de C2 (M-4, M-5, M-7) | hecho | `8e084ae` | pasa; 785 pruebas; 7 min 0 s, que es también la medición antes de T12 (`f1-h2-fix-07.md`, `f1-h2-medicion-verificar.md`) |
| T12 Calificaciones | hecha | `74226f4` | pasa; 836 pruebas; 8 min 31 s |
| T13 Asistencia a clase | hecha | `6184d2f` | pasa; 860 pruebas; 7 min 49 s |
| T14 Situación académica | hecha | `859984a` | pasa; 889 pruebas; 8 min 9 s |
| FIX dependencias: `symfony/mime` por CVE-2026-45070 | hecho | `9d931b6` | pasa; 889 pruebas; 9 min 39 s (`f1-h2-fix-dependencias.md`) |
| FIX M-3 (la materia muestra el catálogo eliminado por la API) | hecho | `1982976` | pasa; 889 pruebas; 9 min 7 s (`f1-h2-fix-m3.md`) |
| FIX M-6 (§7.6 fila 9 después del commit) | hecho | `b9aa299` | pasa; 889 pruebas; 8 min 35 s (`f1-h2-fix-m6.md`) |
| FIX M-10 (configuración de mutación) | hecho | `bc9d01f` | pasa; 892 pruebas; 8 min 8 s (`f1-h2-fix-m10.md`) |
| T15 Humo de H2 y cierre documental | hecha | ver `git log` | pasa; 897 pruebas; 8 min 22 s (`f1-h2-reporte.md`) |

## Correcciones de la auditoría del Lote E — completas salvo I-1 e I-2 (en espera del responsable)

Instrucción del responsable del 28/10/2026 (sobre `1145c77`): primero los FIX, un commit por hallazgo, cada uno con `make verificar` completo sin nada más corriendo; después la documentación (F1-DOC). I-1 e I-2 esperan la decisión del responsable: no se tocan. Sin push, PR ni etiqueta. **No hay ningún commit todavía:** todo lo de abajo está sin confirmar en el árbol de trabajo.

| # | Hallazgo | Estado | Archivos o commit |
|---|---|---|---|
| 1 | M-3: recorrido de seguridad con alumnos compartidos | **Hecho.** `6804a9f` (F1-H2-FIX) y reporte `5ceaa73` (F1-DOC). La primera corrida de `make verificar` de esta sesión falló contaminada (570 fallos, 27 min 21 s); la repetición autorizada pasó: 909 pruebas, 12 min 32 s | — |
| 2 | M-7: quitar los `->skip()` condicionales | **Hecho.** `632611f` (F1-H2-FIX) y reporte `d91c702` (F1-DOC). `make verificar`: 909 pruebas, 7 min 49 s | — |
| 3 | M-1: prueba de `phpstan.neon` | **Hecho.** `ad8738b` (F1-H2-FIX) y reporte `6a6de06` (F1-DOC). `make verificar`: 912 pruebas, 7 min 53 s | — |
| 4 | PT-02: `verificar-cobertura.php` rechaza un clover viejo | **Hecho** (opción A aprobada por el responsable). `b010636` (F1-H2-FIX) y reporte `d60ef54` (F1-DOC). `make verificar`: 917 pruebas, 8 min 6 s; el HTML sigue en `build/coverage/` y nada más lee el clover | — |
| 5 | M-2: RNF-11 | **Hecho**: trazabilidad (ya con `RNF-11 (M-1)`) y punto 2 de la compuerta en `f1-h2-reporte.md` | F1-DOC final |
| 6 | M-4: DA-16 | **Hecho** (DA-16 en el archivo de decisiones y en el registro §4; referencia de DD-45 corregida) | `docs/reportes/f1-h2-decisiones-del-agente.md`, `docs/decisiones/registro-decisiones.md` |
| 7 | M-5: `f1-h2.md` v1.1 | **Hecho** (cabecera y sección 12 «Control de cambios») | `docs/hitos/f1-h2.md` |
| 8 | M-6: plazos vencidos | **Hecho**: registro (DD-20, AB-01, AB-08, AB-10, AB-14, PT-01 y PT-03; PT-02 resuelto) y sección 8 del reporte | F1-DOC final |
| 9 | Reporte §3: hashes del Lote E | **Hecho**: commits 1 a 18 en §3 de `f1-h2-reporte.md`; el 18, el F1-DOC final, como «ver `git log -1`» | F1-DOC final |
| 10 | I-1 e I-2 | En espera de la decisión del responsable | — |

**Resultado:** M-3, M-7, M-1 y PT-02 confirmados, cada uno con su `make verificar` completo (la primera corrida de M-3 se descartó por contaminación; ver `f1-h2-fix-e-m3.md`). Documentación de los puntos 5 a 9 en el commit F1-DOC final. PT-07 (lock de `make verificar` y `composer` huérfano) registrado en `registro-decisiones.md` §3, sin implementar. La observación sobre DD-20 se aplicó en el registro.

## Resumen del FIX de la deuda de C2 y del lote E (T15) — completo; queda la auditoría del lote E

- **Deuda de C2:** M-3 `1982976`, M-6 `b9aa299` y M-10 `bc9d01f`, solo pruebas y sin cambios de comportamiento (plan `f1-h2-plan-fix-deuda-c2.md`, un reporte por hallazgo, `make verificar` completo en cada uno: 9 min 7 s, 8 min 35 s y 8 min 8 s). Las pruebas endurecidas se demostraron con mutaciones.
- **T15:** humo de H2 con los 16 pasos de §9 (`HUMO H2 OK: 16 pasos`), segundo docente de demostración, `GuionDeHumoTest`, trazabilidad, registro de decisiones (DD-42 a DD-55, AB-22 a AB-27 y las DA en §4; DA-15 pendiente de confirmación; M-8 como comportamiento exigido por DD-52) y reporte de cierre `f1-h2-reporte.md` con la compuerta §10 punto por punto.
- **Medición final de `make verificar`** (sin nada más corriendo): **502 s (8 min 22 s)**, 897 pruebas, cobertura 100 / 100 / 99,7 %, 362 mutantes eliminados.
- **Compuerta:** puntos 2 a 9 cumplidos; el 1, cumplido en local y pendiente de comprobar en CI (sin push); el 10, pendiente de la auditoría del lote E; el 11, del responsable.

## Resumen del lote D (T12, T13, T14) — completo. No se inició T15

- **Commits:** antes del lote, `e30e54f` (F1-DOC: auditoría de C2, decisiones del responsable y evidencia de T05 a T07) y `8e084ae` (F1-H2-FIX: pruebas de privacidad M-4, M-5 y M-7). Lote: T12 `74226f4`, T13 `6184d2f`, T14 `859984a`.
- **Medición antes de T12** (sin nada más corriendo, por etapas): **420 s (7 min 0 s)**, 785 pruebas (`f1-h2-medicion-verificar.md`).
- **`make verificar` por tarea** (una corrida cada una, todas pasaron a la primera): T12 **511 s** (836 pruebas), T13 **469 s** (860), T14 **489 s** (889). Ninguna pasó de 15 min; la más lenta dejó 6 min 29 s de holgura.
- **Cobertura por capa (T14):** Dominio 100,0 % (umbral 95), Aplicación 100,0 % (85), Infraestructura y HTTP 99,7 % (70).
- **Mutación (Infection, solo `Dominio/`):** 362 mutantes eliminados, MSI 100 %, MSI cubierto 100 %, ninguno omitido ni con tiempo agotado.
- **PHPStan nivel 7:** sin errores ni línea base en las tres corridas.
- **Rutas nuevas** (todas en `openapi.yaml`, validadas contra respuestas reales en `ContratoDeAcademicoTest`):
  - `GET`/`POST /inscripciones/{inscripcion}/calificaciones` y `PATCH /calificaciones/{calificacion}`;
  - `GET`/`POST /inscripciones/{inscripcion}/asistencias` y `PATCH /asistencias-clase/{asistencia}`;
  - `GET /jugadores/{jugador}/situacion-academica`.
- **Pruebas primero:** cada reporte pega el fallo real antes de implementar. Las pruebas de privacidad que esperan 404, que pasan solas sin ruta, se demostraron **con mutaciones** en copias temporales (filtro por docente, alcance del jugador, «docente del jugador»): todas fallaron por la razón correcta.
- **Recorrido de seguridad de H2** (`tests/Feature/Seguridad/AcademicoSeguridadTest.php`): con cada ruta nueva prueba un recurso ajeno con cuerpos válidos e inválidos, ids inexistentes y v4 (404 byte a byte), el rol equivocado (403 idéntico) y sin sesión (401).
- **Decisiones del agente nuevas** (`f1-h2-decisiones-del-agente.md`):
  - **DA-12:** texto de `jugador_en_baja` generalizado para la captura; el código no cambia; se ajustó la cadena exacta en una prueba de H1 y una de T10.
  - **DA-13:** corregir la calificación o la asistencia de un jugador en baja también da 409.
  - **DA-14 (lectura, para revisión):** el docente dueño sigue viendo calificaciones y asistencias de un alumno ya en baja.
  - **DA-15 (lectura, restrictiva, para revisión):** en la situación, `materias` del docente solo del periodo vigente.
- **Decisión de ubicación (T14):** la situación académica vive en `src/Academico` y no en `src/Consultas`, porque necesita el dominio de T04 y la arquitectura prohíbe que Consultas importe dominio ajeno. Está dentro de las rutas base.
- **Rutas fuera de la especificación en el lote:** ninguna. Todas dentro de las rutas base: `src/Academico`, `app/Http`, `FormatoDeError`, `routes/api.php`, `lang/es`, `openapi.yaml`, `tests/**` y reportes. La lista cerrada de `AlcanceDeJugadores` no creció.
- **Pendiente para el responsable:**
  1. Revisar DA-12 a DA-15, en especial DA-14 y DA-15, que afectan lecturas.
  2. El `F1-H2-FIX` único con la deuda de C2 (M-3, M-6, M-10) antes de T15.
  3. Para T15: registrar en el reporte de cierre que T08 tocó `openapi.yaml` (auditoría de C2) y M-8 en DD-52.
  4. La autorización para iniciar el Lote E (T15) y su auditoría.

## Auditoría de C2 y decisiones del responsable

Auditoría aceptada sin bloqueantes (`f1-h2-auditoria-lote-c2.md`: 0 bloqueantes, 1 importante, 10 menores).

1. **I-1:** la fila 6 de §7.1 se corrigió a «P2, P1, P0» en la especificación (lo hizo el responsable); la implementación ya era la correcta. En `f1-h2-t05.md` queda anotado que debí detenerme y anotarlo (§0.4).
2. **DA-11 confirmada:** `jugador_no_registrado` con 404 (§6.3).
3. **T08 tocó `docs/api/openapi.yaml`** (solo descripciones), ruta que no tenía permitida. Se acepta como omisión de la especificación. **Pendiente para T15:** registrarlo en la lista de rutas fuera de la especificación de `f1-h2-reporte.md`.
4. **Evidencia de «pruebas primero» de T05 a T07** completada con el fallo real que demostró el auditor (`f1-h2-t05.md`, `f1-h2-t06.md`, `f1-h2-t07.md`).

### Deuda de la auditoría (un único `F1-H2-FIX` antes de T15)

Los que tocan privacidad se corrigen de inmediato, antes del Lote D, en un `F1-H2-FIX` propio: **M-4, M-5 y M-7**. M-8 es comportamiento exigido por DD-52: no lleva cambio de código y se registra en T15. El resto queda para el FIX único antes de T15:

| # | Hallazgo | Privacidad | Estado |
|---|---|---|---|
| M-1 | Evidencia de «pruebas primero» de T05 a T07 incompleta | no | Resuelto con documentación (punto 4) |
| M-2 | T08 tocó `openapi.yaml` | no | Aceptado; registrado en las rutas fuera de la especificación de `f1-h2-reporte.md` (T15) |
| M-3 | §7.1 fila 8 y §7.2 fila 6 comprueban «la materia lo sigue mostrando» por `join` y no por la API | no | Resuelto (`1982976`, `f1-h2-fix-m3.md`) |
| M-4 | §7.4 fila 9 inscribe además a J4 en M3: no prueba la rama del entrenador del usuario con dos roles | **sí** | Resuelto (`f1-h2-fix-07.md`) |
| M-5 | 404 comparados solo por código: `GET` de §7.5 fila 6 y DA-10 | **sí** | Resuelto (`f1-h2-fix-07.md`) |
| M-6 | §7.6 fila 9 no prueba «después del commit» por sí sola | no | Resuelto (`b9aa299`, `f1-h2-fix-m6.md`) |
| M-7 | La prueba de arquitectura del localizador no detecta el adaptador ni el nombre en cadenas | **sí** | Resuelto (`f1-h2-fix-07.md`) |
| M-8 | El 409 `jugador_en_baja` al inscribir revela a un docente que una matrícula está en baja | **sí**, pero lo exige DD-52 | Sin cambio de código; registrado en DD-52 y AB-26 (`registro-decisiones.md`, T15) |
| M-9 | Holgura de tiempo de `verificar` menor que la informada | no | Medido antes de T12: 7 min 0 s (`f1-h2-medicion-verificar.md`); el límite de 15 min detiene el lote |
| M-10 | `verificar-mutacion.php` no comprueba la vigencia del resumen; `Errored`/`Ignored` no fallan; `composer.lock` no listado | no | Resuelto (`bc9d01f`, `f1-h2-fix-m10.md`; la parte (c) se registra en `f1-h2-reporte.md`) |

## Resumen del lote C2 (T08, T09, T10, T11) — completo; T11 se hizo antes que T10

T10 estuvo bloqueada por una condición de §0.4 (la búsqueda por matrícula antes de tener alcance) hasta que el responsable aprobó la opción A (puerto `LocalizadorDeJugadores` en Compartido y adaptador en Plantel). Como T11 no depende de T10, se hizo primero. **No se inició el Lote D.** Queda pendiente la auditoría independiente de C2 (§0.5).

- **Commits:** T08 `1a7b472`, T09 `324a977`, T11 `865c75f`, T10 `952150b`; los `F1-H2-FIX` `e3e02be` (reinicio por `DELETE`), `143474e` (PT-05), `752e4eb` (UUID no v7) y `aff1240` (Infection); y los `F1-DOC` de documentación.
- **`make verificar` (compuerta de cada tarea):** T08 6 min 0 s (701 pruebas; una primera corrida lenta de 17 min 16 s por una ralentización transitoria, repetida en 6 min), T09 5 min 51 s (720), T11 6 min 15 s (749), T10 11 min 7 s (776; primera corrida tras apagar el equipo y levantar Docker), FIX de Infection 6 min 21 s (783).
- **Medición por etapas al cierre del lote** (una corrida, sin otros procesos, 783 pruebas): Pint 6 s, PHPStan 3 s, Infection 60 s, verificación de mutación 1 s, Pest con cobertura 272 s (Pest informa 260,5 s), cobertura 1 s, auditoría 1 s. **Total 344 s (5 min 44 s)**, con 9 min 16 s de holgura frente a los 15 min de §0.4.
- **Cobertura por capa:** Dominio 100,0 % (umbral 95), Aplicación 100,0 % (85), Infraestructura y HTTP 99,8 % (70).
- **Mutación (Infection, solo `Dominio/`):** 335 mutantes eliminados, MSI 100 %, MSI cubierto 100 %, y ahora `verificar` falla si alguno se omite o agota el tiempo.
- **Rutas nuevas del lote:** `PUT /materias/{materia}/criterio`, `GET` y `POST /materias/{materia}/inscripciones`. Cambia el contenido de `GET /materias` y `GET /materias/{materia}` (`criterio_aplicado` con el criterio propio). Todas documentadas en `openapi.yaml` y validadas contra respuestas reales. T08 solo actualizó descripciones de rutas de H1.
- **Comando y planificador:** `alertas:recalcular` (diario 02:00 America/Mexico_City; sin texto en consola porque T09 no permitía `lang/es`).
- **Decisiones del agente y de permisos:** **DA-10** (escrituras de plantel solo con alcance de entrenador; aprobada) y **DA-11** (el 404 de matrícula inexistente usa `jugador_no_registrado` de la especificación, no el cuerpo de H1 que mencionó la instrucción; **requiere tu confirmación**, cambiarlo es una línea). Ninguna otra decisión cambia privacidad o permisos.
- **Para la auditoría:** `estatus_deportivo` y `estatus_academico` siguen visibles en el recurso `Jugador` que ve un docente (fila 5 de §7.4, DD-30); `equipos` sale vacío para un docente puro. El puerto `LocalizadorDeJugadores` es una excepción de visibilidad acotada (solo `id` y baja), con prueba de arquitectura; la lista cerrada de `AlcanceDeJugadores` no cambió. El texto de `jugador_en_baja` cambió (H1, solo la prueba de `AsignacionDeJugadoresTest.php:160`).
- **Rutas fuera de la especificación:** solo las autorizadas por el responsable para T10 (`src/Compartido/Aplicacion/**` y `src/Plantel/Infraestructura/**`); `tests/Pest.php` y `tests/Ayudantes/**` dentro de `tests/**`.
- **Variabilidad del entorno:** tres ralentizaciones transitorias (495 s en una etapa de Pest, 17 min 16 s en una corrida de T08 y la primera corrida en frío de T10), sin causa identificada; las repeticiones fueron normales (`f1-h2-t08.md`, «Variabilidad»). Un hallazgo del FIX de Infection: los mutantes «omitidos» son los que tienen un tiempo nominal de pruebas ≥ el límite, no una ejecución lenta del mutante.
- **Pendiente:** confirmar DA-11; decidir la auditoría de C2 (§0.5) y la autorización para iniciar el Lote D.

## Resumen del lote B (T04 a T07)

- **Commits:** T04 `5db7870`, T05 `3a82618`, T06 `f58b835`, T07 `d4c9bcf`; además `dccd059` (medición de `verificar`, `F1-H2-FIX`).
- **`make verificar` por tarea** (sin otros procesos, salvo la primera corrida de T04, contaminada): 9 min 17 s (596 pruebas), 11 min 49 s (623), 12 min 50 s (643), 13 min 57 s (663). Todas pasaron: Pint, PHPStan nivel 7 sin línea base, Infection, Pest, cobertura por capa y auditoría.
- **Cobertura por capa (T07):** Dominio 100,0 % (umbral 95), Aplicación 100,0 % (85), Infraestructura y HTTP 99,9 % (70).
- **Mutación (Infection, solo `Dominio/`):** 323 mutantes eliminados, MSI 100 %; en T06 hubo 5 mutantes sin cobertura (MSI 98 %), resueltos en T07.
- **Rutas nuevas** (todas documentadas en `openapi.yaml` y validadas contra respuestas reales): `GET/POST/DELETE /periodos-escolares`, `GET/POST/DELETE /asignaturas`, `GET/POST /materias`, `GET /materias/{materia}`.
- **Decisiones del agente nuevas:** DA-08 (respuestas del plan de T04, aceptada por el responsable) y DA-09 (orden del listado de materias). Ninguna cambia privacidad ni permisos.
- **Rutas fuera de la especificación:** ninguna en el lote B. `tests/Pest.php` no se tocó.
- **Puntos de atención**
  1. **Tiempo de `verificar`:** 13 min 57 s en T07. **Resuelto** por el FIX `e3e02be`: el reinicio por `DELETE` en lugar de `TRUNCATE` lo baja a ~6 a 7 min (Pest de ~725 s a ~255 a 295 s). Volver a medir al cierre de C2.
  2. **Prueba intermitente de H1** (`ConsultaDeJugadoresTest.php:313`, lotería de sesiones): **resuelta** por el FIX `143474e` (PT-05, `tests/TestCase.php`).
  3. `DELETE /categorias/{id}` (H1) respondía 500 con un UUID válido que no fuera v7: **corregido** en `752e4eb`; las demás rutas de H1 con parámetro ya respondían 404.
  4. En T05 a T07 las clases internas se escribieron antes de ejecutar las pruebas; la evidencia del fallo se obtuvo quitando las rutas (ver cada reporte).

## Resumen del lote A

- **Cobertura por capa** (T03, último estado): Dominio 100,0 % (umbral 95), Aplicación 100,0 % (85), Infraestructura y HTTP 100,0 % (70).
- **Mutación (Infection, solo `Dominio/`, suite `Unit`, con `--with-uncovered`):** 203 mutantes, 203 eliminados; MSI 100 %; MSI cubierto 100 % (mínimos 80 % y 85 %). Evidencia de que falla ante una prueba debilitada: `f1-h2-t01.md`.
- **PHPStan:** nivel 7 sin línea base ni `ignoreErrors`.
- **Decisiones del agente:** DA-01 (rutas de T01 autorizadas por el responsable), DA-02 (adaptador de Pest para Infection y `allow-plugins`), DA-03 (`--with-uncovered` y suite `Unit`), DA-04 (fila 7 de §7.12 en dos niveles), DA-05 (carpeta `alertas/` y columnas de historial), DA-06 (`Identificador` sin `floor`). Ninguna cambia privacidad ni permisos.
- **Rutas fuera de la especificación:** las de DA-01 (autorizadas), `composer.json` `allow-plugins` y `tests/Ayudantes/AdaptadorPestParaInfection.php` (DA-02).

## Pendientes que necesitan una respuesta del responsable

1. **Plan de T04** (`f1-h2-plan-t04.md`): aprobar o corregir, en especial las preguntas 1 a 6.
2. **CI:** `.github/workflows/verificar.yml` debe agregar `bcmath` a `extensions` (composer exige `ext-bcmath`); la ruta no estaba permitida en T02.
3. **Prueba intermitente de H1** (`ConsultaDeJugadoresTest.php:313`, lotería de sesiones): proponer fijar `session.lottery` en `phpunit.xml`.
4. **Tiempo de `verificar`:** 8 min 45 s y creciendo; vigilar el límite de 15 min de §0.4.
5. Reconstruir la imagen de Docker (`make levantar`) para tener `bcmath`.
