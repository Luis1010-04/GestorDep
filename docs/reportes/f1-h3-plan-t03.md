# Plan de F1-H3-T03: entrenamientos (HU-10) y puertos de equipo

**Rutas:** `src/Competencia/**`, `src/Compartido/Aplicacion/**` (solo los puertos de §5.3), `src/Plantel/Infraestructura/**` (solo sus adaptadores y su enlace en `PlantelServiceProvider`), `routes/api.php`, `lang/es`, `docs/api/openapi.yaml`, `tests/**`, y por R-1 `app/Http/**` y `app/Exceptions/FormatoDeError.php`.

## Puertos (§5.3)

- `Compartido/Aplicacion/EquiposDelEntrenador`: `equiposDe(Identificador $usuario): list<Identificador>` y `entrena(Identificador $usuario, Identificador $equipo): bool`. Asignación vigente en `entrenador_equipo`, equipo no eliminado y usuario con rol `entrenador` (§3.4, DA-13).
- `Compartido/Aplicacion/JugadoresDelEquipo`: `de(Identificador $equipo): list<JugadorDelEquipo>` con `id`, `nombres` y `apellidos`. Membresía vigente y sin baja, por `AlcanceDeJugadores` (`incluirBajas` falso, `soloEntrenador` verdadero) para cumplir la regla «toda consulta de jugadores pasa por el alcance» (DA-12).
- Adaptadores `EquiposDelEntrenadorPlantel` y `JugadoresDelEquipoPlantel` en `Plantel/Infraestructura`, enlazados en `PlantelServiceProvider`.

## Entrenamientos

- **Dominio:** `Entrenamiento` (horario con fin posterior al inicio; `modificar` solo en `programado`; transiciones `programado` → `realizado` o `cancelado`), `EstadoDeEntrenamiento`, `RepositorioDeEntrenamientos` (alta, cambio con historial completo, búsqueda, listado por equipo y rango, existencia de empalme). `RepositorioDeLugares::existe`.
- **Aplicación:** `ProgramarEntrenamiento`, `ModificarEntrenamiento`, `VerEntrenamiento`, `ListarEntrenamientos`; excepciones `ResponsableNoAsignado` (422, campo `responsable_id`), `TransicionNoPermitida` (409, campo `estado`), `EntrenamientoNoProgramado` (409, DA-08), `LugarInexistente` (422, DA-11), `EntrenamientoNoEncontrado` (404). El empalme se devuelve como advertencia `empalme_de_lugar` (DA-09).
- **HTTP:** `EntrenamientoController`; `SolicitudDeNuevoEntrenamiento` y `SolicitudDeCambioDeEntrenamiento` con `authorize()` que comprueba el equipo (404 antes que 422); `EntrenamientoRecurso` (con `jugadores` en el detalle, el alta y el cambio; sin ellos en el listado). Rutas `GET`/`POST /equipos/{equipo}/entrenamientos`, `GET`/`PATCH /entrenamientos/{entrenamiento}`.
- **Instantes:** ISO 8601 con zona (`Y-m-d\TH:i:sP`, admite `Z`), se guardan en UTC y se devuelven en UTC.
- **Listado:** `filtro[desde]` y `filtro[hasta]` son días `AAAA-MM-DD` en `America/Mexico_City` sobre `inicia_en`, extremos incluidos; orden `inicia_en` (por defecto) o `-inicia_en` (DA-10).

## Pruebas

- §7.2 filas 1 a 7 (`HU-10 (§7.2 fila N)`), más: empalme al modificar; intervalos que solo se tocan no empalman; cuerpo vacío o campo no admitido; validación por campo; horario invertido al crear (422 en `termina_en`) y al modificar (422 `rango_de_fechas_invalido`); lugar inexistente; filtros; 401, 403 y 404 (ajeno, inexistente, UUID v4).
- Arquitectura: Competencia no importa `Src\Plantel` ni nombra tablas de Plantel; los adaptadores no se usan fuera de su enlace.
- Integración de los adaptadores (asignación cerrada, equipo eliminado, usuario sin rol, membresía cerrada, baja).
- Unitarias de `Entrenamiento` (Infection) y contrato.

## Dudas

DA-07 a DA-13. DA-08 agrega un código de error (`entrenamiento_no_programado`) que la especificación no nombra: queda para aprobación.
