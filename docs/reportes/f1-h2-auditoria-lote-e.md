## Estado de los hallazgos (29/10/2026)

Esta sección la agrega el agente al guardar la auditoría en el repositorio, por instrucción del responsable. Cada commit se comprobó con `git show --stat`. Debajo de la línea sigue la auditoría tal como la entregó el auditor el 28/10/2026, sin cambios.

| Hallazgo | Estado | Commit |
|---|---|---|
| B-1 | **Pendiente** del push del responsable; se cierra con CI en verde en el commit final | — |
| I-1 | Resuelto (DA-17, `docs/arquitectura.md` v1.4) | `1fa94de` |
| I-2 | Resuelto (DA-14 y DA-15 confirmadas) | `1fa94de` |
| M-1 | Resuelto (`ConfiguracionDePhpstanTest`) | `ad8738b` |
| M-2 | Resuelto (RNF-11 en la trazabilidad y en el punto 2 de la compuerta) | `97979da` |
| M-3 | Resuelto (`AcademicoSeguridadTest`) | `6804a9f` |
| M-4 | Resuelto (DA-16) | `97979da` |
| M-5 | Resuelto (`docs/hitos/f1-h2.md` v1.1) | `97979da` |
| M-6 | Resuelto (plazos del registro) | `97979da` |
| M-7 | Resuelto (sin `->skip()` condicionales) | `632611f` |
| M-8 | Sin cambio de código. La medición de la auditoría fue contaminada; se mantiene la medición limpia de 502 s (`f1-h2-reporte.md` §2) | — |
| M-9 | **Pendiente.** Falta el enlace a la corrida de CI del commit final, que aporta el responsable | — |

Mensaje del auditor antes del documento (28/10/2026), sin cambios:

> Escrito para: el responsable del proyecto, que decide sobre la compuerta de H2.
>
> No modifiqué ningún archivo del repositorio: `git status` está limpio y no hice commits, push ni PR. Sí ejecuté `make fresh`, `make humo`, `make verificar` y un `migrate:rollback`. Los dos últimos solo tocan las bases de datos, no el repositorio, y al terminar dejé la base de desarrollo reconstruida con `make fresh`.

---

# Auditoría independiente del Lote E de H2 (T15 y compuerta §10)

**Fecha:** 28 de octubre de 2026 · **Rama:** `hito/f1-h2` · **Commit auditado:** `1145c77` (HEAD local; va 9 commits por delante de `origin/hito/f1-h2`, que está en `cc66784`)
**Modo:** solo lectura, sesión nueva (§0.5).

## Resumen

| Clase | Cantidad |
|---|---|
| Bloqueante | 1 (B-1: la CI no corrió sobre el commit final) |
| Importante | 2 |
| Menor | 9 |

Reproduje las cifras del reporte, salvo la duración de `make verificar`, que depende del entorno (M-8). No encontré debilitamiento de pruebas, umbrales ni configuración. Las rutas fuera de especificación coinciden con §4 del reporte. Tampoco encontré una fuga de privacidad. Para cerrar faltan tres cosas: que la CI quede en verde sobre el commit final, la decisión sobre la ubicación de T14 (I-1) y la confirmación de DA-15 (I-2).

## 1. Resultados reproducidos

| Comando | Resultado de esta auditoría | Reporte |
|---|---|---|
| `make fresh` | código 0; 36 migraciones y 5 seeders | coincide |
| `make humo` | `HUMO H0 OK`, `HUMO H1 OK: 14 pasos`, `HUMO H2 OK: 16 pasos`, código 0 | coincide |
| `make verificar` | código 0. Pint `PASS 407 files`; PHPStan `[OK] No errors`; Infection 362 mutantes eliminados, MSI 100 %, MSI cubierto 100 %; `Mutación completa: 362 mutantes evaluados, ninguno omitido ni con tiempo agotado`; `Tests: 897 passed (5391 assertions)`; cobertura Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,7 %; `No security vulnerability advisories found.` | coincide, salvo la duración: **856 s (14 min 16 s)**, Pest 625,81 s, frente a 502 s (M-8) |
| `migrate:rollback --database=propietario --force` | código 0; 36 `DONE`; después, `migrate:status` dio 36 pendientes y en el esquema solo quedó la tabla `migrations`; `make fresh` volvió a dejar las 36 ejecutadas | coincide |
| `route:list` | las 19 operaciones de §6.1 existen; las 13 rutas están en `openapi.yaml` (líneas 305 a 1821) | coincide |

