# Auditoría independiente del Lote C de H4

**Fecha:** 30 de octubre de 2026 · **Rama:** `hito/f1-h4` en `2ac6155` · **Especificación:** `docs/hitos/f1-h4.md` v1.0.7
**Alcance:** `f900c6b` (FIX del Lote B), `c0f1601` (T05), `4b891a5` (v1.0.7), `51b5fe6` (T06), `2ac6155` (reporte de cierre) y el código del que dependen.
**Reglas:** `docs/hitos/f1-h2.md` §0.5 (H3 §0 y H4 §0 remiten a ella; `f1-h3.md` no tiene §0.5 propia). Sesión nueva, de solo lectura: no se modificó ningún archivo salvo este informe, y no hubo commits ni push.

## 1. Corridas del auditor

Un solo guion, en serie y sin nada más corriendo, sobre `2ac6155` con el árbol limpio. Los logs están en el directorio temporal de esta sesión (`a-*.txt`, `sonda-h4.sh`).

| Corrida | Código | Tiempo | Resultado |
|---|---|---:|---|
| `make levantar` | 0 | 27 s | — |
| `make fresh` | 0 | 6 s | — |
| `make verificar` | 0 | 666 s | Pint 702 archivos; PHPStan sin errores; **1 424 pruebas (9 738 aserciones)**, Pest 517,55 s; cobertura 100,0 / 100,0 / 99,5 % (total 99,6 %); `composer audit` sin avisos |
| `make mutacion` | 0 | 157 s | 687 de 687 eliminados, MSI 100 %, ninguno omitido ni con tiempo agotado |
| `migrate:rollback` | 0 | 4 s | — |
| `migrate:status` | 0 | 1 s | 63 `Pending`, 0 `Ran` |
| `make fresh` | 0 | 6 s | — |
| `make humo` | 0 | 40 s | H0 OK, H1 14 pasos, H2 16, H3 16, **H4 14** |
| Sonda de privacidad del auditor (§4) | 0 | 20 s | Ver §4 |
| `make bajar` | 0 | 8 s | `git status` limpio al terminar |

Las cifras coinciden con las de `f1-h4-reporte.md` §1 y §2, salvo la variación normal de tiempo (verificar 666 frente a 660 s; mutación 157 frente a 154 s).

## 2. Hallazgos

### Bloqueantes

Ninguno.

### Mayores

**M-1. §7.3 fila 6: la prueba no demuestra «en la misma transacción».**
- **Dónde:** `backend/tests/Feature/Rendimiento/DesempenoTest.php:116`.
- **Evidencia:** la fila dice que «se inserta una evaluación nueva en la misma transacción». La prueba solo comprueba que la evaluación existe después de la captura y que la lectura devuelve la última.
  - Ninguna prueba de `tests/Feature/Rendimiento` ni de `tests/Integration/Rendimiento` inyecta una falla en la evaluación para comprobar que el rendimiento se revierte. La única prueba de reversión es `MigracionesDeRendimientoTest.php:437`, y cubre la tabla con su historial, no la evaluación.
  - El código es correcto: `CapturarRendimiento.php:71`, `CorregirRendimiento.php:42` y `FijarUmbral.php:63` envuelven la escritura y `RecalcularEvaluacion` en `Transaccion` (DA-32).
  - El riesgo es que una regresión pase sin aviso. Quitar el envoltorio de `Transaccion` (en Aplicación, fuera del alcance de Infection) no rompe ninguna prueba.
- **Efecto en la compuerta:** §10 punto 5 («una prueba por fila») se cumple en número, pero esta fila tiene una prueba más débil que lo que afirma (H2 §0.5 punto 2).

### Menores

**m-1. `plantilla_de_fabrica` no aparece en `openapi.yaml`.**
- **Dónde:** `docs/api/openapi.yaml`.
- **Evidencia:** §6.3 lista dos códigos reservados.
  - `rendimiento_no_editable` aparece en `openapi.yaml:4181`, como reservado.
  - `plantilla_de_fabrica` no aparece en `openapi.yaml`. Ninguno de los dos está en `lang/es/errores.php`.
- **Impacto:** ninguna ruta lo devuelve (§2.3), pero no se cumple que «todos los códigos de §6.3 estén documentados».

**m-2. Los 409 del umbral no pasan por la prueba de contrato.**
- **Dónde:** `backend/tests/Feature/Rendimiento/ContratoDePlantillasYUmbralesTest.php:34-45`.
- **Evidencia:**
  - El 409 `sin_umbral_aplicable` del GET y del PUT del umbral, y el 409 `conflicto` del PUT, no se validan contra el esquema.
  - Se prueban por estado y cuerpo exacto: `DesempenoTest` («HU-16 (DA-33)») y `PlantillasYUmbralesTest.php:263`.
  - El del desempeño sí se valida (`ContratoDeDesempenoTest.php:28`).
