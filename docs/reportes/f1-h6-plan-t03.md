# F1-H6-T03: plan de recálculo de alertas

**Fecha:** 16 de noviembre de 2026 · **Especificación:** `docs/hitos/f1-h6.md` v1.13 · **Estado:** plan aprobado el 16/11/2026 (DA-112, DA-113, DA-115 a DA-117; renombrado y rutas, §9). No se escribió código de T03: la implementación espera la orden del responsable.
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

**Clase nueva.** `Src\Alertas\Infraestructura\Consola\RecalcularAlertasComando`, firma `alertas:recalcular`, sin argumentos ni opciones. No escribe texto en consola (los mensajes irían en `lang/es`, y no hay ninguno que dar). Devuelve `SUCCESS`. Lo registra `AlertasServiceProvider::boot()` con `$this->commands([...])` cuando corre en consola, igual que Académico y Identidad. El comando solo llama al caso de uso `RecalcularTodasLasAlertas` (`src/Alertas/Aplicacion/`); no contiene reglas.

**Fecha de cálculo.** Se lee una sola vez al empezar, del reloj inyectable de Compartido (`Reloj`/`HoyDeNegocio`, vía `HoyDeNegocioSegunReloj`), y se pasa a todas las reglas; así una corrida que cruza la medianoche no mezcla dos días. `activada_en` y `cerrada_en` llevan el instante del reloj, no `now()` de la base. Las pruebas fijan el día con `RelojFijo`.

**Parámetros.** Se leen una vez por corrida: `sanciones_deportivas` (tarjetas amarillas y rojas, umbral y ventana de faltas) y `alerta_academica_nivel`, de `parametros_institucionales` (DA-100), nunca de constantes (RNF-09). Se asume que Alertas puede leer esa tabla con su propio repositorio en `Infraestructura/`, porque arquitectura §7.9 la lista junto a `alertas` y T02 ya describe estos parámetros como suyos; si el responsable lo ve distinto, es un puerto más.

**Orden dentro de una corrida.**

1. Se pide la lista de membresías vigentes sin baja (§3.4): parejas (jugador, equipo).
2. Por cada pareja, en este orden, porque RN-10 usa el resultado de RN-09:
   1. **Acumulación de sanciones** (RN-09): tarjetas, temporada de la fecha de cálculo y faltas de la ventana → `AcumulacionDeSanciones::seCumple()` → `DecisionDeAlerta::decidir()`.
   2. **Riesgo de baja deportiva** (RN-10): evaluaciones del equipo → `ResultadoDeEvaluacion::delEquipo()` y el resultado de (1) → `RiesgoDeBajaDeportiva::seCumple()`.
3. Una vez por jugador, **alerta académica** (RN-21), con el puerto de §3.2.

Cada decisión da `Abrir`, `Cerrar` o `Ninguna`, y el repositorio la aplica (§6). La unidad de trabajo es el jugador: una transacción por jugador con las tres alertas, para que un fallo no deje a un jugador a medias. Si el recálculo de un jugador lanza una excepción, se informa con `report()` y la corrida sigue con el siguiente; al terminar, el comando devuelve `FAILURE` si hubo alguna. Es una decisión de diseño de T03 dentro del plan, no un cambio de contrato; la prueba del comando con base sin datos sigue saliendo con 0.

**Programación.** `routes/console.php` ya trae `Schedule::command('alertas:recalcular')->dailyAt('02:00')->timezone('America/Mexico_City')` (línea 6, de H2). T03 conserva esa línea, que ahora corresponde al comando nuevo, y agrega `Schedule::command('resumenes:conciliar')->dailyAt('02:00')->timezone('America/Mexico_City')` (DA-113). Sin `withoutOverlapping` ni otras opciones: no están en la spec. Los dos eventos de las 02:00 corren uno tras otro en la misma pasada del planificador; el orden entre ellos no importa, porque la alerta académica calcula el nivel por el puerto y no lee el resumen guardado (P-09).

