# Decisiones del agente en F1-H3

Decisiones que la especificación no cubre (`docs/hitos/f1-h2.md` §0.3, aplicable a H3 por `docs/hitos/f1-h3.md` §0). Cada una dice qué faltaba, qué se eligió, por qué y cómo se revierte.

## Resumen (al cierre del Lote A)

| DA | Tarea | Qué es | ¿Cambia permisos o privacidad? | ¿Necesita aprobación? |
|---|---|---|---|---|
| DA-01 | T01 | La columna del autor en los historiales es `usuario_id` (macro de Compartido), no `autor_id` | No | No |
| DA-02 | T01 | El resultado de un partido existe si y solo si está `jugado`, y nunca un gol sin el otro (`CHECK`) | No | No |
| DA-03 | T01 | `convocatorias.posicion_id` admite nulo hasta que haya alineación | No | No |
| DA-04 | T02 | Código propio por catálogo duplicado (`lugar_duplicado`, `rival_duplicado`, `temporada_duplicada`, `torneo_duplicado`) | No | No |
| DA-05 | T02 | `temporada_solapada` responde 409 sin detalle por campo | No | No |
| DA-06 | T02 | Dos temporadas se cruzan si comparten un día; el alta va bajo un candado consultivo | No | No |
| DA-07 | T03 | Horario invertido: 422 en `termina_en` al crear, `rango_de_fechas_invalido` al modificar | No | No |
| DA-08 | T03 | Código nuevo `entrenamiento_no_programado` (409, sin campo): no se cambian hora, lugar ni responsable fuera de `programado` | No | **Aprobada** el 29/10/2026 (especificación v1.0.1, §6.3) |
| DA-09 | T03 | Empalme de lugar: intervalos que se cruzan (tocarse no cuenta), de cualquier equipo, sin cancelados, al crear y al modificar; la advertencia no dice con cuál choca | Sí: no revela el entrenamiento ajeno con el que choca (restringe) | No |
| DA-10 | T03 | Los filtros del listado son días locales de America/Mexico_City sobre `inicia_en` | No | No |
| DA-11 | T03 | Lugar inexistente o eliminado: 422 `lugar_inexistente` | No | No |
| DA-12 | T03 | `JugadoresDelEquipo` pasa por el alcance, exige además la asignación del usuario al equipo y devuelve solo id, nombres y apellidos | **Sí** (restringe: no revela que un jugador visible por otro equipo está en este) | **Aprobada** el 29/10/2026 (especificación v1.0.1, §5.3) |
| DA-13 | T03 | «Entrenar un equipo» exige asignación vigente, rol `entrenador` y equipo y usuario no eliminados | **Sí** (restringe quién accede al equipo y quién puede ser responsable) | **Aprobada** el 29/10/2026 (especificación v1.0.1, §3.4 y §5.3) |
| DA-14 | T03 | El campo de la respuesta es `jugadores_del_equipo`, para no dar un falso positivo en el detector de consultas sin alcance | No | **Aprobada** el 29/10/2026 (especificación v1.0.1, §6.1 y §6.2) |
| DA-15 | T03 | Los instantes se aceptan con desplazamiento o con `Z`; sin zona, 422 | No | No |

## DA-01 (T01): la columna del autor en los historiales es `usuario_id`

- **Qué faltaba:** §5.1 dice que cada historial guarda «`autor_id` y `ocurrido_en` (`docs/arquitectura.md` §9)», pero §9 y la macro `historial()` de Compartido llaman `usuario_id` al autor.
- **Qué se eligió:** la macro `Schema::historial()` sin cambios (`usuario_id`), como todos los historiales de H1 y H2.
- **Por qué:** la especificación remite a §9 y la macro es la única forma de crear historiales idénticos (arquitectura §8.1, regla 5). «`autor_id`» se lee como descripción, no como nombre.
- **Cómo se revierte:** una migración nueva que renombre la columna en las ocho tablas; ningún código de H3 depende del nombre salvo `EscritorDeHistorial`.

## DA-02 (T01): el resultado de un partido existe si y solo si el partido está `jugado`

- **Qué faltaba:** §5.1 pide un `CHECK` «que goles existan solo si estado es `jugado`»; no dice si un partido `jugado` puede quedar sin goles, ni si puede haber un gol sin el otro.
- **Qué se eligió:** `ck_partidos_resultado`: `goles_favor` y `goles_contra` son ambos nulos o ambos no nulos, y no son nulos exactamente cuando `estado = 'jugado'`. Aparte, `ck_partidos_goles`: cada uno entre 0 y 99.
- **Por qué:** es la lectura más estricta (§0.3, regla 3) y coincide con §3.4: un partido pasa a `jugado` solo al capturar el resultado.
- **Cómo se revierte:** una migración nueva que reemplace el `CHECK` por la implicación simple.