- **Impacto:** el texto de `openapi.yaml` (líneas 4442, 4510 y 4581) es correcto. El contrato no protege esas dos respuestas.

**m-3. La extensión de DA-33 al GET y al PUT del umbral fue decisión del agente, pero la especificación la atribuye al responsable.**
- **Dónde:** `f900c6b`; `docs/hitos/f1-h4.md` §6.3 y §12 fila 1.0.6; `f1-h4-decisiones-del-agente.md` DA-33.
- **Evidencia:**
  - `f1-h4-fix-lote-b.md` §1 dice «Coherencia, decidida por mí: el GET del umbral y el PUT sin cuerpo [...] ahora responden lo mismo».
  - La fila 1.0.6 de §12, escrita en el mismo commit, dice «Correcciones del responsable», y la fila de §6.3 incluye el GET y el PUT.
  - DA-33 perdió su línea «Cómo se revierte», que exige H2 §0.3. La reversión solo figura en el reporte del FIX.
- **Impacto:** documental. El pedido de esta auditoría acepta el 409 en las tres rutas, así que no hay cambio de comportamiento que objetar. Conviene que el responsable confirme por escrito la extensión y que DA-33 recupere su línea de reversión.

**m-4. El reporte de T05 da 336 aserciones; el log muestra 335.**
- **Dónde:** `docs/reportes/f1-h4-t05.md:7`.
- **Evidencia:** `verificar-fix-b.txt` da 9 389 aserciones y `verificar-t05b.txt`, 9 724: la diferencia es 335. El número de pruebas sí coincide (1 410 → 1 421, 11 nuevas).

**m-5. Dos cifras de T06 no tienen log.**
- **Dónde:** `docs/reportes/f1-h4-t06.md` §2 y §4.
- **Evidencia:** el reporte da `make mutacion` en 166 s y el humo en 45 s.
  - En el directorio temporal de la sesión constructora no hay log de mutación de T06: solo están `mutacion-lote-a.log`, `mutacion-t03.txt` y `cierre-mutacion.txt`.
  - `humo.txt` no tiene tiempos.
  - Las corridas de cierre (§2 del reporte final) sí se verifican contra `cierre-*.txt`, y esta auditoría las reprodujo.
- **Impacto:** no se pueden verificar. No afectan la compuerta, que se apoya en las corridas de cierre.

**m-6. La copia de `humo_igual` en `humo-h4.sh` no tiene prueba propia.**
- **Dónde:** `scripts/humo/humo-h4.sh:13-25`.
- **Evidencia:** la función se copió de `humo-h3.sh:12-24` (son idénticas hoy).
  - Las pruebas de su falla (`GuionDeHumoTest.php:117-136`) extraen la función de `humo-h3.sh` con `funcionHumoIgual()` (línea 93).
  - Las pruebas de T06 solo comprueban que `humo-h4.sh` llama a `humo_igual 9` y `humo_igual 10`.
- **Impacto:** si la copia de H4 se rompe o cambia, ninguna prueba lo detecta. La comparación byte a byte de los pasos 9 y 10 depende de ella.
- **Relacionado:** la desviación declarada de T06 (guion antes que pruebas).

**m-7. DA-13 se marca sin efecto en la privacidad, pero revela que otro entrenador capturó un rendimiento.**
- **Dónde:** `f1-h4-decisiones-del-agente.md` (resumen, DA-13) y `registro-decisiones.md` §6.
- **Evidencia:** `ConsultaDePendientesDeRendimientoSql.php:66-69` excluye a un convocado si tiene cualquier rendimiento no eliminado, sin filtrar por `registrado_por`.
  - E1 ve bajar `convocados_sin_rendimiento` cuando E3 captura a un jugador de A.
  - La existencia de una captura ajena también la revela el 409 `rendimiento_duplicado`, que exige la UQ de §5.1. Por eso es coherente con la especificación.
- **Impacto:** de clasificación (H2 §0.5 punto 3). Debería marcarse como «Sí: revela que existe una captura, no su contenido ni su autor».

**m-8. RN-07 no tiene prueba con su identificador.**
- **Dónde:** `docs/trazabilidad.md:132`.
- **Evidencia:** está declarado («Sin prueba con su identificador en H4: RN-07 y RNF-05»). §10 punto 2 exige identificador solo a las historias, y lo cumplen; CLAUDE.md §4 punto 3 pide una prueba por criterio con identificador.
- **Recomendación:** que el responsable decida si basta la cobertura indirecta de HU-15.

