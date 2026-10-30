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
| DA-11 | T02 | Orden de las comprobaciones de la captura; tipos en la solicitud y rangos en el dominio | Sí: un jugador fuera del alcance da 404 antes que `jugador_no_convocado` (restringe) | No |
| DA-12 | T02 | Capturar excluye a los jugadores en baja; leer y corregir lo ya capturado los incluye | Sí (restringe la captura) | No |
| DA-13 | T02 | Un convocado deja de estar pendiente si tiene cualquier rendimiento, de cualquier entrenador | No (no revela de quién) | No |
| DA-14 | T02 | Los valores de métrica se reciben como enteros JSON y se devuelven según el tipo de la métrica | No | No |
| DA-15 | T02 | Mensaje propio de `valor_fuera_de_rango` en Rendimiento (`errores.rendimiento`) | No | No |
| DA-16 | T02 | Los pendientes de captura viven en `src/Consultas` (arquitectura §4.3) | No | **Indicada** por el responsable el 30/10/2026 (v1.0.3) |
| DA-17 | T02 | Puerto `PosicionEnEquipo` con adaptador en Plantel, y prueba de arquitectura de Rendimiento sin Plantel | No | **Indicada** por el responsable el 30/10/2026 (v1.0.3) |
| DA-18 | T02 | Un rendimiento ajeno responde 404; `rendimiento_no_editable` queda reservado | Sí (restringe: no revela que existe) | **Indicada** por el responsable el 30/10/2026 (v1.0.3) |
| DA-19 | T02 | Campos de las respuestas: rendimiento sin `registrado_por`, catálogo sin nombres de posición, pendientes con su conteo | Sí (revela lo mínimo) | No |
| DA-20 | T02 | Un jugador visible y convocado pero sin membresía vigente en el equipo del partido da 404 | Sí (restringe) | No |
| DA-21 | T02 | El enlace del adaptador `ConvocatoriaDePartido` va en `CompetenciaServiceProvider` | No | No |

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

## DA-11 (T02): orden de las comprobaciones de la captura

- **Qué faltaba:** §3.3 y §6.3 dan los códigos, pero no su orden cuando hay más de un error.
- **Qué se eligió:**
  1. Partido ajeno o inexistente: 404, en `authorize()` (§6.1).
  2. Tipos del cuerpo: 422 `validacion_fallida`.
  3. Partido no jugado: 409 `partido_no_jugado`.
  4. Jugador inexistente, fuera del alcance o en baja: el mismo 404.
  5. Jugador no convocado: 422 `jugador_no_convocado`.
  6. Captura previa: 409 `rendimiento_duplicado`.
  7. Rangos de los datos de juego, en el orden de los campos: minutos, amarillas, rojas, calificación y observación.
  8. Métricas: primero que cada una sea de la posición (`metricas.N.metrica_id`), después que estén todas las obligatorias (`metricas`) y al final el rango de cada valor (`metricas.N.valor`).
- **Detalles:** la solicitud valida solo los tipos (`integer:strict`, `boolean:strict`); los rangos los valida el dominio, para responder `valor_fuera_de_rango` y no el código de la regla de Laravel. La observación no lleva `max` en la solicitud: más de 500 caracteres es `valor_fuera_de_rango`, como pide §6.3.
- **Por qué:** es el patrón de H3 (estado antes que rango, DA-33) y de DA-32 (404 antes que `jugador_no_convocado`, para no revelar a un jugador ajeno).
- **Cómo se revierte:** se reordenan las comprobaciones en `CapturarRendimiento` y en `MetricasDeLaPosicion`.

## DA-12 (T02): capturar excluye a los jugadores en baja; leer y corregir los incluye

- **Qué faltaba:** la especificación no dice qué pasa con un jugador convocado que después se da de baja.
- **Qué se eligió:** `VisibilidadDeJugador` sin bajas para capturar (404, como la alineación en DA-34 de H3), y con bajas para listar y corregir un rendimiento ya capturado (como el historial de asistencia en DA-20 de H3).
- **Cómo se revierte:** se cambia el argumento `incluirBajas` en `CapturarRendimiento`, `BuscarRendimientoDelRegistrante` y `ConsultaDeRendimientosSql`.