## DA-03 (T01): `convocatorias.posicion_id` admite nulo

- **Qué faltaba:** §5.1 dice que `rol_alineacion` es nulo mientras no haya alineación, pero no lo dice de `posicion_id`. La convocatoria se crea solo con `jugador_id` (§6.2) y la posición llega con la alineación (T07).
- **Qué se eligió:** `posicion_id` nulo, con clave foránea a `posiciones`. No se agrega un `CHECK` que ate `posicion_id` a `rol_alineacion`: §5.1 no lo nombra.
- **Por qué:** sin nulo no se podría convocar antes de definir la alineación.
- **Cómo se revierte:** una migración nueva con `not null` (exigiría la posición al convocar).

## DA-04 (T02): código propio para cada catálogo duplicado

- **Qué faltaba:** §3.4 dice «Un valor duplicado responde 409», sin código; §6.3 no lo lista.
- **Qué se eligió:** `lugar_duplicado`, `rival_duplicado`, `temporada_duplicada` y `torneo_duplicado`, con `errores` vacío y el mensaje en `lang/es`.
- **Por qué:** el patrón de H1 y H2 (`categoria_duplicada`, `periodo_duplicado`, `asignatura_duplicada`).
- **Cómo se revierte:** cambiar el código en la excepción, `lang/es` y `openapi.yaml`.

## DA-05 (T02): `temporada_solapada` sin detalle por campo

- **Qué faltaba:** §6.3 pone «nombre o fechas» en la columna Campo de `temporada_solapada`, sin decir cuál.
- **Qué se eligió:** 409 con `errores: []`, como los demás 409 de reglas de negocio de H1 y H2.
- **Por qué:** el cruce es entre dos temporadas, no un defecto de un campo; ninguna de las dos lecturas es más segura que la otra y la de H2 ya existe.
- **Cómo se revierte:** dar a `TemporadaSolapada` un campo y formatearla como `CalificacionFueraDeRango`.

## DA-06 (T02): cruce de temporadas con extremos incluidos y bajo candado

- **Qué faltaba:** DD-07 dice que cada fecha cae en a lo más una temporada, pero no si comparten el día de frontera ni cómo se evita que dos altas simultáneas se crucen.
- **Qué se eligió:** dos temporadas se cruzan si comparten al menos un día (extremos incluidos): una que empieza el día en que termina otra se rechaza. La comprobación y la escritura van en una transacción con `pg_advisory_xact_lock(hashtext('temporadas'))`. Las temporadas eliminadas no cuentan.
- **Por qué:** con extremos incluidos, una fecha de frontera caería en dos temporadas; el candado es la opción más estricta sin una restricción de exclusión (que pediría `btree_gist`, una extensión que la especificación no nombra).
- **Cómo se revierte:** cambiar `<=`/`>=` por `<`/`>` en `RepositorioDeTemporadasEloquent::guardarNueva`, y quitar el candado.

## DA-07 (T03): horario invertido de un entrenamiento

- **Qué faltaba:** §6.3 no tiene código para un `termina_en` igual o anterior a `inicia_en`; §5.1 solo pide el `CHECK`.
- **Qué se eligió:** al crear, la regla `after:inicia_en` (422 `validacion_fallida` en `termina_en`). Al modificar solo una de las dos horas, el dominio lanza `rango_de_fechas_invalido` (422, código que ya existe desde H2).
- **Por qué:** la validación más estricta sin códigos nuevos; el `CHECK` de la base queda como última defensa.
- **Cómo se revierte:** cambiar la regla de la solicitud y el código del dominio.

## DA-08 (T03): un entrenamiento que no está `programado` no se modifica (pendiente de aprobación)

- **Qué faltaba:** §3.4 fija las transiciones de estado, pero no dice si se pueden cambiar hora, lugar o responsable de un entrenamiento `realizado` o `cancelado`.
- **Qué se eligió:** solo un entrenamiento `programado` admite cambios de datos; si no, 409 `entrenamiento_no_programado`. Es un **código nuevo** que §6.3 no nombra, por simetría con `partido_no_programado`.
- **Por qué:** la opción más estricta: un entrenamiento realizado tiene asistencia capturada (T04) y moverlo cambiaría a qué día corresponde.
- **Cómo se revierte:** quitar la comprobación de `Entrenamiento::modificar`, la excepción, su mensaje y su respuesta en `openapi.yaml`.

## DA-09 (T03): alcance del empalme de lugar

