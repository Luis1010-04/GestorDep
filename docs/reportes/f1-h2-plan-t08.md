# Plan F1-H2-T08: alcance del docente (REVISIÓN DE PLAN)

**Estado:** plan para aprobar. No se escribió código de T08. Reglas: HU-02 criterio 2, HU-01 criterios 3 y 4, RNF-01, RN-13, RN-14, RN-20, DD-48.

## 1. Hallazgo que necesita decisión (pregunta 1): escrituras de un usuario con los dos roles

`AlcanceDeJugadores` hoy se usa también para decidir **si se puede escribir** sobre un jugador, no solo para leer:

| Operación de H1 | Cómo comprueba el alcance | Roles |
|---|---|---|
| `PATCH /jugadores/{jugador}` (expediente) | `SolicitudDeActualizacionDeJugador::authorize()` y `ActualizarExpedienteDeJugador` llaman a `ConsultaDeJugadores::buscar()` (que pasa por `restringir()`) | `rol:entrenador` |
| `POST /jugadores/{jugador}/baja` | `SolicitudDeBajaDeJugador::authorize()` llama a `VerJugador` (igual) | `rol:entrenador` |

Si solo se agrega la rama del docente (§5.4), un usuario **docente-entrenador (DE)** podría editar el expediente y dar de baja a un jugador que **solo ve porque lo enseña** (por ejemplo J1 de otro entrenador en su materia M3). Un docente puro sigue sin poder (403 antes de llegar). Es un cambio de quién puede modificar qué datos: §0.4 lo trata como decisión de seguridad.

**Recomendación:** las escrituras conservan el alcance de H1 (solo la rama del entrenador). Concretamente:

- `AlcanceDeJugadores::restringir()` gana un parámetro opcional al final, `bool $soloEntrenador = false` (cambio de la interfaz en `src/Compartido/**`, que T08 permite si se justifica: la alternativa sería duplicar la consulta de alcance en Plantel, lo que viola RNF-01). Con `true` la rama del docente no se agrega.
- `ConsultaDeJugadores` gana `buscarParaEscribir(Identificador): ?JugadorConEquipos` (alcance del entrenador, con bajas), usado por `ActualizarExpedienteDeJugador`, `VerJugador`-para-escritura en las dos solicitudes HTTP y `CambiarPosicionDeJugador`. `buscar()` (lectura) usa la unión.
- Una prueba por operación demuestra que DE recibe 404 al editar o dar de baja a J1 (solo docente) y 200 con un jugador de su equipo.

Alternativa si prefieres no tocar la interfaz: aceptar que DE escriba sobre los jugadores que enseña. No lo recomiendo (amplía permisos de escritura sin que la especificación lo pida).

## 2. Consulta propuesta (rama del docente)

En `src/Consultas/Infraestructura/AlcanceDeJugadoresSql.php`, dentro del **mismo** grupo anidado que hoy contiene `1 = 0 OR <rama entrenador>`:

```sql
-- (… filtros de quien llama …) AND (
--   1 = 0
--   OR EXISTS (<rama del entrenador, sin cambios>)
--   OR EXISTS (                                  -- rama del docente, solo si el usuario tiene el rol docente
--     select 1 from inscripciones i
--       join materias m on m.id = i.materia_id
--      where i.jugador_id = jugadores.id         -- la columna que pasa quien llama
--        and i.eliminado_en is null
--        and m.eliminado_en is null
--        and m.docente_id = :usuario
--   )
-- ) AND NOT EXISTS (jugador en baja)             -- solo si no se incluyen bajas (sin cambios)
```

- **Unión sin duplicados:** son dos `EXISTS` (semi-uniones) unidos con `OR`. Nunca se une `inscripciones` a la fila del jugador, así que J3 inscrito en dos materias de D1 sale **una vez** y `meta.total` (el `count()` sobre la misma consulta) cuenta uno; DE con equipo y materia sobre el mismo jugador también sale una vez.
- **Índices:** `ix_inscripciones_jugador_id` (por jugador), `pk_materias` (por `m.id`), `ix_materias_docente_id` y el único parcial `uq_inscripciones_materia_jugador`. El planificador puede arrancar por cualquiera de los dos extremos. Verificaré con `EXPLAIN` sobre una muestra sembrada (sin código nuevo) y lo anotaré en el reporte.
- **Bajas:** la condición `NOT EXISTS (… estatus_deportivo = 'baja')` sigue siendo global y fuera del grupo: J5 sale de los listados y se ve en el detalle con `incluirBajas` (fila 5), igual que en H1.
- **`restringir()` último:** no cambia la forma de la llamada. `ConsultaDeJugadoresSql` sigue aplicando sus filtros primero y llamando a `restringir()` al final; `agruparCondicionesPrevias()` sigue encerrando los `orWhere` previos. La rama nueva está **dentro** del grupo, no al lado, por lo que ningún filtro de quien llama puede volverla un `OR` global.

