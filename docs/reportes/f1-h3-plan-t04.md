# Plan de F1-H3-T04: asistencia y porcentajes (HU-11)

**Rutas:** rutas base (`docs/hitos/f1-h3.md` v1.0.1 §8). §3.2 resuelta por el responsable: la asistencia la ven todos los entrenadores del equipo (§10).

## Rutas

| Ruta | Qué hace |
|---|---|
| `GET /entrenamientos/{entrenamiento}/asistencias` | Registros del entrenamiento, paginados (`id`, `entrenamiento_id`, `jugador_id`, `estado`, `actualizado_en`), sin datos del jugador (DA-19) |
| `POST /entrenamientos/{entrenamiento}/asistencias` | `registros: [{jugador_id, estado}]`; todo o nada, en una transacción, con historial por registro (DA-17) |
| `PATCH /asistencias-entrenamiento/{asistencia}` | `estado`; historial `actualizar` (DA-18) |
| `GET /equipos/{equipo}/asistencia-entrenamientos` | Porcentaje y conteos del equipo, y `por_jugador` con los jugadores vigentes (DA-21) |
| `GET /jugadores/{jugador}/asistencia-entrenamientos` | Porcentaje y conteos del jugador, solo con entrenamientos de equipos que el usuario entrena (§3.4); alcance de entrenador (DA-20) |

Orden de comprobación del alta: 404 (equipo del entrenamiento, en `authorize()`) → 422 de forma → 409 `entrenamiento_no_realizado` → 422 `jugador_no_pertenece_al_equipo` → 409 `asistencia_ya_registrada`.

## Capas

- **Dominio:** `AsistenciaDeEntrenamiento` (inmutable, `conEstado`), `EstadoDeAsistencia` de Competencia, `PorcentajeDeAsistencia` (presentes ÷ (presentes + faltas + justificadas) × 100 con `NumeroDecimal`, HALF_UP a dos decimales; `null` sin registros), `RepositorioDeAsistenciasDeEntrenamiento`.
- **Aplicación:** `RegistrarAsistencias`, `CorregirAsistencia`, `ListarAsistencias`, `VerAsistenciaDelEquipo`, `VerAsistenciaDelJugador`; excepciones `EntrenamientoNoRealizado`, `AsistenciaYaRegistrada`, `JugadorNoPerteneceAlEquipo` (campo `registros.N.jugador_id`, DA-16). Puertos nuevos de Competencia: `ConsultaDeAsistencia` (conteos) y `VisibilidadDeJugador` (alcance por `AlcanceDeJugadores` sin leer tablas de Plantel).
- **Infraestructura:** repositorio con historial (`asistencias_entrenamiento_historial`); la carrera entre dos capturas la detiene la UQ parcial (→ 409). La visibilidad del jugador restringe una consulta sobre un identificador candidato con `AlcanceDeJugadores` (`soloEntrenador`, con bajas).
- **HTTP:** `AsistenciaDeEntrenamientoController`, `SolicitudDeAsistenciasDeEntrenamiento`, `SolicitudDeCorreccionDeAsistenciaDeEntrenamiento` (pertenencia en `authorize()`), recursos y contrato.

## Pruebas

- §7.3 filas 1 a 11 (`HU-11 (§7.3 fila N)`), §7.6 filas 1, 2 y 8 (`RNF-01 (§7.6 fila N)`).
- Adicionales: captura parcialmente inválida no guarda nada; jugador repetido en la lista; historial de alta y corrección; E3 ve y corrige; docente: 403 en las rutas de entrenador y 404 en la del jugador; sin sesión, 401; UUID v4; contrato; unitarias de `PorcentajeDeAsistencia` y `AsistenciaDeEntrenamiento` (Infection), incluido el redondeo (2 de 3 = 66.67).

## Decisiones

DA-16 a DA-22 en `f1-h3-decisiones-del-agente.md`.