- **Qué faltaba:** §3.4 define el empalme, pero no si dos intervalos que solo se tocan (17:00–18:00 y 18:00–19:00) empalman, si cuenta otro equipo ni si se advierte al modificar.
- **Qué se eligió:** se cruzan si `inicia_en < otro.termina_en` y `termina_en > otro.inicia_en` (tocarse no es empalme); cuenta cualquier equipo; los cancelados no cuentan; se advierte al crear y al modificar uno que siga `programado`. La advertencia no dice con qué entrenamiento choca.
- **Por qué:** el lugar es físico y lo comparten todos los equipos; no revelar el entrenamiento ajeno es lo que revela menos.
- **Cómo se revierte:** cambiar la consulta `hayEmpalme` del repositorio.

## DA-10 (T03): filtros del listado de entrenamientos

- **Qué faltaba:** §6.1 nombra `filtro[desde]` y `filtro[hasta]` sin formato ni zona.
- **Qué se eligió:** días `AAAA-MM-DD` en `America/Mexico_City` (RNF-04) sobre `inicia_en`, extremos incluidos; orden por defecto `inicia_en`, también `-inicia_en`.
- **Por qué:** el entrenador piensa en días locales; es el mismo criterio que la temporada del partido (§3.4).
- **Cómo se revierte:** cambiar la conversión en el controlador.

## DA-11 (T03): lugar inexistente o eliminado

- **Qué faltaba:** §6.3 no tiene código para un `lugar_id` que no existe o está eliminado.
- **Qué se eligió:** 422 `lugar_inexistente`, como `periodo_inexistente` (H2) y `temporada_inexistente` (DA-04 de T02).
- **Por qué:** patrón de H2.
- **Cómo se revierte:** cambiar el código y su mensaje.

## DA-12 (T03): `JugadoresDelEquipo` pasa por `AlcanceDeJugadores` y devuelve solo id, nombres y apellidos

- **Qué faltaba:** §5.3 dice qué jugadores devuelve el puerto, pero no con qué datos ni si pasa por el alcance.
- **Qué se eligió:** el adaptador restringe con `AlcanceDeJugadores` (`incluirBajas` falso, `soloEntrenador` verdadero) y devuelve `id`, `nombres` y `apellidos`, ordenados por apellidos, nombres e id.
- **Por qué:** la regla «toda consulta de jugadores pasa por `AlcanceDeJugadores`» (proyecto.md §1) y revelar lo mínimo para tomar asistencia.
- **Cómo se revierte:** agregar campos al objeto `JugadorDelEquipo`.

## DA-13 (T03): qué es «entrenar un equipo»

- **Qué faltaba:** §3.4 pide que el responsable tenga rol entrenador y asignación vigente; §5.3 no dice si el puerto mira el rol.
- **Qué se eligió:** `EquiposDelEntrenador` exige asignación vigente (`hasta` y `eliminado_en` nulos), equipo no eliminado, usuario no eliminado y rol `entrenador`. Se usa igual para el usuario de la sesión (404) y para el responsable (422).
- **Por qué:** la lectura más estricta de §3.4.
- **Cómo se revierte:** quitar las uniones con `usuarios` y `usuario_rol` del adaptador.

## DA-14 (T03): el campo de la respuesta se llama `jugadores_del_equipo`

- **Qué faltaba:** §7.2 fila 1 dice que la respuesta «lista J1, J2 y J3», sin nombrar el campo.
- **Qué se eligió:** `jugadores_del_equipo`, en el alta, el detalle y el cambio; no en el listado.
- **Por qué:** con `jugadores`, la prueba de arquitectura de `AlcanceDeJugadores` marca la cadena como una consulta a la tabla `jugadores` sin alcance (falso positivo). En lugar de ampliar la lista cerrada de usos sin alcance (requiere aprobación) o debilitar el detector, se usa un nombre más descriptivo que no es el de la tabla.
- **Cómo se revierte:** renombrar la clave en `EntrenamientoRecurso` y `openapi.yaml`, y agregar el recurso a la lista cerrada del detector con aprobación.

## DA-15 (T03): los instantes se aceptan con desplazamiento o con `Z`

- **Qué faltaba:** §6.2 nombra `inicia_en` y `termina_en` sin formato; `docs/convenciones-api.md` §1 dice «ISO 8601 en UTC».
- **Qué se eligió:** `AAAA-MM-DDTHH:MM:SS` con desplazamiento (`-06:00`) o con `Z`; sin zona se rechaza (422). Se guardan y se devuelven en UTC.
- **Por qué:** un instante sin zona es ambiguo (RNF-04); con `Z` es el ISO 8601 UTC de la convención.
- **Cómo se revierte:** cambiar `SolicitudDeNuevoEntrenamiento::INSTANTE`.

## DA-16 (T04): el campo de `jugador_no_pertenece_al_equipo` es `registros.N.jugador_id`

