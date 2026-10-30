# Decisiones del agente en F1-H4

Decisiones que la especificación no cubre (`docs/hitos/f1-h2.md` §0.3, aplicable a H4 por `docs/hitos/f1-h4.md` §0 y `docs/hitos/f1-h3.md` §0). Cada una dice qué faltaba, qué se eligió, por qué y cómo se revierte.

## Resumen

| DA | Tarea | Qué es | ¿Cambia permisos o privacidad? | ¿Necesita aprobación? |
|---|---|---|---|---|
| DA-01 | T00 | `synchronous_commit = off` en las conexiones de prueba, en lugar de transacciones por prueba | No | **Aprobada** por el responsable el 30/10/2026 (especificación v1.0.1) |
| DA-02 | T00 | Riesgo de durabilidad de `synchronous_commit = off` | No | Registrada a pedido del responsable |
| DA-03 | Todas | Identidad del modelo de la sesión frente a la línea `Co-Authored-By` de los commits | No | Indicada por el responsable |

## DA-01 (T00): `synchronous_commit = off` en lugar de transacciones por prueba

- **Qué faltaba:** §8 (T00) propone, como ejemplo, «transacciones por prueba en lugar de borrado completo cuando el caso lo permita».
- **Qué se eligió:** `tests/TestCase.php` abre las conexiones `pgsql` y `propietario` con `synchronous_commit = off` (clave de configuración que admite `PostgresConnector` de Laravel), y las purga en `setUp()`.
- **Por qué:**
  - **Las transacciones por prueba no son aplicables.** Las pruebas siembran con `propietario` y piden con `pgsql` (rol de la aplicación). Lo que una conexión escribe sin confirmar es invisible para la otra. Unirlas en una sola sesión exige que la aplicación corra como propietario, lo que debilita las pruebas de permisos, o cambiar los roles de la base, que está fuera de las rutas.
  - **El reinicio rápido ya era barato** (20 a 50 ms). El costo estaba en la espera del disco en cada commit: 3,5 ms frente a 0,9 ms (`docs/reportes/f1-h4-plan-t00.md` §2).
- **Cómo se revierte:** se quita el bucle de `setUp()` y el archivo `ConexionesDePruebaTest.php`.

## DA-02 (T00): riesgo de durabilidad

- **Qué cambia:** con `synchronous_commit = off`, PostgreSQL responde al commit antes de escribir el WAL en el disco. Si el servidor de base de datos se cae, se pueden perder las transacciones de los últimos instantes (hasta unas tres veces `wal_writer_delay`, 200 ms por defecto). La base no queda corrupta ni inconsistente: se pierden transacciones enteras, nunca la mitad de una.
- **Dónde aplica:** solo en las conexiones que abre `Tests\TestCase`, es decir, en `gestordep_pruebas` durante Pest (local y CI). No toca `config/database.php`, la base de desarrollo ni producción. `make fresh`, `make humo` y la aplicación siguen confirmando de forma síncrona.
- **Por qué es aceptable:** la base de pruebas se vacía antes de cada prueba y se reconstruye si cambia su estructura (`EsquemaDePrueba`). Una caída a mitad de la suite haría fallar la corrida igual que antes. La visibilidad entre sesiones, los candados, las restricciones, los disparadores, los permisos y las reversiones no cambian, así que ninguna prueba pierde lo que verifica.
- **Riesgo residual:** si alguien copia el ajuste a la configuración de producción, sí perdería durabilidad. El comentario de `setUp()` y esta DA dejan claro que es solo para pruebas.

## DA-03: identidad del modelo de la sesión

- **Hecho:** la sesión que ejecuta H4 se identifica como **Claude Opus 5.5** (`claude-opus-5-5`).
- **Qué se hace:** por indicación expresa del responsable (30/10/2026), los commits conservan la línea `Co-Authored-By: Claude Haiku 5.5 <noreply@anthropic.com>` y la línea `Claude-Session`. El agente lo señaló en el plan de T00 (pregunta 4) antes de hacer cualquier commit.
