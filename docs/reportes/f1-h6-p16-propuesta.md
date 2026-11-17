# P-16, opción A: propuesta de DA-122 y de la tarea T10

**Fecha:** 17 de noviembre de 2026
**Estado: DIFERIDA.** Decisión del responsable del 17/11/2026 sobre P-16 («opción C»; ver la nota de letras abajo): no se implementa nada y no se crea T10. **El borrador de DA-122 de este documento queda como propuesta, no como decisión, y no está registrado en `docs/decisiones/registro-decisiones.md`.** Nada de lo de aquí se aplicó: ni código, ni `docs/hitos/f1-h6.md`, ni el registro, ni `docs/requisitos.md`. El documento se conserva para cuando aparezca en una fase posterior la salida de un jugador de un equipo.

> **Nota de letras.** En la tabla de opciones de P-16 de `docs/decisiones/pendientes.md`, la «C» era «cerrarlas al dejar el equipo con un evento de dominio y un escucha». La decisión del 17/11/2026 (diferir, sin implementar) no es esa: se registra como «diferido», y se le pide al responsable que confirme la letra. Antes de esta decisión, el 17/11/2026, el responsable había elegido la opción A («la alerta abierta de un equipo que el jugador dejó se cierra al salir del equipo»), que es la que desarrolla este documento.

**Dónde aparece la salida de un equipo (citas verificadas en el texto, 17/11/2026).**
- `docs/hitos/f1-h1.md` §2.3, «Qué NO se hace en H1», línea 56: «Asignar entrenadores adicionales a un equipo, editar o borrar equipos, **quitar a un jugador de un equipo**, editar o renombrar categorías (ver §3, decisión sobre RN-22).» Y en su T14, línea 645: «**No hagas:** quitar a un jugador de un equipo; mover estadísticas pasadas (no existen todavía).»
- **Esa frase dice que no se hace en H1; no dice «fuera de la Fase 1».** Y **no encontré ninguna frase que diga en qué fase aparece el traspaso de jugadores**: `docs/alcance-fases.md` (§3, §5 «Lo que la Fase 1 no hace» y §6) no lo menciona; tampoco el backlog de `docs/requisitos.md` §9 ni ninguna otra especificación de hito (H2 a H6). La única mención es la de H1. Verifiqué además que en el código no hay ruta ni escritura que cierre una membresía (§0).

## 0. Un hecho que condiciona la propuesta

**En la Fase 1 ningún jugador puede dejar un equipo.** Lo verifiqué así:

- `docs/hitos/f1-h1.md` §2.3 (línea 56) deja fuera «quitar a un jugador de un equipo», y su T14 (asignar a otro equipo) repite «**No hagas:** quitar a un jugador de un equipo» (línea 645).
- `backend/routes/api.php` no tiene ninguna ruta que cierre una membresía: solo asignar (`POST /equipos/{equipo}/jugadores`), cambiar la posición (`PATCH …/jugadores/{jugador}`) y dar de baja al jugador.
- Ningún archivo de `backend/src` escribe un `hasta` distinto de nulo en `jugador_equipo` (la búsqueda de `hasta` en `src` solo encuentra `'hasta' => null` al crear y filtros `whereNull('hasta')`). La baja del jugador (RN-23) tampoco cierra sus membresías.
- Los únicos sitios donde `hasta` tiene fecha son las pruebas (`tests/Ayudantes/MundoDeJugadores.php` y varias pruebas de Feature), que insertan la fila directo.

Consecuencia: la regla de DA-122 **solo podrá ejercitarse con filas creadas en pruebas**, como ya pasa con DA-102 (DA-114: «no se puede usar en producción de la Fase 1 hasta que exista esa asignación»). El humo de §9 no puede reproducirla por la API. Lo digo porque cambia lo que significa «cerrar P-16»: se cierra la regla, no un camino alcanzable por un usuario. Si en una fase posterior se agrega la salida de un equipo, esta regla ya estará.