- **Qué faltaba:** §6.3 dice «campo `jugador_id`», pero el alta recibe una lista: hay que decir cuál de los registros falla.
- **Qué se eligió:** `registros.N.jugador_id` (N desde 0), como nombra Laravel los errores de un arreglo; el primero que falla.
- **Por qué:** señala el registro exacto y conserva `jugador_id` como nombre del campo.
- **Cómo se revierte:** cambiar el campo en `JugadorNoPerteneceAlEquipo`.

## DA-17 (T04): la captura por lista es todo o nada

- **Qué faltaba:** §3.4 dice que la asistencia se captura una vez por jugador, pero no qué pasa si un registro de la lista falla.
- **Qué se eligió:** si un registro falla (jugador ajeno o en baja, ya registrado, o repetido en la misma lista), no se guarda ninguno. Orden: 404 → 422 de forma (incluido `distinct`) → 409 `entrenamiento_no_realizado` → 422 `jugador_no_pertenece_al_equipo` → 409 `asistencia_ya_registrada`. La pertenencia se evalúa al capturar (membresía vigente y sin baja hoy).
- **Por qué:** la más estricta, y evita listas a medias que el entrenador no sabría completar.
- **Cómo se revierte:** guardar los válidos y devolver los errores de los demás.

## DA-18 (T04): la corrección no exige que el jugador siga en el equipo

- **Qué faltaba:** §3.4 limita la captura a jugadores vigentes y sin baja, pero no dice nada de la corrección.
- **Qué se eligió:** un entrenador del equipo del entrenamiento puede corregir el estado aunque el jugador haya salido del equipo o esté en baja. Queda el historial `actualizar`.
- **Por qué:** es una corrección del historial, que sigue siendo del equipo (arquitectura §10: la baja conserva el historial para quien ya tenía al jugador), y H6 calcula sanciones (RN-09) con esos datos. No revela nada nuevo: el registro ya era visible.
- **Cómo se revierte:** comprobar la pertenencia en `CorregirAsistencia` (como H2 DA-13).

## DA-19 (T04): el listado de asistencias no trae datos del jugador

- **Qué faltaba:** §6.1 no dice qué campos tiene el listado.
- **Qué se eligió:** `id`, `entrenamiento_id`, `jugador_id`, `estado`, `actualizado_en`; incluye los registros de jugadores ya en baja (historial). Los nombres están en el detalle del entrenamiento (`jugadores_del_equipo`). Orden por `creado_en` e `id`, paginado.
- **Por qué:** revela lo mínimo y no lee tablas de Plantel.
- **Cómo se revierte:** unir los nombres por un puerto.

## DA-20 (T04): el porcentaje de un jugador usa el alcance de entrenador

- **Qué faltaba:** §6.1 dice «con sesión y alcance» para `GET /jugadores/{jugador}/asistencia-entrenamientos`, sin decir qué rama del alcance.
- **Qué se eligió:** `AlcanceDeJugadores` con `soloEntrenador` e `incluirBajas`. Un docente recibe 404 aunque enseñe al jugador; un jugador en baja sigue visible para su entrenador (historial). La ruta no lleva el middleware de rol: un usuario sin alcance recibe 404, no 403.
- **Por qué:** RN-13: el docente, del lado deportivo, solo ve el estado oficial. Es lo que revela menos.
- **Cómo se revierte:** quitar `soloEntrenador` en `VisibilidadDeJugadorSql`.

## DA-21 (T04): forma del porcentaje por equipo

- **Qué faltaba:** §3.4 da la fórmula, pero no la forma de la respuesta ni qué jugadores lista.
- **Qué se eligió:** `{equipo_id, porcentaje, presentes, faltas, faltas_justificadas, por_jugador: [{jugador_id, nombres, apellidos, porcentaje, presentes, faltas, faltas_justificadas}]}`. El total del equipo cuenta todos los registros de sus entrenamientos realizados (también los de jugadores ya en baja); `por_jugador` lista solo a los jugadores vigentes del equipo (`JugadoresDelEquipo`), incluso los que no tienen registros (porcentaje `null`). El campo es `por_jugador` y no `jugadores` (DA-14).
- **Por qué:** el total es un dato agregado del equipo y no se altera al dar de baja a alguien; la lista solo muestra a quien el entrenador puede ver hoy.
- **Cómo se revierte:** filtrar el total por los jugadores vigentes.

## DA-22 (T04): los conteos acompañan al porcentaje

- **Qué faltaba:** §6.1 solo nombra el porcentaje.
- **Qué se eligió:** se devuelven también `presentes`, `faltas` y `faltas_justificadas` (enteros).
- **Por qué:** sin ellos un `null` o un `0.00` no se pueden distinguir de un dato sin registros, y H6 los usará (RN-09).
- **Cómo se revierte:** quitar los tres campos del recurso.