**Renombrado de `resumenes:conciliar`.** `ConciliarResumenesComando` conserva el nombre de la clase y su caso de uso `ConciliarResumenes`; cambian la firma (`resumenes:conciliar`) y su comentario. No hay nada que quitar de «reconciliación de alertas»: el comando nunca la tuvo (§2, punto 1). Las pruebas y documentos que lo llaman por su firma vieja están en §9.

## 5. Escuchas de la alerta académica

**Un escucha para los cuatro eventos**, como el de Académico: `Src\Alertas\Infraestructura\Escuchas\RecalcularAlertaAcademica`. Dos razones: los cuatro hacen lo mismo (recalcular la alerta académica de uno o más jugadores) y así queda un solo lugar para la idempotencia y los reintentos. Se registra en `AlertasServiceProvider::boot()` con `Event::listen([CalificacionRegistrada::class, AsistenciaDeClaseRegistrada::class, JugadorInscrito::class, CriterioDeMateriaModificado::class], RecalcularAlertaAcademica::class)`. Es un cambio del proveedor de Alertas, sin tocar el de Académico.

**Cola.** `implements ShouldQueue`, `public bool $afterCommit = true`, `public int $tries = 3`, igual que `RecalcularResumenAcademico`. Los eventos ya se despachan con `DB::afterCommit` (`PublicadorDeEventosLaravel`), así que el trabajo en cola solo se encola con la transacción confirmada. En Docker corre el servicio `worker` (driver `database`); en pruebas el driver es `sync` (`phpunit.xml`).

**Qué hace según el evento.**

| Evento | Jugadores que recalcula |
|---|---|
| `CalificacionRegistrada`, `AsistenciaDeClaseRegistrada`, `JugadorInscrito` | El de `jugadorId` |
| `CriterioDeMateriaModificado` (solo trae `materiaId`) | Los jugadores con inscripción vigente en esa materia, por `JugadoresDeLaMateria` (§3.2) |

Para cada jugador llama a `RecalcularAlertaAcademicaDeJugador` (`src/Alertas/Aplicacion/`), el mismo caso de uso que usa el comando para la tercera regla de §4. Así el escucha y el comando no pueden divergir.

**Cómo obtiene el nivel.** Por el puerto `RiesgoAcademicoDelJugador` (§3.2), que calcula el nivel, el promedio y las materias reprobadas con las reglas de Académico y los parámetros vigentes, **sin leer `resumenes_academicos`** (P-09, opción A). El escucha de Académico y el de Alertas pueden correr en cualquier orden o en paralelo; ninguno depende del otro.

**Idempotencia.**

- El escucha no guarda estado: cada ejecución recalcula desde los datos y decide con `DecisionDeAlerta::decidir($seCumple, $hayAbierta)`. Repetir el mismo evento da la misma decisión: la primera vez abre o cierra; las siguientes dan `Ninguna`.
- Abrir es un `INSERT` protegido por el índice único parcial `uq_alertas_abierta`. Si el escucha y el comando abren a la vez, el segundo recibe la violación 23505 y la trata como «ya está abierta»: no falla ni reintenta.
- Cerrar es `UPDATE alertas SET cerrada_en = :ahora WHERE id = :id AND cerrada_en IS NULL`; si no afecta filas, otro proceso ya la cerró.
- Un jugador en baja no se recalcula (P-08c): el escucha termina sin escribir.
- Si el trabajo falla tres veces, queda en `failed_jobs`, como el de Académico; el comando diario lo corrige a las 02:00.

**Pruebas del escucha** (driver `sync`): por evento, la fila de §7.1 que corresponda, y el mismo evento despachado dos veces sin duplicar (§7).

## 6. Cierre y apertura

**Reglas (DA-87, arquitectura §7.9).**