## 3. Lecturas de H1 afectadas

| Lectura | Efecto |
|---|---|
| `GET /jugadores` (listar y contar) | Un docente ve a sus inscritos; DE ve la unión. El filtro `filtro[equipo_id]` sigue acotando a **equipos del usuario**: para un docente puro da lista vacía (fila 13), no amplía el alcance. |
| `GET /jugadores/{jugador}` | Un docente ve a sus inscritos (también en baja, fila 5); los demás, 404 igual al de un id inexistente. `equipos` sale vacío para un docente puro porque esa lista ya se limita a las asignaciones del propio usuario (fila 2): no hace falta código. |
| `GET /jugadores/busqueda` | Sin cambio de código: ya es la excepción de RN-20 (`BusquedaDeJugadorPorMatricula`, en la lista cerrada). Hoy ya responde 200 a un docente; T08 solo agrega la prueba (fila 6). |
| Lista cerrada de usos sin alcance (Arch) | **No crece** (fila 14); la prueba de H1 queda intacta. |
| Escrituras de H1 | Ver la pregunta 1. |

## 4. Archivos

- `src/Consultas/Infraestructura/AlcanceDeJugadoresSql.php` (rama del docente + parámetro `soloEntrenador`).
- `src/Compartido/Aplicacion/AlcanceDeJugadores.php` (parámetro opcional; **justificación:** pregunta 1).
- `src/Plantel/Aplicacion/ConsultaDeJugadores.php` y `src/Plantel/Infraestructura/ConsultaDeJugadoresSql.php` (`buscarParaEscribir`), `ActualizarExpedienteDeJugador`, `CambiarPosicionDeJugador`, `VerJugador` o un caso de uso equivalente para las solicitudes `SolicitudDeActualizacionDeJugador` y `SolicitudDeBajaDeJugador` en `app/Http/Requests/`.
- `docs/api/openapi.yaml`: solo las descripciones de `GET /jugadores`, `GET /jugadores/{jugador}` y la búsqueda (ya no dicen «solo entrenadores»); ninguna ruta nueva. `routes/api.php` no cambia.
- Pruebas: `tests/Feature/Academico/AlcanceDelDocenteTest.php` (14 filas de §7.4), `tests/Feature/Academico/EscrituraDeUsuarioConDosRolesTest.php`, ayudante `tests/Ayudantes/MundoDeDocentes.php` (datos de §7.0, inscripciones sembradas directamente) y su `require` en `tests/Pest.php`. Las pruebas de H1 de alcance y de arquitectura **no se modifican**.

## 5. Pruebas (§7.4, una por fila)

Filas 1 a 14 tal como están en la especificación, con los datos de §7.0 (J1 a J5, M1 a M3, DE con ED y M3), más: DE no edita ni da de baja a J1 (404); un docente con una inscripción eliminada o en una materia eliminada ya no ve al jugador; D1 no ve a un jugador solo inscrito en la materia de D2 (404 byte a byte igual al de un id inexistente); la paginación cuenta sin duplicados.

## 6. Otras preguntas

2. **`estatus_deportivo` en la vista del docente.** El recurso `Jugador` de H1 incluye `estatus_academico` y `estatus_deportivo` (`activo`/`baja`), y la fila 5 exige ver `baja`. T08 dice «sin datos deportivos»; entiendo por eso `equipos`, posiciones y riesgo (RN-13, DD-48), no el estatus de baja global (DD-30). *Recomiendo* dejar el recurso igual (§6.1: «su recurso no cambia»). Dime si prefieres ocultar `estatus_deportivo` a un docente puro.
3. **Docente con rol activo pero sin inscripciones:** ve una lista vacía (200), no 403. Es el comportamiento de H1 para un entrenador sin equipos.

## 7. Qué no se hará

Endpoints de inscripción (T10), situación académica (T14), tablas o columnas nuevas, y cualquier lectura de jugadores fuera de `AlcanceDeJugadores`.
