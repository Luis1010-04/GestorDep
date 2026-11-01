# Plan de F1-H4-T00: reducir el costo de reiniciar la base de pruebas (PT-08, opción B)

**Fecha:** 30 de octubre de 2026 · **Base:** `main` en `1fa839a` (etiqueta `f1-h3`) · **Estado:** aprobado por el responsable el 30/10/2026 con condiciones: el ajuste va en código y no por `PGOPTIONS`, dos corridas con el cambio aplicado, la prueba con una sola aserción, la rama `hito/f1-h4`, la línea `Co-Authored-By` tal como se indicó, la investigación de `x.cov` y el riesgo de durabilidad como DA. El resultado está en `f1-h4-t00.md`.

## 1. Línea base (sin modificar nada)

`make verificar` sobre `1fa839a`, con los cuatro contenedores arriba y nada más corriendo:

| Etapa | Duración | Resultado |
|---|---:|---|
| Pint y PHPStan | ~15 s | `PASS … 585 files`, `[OK] No errors` |
| Pest con cobertura | **618 s** (`Duration: 618.37s`) | 1 224 pruebas, 8 285 aserciones |
| Cobertura por capa y `composer audit` | ~55 s | sin avisos |
| **Total** | **689 s (11 min 29 s)**, código 0 | |

Pest se lleva el 90 %. Por suite: Feature 380 s (739 pruebas), Integration 178 s (161), Arch 48 s (42), Unit 6 s (282).

## 2. Dónde se va el tiempo (mediciones)

Mediciones con guiones descartables en el scratchpad y un archivo de prueba temporal en `/tmp` del contenedor. Nada en el repositorio.

| Medida | Valor |
|---|---:|
| Prueba vacía con `TestCase` (arranque de la aplicación y cierre de conexiones) | 80 a 90 ms |
| `esquemaReiniciar()` rápido (huella de 7 ms y vaciado de 5 a 45 ms) | 20 a 50 ms |
| `sesionesPrepararBase()` (reinicio más `RolesSeeder`) | 60 a 130 ms |
| Una petición HTTP de la API | 15 a 30 ms |
| Commit de una fila con `synchronous_commit=on` / `off` | **3,5 ms / 0,9 ms** |
| Pruebas de 3 s o más (casi todas pagan una reconstrucción con `migrate:fresh`) | 40 pruebas, 231 s |

Conclusión: el reinicio rápido de H1 (`9b051a6`) ya es barato. El costo está en la espera del disco en cada commit (`fsync` en el volumen de Docker), que se paga en cada `insert` de los mundos de prueba (autocommit por la conexión `propietario`), en cada petición y en cada reconstrucción.

## 3. Por qué no son «transacciones por prueba»

La especificación da ese ejemplo, pero en este proyecto no es aplicable sin salir de las rutas permitidas:

- Las pruebas usan **dos conexiones con roles distintos**: `propietario` siembra (`mundoInsertar`, `sesionesCrearUsuario`) y `pgsql` (rol de la aplicación, sin `UPDATE` ni `DELETE` sobre historiales) atiende las peticiones. Lo que una conexión escribe dentro de una transacción sin confirmar es invisible para la otra (PostgreSQL no tiene lectura no confirmada).
- Compartir un solo PDO entre las dos haría que la aplicación corriera como propietario. Las pruebas de solo inserción y de permisos dejarían de ejercer el rol real, y eso es **debilitar** pruebas.
- Cambiar de rol dentro de una sola sesión (`SET ROLE`) exige que `propietario` sea miembro del rol de la aplicación. Eso cambia `backend/docker/db/init/01-roles-y-bases.sh` y la integración continua, que están fuera de las rutas de T00.
- Las pruebas que ensucian el esquema (agregan una restricción o un disparador al historial y luego hacen una petición) no pueden hacerlo dentro de una transacción: la petición de `pgsql` se bloquearía esperando el candado del DDL sin confirmar.

## 4. Propuesta

**Un solo cambio, en `tests/TestCase.php`:** en `setUp()`, las dos conexiones de prueba (`pgsql` y `propietario`) se abren con `synchronous_commit = off`. Se usa la clave `synchronous_commit` que ya admite `PostgresConnector` de Laravel, y se purgan las dos conexiones para que ninguna abierta durante el arranque quede con el valor anterior.