| Regla | Cómo la cumple T03 |
|---|---|
| A lo sumo una alerta abierta por (tipo, jugador, equipo); `equipo_id` nulo en la académica | La decisión pregunta antes si hay una abierta (`RepositorioDeAlertas::abiertaDe(tipo, jugador, equipo)`), y el índice único parcial `uq_alertas_abierta` (T01) lo garantiza ante carreras: una violación 23505 al abrir se trata como «ya abierta» |
| `cerrada_en` se escribe una sola vez | Cerrar es `UPDATE … SET cerrada_en = :ahora WHERE id = :id AND cerrada_en IS NULL`. El disparador de T01 solo admite `cerrada_en` de nulo a una fecha; una alerta ya cerrada no se vuelve a escribir |
| Una alerta cerrada no se reabre | El repositorio no tiene operación de reabrir. `abiertaDe()` solo mira `cerrada_en IS NULL`; si la condición vuelve a cumplirse, `Abrir` inserta una fila nueva con su propia `activada_en` |
| El disparador rechaza todo otro UPDATE y todo DELETE | T03 no emite ninguno. El repositorio solo tiene `abrir()` (INSERT) y `cerrar()` (UPDATE de `cerrada_en`); no hay `delete`, ni `Model::delete()`, ni `forceDelete()` |
| `activada_en` y `cerrada_en` no se reescriben nunca en alertas cerradas | El comando nunca hace UPDATE sobre filas con `cerrada_en` no nulo |

**Decisión por alerta.** `DecisionDeAlerta::decidir($seCumple, $hayAbierta)`: `Abrir` si se cumple y no hay abierta; `Cerrar` si no se cumple y hay abierta; `Ninguna` en los otros dos casos (no duplica). Es la tabla de §7.1.

**`causa` al abrir (DA-105, DA-110).** Se escribe una sola vez, al abrir, con las claves de §6.2 de la spec; el disparador impide cambiarla después, así que una alerta que sigue abierta conserva la causa de cuando se activó.

| `tipo` | `causa` |
|---|---|
| `riesgo_baja_academica` | `{nivel_riesgo, promedio, materias_reprobadas}` del puerto de §3.2 |
| `riesgo_baja_deportiva` | `{resultado, promedio, acumulacion_sanciones}`; `promedio` es `null` con `datos_insuficientes` |
| `acumulacion_sanciones_deportivas` | `{tarjetas_amarillas, tarjetas_rojas, faltas_en_ventana}`, enteros |

Los tres conteos de la causa de sanciones salen de la misma lectura que la regla; para no calcularlos dos veces, el caso de uso pide al Dominio los conteos y la decisión juntos. Eso exige un método de lectura en `AcumulacionDeSanciones` (por ejemplo, `conteos()`), que T02 no tiene: es un cambio al Dominio de T02 y **no está en las rutas permitidas de T03** (`src/Alertas/Dominio/` no figura). Se pide aprobarlo con el plan (ver §8, «Rutas que hay que pedir»); la alternativa es calcular los conteos en Aplicación, que duplica la regla y no se recomienda.

**Jugador en baja (P-08c, DA-99).** Las alertas abiertas de un jugador en baja no se cierran ni se tocan; T04 no las muestra. T03 no abre alertas nuevas a quien está en baja, porque el jugador no figura en la lista de membresías vigentes (§3.4).

**Cierre por tiempo.** Lo cubre el comando diario: las faltas salen de la ventana y la tarjeta roja deja de contar al terminar la temporada. Al recalcular, la regla ya no se cumple y la decisión es `Cerrar`.

## 7. Pruebas previstas

Se escriben primero y deben fallar por la razón correcta (no por falta de clase). PostgreSQL real; un nombre por fila, con el identificador al principio; `RelojFijo` para el día. Datos con fábricas y los mundos de `tests/Ayudantes/` (los helpers compartidos van ahí, no dentro de un archivo de prueba, por el paralelismo de Pest). Archivos en `tests/Feature/Alertas/` salvo donde se indica.

### 7.1 Las filas de las tablas de la spec