## DA-13 (T02): cuándo un convocado está pendiente

- **Qué faltaba:** §3.3 dice «al menos un convocado sin rendimiento registrado», sin decir registrado por quién. La UQ parcial (`partido_id`, `jugador_id`) permite una sola captura por jugador y partido, sea del entrenador que sea.
- **Qué se eligió:** un convocado no eliminado, visible por `AlcanceDeJugadores` sin bajas, está pendiente si no tiene **ningún** rendimiento no eliminado en el partido. Si E3 ya capturó a un jugador, E1 no puede capturarlo (409), así que para E1 tampoco queda pendiente. La lista solo trae partidos jugados de los equipos que entrena el usuario (`EquiposDelEntrenador`), del más reciente al más antiguo, con el número de convocados pendientes.
- **Privacidad:** el conteo no dice quién capturó ni qué capturó (RN-14).
- **Cómo se revierte:** se agrega `registrado_por = usuario` a la subconsulta `not exists`.

## DA-14 (T02): valores de métrica como enteros JSON

- **Qué faltaba:** §6.2 dice `{metrica_id, valor}` sin tipo. DD-22 manda decimales como cadena y enteros como número.
- **Qué se eligió:** la solicitud exige `valor` entero (`integer:strict`), porque las nueve métricas sembradas son enteras (DA-07). La respuesta y el catálogo devuelven el valor, el mínimo y el máximo como entero si la métrica es entera y como cadena con dos decimales si es decimal (esquema `NumeroDeMetrica`).
- **Límite:** una métrica decimal sembrada en el futuro no se podría capturar hasta aceptar también cadenas en la solicitud. El dominio ya maneja decimales (`Metrica::admite`).
- **Cómo se revierte:** se acepta un entero o una cadena decimal en `metricas.*.valor`.

## DA-15 (T02): mensaje propio de `valor_fuera_de_rango` en Rendimiento

- **Qué faltaba:** `lang/es/errores.php` tiene `valor_fuera_de_rango` = «Los goles deben estar entre 0 y 99.» (H3). Una prueba de H3 (`AlineacionYResultadoTest`, fila 8) comprueba ese mensaje, así que no se puede generalizar sin cambiar una aserción.
- **Qué se eligió:** el código sigue siendo `valor_fuera_de_rango` (§6.3). `FormatoDeError` busca el mensaje de las reglas de campo de Rendimiento primero en `errores.rendimiento.{codigo}` («El valor está fuera del rango permitido.») y, si no existe, en `errores.reglas.{codigo}`.
- **Cómo se revierte:** se borra la clave `errores.rendimiento` y el método `reglaDeCampoDeRendimiento`.

## DA-16 (T02): pendientes de captura en Consultas (indicada por el responsable)

- **Decisión:** `GET /partidos/pendientes-de-rendimiento` es una sola consulta en `src/Consultas`, según `docs/arquitectura.md` §4.3: `ConsultaDePendientesDeRendimiento` (Aplicación) y `ConsultaDePendientesDeRendimientoSql` (Infraestructura), con `AlcanceDeJugadores` y filtro por los equipos del entrenador. Especificación v1.0.3, rutas de T02.

## DA-17 (T02): puerto `PosicionEnEquipo` (indicada por el responsable)

- **Decisión:** el puerto `PosicionEnEquipo` en `src/Compartido/Aplicacion` devuelve la posición de la membresía vigente y no eliminada (DD-05). Su adaptador, `PosicionEnEquipoPlantel`, vive en `src/Plantel/Infraestructura`, con su enlace en `PlantelServiceProvider`. `tests/Arch/RendimientoSinPlantelTest.php` comprueba que Rendimiento no importa Plantel ni nombra sus tablas, como `CompetenciaSinPlantelTest`. Especificación v1.0.3.

## DA-18 (T02): un rendimiento ajeno da 404 (indicada por el responsable)

