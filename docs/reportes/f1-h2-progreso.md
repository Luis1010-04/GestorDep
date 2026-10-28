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