| Archivo | Prueba (nombre) | Fila |
|---|---|---|
| `RecalculoDeAlertaAcademicaTest.php` | `RN-21 (§7.1 fila 1): en_riesgo sin alerta abierta, el comando crea la alerta` | §7.1 f1 |
| | `RN-21 (§7.1 fila 2): en_riesgo con alerta abierta, el comando no la duplica` | f2 |
| | `RN-21 (§7.1 fila 3): en_observacion sin alerta abierta, no se crea` | f3 |
| | `RN-21 (§7.1 fila 4): en_observacion con alerta abierta, se cierra con cerrada_en` | f4 |
| | `RN-21 (§7.1 fila 5): sin_riesgo con alerta abierta, se cierra` | f5 |
| | `RN-21 (DA-88): CalificacionRegistrada, AsistenciaDeClaseRegistrada, JugadorInscrito y CriterioDeMateriaModificado recalculan la alerta académica` (cuatro pruebas, una por evento, con el driver `sync`) | f1 por evento |
| | `RN-21 (DA-88): el mismo evento dos veces no duplica la alerta` | f2 por evento |
| | `RN-21 (DA-88): la baja de nivel por un evento cierra la alerta` | f4 por evento |
| `AcumulacionDeSancionesTest.php` | `RN-09 (§7.3 fila 1): 1 tarjeta roja en la temporada, se activa` | §7.3 f1 |
| | `RN-09 (§7.3 fila 2): 2 amarillas en la temporada, no se activa` | f2 |
| | `RN-09 (§7.3 fila 3): 3 amarillas en la temporada, se activa` | f3 |
| | `RN-09 (§7.3 fila 4): 3 faltas en 30 días, se activa` | f4 |
| | `RN-09 (§7.3 fila 5): 2 faltas y 1 justificada en 30 días, no se activa` | f5 |
| | `RN-09 (§7.3 fila 6): 3 faltas repartidas en 31 días, no se activa` (la spec no da fechas; la prueba usa `fecha_calculo − 30`, `− 15` y `0`, que abarcan 31 días; es un dato de prueba, no una regla nueva) | f6 |
| | `RN-09 (§7.3 fila 7): 1 tarjeta roja de la temporada anterior, no se activa` | f7 |
| | `RN-09 (§7.3 fila 8): alerta abierta por 3 faltas; en el recálculo diario la más antigua ya salió de la ventana, se cierra` (se mueve `RelojFijo`) | f8 |
| | `RN-09 (§7.3 fila 9): alerta abierta por 1 roja; el recálculo ocurre después del fin de la temporada, se cierra` | f9 |
| `RiesgoDeBajaDeportivaTest.php` | `RN-10 (§7.4 fila 1): no_pasa sin sanciones, se activa` | §7.4 f1 |
| | `RN-10 (§7.4 fila 2): pasa con sanciones, se activa` | f2 |
| | `RN-10 (§7.4 fila 3): pasa sin sanciones, no se activa` | f3 |
| | `RN-10 (§7.4 fila 4): datos_insuficientes sin sanciones, no se activa` | f4 |
| | `RN-10 (§7.4 fila 5): datos_insuficientes con sanciones, se activa` | f5 |

Las evaluaciones de §7.4 son filas reales de `evaluaciones_rendimiento`; las sanciones, filas reales de `rendimientos_partido` y `asistencias_entrenamiento`.

### 7.2 Las pedidas además, y las que salen de las decisiones