## 1. Borrador de DA-122

**DA-122. Alertas de un equipo que el jugador dejó (P-16, opción A).**

1. **Cuándo cuenta como dejada.** La pareja (jugador, equipo) de una alerta abierta de `riesgo_baja_deportiva` o de `acumulacion_sanciones_deportivas` ya no tiene membresía vigente: no existe una fila de `jugador_equipo` con ese jugador y ese equipo, con `hasta` nulo y `eliminado_en` nulo. Es la misma definición de «vigente» que ya usa el puerto `MembresiasVigentes` (DA-115, `MembresiasVigentesPlantel`: `whereNull('hasta')` y `whereNull('eliminado_en')`), de modo que no se agrega un concepto nuevo. Con esa definición, una fecha `hasta` futura también cuenta como dejada, igual que hoy en todas las consultas.
2. **Quién la cierra y cuándo.** `alertas:recalcular` (DA-113), en su siguiente ejecución. No hay evento (DD-32: `JugadorAsignadoAEquipo` y `JugadorDadoDeBaja` no se crean y «`alertas:recalcular` (H6) concilia lo que ocurrió antes»), así que puede pasar hasta un día después de que el jugador deje el equipo (DA-88).
3. **Estado al cerrarse.** Se escribe `cerrada_en` con el instante de la ejecución, como en cualquier otro cierre del comando. `activada_en`, `causa`, `tipo`, `jugador_id` y `equipo_id` no cambian y la fila se conserva (§5.1: «no se borra»; RN-17). La tabla no tiene columna de motivo: **no se agrega**, así que el cierre por salida del equipo y el cierre por condición se ven igual en la base.
4. **Qué no cierra.**
   - La alerta académica (`equipo_id` nulo, DA-87): no depende del equipo.
   - Las alertas de un jugador en baja: se conservan (DA-99, «el comando no las cierra por la baja»). El comando ya sabe distinguirlo: `MembresiasVigentes::todas()` no devuelve a los jugadores en baja, y a uno sin baja y sin membresía lo devuelve una vez con equipo nulo. Cierra solo si el **jugador aparece** en `todas()` y la pareja (jugador, equipo) **no**.
   - Las alertas de otro equipo donde el jugador sigue (la pareja se evalúa por equipo, RN-09 y RN-10).
   - Las alertas ya cerradas.
5. **Si se reevalúa después.** La alerta cerrada no se reabre (DA-87). Mientras la pareja no tenga membresía vigente, el comando no la evalúa para abrir alertas (solo recorre membresías vigentes, DA-115). Si el jugador vuelve al equipo (una fila nueva de `jugador_equipo`, que el índice único parcial permite) y la condición se cumple, se crea una alerta nueva con su propio `activada_en` (DA-87).
6. **Visibilidad.** No cambia. `VisibilidadDeAlertasSql` ya oculta estas alertas desde el momento en que el jugador deja el equipo (DA-90, probado por «DA-90 (visibilidad)» de T08); el cierre solo actualiza la base. Lo que cambia es lo que pasa **si el jugador regresa**: sin DA-122 reaparecería la alerta vieja con su `activada_en` original; con DA-122, aparece una nueva solo si la condición se cumple.

**En qué se apoya.** §10 punto 7 de `docs/hitos/f1-h6.md` («una alerta que deja de cumplirse se cierra y se conserva»); `docs/arquitectura.md` §11 punto 3 («si deja de cumplirse, se cierra con `cerrada_en`»); RN-09 y RN-10 («se calcula por equipo»); RN-22 (DA-102: las alertas deportivas y de sanciones de un equipo se calculan con los datos de todos los entrenadores del equipo y las ven todos ellos); DA-87, DA-88, DA-90, DA-99, DA-113, DA-115 y DD-32. Los requisitos no dicen qué pasa con la alerta de un equipo que el jugador deja: la regla se deduce de las anteriores y de la decisión del responsable.

