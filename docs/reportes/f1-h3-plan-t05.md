# Plan de F1-H3-T05: partidos (HU-12, criterio 1) — REVISIÓN DE PLAN

**Rutas:** rutas base (`docs/hitos/f1-h3.md` v1.0.1 §8). **Pruebas:** §7.4 filas 1 a 8.
**Estado:** el agente se detiene aquí y espera la aprobación antes de codificar.

## 1. Rutas

| Ruta | Qué hace |
|---|---|
| `GET /equipos/{equipo}/partidos` | Partidos del equipo, paginados; `filtro[temporada_id]` (UUID) y `filtro[estado]` (`programado`, `jugado`, `cancelado`); orden `inicia_en` (por defecto) o `-inicia_en` |
| `POST /equipos/{equipo}/partidos` | Alta en `programado`: `inicia_en`, `lugar_id`, `rival_id`, `torneo_id` (opcional según §3.4), `tipo`, `condicion`. 201 |
| `GET /partidos/{partido}` | Detalle |
| `PATCH /partidos/{partido}` | Cualquiera de los campos de alta, o `estado = cancelado` |

**Recurso `Partido`:** `id`, `equipo_id`, `temporada_id`, `torneo_id` (o nulo), `rival_id`, `lugar_id`, `inicia_en` (UTC), `tipo`, `condicion`, `estado`, `goles_favor`, `goles_contra` (nulos hasta T07) y `actualizado_en`. No lleva categoría (DD-18, fila 1).

**Pertenencia:** la misma de T03. `EquiposDelEntrenador::entrena` se comprueba en `authorize()`, así que el 404 gana al 422. Un partido ajeno, inexistente o con un UUID v4 da el mismo 404 (fila 7).

## 2. Cómo se deriva la temporada (el punto de la revisión)

1. **Día local.** `inicia_en` se convierte a `America/Mexico_City` (RNF-04) y se toma su fecha `AAAA-MM-DD`. Ejemplo de la fila 8: `2026-12-16T00:30:00Z` es el `2026-12-15` a las 18:30 locales.
   - La zona sale de `config('app.zona_presentacion')`, la misma que usa `HoyDeNegocio`.
   - Para no leer `config` en Aplicación, `CompetenciaServiceProvider` construye el caso de uso con un `DateTimeZone`.
   - La conversión vive en el dominio: `Partido::diaLocal(DateTimeZone)`. Es PHP puro y queda cubierta por Infection.
2. **Búsqueda.** `RepositorioDeTemporadas::queContiene(DateTimeImmutable $dia): ?Identificador`. Devuelve la temporada no eliminada con `fecha_inicio <= dia <= fecha_fin`, extremos incluidos, como el cruce de DA-06. DD-07 garantiza que hay a lo más una; aun así, la consulta ordena por `fecha_inicio` e `id` y toma una, para no fallar si alguna vez hubiera dos.
3. **Sin temporada:** 422 `sin_temporada` con campo `inicia_en` (fila 2). Las temporadas eliminadas no cuentan.
4. **Al modificar `inicia_en`**, la temporada se vuelve a derivar y el torneo se vuelve a validar contra ella.
5. **No se guarda una temporada enviada por el cliente:** el campo no existe en el cuerpo. Si llega, se rechaza con `prohibited`, como cualquier otro campo no admitido del `PATCH`.

## 3. Validación del torneo (§3.4)

- `oficial` o `torneo` sin `torneo_id` → 422 `torneo_obligatorio` en `torneo_id` (fila 3). En `amistoso` o `interno` es opcional (fila 4).
- Un torneo indicado debe ser de la temporada derivada → 422 `torneo_de_otra_temporada` en `torneo_id` (fila 5). Se compara contra la temporada derivada, no contra la que el cliente crea.
- Orden: forma (422) → `sin_temporada` → `torneo_obligatorio` → torneo inexistente → `torneo_de_otra_temporada`.
- Al modificar `tipo`, `inicia_en` o `torneo_id`, la regla se evalúa sobre el resultado final. `torneo_id: null` en un `PATCH` quita el torneo, y solo se permite si el tipo final lo deja opcional.

## 4. Estados y modificación

- `PATCH` solo admite `estado = cancelado` (422 con otro valor). `programado` → `cancelado` responde 200. Cancelar un partido `jugado` o ya `cancelado` da 409 `transicion_no_permitida` en `estado` (§2.3: no se cancela un partido jugado).
- Cambiar datos de un partido que no está `programado` da 409 `partido_no_programado`, que §6.3 ya nombra. Es el mismo criterio que DA-08 en entrenamientos.
- Datos y estado en la misma petición: primero los datos, después el estado, con una sola fila de historial (igual que T03).
- Historial `crear` y `actualizar` en `partidos_historial`, con la instantánea completa.

## 5. Capas