## 3. Verificaciones pedidas

### 3.1 Compuerta de §10, punto por punto

| # | Punto | Evidencia | Estado |
|---|---|---|---|
| 1 | `make verificar` en local y en CI; `make mutacion` | Local: §1 de este informe y `cierre-verificar.txt` / `cierre-mutacion.txt`. CI: no ejecutada (no hay push) | **Local cumplido; CI pendiente** |
| 2 | Historias con identificador; diferidos de §2.2 anotados | HU-14, HU-15, HU-16 y HU-04 c2 con pruebas (§3.3). `trazabilidad.md:124-130` lista los seis diferidos de §2.2 | Cumplido (ver m-8) |
| 3 | `openapi.yaml` con las rutas de §6.1 y respuestas reales iguales | §3.2 | Cumplido (ver m-1 y m-2) |
| 4 | `make fresh` y `migrate:rollback` | §1: código 0; 63 migraciones revertidas y `Pending` | Cumplido |
| 5 | Tablas de §7, una prueba por fila | §3.3 | Cumplido en número (ver M-1) |
| 6 | `make humo` H0 a H4 | §1: código 0, H4 con 14 pasos | Cumplido |
| 7 | Pasa, no pasa, datos insuficientes; RN-14; docente sin rendimiento | `CalculoDeDesempenoTest`, `DesempenoTest`, `RendimientoSeguridadTest` y la sonda (§4) | Cumplido |
| 8 | Reporte de cierre con tiempos por etapa | `f1-h4-reporte.md` §2 y §3, contrastado en §3.8 | Cumplido |
| 9 | DD-09 y DD-10 confirmadas; AB-01 por validar | `registro-decisiones.md` (diff de `51b5fe6`) | Cumplido |
| 10 | Auditoría del Lote C sin bloqueantes | Este informe | Cumplido: sin bloqueantes |
| 11 | Aprobación del responsable, etiqueta y fusión | — | Pendiente (responsable) |

### 3.2 Rutas de §6.1 frente a `openapi.yaml` y la API

- **Rutas:** las 10 rutas están en `routes/api.php:124-133`, en el grupo `rol:entrenador`, con `whereUuid` en `{partido}`, `{rendimiento}`, `{equipo}` y `{jugador}`. También están en `openapi.yaml:3909-4588`, con 401, 403 y 404 donde corresponde.
- **Códigos de §6.3:**
  - Documentados: todos, salvo `plantilla_de_fabrica` (m-1).
  - `sin_umbral_aplicable` 409 aparece en los tres sitios: GET del umbral (`:4442`), PUT del umbral (`:4510`, con `conflicto`) y desempeño (`:4581`).
- **Respuestas reales:** las validan `ContratoDeRendimientoTest`, `ContratoDePlantillasYUmbralesTest` y `ContratoDeDesempenoTest`, salvo los 409 del umbral (m-2).

### 3.3 Filas de §7.1 a §7.4

| Tabla | Filas con prueba (por identificador y por contenido) | Sin prueba |
|---|---|---|
| §7.1 | 1 a 11 y 13 (`CapturaDeRendimientoTest.php:50-259`); la 13 también en `RendimientoSeguridadTest.php:117` | 12: eliminada en v1.0.3, anotada en la especificación y en `trazabilidad.md:122` |
| §7.2 | 1, 2, 3, 5, 6 y 7 (`PlantillasYUmbralesTest.php:57-137`); la 7 también en `CriterioDeEvaluacionTest` | 4: reservada en v1.0.4, anotada igual |
| §7.3 | 1 a 7 (`DesempenoTest.php:58-137`); 1 a 4 también en `CalculoDeDesempenoTest` (unitarias) | — (la fila 6, más débil: M-1) |
| §7.4 | 1 y 2 (`RendimientoSeguridadTest.php:164,180`); 3 (`:130` y `CapturaDeRendimientoTest.php:271`); 4 (`:145` y `CapturaDeRendimientoTest.php:284`); 5 (`:96` y `DesempenoTest.php:237`) | — |

Se leyó el cuerpo de las pruebas de §7.1 filas 9 a 13, §7.3 filas 5 a 7, §7.4 completa y la del DA-33. Afirman lo que dice cada fila, salvo M-1.

### 3.4 Seguridad (T05)

- **Pruebas (`RendimientoSeguridadTest.php`):**
  - E2 contra las 9 variantes de ruta con recurso, con cuatro identificadores (ajeno, inexistente, v4 y v1) y cuerpos válidos, inválidos y vacíos. Compara el cuerpo byte a byte con un 404 de referencia y la huella de las 9 tablas de Rendimiento.
  - Docente: 403 con el cuerpo exacto en las 10 rutas. Sin sesión: 401 en las 10 rutas.