| Archivo | Prueba (nombre) |
|---|---|
| `RecalculoDiarioTest.php` | `RN-21, RN-09, RN-10 (ADR-07): alertas:recalcular dos veces da la misma tabla de alertas, mismos id, mismas fechas y sin duplicados` |
| | `RN-09 (§7.3 fila 9, ADR-07): el recálculo posterior al fin de temporada cierra la acumulación y conserva activada_en` (la explícita que pide la spec, junto a la fila 9) |
| | `DA-87: una alerta cerrada no se reabre; si la condición vuelve se crea una nueva` |
| | `DA-87: alertas:recalcular no toca activada_en ni cerrada_en de alertas ya cerradas` |
| | `DA-87 (T01): un UPDATE distinto de cerrada_en y todo DELETE sobre alertas siguen siendo rechazados tras el recálculo` (solo si T01 no lo prueba ya; se revisa antes de escribirla) |
| | `P-08c (DA-99): un jugador en baja no recibe alertas nuevas y las abiertas se conservan` |
| | `DA-91 (P-08b): sin temporada que contenga la fecha de cálculo no se cuentan tarjetas, pero 3 faltas sí activan` |
| | `DA-105 (§6.2): la causa de cada alerta lleva las claves de su tipo` (tres pruebas, una por tipo) |
| | `DA-113: alertas:recalcular sale con 0 y sin texto cuando no hay datos` |
| | `DA-113: alertas:recalcular está programado a las 02:00 de America/Mexico_City` |
| | `DA-113: resumenes:conciliar está programado a las 02:00 de America/Mexico_City` |
| | `DA-113: resumenes:conciliar no crea ni cierra alertas` |
| `EntrenadoresDelEquipoTest.php` | `DA-102: con dos entrenadores en el equipo, basta un no_pasa para activar el riesgo de baja deportiva` (las filas de `entrenador_equipo` se crean directamente en la prueba; DA-114) |
| | `DA-102: con dos entrenadores, uno que pasa y otro sin evaluación, el resultado es pasa` |
| | `DA-107: sin ninguna evaluación guardada el resultado es datos_insuficientes` |
| | `DA-102: la tarjeta que registró solo el segundo entrenador cuenta para la acumulación` |
| | `DA-103: dos entrenadores registran la misma tarjeta en un partido y cuenta una` |
| | `DA-103: si los dos registran cantidades distintas, cuenta la mayor` |
| | `DA-92: de cada entrenador cuenta su última evaluación, no las anteriores` |
| `PuertosDeAlertasTest.php` | Una prueba de contrato por adaptador: `DA-112: TarjetasPorPartido devuelve las filas de todos los entrenadores del equipo`, `DA-112: FaltasDeEntrenamiento solo trae entrenamientos realizados y la ventana pedida`, `DA-112: TemporadaDeFecha devuelve la temporada que contiene la fecha, o nulo`, `DA-112: EvaluacionesVigentesDelEquipo devuelve la última por entrenador`, `P-09: RiesgoAcademicoDelJugador calcula sin leer resumenes_academicos` (se borra el resumen guardado y el nivel sale igual), `P-09: JugadoresDeLaMateria`, `DA-112: MembresiasVigentes excluye a los jugadores en baja` |
| `tests/Unit/Alertas/` | Si se aprueba el cambio al Dominio de §6: una prueba por método nuevo de `AcumulacionDeSanciones`, con la regla de mutación (Infection) al 100 % sobre `Dominio/` |

**Orden de escritura.** Primero `PuertosDeAlertasTest` y las pruebas de Dominio; luego RN-21, RN-09 y RN-10 por comando; después los escuchas, la idempotencia y la programación; al final las de DA-102 y DA-103. La suite de H2 con `alertas:recalcular` se renombra en el mismo commit que el comando (§9).

## 8. Archivos

Rutas bajo `backend/`. Los nombres de clase son una propuesta de este plan; los fija la aprobación.

### 8.1 Código nuevo, dentro de las rutas permitidas de T03

| Ruta | Contenido |
|---|---|
| `src/Alertas/Aplicacion/RecalcularTodasLasAlertas.php` | Lo que ejecuta el comando: lista las membresías y recorre los jugadores |
| `src/Alertas/Aplicacion/RecalcularAlertasDeportivasDeMembresia.php` | RN-09 y después RN-10 de una pareja (jugador, equipo) |
| `src/Alertas/Aplicacion/RecalcularAlertaAcademicaDeJugador.php` | RN-21 de un jugador; lo usan el comando y el escucha |
| `src/Alertas/Aplicacion/RepositorioDeAlertas.php`, `RepositorioDeParametrosDeAlertas.php` | Interfaces: abiertaDe, abrir, cerrar; parámetros de sanciones y nivel académico |
| `src/Alertas/Infraestructura/RepositorioDeAlertasSql.php`, `RepositorioDeParametrosDeAlertasSql.php` | SQL con `DB`; abrir captura la violación 23505 |
| `src/Alertas/Infraestructura/Consola/RecalcularAlertasComando.php` | `alertas:recalcular` (DA-113) |
| `src/Alertas/Infraestructura/Escuchas/RecalcularAlertaAcademica.php` | Escucha de los cuatro eventos (DA-88) |
| `src/Compartido/Aplicacion/TarjetasPorPartido.php`, `FaltasDeEntrenamiento.php`, `TemporadaDeFecha.php`, `EvaluacionesVigentesDelEquipo.php` | Los cuatro puertos de DA-112 |
| `src/Compartido/Aplicacion/TarjetasDeUnRegistro.php`, `FaltaDeEntrenamiento.php`, `EvaluacionVigente.php` | Los DTO de esos puertos |
| `src/Rendimiento/Infraestructura/TarjetasPorPartidoSql.php`, `EvaluacionesVigentesDelEquipoSql.php` | Adaptadores de Rendimiento |
| `src/Competencia/Infraestructura/FaltasDeEntrenamientoSql.php`, `TemporadaDeFechaSql.php` | Adaptadores de Competencia |
| `tests/Feature/Alertas/RecalculoDeAlertaAcademicaTest.php`, `AcumulacionDeSancionesTest.php`, `RiesgoDeBajaDeportivaTest.php`, `RecalculoDiarioTest.php`, `EntrenadoresDelEquipoTest.php`, `PuertosDeAlertasTest.php` | Pruebas de §7 |