- **Dominio:**
  - `Partido` (inmutable; `conDatos`, `cancelado`, `diaLocal`) y los enumerados `TipoDePartido`, `CondicionDePartido` y `EstadoDePartido`.
  - `PartidoNoProgramado` y la regla del torneo como método del dominio: `Partido::exigeTorneo()` para `oficial` y `torneo`.
  - `RepositorioDePartidos`.
  - Se agregan `RepositorioDeTemporadas::queContiene`, `RepositorioDeTorneos::buscar` (temporada del torneo) y `RepositorioDeRivales::existe`.
- **Aplicación:** `ProgramarPartido`, `ModificarPartido`, `VerPartido`, `ListarPartidos`, `BuscarPartidoDelEntrenador`, `ReferenciasDePartido` (lugar, rival y torneo); excepciones `SinTemporada`, `TorneoObligatorio`, `TorneoDeOtraTemporada` (con campo, `ReglaDeCampoViolada`), `RivalInexistente` y `TorneoInexistente`.
- **Infraestructura:** `RepositorioDePartidosEloquent` con historial y los enlaces.
- **HTTP:** `PartidoController`, `SolicitudDeNuevoPartido` y `SolicitudDeCambioDePartido` (pertenencia en `authorize()`, campos no admitidos rechazados), `PartidoRecurso`, `FormatoDeError`, `lang/es` y `openapi.yaml`.

## 6. Pruebas

- **§7.4, una por fila (`HU-12 (§7.4 fila N)`):**
  1. Oficial con TO1 → 201, `temporada_id = T1`, sin campo de categoría (se comparan las claves exactas del recurso).
  2. Fecha fuera de toda temporada → 422 `sin_temporada` en `inicia_en`.
  3. Oficial sin torneo → 422 `torneo_obligatorio`.
  4. Amistoso sin torneo → 201.
  5. Fecha de otra temporada T2 con TO1 → 422 `torneo_de_otra_temporada`.
  6. E1 cancela → 200, y un partido cancelado no admite resultado (ver la pregunta 1).
  7. Partido de B consultado por E1 → 404 byte a byte igual al de un inexistente.
  8. `2026-12-16T00:30:00Z` → T1.
- **Adicionales:**
  - frontera del día local: `2026-12-16T06:00:00Z` ya es el 16 local y no tiene temporada;
  - `PATCH` que mueve la fecha a otra temporada (deriva T2 y exige que el torneo sea de T2);
  - cambio de tipo a `oficial` sin torneo;
  - `torneo_id: null` en un amistoso;
  - `partido_no_programado` y `transicion_no_permitida`;
  - campos no admitidos (`temporada_id`, `estado = jugado`, `goles_favor`);
  - lugar, rival o torneo inexistentes o eliminados;
  - filtros y orden del listado;
  - E3 ve el partido;
  - 401, 403 y 404 (UUID v4);
  - historial;
  - contrato;
  - unitarias del dominio (día local, regla del torneo, transiciones) para Infection.

## 7. Decisiones que propongo (DA-23 a DA-27, se registran al aprobar)

- **DA-23:** la zona se inyecta como `DateTimeZone` desde `config('app.zona_presentacion')` en el proveedor de servicios, sin un puerto nuevo en Compartido.
- **DA-24:** códigos nuevos `rival_inexistente` y `torneo_inexistente` (422, sin campo), como `lugar_inexistente` (DA-11).
- **DA-25:** al cambiar `inicia_en`, `tipo` o `torneo_id`, la temporada y el torneo se revalidan sobre el resultado final.
- **DA-26:** `filtro[temporada_id]` con un UUID inexistente devuelve una lista vacía, no 404; con un texto que no es UUID, 422.
- **DA-27:** cancelar un partido `jugado` o ya `cancelado` da 409 `transicion_no_permitida` en `estado`.

## 8. Preguntas para la aprobación

1. **Fila 6, segunda mitad («un partido cancelado no admite resultado»).** La ruta `PUT /partidos/{partido}/resultado` llega en T07. Propongo que en T05 la fila 6 compruebe la cancelación (200, estado `cancelado`, historial) y que el dominio rechace el resultado de un partido cancelado (prueba unitaria). La comprobación por HTTP iría en T07, con el identificador de la fila 6. ¿De acuerdo, o prefieres que T05 deje solo la cancelación y T07 toda la segunda mitad?
2. **Temporada eliminada después de crear el partido.** El partido conserva su `temporada_id`, que sigue visible (HU-28, criterio 3), y un `PATCH` que no toca `inicia_en` no revalida la temporada. Solo se revalida si cambia la fecha. ¿De acuerdo?
3. **Zona de negocio (DA-23).** La alternativa es un puerto en Compartido (`DiaDeNegocio`), pero T05 no tiene permitida esa ruta. Recomiendo el `DateTimeZone` inyectado.