- **Sonda del auditor, contra la aplicación levantada después de `make humo` y por la API:**
  - **404:** **60 de 60** respuestas 404 de E2 son idénticas byte a byte a la de un partido inexistente. Incluye, además de lo que prueba T05, un id que no es UUID (`abc-123`, que no casa con la ruta) y cuerpos de captura, de corrección y de umbral.
  - **Sin cambios:** el estado de E1 (lista, umbral y desempeño de P1, sin `calculado_en`) no cambió.
  - **Docente:** **10 de 10** con 403 `sin_permiso`.
  - **Sin sesión:** **10 de 10** con 401 `no_autenticado`.

### 3.5 Mutaciones de alcance y M11

- **Logs:** coinciden con la tabla de `f1-h4-t05.md` §2: primera corrida 12 de 18 eliminados (`mutaciones-h4.txt`), segunda 17 de 18 (`mutaciones-h4-2.txt`).
- **Equivalencia de M11:** se intentó construir un caso que la distinga y no se encontró. La equivalencia se sostiene.
  - En `ComprobarJugadorDelEquipo::ejecutar` (líneas 33-40), M11 quita la comprobación de `VisibilidadDeJugador` (con `incluirBajas: true`). Las otras dos condiciones son:
    - **C1, `EquiposDelEntrenadorPlantel::entrena`:** asignación de `entrenador_equipo` con `hasta` y `eliminado_en` nulos, equipo y usuario no eliminados, y rol `entrenador` en `usuario_rol` ⨝ `roles`.
    - **C3, `PosicionEnEquipoPlantel::de`:** membresía de `jugador_equipo` en **ese** equipo, con `hasta` y `eliminado_en` nulos.
  - La rama del entrenador de `AlcanceDeJugadoresSql` (líneas 22-27 y 49-59) exige:
    - el rol `entrenador` en `UsuarioActual::roles()`. Viene de `UsuarioModelo::codigosDeRoles`: la misma unión `usuario_rol` ⨝ `roles`, leída en cada llamada;
    - una membresía vigente y no eliminada en **algún** equipo con asignación vigente y no eliminada del usuario.
  - Con `incluirBajas: true` no se agrega el filtro de baja, y ni C3 ni el alcance miran `jugadores.eliminado_en`.
  - Por lo tanto, C1 ∧ C3 implica la condición de M11 para cualquier estado de la base: el equipo de C1 y la membresía de C3 forman el testigo del `EXISTS`, y el rol de C1 sale de la misma tabla que `roles()`. Además, `ExigirRol` (`rol:entrenador`) ya exige ese rol en la ruta.
  - Solo podría distinguirlos un cambio concurrente entre las consultas, que no es un caso observable por prueba.

### 3.6 Mensajes de excepción

Se buscaron literales con tildes o eñes en `src/` y `app/` fuera de comentarios: ninguno. Las excepciones de Rendimiento pasan códigos estables (`SinUmbralAplicable` → `sin_umbral_aplicable`, `JugadorNoConvocado` → `jugador_no_convocado`, etc.), y los mensajes viven en `lang/es/errores.php`. `TextosEnLangTest` corre en `make verificar` y pasó.

### 3.7 Humo

- **Pasos:** `humo-h4.sh` recorre los pasos 1 a 14 de §9 v1.0.7 en orden.
  - El paso 3 agrupa la preparación por la API: categoría, equipo, tres jugadores, temporada, lugar, rival, partido, convocatoria y resultado.
  - Los pasos 9 y 10 los hace E2, con `humo_igual` contra el 404 de un partido inexistente.
- **Escrituras:** sin escrituras en la base fuera de la API. El guion solo usa `curl` contra la API y `php -r` para leer constantes del seeder y generar un UUID. No contiene `artisan`, `DB::` ni `psql`.
- **Makefile:** agrega una sola línea (`Makefile:47`).
- **Pendiente:** ver m-6.

### 3.8 Números de los reportes frente a los logs