- **Qué cambia:** el commit regresa sin esperar a que el WAL llegue al disco. La visibilidad entre sesiones, los candados, las restricciones, los disparadores, los permisos y las reversiones son idénticos. Solo cambia la durabilidad ante una caída del servidor, que en una base de pruebas que se vacía en cada prueba no importa.
- **Qué no cambia:** la configuración de producción y de desarrollo (`config/database.php` no se toca), el esquema, las dependencias, el reinicio rápido, la red de seguridad de la huella y ninguna aserción.
- **Evidencia previa, sin tocar código:** `composer verificar` con `PGOPTIONS='-c synchronous_commit=off'` (el mismo efecto, aplicado desde el entorno) pasó con código 0: **Pest 406 s** (`Duration: 406.07s`) frente a 618 s, **−34 %**; total 440 s. Las 1 224 pruebas pasaron con las mismas 8 285 aserciones. Por suite: Feature 265 s, Integration 99 s, Arch 35 s, Unit 3 s. Las pruebas de 3 s o más bajaron de 40 (231 s) a 9 (50 s).
- **Frente al criterio de §8:** 25 % menos que 639 s es 479 s o menos. La medición previa da 406 s (−36 % frente a T08 y −34 % frente a la línea base de hoy).

**Descartado por ahora (palanca 2):** deshacer de forma dirigida el DDL de las pruebas que marcan el esquema como sucio, en lugar de `migrate:fresh`. Con la palanca 1 quedan unos 50 s en reconstrucciones. Además, `ReinicioDeEsquemaTest` exige que esos casos devuelvan `'completo'`, así que habría que cambiar el significado de ese valor. No compensa el riesgo. Queda como opción si H4 vuelve a acercar Pest al límite.

## 5. Pruebas

Pruebas primero:

1. `PT-08: las conexiones de prueba confirman sin esperar al disco`. Comprueba que `show synchronous_commit` devuelve `off` en `pgsql` y en `propietario` dentro de una prueba con `TestCase`. Debe fallar antes del cambio (devuelve `on`), y se pegará la salida.
2. La suite completa sin cambiar ninguna aserción. Es el criterio principal.

**Necesita autorización:** la prueba 1 no cabe en las rutas de T00 (`tests/TestCase.php`, `tests/Ayudantes/**` solo `EsquemaDePrueba`, `tests/Pest.php` y reportes). Propongo un archivo nuevo, `tests/Integration/Compartido/ConexionesDePruebaTest.php`. La alternativa es no escribir prueba propia y usar como evidencia solo la medición.

## 6. Medición después del cambio

Dos corridas de `make verificar`, una tras otra y sin nada más en marcha, con la tabla por etapa de la sección 1. La variación entre corridas sin cambios ronda el 10 % (T08 639 s, T09 555 s, hoy 618 s); por eso son dos.

## 7. Archivos

- `backend/tests/TestCase.php` (modificar `setUp()` y su PHPDoc).
- `backend/tests/Integration/Compartido/ConexionesDePruebaTest.php` (nuevo, si se autoriza).
- `docs/reportes/f1-h4-t00.md` (reporte), `docs/reportes/f1-h4-decisiones-del-agente.md` (DA-01: palanca elegida y por qué no transacciones).
- Commit `F1-H4-T00: confirmacion asincrona en las conexiones de prueba (PT-08)`.

## 8. Preguntas al responsable

1. ¿Apruebas la palanca 1 (`synchronous_commit = off` solo en pruebas) en lugar de transacciones por prueba?
2. ¿Autorizas el archivo de prueba de la sección 5?
3. ¿En qué rama trabajo? La especificación dice «la que indique el responsable». Propongo `hito/f1-h4` desde `f1-h3`, igual que en H3.
4. **Coautoría de los commits:** las instrucciones piden `Co-Authored-By: Claude Haiku 5.5`, pero el modelo de esta sesión es Claude Opus 5.5. ¿Lo dejo con el modelo real (`Claude Opus 5.5 <noreply@anthropic.com>`) o conservo el texto indicado?