## 2. Compuerta §10, punto por punto

| # | Estado comprobado | Evidencia |
|---|---|---|
| 1 | **Local: cumple. CI: no cumple** (B-1) | En local, la sección 1. En la API pública de GitHub Actions, la rama `hito/f1-h2` tiene solo dos corridas: `15df1f6` (success) y `cc66784` (failure en el paso «Verificar»). `git ls-remote` confirma que el remoto está en `cc66784`: los commits `9d931b6` a `1145c77` nunca pasaron por la CI |
| 2 | Cumple, con matices (M-2) | `trazabilidad.md` cita pruebas que existen; comprobé que existen todos los `*Test` citados. RN-12 y RNF-11 no tienen ninguna prueba con su identificador |
| 3 | Cumple | 19 de 19 en `openapi.yaml` y en `route:list`; `ContratoDeAcademicoTest` (7 pruebas) valida respuestas reales de todas, incluidos los 404 |
| 4 | Cumple | Reproducido (sección 1) |
| 5 | Cumple, salvo §7.12 filas 1 y 2 (M-1) | Cada fila de §7.1 a §7.8, §7.10 y §7.11 tiene una prueba que la cita por número; las 13 filas de riesgo de §7.9 están en `RiesgoAcademicoTest.php:17-70`. Revisé al azar el contenido de §7.10 filas 9, 10 y 14 y coincide con lo que afirma la tabla |
| 6 | Cumple | Reproducido |
| 7 | Cumple | Cubierto por las pruebas citadas en el reporte y por los pasos 5, 11, 12, 13 y 15 del humo |
| 8 | Cumple, con I-1 | Las secciones 3 a 5 del reporte cubren lo pedido |
| 9 | Cumple | `registro-decisiones.md` §1: DD-42 a DD-55 (líneas 51 a 64). §2: AB-10 a AB-21 (de H1) y AB-22 a AB-27 (de H2), con las validaciones de §3 (DD-20, RN-01, RN-04, DD-43, DD-44 y AB-08) en AB-22 a AB-25. §4: DA-08 a DA-15, y DA-01 a DA-07 agrupadas en el párrafo introductorio. Ver M-4 y M-6 |
| 10 | Pendiente | Esta auditoría completa el punto, si se resuelven B-1, I-1 e I-2 |
| 11 | Pendiente del responsable | Sin etiqueta `f1-h2` ni fusión (comprobado con `git ls-remote`) |

## 3. Debilitamiento (punto 3 del encargo)

Comparé con `f1-h1` y con `cc66784`. No encontré nada debilitado.