### 8.2 Código modificado, dentro de las rutas permitidas

| Ruta | Cambio |
|---|---|
| `src/Alertas/Infraestructura/AlertasServiceProvider.php` | `bind` de los repositorios; `Event::listen` del escucha; `commands([RecalcularAlertasComando::class])` en consola |
| `src/Rendimiento/Infraestructura/RendimientoServiceProvider.php` y `src/Competencia/Infraestructura/CompetenciaServiceProvider.php` | `bind` de los adaptadores de DA-112 (solo el registro, como autoriza la spec) |
| `src/Academico/Infraestructura/Consola/ConciliarResumenesComando.php` | Solo la firma (`resumenes:conciliar`) y el comentario. No hay reconciliación de alertas que quitar (§2, punto 1) |
| `routes/console.php` | Al final, `alertas:recalcular` y `resumenes:conciliar` a las 02:00, ambos en America/Mexico_City. La línea 6 actual se convierte primero en la de `resumenes:conciliar` (renombrado, §9.4) y la de `alertas:recalcular` se agrega con el comando nuevo |

### 8.3 Rutas fuera de las permitidas originales de T03, aprobadas el 16/11/2026

Aprobadas por el responsable el 16/11/2026 (DA-115, DA-117, y `tests/Ayudantes/MundoDeAlertas.php` y `tests/Unit/Alertas/**`). Las razones están en §2 (puntos 3, 4 y 6), §3 y §6.

| Ruta | Para qué | Se aprueba con |
|---|---|---|
| `src/Compartido/Aplicacion/RiesgoAcademicoDelJugador.php`, `RiesgoAcademico.php`, `JugadoresDeLaMateria.php`, `MembresiasVigentes.php`, `MembresiaVigente.php` | El puerto P-09 con su forma, y los dos puertos que el comando y el escucha necesitan (DA-112 solo nombra cuatro) | Preguntas 1 y 2 |
| `src/Academico/Infraestructura/` (adaptadores de P-09 y de `JugadoresDeLaMateria`) y su `bind` en `AcademicoServiceProvider` | Adaptador en el contexto dueño del dato | Pregunta 2 |
| `src/Plantel/Infraestructura/` (adaptador de `MembresiasVigentes`) y su `bind` en `PlantelServiceProvider` | Idem | Pregunta 1 |
| `src/Alertas/Dominio/AcumulacionDeSanciones.php` | Un método que dé los conteos de tarjetas (§6), para la causa de sanciones | Pregunta 3 |
| `tests/Ayudantes/MundoDeAlertas.php` | Agregar los datos de tarjetas, faltas y evaluaciones que usan las pruebas de §7 | Preguntas 1 a 3 |
| `tests/Unit/Alertas/` | Pruebas del método nuevo de Dominio | Pregunta 3 |
| Las de §9 | Renombrado | Con el plan |

### 8.4 Rutas que T03 no toca

