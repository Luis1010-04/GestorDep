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
| T10 Inscripciones | **bloqueada (§0.4)** | (sin commit) | no se implementó; ver `f1-h2-t10.md` |
| T11 Criterio por materia | hecha | `865c75f` | pasa; 749 pruebas; 6 min 15 s |

## PUNTO DE REANUDACIÓN (la sesión se interrumpió a petición del responsable)

**Último estado:** T10 está **implementada pero sin confirmar**: el código y las pruebas están en el árbol de trabajo (sin commit) y `make verificar` de T10 se estaba corriendo cuando se apagó el equipo, por lo que **no hay resultado**. Antes de apagarse: PHPStan sin errores y 294 pruebas verdes en la corrida estrecha (`tests/Feature/Academico`, `tests/Unit/Academico`, `tests/Arch` y `AsignacionDeJugadoresTest`). Último commit: `1dfe522`. No hay `git stash` pendiente.

Opción A de T10 aprobada por el responsable (rutas adicionales solo para T10: `src/Compartido/Aplicacion/**` y `src/Plantel/Infraestructura/**`); condiciones: el puerto devuelve solo identificador y baja; prueba de arquitectura de que solo lo usa `InscribirJugador` sin tocar la lista cerrada; mensaje de `jugador_en_baja` = «El jugador está en baja: no se puede asignar ni inscribir.» (ya cambiado en `lang/es` y en `AsignacionDeJugadoresTest.php:160`).

**Archivos sin commit de T10** (todos bajo `backend/` salvo `docs/api/openapi.yaml`): `app/Http/Controllers/InscripcionController.php`, `app/Http/Requests/SolicitudDeNuevaInscripcion.php`, `app/Http/Resources/InscripcionRecurso.php`, `app/Exceptions/FormatoDeError.php`, `lang/es/errores.php`, `routes/api.php`, `src/Academico/Aplicacion/{ConsultaDeInscripciones,InscribirJugador,InscripcionConJugador,JugadorEnBajaNoInscribible,JugadorNoRegistrado,JugadorYaInscrito,ListarInscripciones}.php`, `src/Academico/Dominio/{Inscripcion,RepositorioDeInscripciones}.php`, `src/Academico/Infraestructura/{ConsultaDeInscripcionesSql,RepositorioDeInscripcionesEloquent,AcademicoServiceProvider}.php`, `src/Compartido/Aplicacion/{JugadorLocalizado,LocalizadorDeJugadores}.php`, `src/Plantel/Infraestructura/{LocalizadorDeJugadoresPlantel,PlantelServiceProvider}.php`, y las pruebas `tests/Feature/Academico/{InscripcionesTest,ContratoDeAcademicoTest}.php`, `tests/Feature/Plantel/AsignacionDeJugadoresTest.php`, `tests/Arch/LocalizadorDeJugadoresTest.php`, `tests/Unit/Academico/InscripcionTest.php`.

**Qué falta, en orden:**
1. Correr `make verificar` (sin otros procesos). Si falla, corregir (PHPStan/Pint se corrigen con `vendor/bin/pint` y revisión de tipos). Si pasa, anotar duración y número de pruebas.
2. **Reescribir `docs/reportes/f1-h2-t10.md`**: hoy describe el bloqueo y está obsoleto. Debe llevar archivos, pruebas (§7.5 filas 1 a 12 y §7.10 fila 23), fallo previo (6 pruebas de 201→404, 5 de 422→404, 2 de 409→404, 2 de 200→404 y las de arquitectura), `verificar`, y registrar **DA-11** (ver abajo). Quitar la fila «bloqueada» de la tabla de arriba y poner el commit.
3. Commit `F1-H2-T10: inscripciones` (con `Co-Authored-By`), y actualizar la fila de T10 de esta tabla.
4. **`F1-H2-FIX`**: `make verificar` debe fallar si Infection omite o descarta mutantes por tiempo, o fijar un tiempo límite explícito que no se alcance. Reportar **antes y después con un mutante forzado a timeout**. Contexto: en una corrida de T08 hubo «50 mutants required more time than configured» (símbolo `S`) y `verificar` pasó igual; `infection.json5` ya tiene `"timeout": 20`.
5. Medir `make verificar` por etapas (`scratchpad/etapas.sh` o equivalente), reescribir el resumen del lote C2 (con T11 hecha antes que T10) y **detenerse: no iniciar el Lote D**. Los commits de documentación llevan `F1-DOC`.