- **`composer.json`**: no cambió en el Lote E (`git diff cc66784..HEAD`). Desde H1 solo cambió por `ext-bcmath`, Infection en `require-dev`, `allow-plugins` y los dos pasos nuevos del script `verificar`. No tiene ninguna clave `audit`, así que `composer audit` no ignora avisos.
- **`composer.lock`** (Lote E): solo cambian `symfony/mime` (8.1.7 → 8.1.10) y `symfony/polyfill-mbstring` (1.43.0 → 1.43.3), los dos documentados en `f1-h2-fix-dependencias.md:30`. El vendor del contenedor está en 8.1.10.
- **`phpstan.neon`**: nivel 7, sin `ignoreErrors` ni línea base. No hay `phpstan-baseline*`.
- **`infection.json5`**: `minMsi` 80, `minCoveredMsi` 85, sin `mutators` ni `ignore`. Solo excluye `Aplicacion` e `Infraestructura`.
- **`bin/verificar-cobertura.php`**: sin cambios desde H1 (umbrales 95, 85 y 70).
- **`bin/verificar-mutacion.php`**: es nuevo (`aff1240`) y añade una exigencia.
- **Supresiones**: no hay ningún `phpstan-ignore`, `infection-ignore`, `codeCoverageIgnore`, `markTestSkipped` ni `->todo(` en `src`, `app`, `tests`, `database`, `routes` ni `bin`. Solo hay dos `->skip(` condicionales (M-7).
- **Pruebas modificadas en el Lote E**: todas se endurecen.
  - `AsignaturasTest` y `PeriodosEscolaresTest` agregan aserciones por la API (M-3 de C2).
  - `CriterioPorMateriaTest` (fila 9 de §7.6) cambia `Event::fake` por un escucha que exige exactamente un despacho, ya confirmado y visible desde otra conexión.
  - `UsuariosDemoSeederTest` sube las cifras de 3 a 4 por el segundo docente.

## 4. Rutas fuera de especificación (punto 4 del encargo)

Revisé, commit por commit desde `f1-h1`, los archivos de cada uno frente a las rutas permitidas de su tarea.

- **DA-01** (T01: `FormatoDeError`, `src/*/Infraestructura`, `tests/Feature`, `tests/Integration` y `tests/Ayudantes`), **DA-02** (`allow-plugins` y el adaptador), **DA-05** (la carpeta `alertas/` corregida en `15df1f6`), **DA-07** (`.github/workflows/verificar.yml` en `a3f0c03`) y **DA-10** (`src/Plantel/Aplicacion` en T08) aparecen en `f1-h2-decisiones-del-agente.md`, en el registro §4 y en la tabla §4 del reporte. Coinciden con lo que se tocó.
- También aparecen en el reporte: `composer.lock`, `CategoriaController` (FIX), `openapi.yaml` en T08 (M-2 de C2) y `src/Compartido/Aplicacion` con `src/Plantel/Infraestructura` en T10 (opción A).
- **No encontré otras.** Los commits de T05 a T07, T11 a T15 y los FIX de pruebas se quedan dentro de sus rutas. T15 coincide con su lista permitida.
- **Matiz de T09:** creó los cuatro eventos en `src/Compartido/Dominio/Eventos/` y el publicador en `src/Compartido/{Aplicacion,Infraestructura}`. La especificación lo permite («solo el mecanismo de eventos… si H0 no lo ofrece»; comprobé que `PublicadorDeEventos` no existía en `f1-h1`), y los eventos tienen que vivir ahí por la arquitectura. Lo acepto, pero el reporte (línea 260) no lo explica.
- **Ubicación de T14 en `src/Academico`:** **no aparece en el registro de decisiones ni como DA.** Ver I-1.

## 5. Privacidad (punto 5 del encargo)

- `AcademicoSeguridadTest` recorre las 10 rutas con un recurso en la ruta. D2 ataca los recursos de D1 con cuerpos válidos, inválidos, de tipo erróneo y vacíos, con el id propio, uno v7 inexistente y un UUID v4. Compara con `getContent()` contra la respuesta de referencia y comprueba que no se escribió ninguna fila.
- La situación académica se prueba para D1, D2, A y DE, con 404 byte a byte; el entrenador recibe 403 en todas las rutas docentes y sin sesión todas responden 401. También hay una prueba de que un docente no ve nada del otro aunque compartan alumno.
- En el código, la pertenencia se resuelve por `materias.docente_id` (`ConsultaDeInscripcionesSql.php:59-66`, `ComprobarCalificacionDelDocente.php:18-23`) y no por el alcance de jugadores, así que un alumno compartido no abre las inscripciones del otro docente. El único acceso a `jugadores` en la situación pasa por `restringir(..., incluirBajas: true)` (`ConsultaDeSituacionAcademicaSql.php:33-37`).
- Al recorrido le faltan algunos casos de §0.5.4 (M-3). No encontré ninguna fuga.