- **Decisión:** corregir un rendimiento de otro entrenador, aunque entrene el mismo equipo, responde 404 con el mismo cuerpo que uno inexistente (§3.3, RN-14). `rendimiento_no_editable` queda reservado en §6.3, sin caso de prueba en H4. La fila 12 de §7.1 se eliminó (en H3 un partido `jugado` no cambia de estado) y se agregó la fila 13 (E3 corrige el de E1, 404). Especificación v1.0.3.

## DA-19 (T02): campos de las respuestas

- **Qué faltaba:** §6.2 solo da los campos del desempeño.
- **Qué se eligió:**
  - **Rendimiento:** `id`, `partido_id`, `jugador_id`, `equipo_id`, `posicion_id`, los datos de juego, `observacion`, `metricas` (`{metrica_id, valor}`, en el orden de la posición) y `actualizado_en`. Sin `registrado_por`, que siempre es quien consulta.
  - **Catálogo:** solo las métricas activas, ordenadas por código, con `posiciones` (`posicion_id`, `obligatoria`, `orden`), sin nombres de posición, porque Rendimiento no lee Plantel (DA-17).
  - **Pendientes:** `partido_id`, `equipo_id`, `inicia_en` y `convocados_sin_rendimiento`, sin datos de jugadores.
  - **Paginación:** la lista por partido y los pendientes, 15 por página (como el resto); el catálogo no se pagina.
- **Cómo se revierte:** se agregan campos en los recursos y en `openapi.yaml`.

## DA-20 (T02): convocado sin membresía vigente en el equipo

- **Qué faltaba:** un jugador puede seguir convocado y visible (por otro equipo del entrenador) después de dejar el equipo del partido. Entonces no tiene posición en ese equipo (DD-05) y no hay métricas que pedirle.
- **Qué se eligió:** 404, el mismo cuerpo que un jugador fuera del alcance.
- **Cómo se revierte:** se cambia la excepción en `CapturarRendimiento` cuando `PosicionEnEquipo` devuelve nulo.

## DA-21 (T02): enlace del adaptador de Competencia

- **Qué faltaba:** las rutas de T02 permiten en `src/Competencia/Infraestructura/**` «solo el adaptador», y no mencionan su enlace en el contenedor.
- **Qué se eligió:** una línea en `CompetenciaServiceProvider` (`ConvocatoriaDePartido` → `ConvocatoriaDePartidoCompetencia`), como hizo H3 con los adaptadores de Plantel. Si el enlace fuera en `RendimientoServiceProvider`, Rendimiento importaría una clase de Competencia.
- **Cómo se revierte:** se mueve el enlace al proveedor que el responsable indique.

## DA-22 (T03): prueba de la carrera de dos capturas (indicada por el responsable)

- **Decisión:** `CapturaDeRendimientoTest` reemplaza en el contenedor `RepositorioDeRendimientos` por un decorador cuyo `existeDe` siempre devuelve falso. Así la segunda captura llega al `insert`, choca con `uq_rendimientos_partido_partido_jugador` y debe responder 409 `rendimiento_duplicado` en `jugador_id`, sin escribir nada. Cubre las líneas 64 y 65 de `RepositorioDeRendimientosEloquent` que T02 dejó sin cubrir.
- **Nota:** la prueba pasó desde la primera corrida, porque el código que atrapa la violación ya existía desde T02; no cambia código de producción.

## DA-23 (T03): migración `050500` corregida en su lugar (autorizada por el responsable)

- **Decisión:** como H4 no se ha fusionado, se editó `2026_10_30_050500_crear_tabla_plantillas_evaluacion.php` en lugar de crear una migración nueva. `uq_plantillas_evaluacion_nombre_posicion` se reemplazó por `uq_plantillas_evaluacion_propia` (`nombre`, `posicion_id`, `creada_por`, donde `creada_por` no es nulo) y `uq_plantillas_evaluacion_fabrica` (`nombre`, `posicion_id`, donde `creada_por` es nulo), ambos con `eliminado_en is null`. Especificación v1.0.4 §5.1.
- **Verificación:** las pruebas de cada índice (una inserción que falla y otra que pasa) y la prueba de `migrate:rollback` existente, que deshace y vuelve a aplicar el grupo de Rendimiento.
- **Riesgo:** una base local migrada antes de este cambio conserva el índice viejo. Se arregla con `make fresh`.