**¿Exige cambiar `docs/requisitos.md`? No.** RN-09, RN-10 y RN-22 hablan de calcular y ver por equipo y no excluyen este cierre. Lo que sí convendría, por un F1-DOC aparte y **no aplicado aquí**, es una frase en `docs/arquitectura.md` §11 punto 3: «también se cierra la alerta deportiva o de sanciones de un equipo que el jugador dejó (DA-122)», porque ese punto habla de «deja de cumplirse» la condición y este caso es de pertenencia.

**Decisiones que dejo marcadas para el responsable** (el borrador propone la primera de cada par):

| # | Punto | Propuesta | Alternativa |
|---|---|---|---|
| a | Valor de `cerrada_en` | El instante de la ejecución del comando | La fecha `hasta` de la membresía (a medianoche): refleja cuándo salió, pero no cuándo se cerró |
| b | `hasta` futura | Cuenta como dejada (como hoy en todas las consultas) | No contarla hasta que llegue la fecha: cambia la definición de «vigente» solo para Alertas |
| c | Motivo del cierre | No se guarda | Una columna nueva `motivo_de_cierre`: cambia el esquema de §5.1 y la migración de T01 |

## 2. Cambio de especificación: tarea nueva T10 (recomendada) o reapertura de T03

**Recomiendo T10.** T03 está terminada y aprobada, con 14 commits, su plan, su reporte y su addendum de desviaciones; reabrirla invalidaría ese reporte y obligaría a reeditar un documento ya aprobado. T10 deja la historia intacta y el cambio queda identificable por su commit y su reporte.

**Texto propuesto para `docs/hitos/f1-h6.md` §8, después de T09** (no aplicado):

> ### F1-H6-T10: Alertas de un equipo que el jugador dejó (P-16, DA-122) — Lote C
> - **Rutas permitidas:** `src/Alertas/Aplicacion/**`, `src/Alertas/Infraestructura/RepositorioDeAlertasSql.php`, `tests/Feature/Alertas/**`, `tests/Ayudantes/MundoDeAlertas.php`, `tests/Unit/Alertas/**`, `docs/trazabilidad.md`, `docs/decisiones/registro-decisiones.md`, `docs/decisiones/pendientes.md`, reportes.
> - **Haz:** que `alertas:recalcular` cierre, con `cerrada_en`, las alertas abiertas de `riesgo_baja_deportiva` y de `acumulacion_sanciones_deportivas` de un (jugador, equipo) sin membresía vigente, solo para jugadores sin baja (DA-122). Hace falta un método nuevo de `RepositorioDeAlertas` que liste las alertas abiertas por equipo; no se cambia el puerto `MembresiasVigentes` (DA-115) ni el esquema.
> - **No hagas:** una ruta o un evento para que un jugador salga de un equipo (H1 §2.3); una columna de motivo; reabrir alertas cerradas.
> - **Pruebas:** una por regla de DA-122 (§4 de `docs/reportes/f1-h6-p16-propuesta.md`), con identificador `DA-122` o `P-16` en el nombre; mutación de la condición nueva en copia temporal, como en T08.

**Otros cambios de la misma especificación** (todos para la v1.16, no aplicados):

- §0, tabla de lotes: T10 en el lote C.
- §3.3: DA-122, con el texto de §1 de esta propuesta.
- §10 punto 7: añadir «una alerta de un equipo que el jugador dejó se cierra».
- §11: nota de que en la Fase 1 no hay ruta para salir de un equipo (§0 de esta propuesta).
- §12 (control de cambios): entrada de la 1.16; y la cabecera de versión.

**Otros documentos:** `docs/decisiones/registro-decisiones.md` §10 (fila de DA-122); `docs/decisiones/pendientes.md` (P-16 pasa a «Cerrado: opción A aprobada»); `docs/trazabilidad.md` (RN-09, RN-10 y HU-22 citan las pruebas de T10); un reporte `docs/reportes/f1-h6-t10.md`; y en `docs/reportes/f1-h6-reporte.md` el punto 7 de §10 deja de tener un criterio sin prueba, y §7 quita P-16 de lo abierto.