`frontend/`; `database/migrations/**` (no hay tablas ni columnas nuevas; la migración de `alertas` es de T01 y no se edita); `docker-compose*`, `Dockerfile` y `.github/**` (solo T00); `routes/api.php` y `app/Http/**` (no hay rutas, T04); `docs/api/openapi.yaml` (sin rutas nuevas); `lang/es/**` (el comando no escribe texto); `src/Consultas/**`; `docs/arquitectura.md` y `docs/requisitos.md`. Si se aprueban los puertos de §8.3, `docs/arquitectura.md` §4.3 los tendría que nombrar: eso es un F1-DOC aparte, con aprobación, no parte de T03.

## 9. Renombrado aprobado (16/11/2026)

**Aprobado por el responsable el 16/11/2026: 23 líneas en 5 archivos de H2 (17 llamadas, 5 títulos y 1 comprobación del horario), 3 líneas de código (firma y comentario de `ConciliarResumenesComando.php`, comentario de `ConciliarResumenes.php`) y la programación en `routes/console.php`, donde la de `resumenes:conciliar` se agrega junto a la de `alertas:recalcular`. Los documentos de §9.2 se revisan con esta aprobación.** Los números de línea se verificaron con `grep -n "alertas:recalcular"` el 16/11/2026, en la rama `hito/f1-h6-especificacion`.

### 9.1 Pruebas de H2 (`backend/tests/Feature/`)

La spec habla de «23 llamadas en 5 archivos». El `grep` da **23 líneas** en 5 archivos, y no todas son llamadas: 17 son `consolaArtisan('alertas:recalcular')`, 5 son títulos de prueba y 1 es la comprobación del horario. Hay que cambiar las 23, pero la cifra de «llamadas» es 17.

| Archivo | Líneas | Qué son |
|---|---|---|
| `Academico/ContratoDeAcademicoTest.php` | 172 | 1 llamada |
| `Academico/CriterioPorMateriaTest.php` | 210, 218, 226 | 3 llamadas |
| `Academico/RecalculoDelResumenTest.php` | 153 (título, fila 16 de §7.10), 157 y 161 (llamadas), 167 (título, fila 17), 174 y 176 (llamadas), 188 y 193 (llamadas), 223 (título, fila 24: horario), 227 (el `str_contains` del horario), 235 (título, fila 8 de §7.12), 239 (llamada) | 12 líneas: 7 llamadas, 4 títulos y 1 comprobación del horario |
| `Academico/SituacionAcademicaTest.php` | 49, 186, 257, 292, 304 (llamadas), 283 (título, fila 16) | 6 líneas: 5 llamadas y 1 título |
| `Seguridad/AcademicoSeguridadTest.php` | 226 | 1 llamada |

Todas pasan a `resumenes:conciliar`. La prueba del horario (línea 223 a 233) sigue comprobando `0 2 * * *` en America/Mexico_City, ahora con `resumenes:conciliar`. La prueba nueva del horario de `alertas:recalcular` va en `tests/Feature/Alertas/` (§7.2).

### 9.2 Documentos

| Documento | Línea | Qué dice hoy | Propuesta |
|---|---|---|---|
| `docs/estrategia-pruebas.md` | 68 | La fila del comando `alertas:recalcular` dice «Concilia resúmenes y alertas abiertas…» | Dejar la fila para `alertas:recalcular`, quitar «Concilia resúmenes y», y agregar una fila `resumenes:conciliar` (H2): concilia resúmenes; idempotente; programado a las 02:00. **Se cambia** |
| `docs/criterios-aceptacion-fase-1.md` | 33 | Criterio de H6: «`alertas:recalcular` es idempotente, está programado a diario y cierra las alertas que vencen con el tiempo» | Ya describe el comando de Alertas, que es el nuevo. **Sin cambio** |
| `docs/CAMBIOS-v1.2.md` | 13 y 34 | Historia de la versión 1.2: «`alertas:recalcular` programado» | Documento histórico de lo que se cambió en v1.2. **Sin cambio** (recomendado) |
| `docs/decisiones/registro-decisiones.md` | 32 (DD-21) | «`alertas:recalcular` programado a diario a las 02:00» | Sigue siendo cierto con el comando nuevo. **Sin cambio**; DA-113 ya registra el movimiento |
| `docs/decisiones/registro-decisiones.md` | 57 (DD-46) y 43 (DD-32) | DD-46: el resumen se recalcula con los cuatro eventos «y con `alertas:recalcular`»; DD-32: `alertas:recalcular` (H6) concilia lo que ocurre sin evento | **No estaban en la lista de la spec.** Hoy nombran al comando que pasa a ser `resumenes:conciliar`. Propuesta: anotar «hoy `resumenes:conciliar` (DA-113)» en las dos filas, sin reescribir su texto |
| `docs/hitos/f1-h1.md` (72), `f1-h2.md` (129, 589, 590, 591, 597, 634, 711, 793, 803), `f1-h5.md` (110) | | Especificaciones de hitos cerrados que nombran el comando | Históricos. **Sin cambio** (recomendado); la nota de DA-113 en `f1-h6.md` basta |
| `docs/arquitectura.md` | 85, 399, 401, 412 | Ya marca el cambio como estado futuro, T03 (DA-113) | Al cerrar T03, un F1-DOC quita «estado futuro» |