**DA-11 pendiente de escribir:** la instrucción del responsable para T10 dice que la matrícula inexistente da 404 «con los mismos cuerpos que en H1», pero §6.3 y §7.5 fila 3 de la especificación piden `404 jugador_no_registrado` (H1 responde `recurso_no_encontrado`). Se implementó la especificación (`jugador_no_registrado`, formato estándar de error, sin datos de nadie) y `jugador_en_baja` con el mismo código y cuerpo que H1 (texto nuevo). Si el responsable prefiere el cuerpo `recurso_no_encontrado`, el cambio es una línea en `FormatoDeError` (`JugadorNoRegistrado`) y el texto esperado de la prueba de la fila 3. Hay que avisarlo en el reporte.

**Pendientes de decisión del responsable:** ninguno bloqueante, salvo la nota de DA-11. Datos útiles: los scripts de medición están en el directorio temporal de la sesión (`scratchpad/verificar.sh`, `etapas.sh`) y pueden no existir en la nueva sesión; el equivalente es `make verificar` y `docker compose exec -T app vendor/bin/pint` antes.

## Resumen del lote C2 (T08, T09, T11; T10 bloqueada)

**El lote no está completo: T10 no se implementó** (condición de §0.4, `f1-h2-t10.md`). Se hizo T11 antes porque no depende de ella. La auditoría independiente del lote C2 (§0.5) debe decidirse con esto a la vista: faltan las rutas de inscripción y su prueba de la fila 23 de §7.10.

- **Commits:** T08 `1a7b472`, T09 `324a977`, T11 `865c75f`; además los tres `F1-H2-FIX` previos (`e3e02be`, `143474e`, `752e4eb`) y los `F1-DOC` de documentación.
- **`make verificar` (compuerta de cada tarea):** T08 6 min 0 s (701 pruebas; una primera corrida lenta de 17 min 16 s con 50 mutantes omitidos por una ralentización transitoria, repetida en 6 min), T09 5 min 51 s (720), T11 6 min 15 s (749).
- **Medición por etapas al cierre del lote** (una corrida, sin otros procesos, 749 pruebas): Pint 8 s, PHPStan 7 s, Infection 64 s, Pest con cobertura 305 s (Pest informa 285,5 s), cobertura 3 s, auditoría 9 s. **Total 396 s (6 min 36 s)**, por debajo de los 15 min de §0.4 con holgura de 8 min 24 s.
- **Cobertura por capa (T11):** Dominio 100,0 % (umbral 95), Aplicación 100,0 % (85), Infraestructura y HTTP 99,9 % (70).
- **Mutación (Infection, solo `Dominio/`):** 331 mutantes eliminados, MSI 100 %, MSI cubierto 100 %.
- **Rutas nuevas:** `PUT /materias/{materia}/criterio`. Cambia el contenido de `GET /materias` y `GET /materias/{materia}` (`criterio_aplicado` con el criterio propio). Documentadas y validadas contra respuestas reales. T08 solo actualizó descripciones de rutas de H1.
- **Comando y planificador:** `alertas:recalcular` (diario 02:00 America/Mexico_City; sin texto en consola porque T09 no permitía `lang/es`).
- **Decisiones del agente y de permisos:** **DA-10** (escrituras de plantel solo con alcance de entrenador; aprobada por el responsable). Ninguna otra decisión cambia privacidad o permisos.
- **Para la auditoría (anotado por petición del responsable):** `estatus_deportivo` y `estatus_academico` siguen visibles en el recurso `Jugador` que ve un docente (fila 5 de §7.4, DD-30); `equipos` sale vacío para un docente puro.
- **Rutas fuera de la especificación:** ninguna; `tests/Pest.php` y `tests/Ayudantes/**` se tocaron dentro de `tests/**`.
- **Variabilidad:** dos ralentizaciones transitorias del entorno (495 s en una etapa de Pest del FIX de velocidad y 17 min 16 s en una corrida de T08), sin causa identificada; las repeticiones fueron normales (`f1-h2-t08.md`, «Variabilidad»).
- **Pendiente de respuesta:** cómo resolver la búsqueda por matrícula de T10 (opciones A, B y C en `f1-h2-t10.md`; recomiendo A) y el texto de `jugador_en_baja`.

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