## 3. Impacto

**Código (T10, sin tocar rutas ni esquema).** `src/Alertas/Aplicacion/RecalcularTodasLasAlertas.php` (el recorrido de cierre), `src/Alertas/Aplicacion/RepositorioDeAlertas.php` y `src/Alertas/Infraestructura/RepositorioDeAlertasSql.php` (un método que liste las alertas abiertas deportivas y de sanciones). Si la regla de «dejada» se escribe como función pura, iría a `src/Alertas/Dominio/`; la propuesta la deja en Aplicación porque solo compara dos conjuntos de identificadores.

**Pruebas nuevas** (en `tests/Feature/Alertas/`, con `DA-122` o `P-16` en el nombre):

1. La alerta deportiva abierta de A se cierra cuando la membresía en A tiene `hasta`; `activada_en` y `causa` no cambian y `cerrada_en` es el instante de la ejecución.
2. Lo mismo con la de sanciones.
3. Con una alerta abierta en B y la membresía en B vigente, la de B no cambia.
4. La alerta académica no se cierra por salir de un equipo.
5. Un jugador en baja con la membresía dejada conserva su alerta abierta (DA-99).
6. Una segunda ejecución no cambia nada (idempotencia).
7. Si el jugador vuelve al equipo y la condición se cumple, se crea una alerta nueva y la cerrada sigue cerrada (DA-87).
8. Una membresía con `eliminado_en` también cuenta como dejada.

**Pruebas existentes.** No espero que cambie ninguna, pero se confirma corriendo la suite: `RecalculoDiarioTest` («DA-87 … ya cerradas» y «P-08c (DA-99)») no tiene alertas de un equipo sin membresía; `AlertasDelJugadorTest` («DA-90 (visibilidad)») no ejecuta el comando y su alerta ya es invisible; la mutación 1.05 de T08 sigue viva como prueba de la visibilidad entre la salida y la siguiente ejecución del comando.

**Humo de §9: el guion no cambia** (y no puede reproducir este caso por la API, §0). **Sí hay que volver a correrlo**: los pasos 4 y 15 ejecutan `alertas:recalcular`, el comando que cambia. Eso exige `make fresh` y `make humo` completo (§10 punto 6), con tu permiso explícito para `make fresh`. `migrate:rollback` no hace falta: no hay migración.

**Mutación.**
- `make mutacion` (Infection, solo `Dominio/`) hay que volver a correrlo antes de cerrar (§10 punto 1). Si la regla queda en Aplicación, no genera mutantes nuevos; si se escribe en Dominio, cada mutante debe quedar matado.
- La condición nueva (jugador presente en `todas()` y pareja ausente, y las dos exclusiones) se cubre con **un guion de mutación en copia temporal**, igual al de T08: tres o cuatro mutaciones que deben matar las pruebas de T10.
- Las 35 mutaciones de filtros de T08 **no hace falta repetirlas**: T10 no toca `src/Consultas/`.

**`make verificar`:** una vez al terminar T10, en el árbol final. `make pruebas` aparte no hace falta (está incluido).

**`docs/api/openapi.yaml`:** no cambia: ninguna ruta ni respuesta cambia.

## 4. Resolución

El responsable decidió el 17/11/2026 **diferir** (ver el estado al inicio): no se aprueba DA-122, no se crea T10 y no se reabre T03. Lo que quedaba por decidir (los puntos a, b y c de §1, T10 o T03) se retoma cuando exista la salida de un equipo. Mientras tanto, el criterio del punto 7 de §10 («una alerta que deja de cumplirse se cierra y se conserva») queda **sin prueba** para este caso, diferido a la fase donde aparezca el traspaso, que ningún documento fija.

No toco `docs/requisitos.md`, `docs/arquitectura.md` ni `docs/hitos/f1-h6.md`.