## DA-24 (T03): RN-06 como enumeración del dominio (indicada por el responsable)

- **Decisión:** `src/Rendimiento/Dominio/CategoriaDeCalificacion.php` es una enumeración con `deficiente`, `regular`, `bueno` y `sobresaliente`, y `de(int)` aplica los cortes de RN-06. Fuera de 1 a 10 lanza `InvalidArgumentException`, porque `DatosDeJuego` ya garantiza el rango. `RendimientoRecurso` agrega `categoria_calificacion`, de solo lectura, así que el campo sale en la captura, la corrección y la lista por partido. Sin tabla nueva. Especificación v1.0.4 §3.1 y §6.2.

## DA-25 (T03): límite técnico de la ventana

- **Decisión, del responsable:** `ventana_partidos` acepta de 1 a 32 767. El máximo es el de la columna `smallint`; existe para que un valor mayor dé `valor_fuera_de_rango` y no un 500. **No es una regla de negocio** ni un parámetro de RNF-09. Vive como constante privada de `CriterioDeEvaluacion`.
- **Cómo se revierte:** si la institución fija una ventana máxima, se lee de una tabla y la constante queda solo como tope técnico.

## DA-26 (T03): qué plantilla de fábrica se aplica

- **Qué faltaba:** §3.3 habla de «la plantilla de fábrica de su posición», en singular, pero el índice de fábrica admite varias con distinto nombre en una posición.
- **Qué se eligió:**
  - Se aplica la más antigua (`creado_en`, después `id`). Hoy solo existe «Desarrollo».
  - Si una posición no tiene ninguna, el GET del umbral y el PUT sin cuerpo responden 404, porque no hay umbral que aplicar. `PlantillasDeFabricaSeeder` siembra una en cada posición.
- **Cómo se revierte:** se cambia el orden en `RepositorioDePlantillasSql::deFabricaDe`.

## DA-27 (T03): carrera de dos cambios del mismo umbral

- **Qué faltaba:** dos PUT simultáneos pueden chocar con `uq_umbrales_rendimiento_vigente`.
- **Qué se eligió:** el repositorio convierte la violación en `UmbralConcurrente` y la API responde 409 `conflicto`, el código genérico que ya existía, sin campo. La transacción se deshace completa y no quedan dos umbrales vigentes. Así estaba en el plan aprobado.

## DA-28 (T03): jugador en baja en el umbral — **RETIRADA en F1-H4-FIX**

- **Qué se había elegido en T03:** el GET consultaba el umbral de un jugador en baja, pero el PUT lo trataba como fuera del alcance (404).
- **Por qué se retiró:** el responsable pidió quitarla salvo que hubiera una fuente en `docs/requisitos.md`, y no la hay. RN-23 y HU-29 solo sacan al jugador en baja de convocatorias, listados y alertas; nada impide fijar su umbral.
- **Estado actual:** `ComprobarJugadorDelEquipo` incluye siempre a los jugadores en baja, en el GET y en el PUT; se quitó el parámetro `incluirBajas`. Ninguna prueba ejercía el bloqueo, así que no hubo prueba que quitar. La captura (DA-12) no cambia.

## DA-29 (T03): «su posición» en las plantillas propias

- **Qué faltaba:** §3.3 y §6.1 dicen que el entrenador crea plantillas «de su posición», pero un entrenador no tiene posición.
- **Qué se eligió:** se lee como la posición de la plantilla (`posicion_id` del cuerpo). Cualquier entrenador crea plantillas de cualquier posición. Una posición inexistente o con UUID no v7 da 422 `posicion_inexistente`, con el código de Competencia. La existencia la comprueba la clave foránea al escribir, como DA-31 de H3, porque Rendimiento no lee `posiciones`.

## DA-30 (T03): historial del cambio de umbral

