# F1-H6-T03: plan del recálculo de alertas

**Fecha:** 15 de noviembre de 2026; actualizado el 16 de noviembre con las decisiones del responsable · **Especificación:** `docs/hitos/f1-h6.md` v1.10 · **Estado:** plan para aprobación. No se escribió código de T03.
**Base:** DA-88, aprobada el 15/11/2026 (recálculo mixto, con el apartamiento de ADR-07 por escrito). T02 ya dejó las reglas en `src/Alertas/Dominio/`.

## 1. Qué construye

1. **Escucha de la alerta académica.** `Src\Alertas\Infraestructura\RecalcularAlertaAcademica`, registrado en `AlertasServiceProvider` para `CalificacionRegistrada`, `AsistenciaDeClaseRegistrada`, `CriterioDeMateriaModificado` y `JugadorInscrito`. En cola (`ShouldQueue`), `afterCommit`, idempotente.
2. **Caso de uso `RecalcularAlertasDeJugador`** (`src/Alertas/Aplicacion/`): para un jugador, decide las tres alertas con las reglas de T02 y aplica la acción (`Abrir`, `Cerrar`, `Ninguna`).
3. **Caso de uso `RecalcularTodasLasAlertas`**: recorre los jugadores sin baja y llama al anterior. Es lo que ejecuta `alertas:recalcular`.
4. **Repositorio `RepositorioDeAlertas`** (interfaz en `Aplicacion/`, SQL en `Infraestructura/`): alerta abierta por tipo, jugador y equipo; abrir; cerrar.

## 2. Fecha de cálculo

- La da el reloj inyectable de Compartido (`HoyDeNegocioSegunReloj`), en la zona de negocio. Las pruebas usan `RelojFijo`.
- Se toma una sola vez al empezar el comando y se pasa a todas las reglas, para que una corrida que cruza la medianoche no mezcle dos días.
- `activada_en` y `cerrada_en` llevan el instante del reloj, no `now()` de la base.

## 3. Orden de las tres reglas

Por jugador y, en las deportivas, por cada equipo con membresía vigente:

1. **Acumulación de sanciones** (RN-09), porque RN-10 la necesita.
2. **Riesgo de baja deportiva** (RN-10), con el resultado de la evaluación y el de (1).
3. **Alerta académica** (RN-21), una por jugador, sin equipo.

Cada alerta se decide con `DecisionDeAlerta::decidir(seCumple, hayAbierta)`.

## 4. Idempotencia y concurrencia

- Abrir es un `INSERT` protegido por la unicidad parcial de T01. Si dos procesos abren a la vez (el escucha y el comando), el segundo recibe 23505 y lo trata como «ya está abierta»: no falla ni reintenta.
- Cerrar es `UPDATE … SET cerrada_en = :ahora WHERE id = :id AND cerrada_en IS NULL`. Si no afecta filas, otro proceso ya la cerró.
- El comando nunca reescribe `activada_en` ni `cerrada_en` de una alerta cerrada (lo impide además el disparador).
- **Pruebas:** ejecutar `alertas:recalcular` dos veces seguidas y comparar la tabla completa (mismas filas, mismos `id`, mismas fechas); ejecutar el escucha dos veces con el mismo evento.

## 5. Pruebas previstas (`tests/Feature/Alertas/`)

- §7.1, filas de activación y de cierre, por evento (driver `sync`) y por comando.
- §7.3, filas de activación y de cierre: las faltas que salen de la ventana y la tarjeta roja tras el fin de la temporada, moviendo `RelojFijo`.
- §7.4, las cinco filas, con datos reales en `evaluaciones_rendimiento`.
- `alertas:recalcular` dos veces: mismo resultado, sin filas duplicadas.
- Un jugador en baja no recibe alertas nuevas.
- DA-102: con dos entrenadores en un equipo, basta un `no_pasa`. DA-103: la misma tarjeta registrada por dos entrenadores cuenta una vez.
- El comando sale con código 0 y no escribe texto.

## 6. Lo que este plan no puede resolver solo

**Actualización del 16/11/2026.** El responsable decidió los puntos 3 a 7; quedan abiertos el 1 y el 2.