## 6. Hallazgos

### BLOQUEANTE

**B-1. La CI no corrió sobre el commit final, y la última corrida de la rama falló.**
- Dónde: punto 1 de §10; `docs/reportes/f1-h2-reporte.md:281`.
- Evidencia: el remoto está en `cc66784` (corrida en failure, paso «Verificar»). Los 9 commits del Lote E, entre ellos el FIX de `symfony/mime`, no se han enviado. Por la API pública no puedo leer la causa de la falla: los registros piden autenticación, así que no puedo confirmar que fuera `composer audit` (M-9).
- El reporte lo declara bien («CI: pendiente»): no es un error del constructor, pero el punto 1 sigue abierto.
- Cómo se cierra: el responsable hace push y la corrida sobre `1145c77`, o sobre el commit final que resulte, queda en verde.

### IMPORTANTE

**I-1. T14 resolvió por su cuenta una contradicción de arquitectura y no la registró.**
- Dónde: `docs/arquitectura.md:83` dice que «una consulta que cruza contextos… vive en el contexto Consultas». La situación académica lee `jugadores` (tabla de Plantel) desde `src/Academico/Infraestructura/ConsultaDeSituacionAcademicaSql.php:33`, y T10 hace lo mismo en `ConsultaDeInscripcionesSql`. El agente vio el conflicto con la otra regla (Consultas no importa el dominio ajeno, `f1-h2-t14.md:7`) y eligió `src/Academico`.
- Por qué importa: §0.4 manda detenerse ante una contradicción entre documentos. La decisión solo figura en los reportes (`f1-h2-reporte.md:212`, `f1-h2-t14.md:7`, `f1-h2-plan-t14.md:7`). No está en `f1-h2-decisiones-del-agente.md` ni en `registro-decisiones.md` §4, a diferencia de lo que pide el encargo.
- No hay impacto de privacidad: la lectura pasa por `AlcanceDeJugadores` y la lista cerrada (§7.4 fila 14) no creció.
- Propuesta: que el responsable decida (aceptarlo o moverlo) y registrarlo como DA, o como corrección de la arquitectura, antes de aprobar.

**I-2. DA-15 sigue sin confirmar y es una decisión de privacidad.**
- Dónde: `registro-decisiones.md:126` y `:100` (AB-27, «Antes de H6»); `f1-h2-reporte.md:273`.
- Por qué importa: §0.4 pone como condición de parada «una decisión de seguridad o privacidad que cambie quién ve qué datos de un jugador». DA-15 restringe lo que el docente ve de sus propias materias de periodos pasados. Es restrictiva y coherente con §0.3, pero cambia la lectura que §6.2 describe.
- DA-14 (`:125`) se deriva de DD-52 («sigue visible para quien ya lo tenía») y la considero de riesgo bajo, aunque también está «sujeta a confirmación».
- Propuesta: confirmar o rechazar DA-15 y DA-14 explícitamente dentro del punto 11, no dejarlas para H6.

### MENOR

- **M-1.** §7.12 filas 1 y 2 no tienen prueba automatizada (son las únicas de §7 sin referencia en `tests/`). La 1 es manual por diseño. La 2 («sin línea base ni `ignoreErrors`») podría tener una guarda al estilo de `VerificarMutacionTest.php:85-124`: hoy nada impide que se agregue un `ignoreErrors` en `phpstan.neon` sin que falle una prueba.
- **M-2.** RNF-11 tampoco tiene ninguna prueba con su identificador (0 coincidencias en `tests/`), igual que RN-12. El reporte (`f1-h2-reporte.md:282`) solo menciona RN-12. Hay que anotarlo junto a RN-12 o etiquetar las pruebas de herramientas.
- **M-3.** Cobertura parcial del recorrido de §0.5.4 en `AcademicoSeguridadTest.php:36-75`: solo ataca D2 y siempre sobre recursos de I1 (un alumno que no comparten).
  - El caso del alumno compartido está solo en parte y fuera del recorrido: `CalificacionesTest.php:241` (POST sobre I3a) y `AsistenciasTest.php:161` (GET sobre I3a).
  - Faltan: GET de calificaciones de I3a, PATCH de una calificación o asistencia de I3a, y DE con M3 contra I1.
  - El `DELETE` de catálogos con un id no v7 está en `PeriodosEscolaresTest.php:190` y `AsignaturasTest.php:125`, no en el recorrido.
  - El código es correcto (sección 5). Lo que falta es protección contra regresiones.