| Cifra del reporte | Log | ¿Coincide? |
|---|---|---|
| Cierre: Pint en el s. 47, PHPStan en el 121, 1 424 pruebas, `Duration: 509.58s`, total 660 s | `cierre-verificar.txt` (líneas 15, 19, 1684 y 1685; `segundos=659`) | Sí (659 → «660 s» es redondeo del tiempo hasta la última línea) |
| Cierre: mutación 687 de 687, `2m 31s`, 154 s | `cierre-mutacion.txt` | Sí |
| Cierre: fresh 5 s, rollback 3 s, status 63 `Pending`, fresh 6 s, humo 39 s | `cierre-*.txt` | Sí |
| FIX: 1 410 pruebas, 9 389 aserciones, Pest 508 s, total 562 s | `verificar-fix-b.txt` | Sí |
| T05: primera corrida falla en PHPStan, 116 s | `verificar-t05.txt` (código 2, `Found 1 error`) | Sí |
| T05: 1 421 pruebas, Pest 530 s, total 610 s | `verificar-t05b.txt` (`529.54s`, `segundos=610`) | Sí |
| T05: «11 pruebas y 336 aserciones» | 9 724 − 9 389 = 335 | **No** (m-4) |
| T06: 1 424 pruebas, Pest 512 s, total 675 s | `verificar-t06.txt` (`512.13s`, `segundos=675`) | Sí |
| T06: `make mutacion` 166 s; humo 45 s | Sin log | **No verificable** (m-5) |
| Cobertura 100 / 100 / 99,5 % | `cierre-verificar.txt` y esta auditoría | Sí |

### 3.9 Numeración de DA

- **Numeración:** DA-01 a DA-35, sin duplicados ni huecos; DA-28 retirada y tachada. T05 y T06 no agregaron DA.
- **Registro:** la sección 6 de `registro-decisiones.md` coincide con el archivo de DA.
- **Contraste con el código:**
  - DA-12: `incluirBajas` falso solo en `CapturarRendimiento.php:111` y en los pendientes.
  - DA-13: `ConsultaDePendientesDeRendimientoSql.php:66-69`.
  - DA-26: `RepositorioDePlantillasSql.php:79-80`, por `creado_en` y después `id`.
  - DA-32: `Transaccion` en los tres casos de uso.
  - DA-33: `SinUmbralAplicable` en `ConsultarDesempeno`, `ConsultarUmbral` y `FijarUmbral`, y `RecalcularEvaluacion` no inserta.
  - DA-34: `CalificacionesDelEntrenadorSql.php`, que descarta los rendimientos sin fecha.
  - DA-35: recálculo solo del entrenador en `FijarUmbral`; la corrección siempre recalcula.
  - Todas coinciden. Hay observaciones de forma en m-3 y m-7.

### 3.10 Desviaciones declaradas

Las cuatro desviaciones de `f1-h4-reporte.md` §9 están en el reporte de su tarea:
- el `cat >>` de T02;
- el `cat` del FIX (`f1-h4-fix-lote-b.md` §5);
- las pruebas de T06 escritas después del guion (`f1-h4-t06.md` §7);
- la coautoría (DA-03).

**Rutas.** H2 §0.5 punto 5 pide revisar las rutas modificadas fuera de las permitidas:
- `c0f1601` toca solo `tests/Feature/Seguridad/**` y reportes.
- `51b5fe6` toca `Makefile` (una línea), `tests/Unit/Herramientas/**`, `scripts/humo/humo-h4.sh`, `trazabilidad.md`, `registro-decisiones.md` y reportes.
- `2ac6155` toca el reporte y la trazabilidad.
- `4b891a5` toca solo la especificación.
- Todo está dentro de lo permitido.
- `f900c6b` toca `app/**`, `lang/es`, `src/Rendimiento/**`, `openapi.yaml` y la especificación. Está declarado en `f1-h4-reporte.md` §4: las rutas base siguen pendientes de confirmación y la especificación se cambió por versión.

**Otras observaciones:**
- T05 declara «Desviaciones: ninguna». Su guion de mutación trabajó sobre una copia en `/tmp` del contenedor, y `git status` del cierre quedó limpio.
- No se encontró ninguna desviación oculta, salvo la atribución de m-3.

## 4. Veredicto

**Aprobado con condiciones.** No hay hallazgos bloqueantes. Las corridas reproducen las cifras de cierre, y la privacidad resistió la sonda y el recorrido de T05.

Condiciones antes de la etiqueta `f1-h4`:

1. **M-1:** una prueba que fuerce una falla en la evaluación (en la captura, la corrección o el cambio de umbral) y compruebe que no queda ni el rendimiento ni el umbral, o bien que el responsable acepte explícitamente la prueba actual de §7.3 fila 6.
2. **§10 punto 1:** la corrida de la integración continua tras el push.
3. **m-3:** que el responsable confirme la extensión del 409 al GET y al PUT del umbral, y que DA-33 recupere su línea «Cómo se revierte».

Los demás menores (m-1, m-2 y m-4 a m-8) pueden atenderse en un FIX o quedar anotados, a criterio del responsable.