| # | Decisión |
|---|---|
| 3 | P-09, opción A: el escucha de Alertas calcula el nivel de riesgo por un puerto, sin leer el resumen guardado |
| 4 | P-10, opción A: ya está en el Dominio (`ResultadoDeEvaluacion::delEquipo()` y `TarjetasDePartido::elMayorPorPartido()`), con T02 |
| 5 | P-08b, opción A: sin temporada no se cuentan tarjetas; las faltas sí. `AcumulacionDeSanciones` acepta una temporada nula |
| 6 | DA-107, decisión por defecto: sin evaluación, `datos_insuficientes` (`delEquipo([])`) |
| 7 | P-08c, opción A: las alertas abiertas de un jugador en baja se conservan y no se muestran |

El punto 3 necesita un puerto nuevo, «nivel de riesgo académico de un jugador», con su adaptador en Académico: es el mismo patrón que DA-109 aprobó para T07 y cae dentro del punto 1.

Tabla original, con los puntos 1 y 2 todavía por decidir:

| # | Asunto | Opciones | Recomendación |
|---|---|---|---|
| 1 | **Cómo lee Alertas los datos de otros contextos.** Necesita el nivel de riesgo (Académico), la última evaluación por entrenador (Rendimiento), las tarjetas por partido y entrenador (Rendimiento), las faltas a entrenamientos y la temporada vigente (Competencia), y las membresías y bajas (Plantel). `docs/arquitectura.md` §4.3 solo autoriza a Consultas a leer tablas ajenas con SQL, y las rutas de T03 no incluyen `src/Compartido/Aplicacion/**` ni los adaptadores de esos contextos | (a) Puertos en `src/Compartido/Aplicacion/` con su adaptador en cada contexto: hay que ampliar las rutas de T03. (b) Autorizar por escrito que `src/Alertas/Infraestructura/` lea esas tablas con SQL de solo lectura, como Consultas. (c) Que Consultas exponga las lecturas y Alertas dependa de Consultas | (a), que es la regla vigente; (b) es más barata y necesita una enmienda a §4.3 |
| 2 | **Dueño del comando.** `alertas:recalcular` vive hoy en Académico (`ConciliarResumenesComando`) y solo concilia resúmenes. T03 permite tocar ese archivo, lo que metería una llamada a Alertas dentro de Académico | (a) El comando pasa a `src/Alertas/Infraestructura/Consola/` y llama primero a `ConciliarResumenes` de Académico y después a las alertas. (b) Se queda en Académico y llama a un caso de uso de Alertas | (a): el nombre del comando es de Alertas. Exige borrar un archivo de Académico y mover su prueba |
| 3 | **Orden de los dos escuchas (P-09).** El resumen académico y la alerta se recalculan con dos trabajos en cola del mismo evento; la alerta puede leer el resumen anterior | A, B o C del expediente | A: el escucha de Alertas calcula el nivel él mismo a través de un puerto |
| 4 | **Dónde viven «basta un `no_pasa`» y «el mayor por partido» (P-10)** | A, B o C del expediente | A: en el Dominio; T02 dejó las reglas con un solo resultado y un conteo por partido, listas para recibir esa reducción |
| 5 | **Sin temporada que contenga la fecha de cálculo (P-08b)** | A, B o C del expediente | A: no se cuentan tarjetas; las faltas sí |
| 6 | **Sin evaluación guardada o sin umbral aplicable (P-08a)** | Responden RN-08 y RN-10 | Se trata como `datos_insuficientes` |
| 7 | **Alertas abiertas de un jugador en baja (P-08c)** | A, B o C del expediente | A: se conservan abiertas y no se muestran (DA-99) |
| 8 | **Contenido de `causa`** | Lo define DA-105 (§6.2) | Sin cambio |

## 7. Archivos previstos

Dentro de las rutas de T03: `src/Alertas/Aplicacion/**`, `src/Alertas/Infraestructura/**`, `routes/console.php` (sin cambiar la hora) y `tests/Feature/Alertas/**`.

Fuera de las rutas de T03, según lo que decidas en §6: `src/Compartido/Aplicacion/**` y un adaptador en la Infraestructura de Académico, Competencia, Rendimiento y Plantel (punto 1, opción a); `tests/Feature/Academico/**` o donde esté la prueba actual del comando (punto 2, opción a).

## 8. Cuándo me detengo

- Una fila de §7.1, §7.3 o §7.4 no se puede cumplir con los datos que H3 a H5 guardan.
- Hace falta una tabla, un evento o una dependencia que no esté en este plan.
