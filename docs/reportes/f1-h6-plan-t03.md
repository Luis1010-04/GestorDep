# F1-H6-T03: plan de recálculo de alertas

**Fecha:** 16 de noviembre de 2026 · **Especificación:** `docs/hitos/f1-h6.md` v1.11 · **Estado:** plan para aprobación (revisión de plan). No se escribió código, pruebas ni migraciones, y no se ejecutó `make`.
**Nota:** este archivo sustituye al plan de T03 del 15 y 16 de noviembre; esa versión sigue en el historial de git.

## 1. Alcance y reglas aplicadas

T03 construye el recálculo de las tres alertas (HU-22, RN-21, RN-09, RN-10, ADR-07 con DA-88) sobre las reglas puras que T02 dejó en `src/Alertas/Dominio/`. No crea rutas HTTP, tablas, migraciones ni eventos (la tabla `alertas` y su disparador son de T01).

| Decisión | Qué manda a T03 | Dónde está en este plan |
|---|---|---|
| DA-88 | La alerta académica se recalcula por escucha de `CalificacionRegistrada`, `AsistenciaDeClaseRegistrada`, `CriterioDeMateriaModificado` y `JugadorInscrito`, en cola `afterCommit` e idempotente. Las deportivas y las de sanciones, con el comando diario | §4, §5 |
| DA-112 | Alertas lee otros contextos solo por puertos de `src/Compartido/Aplicacion/`, con adaptador en el contexto dueño. Cuatro puertos nuevos: `TarjetasPorPartido` (Rendimiento), `FaltasDeEntrenamiento` (Competencia), `TemporadaDeFecha` (Competencia) y `EvaluacionesVigentesDelEquipo` (Rendimiento). Falta construir el del nivel de riesgo académico (P-09, adaptador en Académico) | §3 |
| DA-113 | `alertas:recalcular` vive en `src/Alertas/Infraestructura/Consola/` y se programa a las 02:00 (America/Mexico_City) en `routes/console.php`. El comando de resúmenes de Académico pasa a `resumenes:conciliar`, también a las 02:00 | §4, §8, §9 |
| DA-102 y DA-103 | Las alertas deportivas y de sanciones cuentan datos de todos los entrenadores del equipo; basta un `no_pasa`; por partido, jugador y tipo de tarjeta cuenta el mayor número registrado por un entrenador. Ya están en el Dominio (T02) | §3, §7 |
| DA-107 | Sin evaluación guardada, el resultado es `datos_insuficientes` | §3 (puerto de evaluaciones), §7 |
| DA-91 y P-08b | Las tarjetas cuentan por temporada del partido; si ninguna temporada contiene la fecha de cálculo, no se cuentan tarjetas, pero sí faltas | §3 (`TemporadaDeFecha`), §7 |
| DA-92 | Se toma la última evaluación de cada entrenador del equipo en `evaluaciones_rendimiento` | §3 |
| DA-114 | No hay ruta para asignar entrenadores adicionales; las pruebas de DA-102 crean las filas de `entrenador_equipo` directamente | §7 |
| P-09 (A) | El escucha calcula el nivel de riesgo por un puerto, sin leer el resumen guardado | §3, §5 |
| P-08c (A) y DA-99 | Las alertas abiertas de un jugador en baja se conservan y no se muestran; T03 no abre alertas nuevas a un jugador en baja | §4, §6 |
| DA-87, DA-100, DA-105 | Tabla `alertas`, claves de `parametros_institucionales` y forma de `causa` | §6 |

Fuera de T03: las rutas y consultas de alertas y del listado (T04), la vista para convocar y los gráficos (T05, T07), el guion de humo de H6 (T09).

## 2. Estado de partida

Solo lectura, con grep y lectura corta (rutas bajo `backend/`).

