# Decisiones del agente en F1-H4

Decisiones que la especificación no cubre (`docs/hitos/f1-h2.md` §0.3, aplicable a H4 por `docs/hitos/f1-h4.md` §0 y `docs/hitos/f1-h3.md` §0). Cada una dice qué faltaba, qué se eligió, por qué y cómo se revierte.

## Resumen

| DA | Tarea | Qué es | ¿Cambia permisos o privacidad? | ¿Necesita aprobación? |
|---|---|---|---|---|
| DA-01 | T00 | `synchronous_commit = off` en las conexiones de prueba, en lugar de transacciones por prueba | No | **Aprobada** por el responsable el 30/10/2026 (especificación v1.0.1) |
| DA-02 | T00 | Riesgo de durabilidad de `synchronous_commit = off` | No | Registrada a pedido del responsable |
| DA-03 | Todas | Identidad del modelo de la sesión frente a la línea `Co-Authored-By` de los commits | No | Indicada por el responsable |
| DA-04 | T01 | `entrenador_id` en `umbrales_rendimiento` y `evaluaciones_rendimiento` (desviación de `docs/arquitectura.md` §7.6) | Sí: cada entrenador tiene su umbral y su evaluación (restringe, RN-14) | Pedida por §5.1 de la especificación |
| DA-05 | T01 | La evaluación copia `calificacion_minima` y `ventana_partidos`; `umbral_id` y `plantilla_id` opcionales | No | **Indicada** por el responsable el 30/10/2026 (v1.0.2) |
| DA-06 | T01 | `CHECK` de origen de la plantilla y de promedio nulo con datos insuficientes | No | **Indicada** por el responsable el 30/10/2026 (v1.0.2) |
| DA-07 | T01 | Rangos de fábrica de las métricas: enteros de 0 a 99; pases completados de 0 a 300 | No | **Indicada** por el responsable el 30/10/2026 (v1.0.2); por validar (AB-01) |
| DA-08 | T01 | Los seeders de Rendimiento solo insertan lo que falta: no deshacen un ajuste hecho en la base | No | No |
| DA-09 | T01 | `vigente_desde` y `vigente_hasta` del umbral son instantes (`timestamptz`), no fechas | No | No |
| DA-10 | T01 | `evaluaciones_rendimiento` usa la macro `soloInsercion` de Compartido | No | No |

## DA-01 (T00): `synchronous_commit = off` en lugar de transacciones por prueba

- **Qué faltaba:** §8 (T00) propone, como ejemplo, «transacciones por prueba en lugar de borrado completo cuando el caso lo permita».
- **Qué se eligió:** `tests/TestCase.php` abre las conexiones `pgsql` y `propietario` con `synchronous_commit = off` (clave de configuración que admite `PostgresConnector` de Laravel), y las purga en `setUp()`.
- **Por qué:**
  - **Las transacciones por prueba no son aplicables.** Las pruebas siembran con `propietario` y piden con `pgsql` (rol de la aplicación). Lo que una conexión escribe sin confirmar es invisible para la otra. Unirlas en una sola sesión exige que la aplicación corra como propietario, lo que debilita las pruebas de permisos, o cambiar los roles de la base, que está fuera de las rutas.
  - **El reinicio rápido ya era barato** (20 a 50 ms). El costo estaba en la espera del disco en cada commit: 3,5 ms frente a 0,9 ms (`docs/reportes/f1-h4-plan-t00.md` §2).
- **Cómo se revierte:** se quita el bucle de `setUp()` y el archivo `ConexionesDePruebaTest.php`.

## DA-02 (T00): riesgo de durabilidad

- **Qué cambia:** con `synchronous_commit = off`, PostgreSQL responde al commit antes de escribir el WAL en el disco. Si el servidor de base de datos se cae, se pueden perder las transacciones confirmadas en la ventana previa a la caída: como máximo, tres veces `wal_writer_delay`, es decir, **unos 600 ms** con el valor por defecto de 200 ms. (El plan y la entrega de T00 decían por error unos 200 ms; corregido a pedido del responsable.) La base no queda corrupta ni inconsistente: se pierden transacciones enteras, nunca la mitad de una.
- **Dónde aplica:** solo en las conexiones que abre `Tests\TestCase`, es decir, en `gestordep_pruebas` durante Pest (local y CI). No toca `config/database.php`, la base de desarrollo ni producción. `make fresh`, `make humo` y la aplicación siguen confirmando de forma síncrona.
- **Por qué es aceptable:** la base de pruebas se vacía antes de cada prueba y se reconstruye si cambia su estructura (`EsquemaDePrueba`). Una caída a mitad de la suite haría fallar la corrida igual que antes. La visibilidad entre sesiones, los candados, las restricciones, los disparadores, los permisos y las reversiones no cambian, así que ninguna prueba pierde lo que verifica.
- **Riesgo residual:** si alguien copia el ajuste a la configuración de producción, sí perdería durabilidad. El comentario de `setUp()` y esta DA dejan claro que es solo para pruebas.

## DA-03: identidad del modelo de la sesión

- **Hecho:** la sesión que ejecuta H4 se identifica como **Claude Opus 5.5** (`claude-opus-5-5`).
- **Qué se hace:** por indicación expresa del responsable (30/10/2026), los commits conservan la línea `Co-Authored-By: Claude Haiku 5.5 <noreply@anthropic.com>` y la línea `Claude-Session`. El agente lo señaló en el plan de T00 (pregunta 4) antes de hacer cualquier commit.