### 9.3 Dos comentarios de código

| Archivo | Línea | Cambio | ¿Está en las rutas de T03? |
|---|---|---|---|
| `src/Academico/Infraestructura/Consola/ConciliarResumenesComando.php` | 9 | El comentario nombra `alertas:recalcular` | Sí, es el archivo de la firma |
| `src/Academico/Aplicacion/ConciliarResumenes.php` | 6 | El comentario nombra `alertas:recalcular` | **No** |

### 9.4 Orden propuesto

1. **Un solo commit del código del renombrado:** firma y comentario de `ConciliarResumenesComando`, comentario de `ConciliarResumenes`, la línea 6 de `routes/console.php` (que pasa a programar `resumenes:conciliar` a las 02:00) y las 23 líneas de las pruebas de H2. Sin ese cambio de pruebas, `make verificar` falla; con él, la suite de H2 queda verde sin que el comando nuevo exista todavía.
2. Después, el código de T03 (Alertas) en commits propios: puertos y adaptadores, repositorio y reglas de Aplicación, comando (con su línea de `alertas:recalcular` a las 02:00 en `routes/console.php`), escucha.
3. Los documentos de §9.2 en un F1-DOC aparte, con la aprobación de este plan.

Orden de 1 y 2: el renombrado va primero porque libera el nombre `alertas:recalcular`; mientras Académico lo use, el comando de Alertas no se puede registrar sin chocar.

## 10. Precisiones resueltas (16/11/2026)

### 10.1 Los dos comandos a las 02:00 son independientes

`alertas:recalcular` y `resumenes:conciliar` se programan los dos a las 02:00 (America/Mexico_City) y ninguno llama al otro. `alertas:recalcular` no llama a `ConciliarResumenes` de Académico, y la alerta académica calcula el nivel por el puerto de P-09, sin leer el resumen guardado. Confirmado el 16/11/2026 (DA-113).

### 10.2 La especificación de T03 cita §7.1, §7.3 y §7.4

Las pruebas de T03 cubren las filas de §7.1 (RN-21), §7.3 (RN-09) y §7.4 (RN-10); la especificación lo dice así desde la v1.13. Confirmado el 16/11/2026.

### 10.3 Respuestas a las tres preguntas de la revisión de plan

Las tres se resolvieron con la opción recomendada, el 16/11/2026 (`docs/decisiones/registro-decisiones.md` §10):

| Pregunta | Decisión | Registro |
|---|---|---|
| 1. Parejas (jugador, equipo) sin usuario de sesión | Puerto `MembresiasVigentes`, adaptador en Plantel | DA-115 |
| 2. Forma del puerto de P-09 y jugadores de una materia | `RiesgoAcademicoDelJugador` con nivel, promedio y materias reprobadas; `JugadoresDeLaMateria`; ambos con adaptador en Académico | DA-115 |
| 3a. Temporada de cada partido | `InicioDePartidos` más `TemporadaDeFecha`, sin puerto nuevo | DA-117 |
| 3b. Conteos de sanciones | Método nuevo en `AcumulacionDeSanciones` (Dominio de Alertas) | DA-117 |
| 3c. `promedio` de la causa deportiva | El de la evaluación que decide; decisión por defecto, privacidad Sí | DA-116 |