| Qué | Hallazgo |
|---|---|
| Comando actual | `src/Academico/Infraestructura/Consola/ConciliarResumenesComando.php`, firma `alertas:recalcular`. Su `handle` solo llama a `ConciliarResumenes::ejecutar()` (`src/Academico/Aplicacion/`), que recalcula el resumen de cada jugador con inscripciones, y devuelve `SUCCESS` sin escribir texto. Lo registra `AcademicoServiceProvider::boot()` (si corre en consola). `routes/console.php:6` lo programa con `dailyAt('02:00')` y zona America/Mexico_City |
| Dominio de Alertas (T02) | `src/Alertas/Dominio/`: `AccionSobreAlerta`, `AcumulacionDeSanciones`, `AlertaAcademica`, `DecisionDeAlerta`, `InasistenciaAEntrenamiento`, `ParametrosDeSanciones`, `ResultadoDeEvaluacion` (con `delEquipo()`), `RiesgoDeBajaDeportiva`, `TarjetasDePartido` (con `elMayorPorPartido()`), `TarjetasRegistradas`, `VentanaDeFaltas`. Sus únicos `use` son `DateTimeImmutable` y `Src\Compartido\Dominio\ReglaDeNegocioViolada`: no importan otros contextos |
| Aplicación e Infraestructura de Alertas | `src/Alertas/Aplicacion/` solo tiene `.gitkeep`. `src/Alertas/Infraestructura/AlertasServiceProvider.php` está vacío, aunque ya figura en `bootstrap/providers.php` |
| Escuchas de Académico | Uno: `src/Academico/Infraestructura/Escuchas/RecalcularResumenAcademico.php`, `ShouldQueue`, `afterCommit = true`, 3 intentos. `AcademicoServiceProvider::boot()` lo registra con `Event::listen` para los cuatro eventos |
| Publicación de eventos | Los casos de uso de `src/Academico/Aplicacion/` (`RegistrarCalificacion`, `CorregirCalificacion`, `RegistrarAsistencia`, `CorregirAsistencia`, `DefinirCriterioDeMateria`, `InscribirJugador`) llaman a `PublicadorDeEventos`; `PublicadorDeEventosLaravel` despacha con `DB::afterCommit`. Los eventos viven en `src/Compartido/Dominio/Eventos/`. Tres traen `jugadorId` (`CalificacionRegistrada`, `AsistenciaDeClaseRegistrada`, `JugadorInscrito`); `CriterioDeMateriaModificado` trae solo `materiaId` |
| Cola | `config/queue.php`: por defecto `database`. `phpunit.xml`: `sync` |
| Tabla `alertas` (T01) | Existe: `database/migrations/alertas/2026_11_15_080100_crear_tabla_alertas.php`, con el disparador `rechazar_modificacion_alerta` (solo permite `cerrada_en` de nulo a fecha), el índice único parcial `uq_alertas_abierta` por (tipo, jugador, equipo o uuid nulo) y el `CHECK` del tipo. El seeder `ParametrosInstitucionalesSeeder` ya siembra las cuatro claves de DA-100 |
| Puertos de Compartido | `src/Compartido/Aplicacion/` no tiene ninguno de los cuatro de DA-112 ni el del nivel de riesgo académico. Sí tiene `InicioDePartidos` (adaptador en Competencia), `JugadoresDelEquipo`, `EquiposDelEntrenador` y `AlcanceDeJugadores` |
| Reloj | `Src\Compartido\Dominio\Reloj` (`ahora()`, `hoy()`) y `HoyDeNegocio`, con `HoyDeNegocioSegunReloj` en Infraestructura. Las pruebas usan `RelojFijo` |

**Lo que difiere de la especificación**

1. **El comando no reconcilia alertas.** T03 dice «quitar la reconciliación de alertas» de `ConciliarResumenesComando`, y `docs/estrategia-pruebas.md` línea 68 dice «Concilia resúmenes y alertas abiertas». El código solo concilia resúmenes; nunca tocó alertas. El cambio en ese archivo es solo la firma y el comentario.
2. **No son 23 llamadas, son 17.** La cifra «23 llamadas en 5 archivos» cuenta líneas con la cadena `alertas:recalcular`: 17 llamadas a `consolaArtisan(...)`, 5 títulos de prueba y 1 comprobación del horario. Detalle en §9.
3. **Faltan datos para el comando que ningún puerto de DA-112 cubre.** El comando corre sin usuario de sesión y necesita las parejas (jugador, equipo) con membresía vigente y sin baja; `JugadoresDelEquipo` pasa por `AlcanceDeJugadores` con el usuario de la sesión, así que no sirve. Tampoco lo cubre la lista de jugadores con inscripciones para el escucha de `CriterioDeMateriaModificado`, que solo trae `materiaId`. Ver §3 y la pregunta 1 de §10.
4. **El puerto P-09 no devuelve solo el nivel.** La `causa` de la alerta académica lleva `nivel_riesgo`, `promedio` y `materias_reprobadas` (DA-105, DA-110), así que el puerto debe devolver los tres. Ver §3.
5. **La temporada de cada partido no es dato de Rendimiento.** `TarjetasRegistradas` (T02) lleva `temporadaId` por partido, pero `partidos.temporada_id` es de Competencia. Ver §3.
6. **Rutas permitidas.** T03 permite adaptadores solo en Rendimiento y Competencia, y en Académico solo el archivo del comando. El adaptador del puerto P-09 (Académico) y el de la lista de membresías (Plantel) quedan fuera. Ver §8 y la pregunta 2 de §10.

## 3. Puertos

[pendiente]

## 4. Comando y programación

[pendiente]

## 5. Escuchas de la alerta académica

[pendiente]

## 6. Cierre y apertura

[pendiente]

## 7. Pruebas previstas

[pendiente]

## 8. Archivos

[pendiente]

## 9. Cambios de renombrado fuera de rutas permitidas

[pendiente]

## 10. Preguntas abiertas para Luis

[pendiente]