## DA-04 (T01): `entrenador_id` en umbrales y evaluaciones

- **Qué faltaba:** `docs/arquitectura.md` §7.6 no tiene `entrenador_id` en `umbrales_rendimiento` ni en `evaluaciones_rendimiento`. La especificación (§5.1, nota) lo agrega porque RN-08 y HU-16 son por entrenador, y pide registrarlo como DA.
- **Qué se eligió:** `entrenador_id` obligatorio, con clave foránea a `usuarios`, en las dos tablas. La UQ parcial del umbral vigente es (`jugador_id`, `equipo_id`, `entrenador_id`) donde `vigente_hasta IS NULL` y `eliminado_en IS NULL`. El índice de evaluaciones es (`jugador_id`, `equipo_id`, `entrenador_id`, `calculado_en desc`).
- **Por qué:** RN-14 y HU-16, criterio 3: cada entrenador evalúa con su propio umbral y sus propios datos.
- **Cómo se revierte:** una migración nueva que quite la columna y rehaga el índice. La arquitectura queda por actualizar a decisión del responsable.

## DA-05 (T01): la evaluación guarda lo aplicado

- **Qué faltaba:** sin umbral propio, la evaluación usa la plantilla de fábrica (§3.3), y no hay fila de `umbrales_rendimiento` a la que apuntar.
- **Qué se eligió (indicado por el responsable):** `calificacion_minima numeric(4,2)` y `ventana_partidos`, obligatorias, copiadas del umbral o de la plantilla en el momento del cálculo. `umbral_id` y `plantilla_id` son opcionales, como referencias de procedencia, sin `CHECK` de exclusión.
- **Por qué:** `docs/arquitectura.md` §7.1 conserva las evaluaciones porque «lo que el entrenador vio al decidir» no se reconstruye. Con los valores copiados, la evaluación no depende de un umbral o una plantilla que cambie después.
- **Cómo se revierte:** una migración nueva que quite las tres columnas.

## DA-06 (T01): dos `CHECK` de consistencia

- **Qué faltaba:** §5.1 dice que `creada_por` es nulo si la plantilla es de fábrica y que `promedio` es nulo con datos insuficientes, sin nombrar una restricción.
- **Qué se eligió (indicado por el responsable):** `ck_plantillas_evaluacion_origen`: `(creada_por is null) = es_de_fabrica`; `ck_evaluaciones_rendimiento_promedio`: `(promedio is null) = (resultado = 'datos_insuficientes')`. Cada uno tiene en `MigracionesDeRendimientoTest` inserciones que fallan y otras que pasan.
- **Cómo se revierte:** una migración nueva que quite cada `CHECK`.

## DA-07 (T01): rangos de fábrica de las métricas

- **Qué faltaba:** ningún documento da `minimo` ni `maximo` de las métricas, y la captura se valida contra ellos (`valor_fuera_de_rango`).
- **Qué se eligió (indicado por el responsable):** tipo `entero` y mínimo 0 en las nueve; máximo 99 en todas, salvo `pases_completados`, con máximo 300. Por validar con la institución (AB-01); el cambio se hace en la tabla `metricas`, no en el código.
- **Cómo se revierte:** se actualizan las filas de `metricas`.

## DA-08 (T01): los seeders de Rendimiento no deshacen ajustes

- **Qué faltaba:** §8.1 regla 6 pide seeders idempotentes, sin decir si un seeder repetido restaura los valores de fábrica. `PosicionesSeeder` actualiza el nombre; `CriterioGeneralSeeder` no toca lo que existe.
- **Qué se eligió:** como `CriterioGeneralSeeder`. `MetricasSeeder` inserta solo las métricas y relaciones que faltan; `PlantillasDeFabricaSeeder`, solo la «Desarrollo» de fábrica que falte en cada posición.
- **Por qué:** §3.2 y AB-01 dicen que la institución ajusta métricas y umbrales en la base. Un `make fresh --seed` posterior no debe deshacer ese ajuste (RNF-09).
- **Cómo se revierte:** el seeder actualiza los campos en lugar de saltarse la fila.

## DA-09 (T01): vigencias del umbral como instantes

- **Qué faltaba:** §5.1 nombra `vigente_desde` y `vigente_hasta` sin tipo. El criterio académico de H2 usa `date`.
- **Qué se eligió:** `timestamptz`.
- **Por qué:** un entrenador puede cambiar el umbral más de una vez el mismo día. HU-15 pide registrar cuándo, y la evaluación tiene que saber qué umbral estaba vigente en `calculado_en`. Con fechas, dos cambios del mismo día serían indistinguibles.
- **Cómo se revierte:** una migración nueva que cambie el tipo a `date`.

## DA-10 (T01): `evaluaciones_rendimiento` de solo inserción con la macro de Compartido

- **Qué faltaba:** §5.1 dice «solo inserción, sin auditoría», sin decir cómo. Hasta H3, la macro `soloInsercion` solo se usaba en los historiales.
- **Qué se eligió:** `Schema::soloInsercion('evaluaciones_rendimiento')`: un disparador que rechaza `UPDATE` y `DELETE` incluso al propietario, más la revocación de esos permisos al rol de la aplicación.
- **Efecto visible:** el error del disparador dice `historial_solo_insercion: evaluaciones_rendimiento`, porque la función de la macro es compartida. No llega al usuario: ninguna ruta actualiza evaluaciones.
- **Cómo se revierte:** una migración nueva que quite el disparador y devuelva los permisos.
