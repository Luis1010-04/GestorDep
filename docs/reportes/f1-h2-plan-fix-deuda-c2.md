# Plan F1-H2-FIX: deuda menor de la auditoría de C2 (M-3, M-6, M-10)

Autorizado por el responsable. Regla: **solo pruebas, o código que no altere comportamiento**; si un hallazgo exigiera cambiar comportamiento, me detengo y pregunto. Un commit, un reporte y un `make verificar` completo por hallazgo, sin nada más corriendo.

## M-3

**Hallazgo (textual):** «§7.1 fila 8 y §7.2 fila 6 comprueban "la materia lo sigue mostrando" con un `join` en la base, no por la API (`PeriodosEscolaresTest.php:168`, `AsignaturasTest.php:113`). La comprobación por la API existe en `MateriasTest.php:185` (§7.3 fila 8), así que la fila queda cubierta en conjunto. Sugerencia: citar esa prueba en la trazabilidad de T15.»

**Cambio propuesto (solo pruebas):** en las dos pruebas, después del `DELETE`, consultar la materia **por la API** (`GET /materias/{materia}` del docente dueño) y afirmar que el recurso sigue mostrando el periodo (fila 8 de §7.1) o la asignatura (fila 6 de §7.2) eliminados, con su id y su nombre o clave. Se conserva la comprobación por la base (no se quita nada). Las pruebas siembran el criterio general, que `GET /materias/{materia}` necesita. Además se citan en la trazabilidad de T15.

## M-6

**Hallazgo (textual):** «§7.6 fila 9 por sí sola no prueba "después del commit" (`CriterioPorMateriaTest.php:153`, `Event::fake` sin transacción observable). La propiedad la garantizan la fila 10 y la prueba del publicador (`RecalculoDelResumenTest.php:283`). Sin acción, salvo citarlas juntas en la trazabilidad.»

**Cambio propuesto (solo pruebas):** la prueba de la fila 9 pasa de `Event::fake` a un oyente que, **en el momento en que se despacha el evento**, registra el nivel de transacción de la conexión de la aplicación y lee el criterio vigente de la materia por **otra conexión** (`propietario`). Si el evento saliera antes del commit, esa otra conexión no vería el criterio nuevo (aislamiento de PostgreSQL). Afirma: una sola publicación, con el id de la materia, nivel de transacción 0 y el criterio `6.00` ya visible desde otra conexión. Se citan juntas las filas 9 y 10 y la prueba del publicador en la trazabilidad.

## M-10

**Hallazgo (textual):** «(a) `bin/verificar-mutacion.php` no comprueba que `build/infection/resumen.log` sea de la corrida actual. Si Infection dejara de escribirlo (por ejemplo, al quitar `logs.summary` de `infection.json5`), un resumen viejo limpio pasaría. (b) `Errored` e `Ignored` no hacen fallar la verificación. Es el criterio de Infection, aceptable, pero conviene documentarlo. (c) `composer.lock` cambió en T01 y T02 sin estar listado: es consecuencia inevitable de los cambios permitidos de `composer.json`.»

**Cambio propuesto, sin cambiar el comportamiento de `verificar` ni de la herramienta:**

- **(a)** Un resumen viejo solo puede leerse si la configuración deja de escribir el que lee la herramienta. Se cierra ese hueco **con una prueba** de `tests/Unit/Herramientas/` que exige que `infection.json5` escriba `logs.summary` en `build/infection/resumen.log` y que el script `verificar` de `composer.json` ejecute Infection y, justo después, `bin/verificar-mutacion.php` con esa **misma** ruta. Si alguien quita o cambia el resumen, la prueba falla. Se descartó borrar el resumen antes de Infection o comparar fechas: cambiaría el script `verificar` o la herramienta (comportamiento).
- **(b)** Se documenta con pruebas el criterio vigente: un resumen con `Errored` (Infection los cuenta como eliminados) o con `Ignored` sale con código 0. Otra prueba exige que `infection.json5` no declare mutadores ignorados (`mutators` con `ignore`), de modo que `Ignored` siga en 0 salvo que alguien cambie la configuración a la vista.
- **(c)** Solo documentación: se anota en el reporte del FIX y se incluirá en la lista de rutas fuera de la especificación del reporte de cierre de H2 (T15).

## Dudas

Ninguna: ningún hallazgo exige cambiar comportamiento.
