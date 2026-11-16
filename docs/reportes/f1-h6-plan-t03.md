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

Todos son interfaces en `src/Compartido/Aplicacion/`, con tipos primitivos, `Identificador`, `DateTimeImmutable` y DTO propios de Compartido. Los consume la capa de **Aplicación** de Alertas; ella arma los objetos de Dominio de T02 (`TarjetasRegistradas`, `InasistenciaAEntrenamiento`, `ResultadoDeEvaluacion`) y llama a las reglas. El Dominio de Alertas no ve ningún puerto. Cada adaptador se registra con `bind` en el proveedor de su contexto, como los de DA-109. Todas las lecturas son de solo lectura. Cada puerto se valida con una prueba de contrato del adaptador (`tests/Feature/Alertas/Puertos/`), con PostgreSQL real, y con la prueba de la regla que lo usa (§7).

### 3.1 Los cuatro de DA-112

| Puerto | Firma | Adaptador | Qué hace y cómo lo usa Alertas |
|---|---|---|---|
| `TarjetasPorPartido` | `de(Identificador $jugador, Identificador $equipo): list<TarjetasDeUnRegistro>`; DTO `{partidoId: string, amarillas: int, rojas: int}` | Rendimiento (`rendimientos_partido`) | Una entrada por fila registrada por **cualquier** entrenador del equipo (DA-102): de ahí sale el máximo por partido y tipo (DA-103). Sin el entrenador en el DTO, porque la regla no lo necesita. Alertas lo convierte en `TarjetasRegistradas` (con la temporada, ver 3.3) y llama a `TarjetasDePartido::elMayorPorPartido()` |
| `FaltasDeEntrenamiento` | `de(Identificador $jugador, Identificador $equipo, DateTimeImmutable $desde, DateTimeImmutable $hasta): list<FaltaDeEntrenamiento>`; DTO `{fecha: DateTimeImmutable, justificada: bool}` | Competencia (`asistencias_entrenamiento` y `entrenamientos` en estado `realizado`; DA-91) | Solo trae lo que cae en la ventana (de `fecha de cálculo − (días − 1)` a la fecha de cálculo, con `faltas_ventana_dias` de `parametros_institucionales`). Alertas lo convierte en `InasistenciaAEntrenamiento`; `VentanaDeFaltas::contar()` aplica el recorte por día de negocio. El adaptador no decide si la falta justificada cuenta: eso es del Dominio |
| `TemporadaDeFecha` | `de(DateTimeImmutable $fecha): ?Identificador` | Competencia (`temporadas`; sin solape por §7.5 de arquitectura, así que hay a lo sumo una) | Da la temporada de la fecha de cálculo. `null` si ninguna la contiene: se pasa `null` a `AcumulacionDeSanciones::seCumple()` y no se cuentan tarjetas, pero sí faltas (DA-91, P-08b) |
| `EvaluacionesVigentesDelEquipo` | `de(Identificador $jugador, Identificador $equipo): list<EvaluacionVigente>`; DTO `{entrenadorId: string, resultado: string, promedio: ?string}` (decimal como texto) | Rendimiento (`evaluaciones_rendimiento`) | La última fila por entrenador, por `calculado_en` (DA-92). Alertas convierte cada `resultado` a `ResultadoDeEvaluacion` y llama a `delEquipo()`: basta un `no_pasa`, y sin filas el resultado es `datos_insuficientes` (DA-107). De ahí sale también el `promedio` de la causa (el de la evaluación que decide: la `no_pasa` de menor promedio; si ninguna, la de mayor promedio; sin evaluaciones, `null`). **Esa elección es una propuesta de este plan, no de la spec**: DA-105 solo dice que `promedio` es `null` con `datos_insuficientes` |

Dos notas: (a) el adaptador de evaluaciones no filtra por los entrenadores que hoy están asignados, porque Rendimiento no lee `entrenador_equipo`; en la Fase 1 no hay ruta para quitar ni agregar entrenadores a un equipo (DA-114), así que no hay diferencia observable. (b) Ninguno de los cuatro recibe usuario ni pasa por `AlcanceDeJugadores`: el comando corre sin sesión y la visibilidad se aplica al leer las alertas (T04).

### 3.2 El del nivel de riesgo académico (P-09, ya decidido)

| Puerto | Firma | Adaptador | Qué hace |
|---|---|---|---|
| `RiesgoAcademicoDelJugador` | `de(Identificador $jugador): ?RiesgoAcademico`; DTO `{nivel: string, promedio: ?string, materiasReprobadas: int}`; `null` si el jugador no tiene inscripciones del periodo vigente | Académico | Calcula el nivel con las mismas reglas del resumen (`ParametrosDeRiesgo`, `parametros_institucionales`), **sin leer `resumenes_academicos`** (P-09, opción A), de modo que el escucha de Alertas no depende del orden respecto al del resumen. Cuenta como `materiasReprobadas` todas las del periodo vigente (DA-110). Alertas pasa `nivel` y `parametros['alerta_academica_nivel']` a `AlertaAcademica::seCumple()`; `null` equivale a «sin riesgo» |
| `JugadoresDeLaMateria` | `de(Identificador $materia): list<Identificador>` | Académico | Los jugadores con inscripción vigente en la materia. Lo necesita el escucha de `CriterioDeMateriaModificado`, que solo trae `materiaId` (`RecalcularResumenesDeMateria` hace lo mismo dentro de Académico). **No está en DA-112 ni en P-09**; se propone como segundo método del mismo puerto, o como puerto aparte. Ver pregunta 2 de §10 |

La forma del DTO y el segundo puerto son lo que este plan agrega a lo ya decidido; el expediente P-09 solo fijó que el nivel se calcula por un puerto.

### 3.3 La temporada de cada partido

`TarjetasRegistradas` lleva `temporadaId` por partido, pero `partidos.temporada_id` es de Competencia y el adaptador de Rendimiento no puede leerla. Propuesta, sin puerto nuevo: Alertas obtiene la temporada de cada partido con el puerto que ya existe, `InicioDePartidos` (fecha de inicio del partido, adaptador en Competencia), y con `TemporadaDeFecha` aplicado a esa fecha. Eso coincide con `partidos.temporada_id` porque arquitectura §7.5 define esa columna como «la que contiene la fecha». Alternativa: un puerto `TemporadaDelPartido`. Ver pregunta 3 de §10.

### 3.4 El que falta para el comando

| Puerto | Firma | Adaptador | Qué hace |
|---|---|---|---|
| `MembresiasVigentes` | `todas(): list<MembresiaVigente>`; DTO `{jugadorId: string, equipoId: string}` | Plantel (`jugador_equipo` vigente, jugadores sin baja) | Las parejas (jugador, equipo) sobre las que el comando evalúa las deportivas y las de sanciones, y los jugadores para la académica (la lista de jugadores se obtiene de las mismas parejas, sin repetir). **No está en DA-112**, y `JugadoresDelEquipo` no sirve porque exige usuario. Ver pregunta 1 de §10 |

Un jugador en baja no entra en esta lista: no se le abren alertas, y las abiertas se conservan sin tocar (P-08c, DA-99).

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