- **M-4.** El desempate del periodo vigente por `id` es una decisión donde la especificación calla. `f1-h2-t05.md:57` lo declara «ninguna DA nueva», y ahora aparece dentro de DD-45 en `registro-decisiones.md:56` con «Dónde quedó: H2 §3», que no lo dice. Debería ser una DA o marcarse como agregado.
- **M-5.** `docs/hitos/f1-h2.md` se modificó en `e30e54f` (§7.1 fila 6, P1/P2) sin subir la versión ni la fecha (`f1-h2.md:3`, sigue «1.0», 26 de octubre).
- **M-6.** El registro cierra H2 con entradas cuyo plazo era «H2» y siguen abiertas: AB-01 (`:74`), AB-08 (`:81`), AB-10 (`:83`), AB-14 (`:87`) y PT-01 a PT-03 (`:108-110`). PT-02, el clover viejo, sigue sin resolverse: `verificar-cobertura.php` no cambió. La sección 8 del reporte solo menciona AB-22 a AB-25 y PT-04. Conviene moverles el plazo o anotarlas como pendientes.
- **M-7.** Hay `->skip()` condicionales en `AlcanceDelDocenteTest.php:155` (§7.4 fila 14) y `LocalizadorDeJugadoresTest.php:78`: si se renombra la función auxiliar de `tests/Arch`, la prueba deja de correr sin avisar. En la suite completa de hoy ninguna se omite.
- **M-8.** Mi corrida de `make verificar` tardó 856 s (14 min 16 s), con Pest en 625,81 s, frente a 502 s en el reporte (`f1-h2-reporte.md:25,41`). Mientras corría Pest ejecuté `migrate:rollback` y `make fresh` sobre la base de desarrollo, así que no contradice la cifra del reporte, pero muestra que la holgura frente al límite de 15 min (§0.4) puede ser de menos de un minuto.
- **M-9.** El reporte atribuye la falla de la CI en `cc66784` a `composer audit` (`f1-h2-reporte.md:281`). Con la información pública solo se ve que falló el paso «Verificar». La cifra viene del responsable y no la puedo reproducir; conviene enlazar la corrida.

## 7. Exactitud del reporte (punto 7 del encargo)

- Reproduje las cifras de las secciones 1, 2 y 6 (897 pruebas y 5 391 aserciones, 407 archivos, 362 mutantes, 100/100/99,7 %, 36 migraciones, 19 rutas, humo con 16 pasos), salvo la duración (M-8).
- Las cifras de «pruebas tras la tarea» de la tabla §3 son históricas; no las reconstruí commit por commit.
- El estado de los puntos 1, 10 y 11 está bien declarado.
- Lo que falta en el reporte: I-1 no figura como decisión registrada, RNF-11 no aparece en el punto 2 (M-2) y no se mencionan los pendientes con plazo «H2» (M-6).

## 8. Qué resolver primero

1. B-1: push y CI en verde sobre el commit final.
2. I-1: decisión sobre la ubicación de T14 (y T10) y su registro.
3. I-2: confirmar DA-15 y DA-14.
4. Las menores pueden ir en un FIX o en F1-DOC posteriores. Las que más valen: M-1 (guarda de `phpstan.neon`) y M-3 (completar el recorrido de seguridad).