- **Qué se eligió:**
  - **Cierre del vigente:** historial `actualizar`, con la instantánea completa del umbral en `datos_anteriores` (calificación y ventana incluidas, que son «el valor anterior» de HU-15). `datos_nuevos` lleva la misma instantánea con `vigente_hasta`.
  - **Umbral nuevo:** historial `crear`. El cierre y el alta comparten el instante, así que `vigente_hasta` del anterior es igual a `vigente_desde` del nuevo.
  - **Primer PUT:** no cierra nada, por decisión del responsable. La plantilla de fábrica que se aplicaba antes no cambia, así que no tiene historial.
  - **Autor y fecha:** son `usuario_id` y `ocurrido_en` del historial.

## DA-31 (T03): campos desconocidos y tipos en plantillas y umbral

- **Qué se eligió:** como la captura de T02, `SolicitudDePlantilla` y `SolicitudDeUmbral` rechazan cualquier campo no admitido con `validacion_fallida` (por ejemplo, `es_de_fabrica` o `jugador_id`).
  - `calificacion_minima` viaja como cadena con hasta dos decimales (DD-51), con la misma expresión que Académico.
  - `ventana_partidos`, como entero JSON estricto.
  - En el PUT, la calificación y la ventana van juntas (`required_with`). `umbral_ambiguo` lo decide el caso de uso después de validar los tipos.

## DA-32 (T04): puerto `Transaccion` para escribir y evaluar juntos

- **Qué faltaba:** §3.3 pide que la evaluación se inserte en la misma transacción que la captura, la corrección o el cambio de umbral, y cada repositorio ya abría su propia transacción.
- **Qué se eligió:**
  - Un puerto `Transaccion` en `src/Rendimiento/Aplicacion`, con su adaptador `TransaccionSql` (`ConnectionInterface::transaction`). `CapturarRendimiento`, `CorregirRendimiento` y `FijarUmbral` envuelven la escritura y `RecalcularEvaluacion`.
  - Las transacciones de los repositorios quedan anidadas como puntos de guardado. Las carreras de T02 y T03 (`rendimiento_duplicado`, `conflicto`) siguen deshaciendo todo; sus pruebas pasan sin cambios.
  - Una captura rechazada (422) no deja evaluación, y hay prueba de ello.
- **Cómo se revierte:** se mueve la inserción de la evaluación dentro de cada repositorio.

## DA-33 (T04): sin criterio aplicable no se inserta evaluación

- **Qué faltaba:** si un jugador no tiene umbral propio y no hay plantilla de fábrica para su posición (ni la actual ni la guardada en el rendimiento), no hay criterio con qué calcular.
- **Qué se eligió:** la captura, la corrección o el cambio de umbral se guardan igual y no se inserta evaluación; la lectura del desempeño responde 404, como el GET del umbral (DA-26). Con los seeders de fábrica no ocurre.
- **Cómo se revierte:** se lanza una excepción en `RecalcularEvaluacion::ejecutar` en lugar de omitir la inserción.

## DA-34 (T04): un rendimiento sin fecha de partido no cuenta

- **Qué faltaba:** `InicioDePartidos` no devuelve partidos eliminados ni inexistentes, así que su rendimiento no tiene fecha con que ordenarse.
- **Qué se eligió:** ese rendimiento no cuenta en el promedio. En H3 un partido no se elimina desde la API, así que hoy no ocurre.
- **Cómo se revierte:** en `CalificacionesDelEntrenadorSql`, se ordena al final en lugar de descartarlo.

## DA-35 (T04): decisiones menores del plan, sin objeción del responsable

- **Corrección sin cambio:** una corrección que no cambia la calificación también inserta evaluación, porque §3.3 dice «cada captura o corrección».
- **Alcance del recálculo:** el cambio de umbral recalcula solo para el entrenador que lo hizo.
- **`ResultadoDeRendimiento`:** es una enumeración nueva con los tres valores que ya fija el `CHECK` de T01.
- **Recurso del desempeño:** no incluye `umbral_id` ni `plantilla_id` (DA-19); son procedencia interna.
