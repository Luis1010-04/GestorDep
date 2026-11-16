# Pendientes de decisión de H6 (modo lote)

**Fecha:** 15 de noviembre de 2026
**Origen:** lote ordenado por el responsable sobre `docs/hitos/f1-h6.md` v1.8, y la auditoría `docs/reportes/auditorias/auditoria-especificacion-h6-v1.8-20261115.md`.
**Estado del lote:** se detuvo antes de T01 por los bloqueantes B1 a B5 de esa auditoría. El responsable los resolvió el 15/11/2026 (ver «Actualización» al final) y el lote siguió con T01.

**Sobre el PDF del cliente.** El procedimiento pide citar la sección del PDF. El PDF no está en el repositorio (ver P-11). Donde dice «PDF», la cita es del mapa de `docs/requisitos.md` §11 y de `docs/alcance-fases.md` §3, que resumen sus puntos; no del texto original.

**Ninguna decisión por defecto se aplicó por cuenta del agente.** La única decisión nueva es DA-104 (P-13), que aprobó el responsable. El siguiente DA libre es DA-105. Donde el expediente dice «responden los requisitos», la opción recomendada se aplicaría al ejecutar la tarea.

| Id | Pregunta | Tarea | Estado |
|---|---|---|---|
| P-01 | Seeder de parámetros frente a tres pruebas de Académico | T01 | Cerrado: opción A aprobada |
| P-02 | `routes/api.php` fuera de las rutas permitidas | T04 a T07 | Cerrado: opción A aprobada |
| P-03 | DA-88 corregida sin aprobación explícita | T03, T05 | Cerrado: DA-88 aprobada; T03 y T05 se detienen en su plan |
| P-04 | Indicador de aviso pendiente para el docente | T04 | Responden los requisitos; sin aplicar |
| P-05 | `filtro_invalido` o `validacion_fallida` | T04 | Sin respuesta |
| P-06 | Formas de respuesta sin definir | T04, T05, T07 | Cerrado: DA-105, [Decisión por defecto] |
| P-07 | Guion de humo que no se puede ejecutar como está | T09 | Sin respuesta |
| P-08 | Casos sin regla de DA-91, DA-92 y DA-99 | T02, T03 | Parcial: uno responde, dos sin respuesta |
| P-09 | Orden de los dos escuchas del mismo evento | T03 | Sin respuesta |
| P-10 | Qué recibe el Dominio con DA-102 y DA-103 | T02, T03 | Sin respuesta |
| P-11 | El PDF del cliente no está en el repositorio | Todo el lote | Cerrado: transcripción versionada |
| P-12 | Valores que dependen de la institución | T01, T03 | Pendiente de institución |
| P-13 | T00 tocó cuatro archivos de pruebas fuera de §7 de su plan | T00 | Cerrado: aprobado por el responsable (DA-104) |
| P-14 | Los porcentajes de asistencia de los gráficos | T07 | Bloqueo: reglas de otros contextos o rutas no permitidas |

---

## P-01. Seeder de parámetros frente a tres pruebas de Académico

- **Pregunta:** ¿cómo siembra T01 los tres parámetros de DA-100 si tres pruebas existentes fijan que `parametros_institucionales` tiene una sola fila?
- **Tarea afectada:** T01 (solo la parte de parámetros; la tabla `alertas` no depende de esto).
- **Evidencia:** `backend/tests/Integration/Academico/MigracionesDeAcademicoTest.php`, líneas 520, 538 y 549. El archivo no está en las rutas de T01.

| Opción | Qué hace | A favor | En contra |
|---|---|---|---|
| A | Autorizar a T01 a actualizar esas tres pruebas: esperan las cuatro claves y siguen comprobando el valor de `riesgo_academico`, la idempotencia y que no se pisa un valor ajustado | Es la consecuencia directa de la autorización M3; las pruebas no se debilitan, cambian de 1 a 4 filas (RNF-09) | Toca pruebas de otro contexto; necesita tu aprobación |
| B | Sembrar los parámetros de H6 en un seeder nuevo y dejar intacto `ParametrosInstitucionalesSeeder` | Dos de las tres pruebas quedan igual | La tercera (`DatabaseSeeder`) falla de todos modos; contradice M3 y agrega un archivo no previsto |
| C | Sembrar los parámetros en una migración de datos | No toca seeders ni sus pruebas | `docs/arquitectura.md` §8.4 pone los datos de fábrica en seeders, y las pruebas dan por hecho que las migraciones no siembran nada |

- **PDF y requisitos:** no aplican; es una cuestión de rutas permitidas. RNF-09 exige que los parámetros estén en tabla.
- **Recomendación:** A.
- **Estado:** bloqueo. No se aplicó: cambia una ruta no permitida.

## P-02. `routes/api.php` fuera de las rutas permitidas

- **Pregunta:** ¿dónde se registran las siete rutas de §6.1?
- **Tareas afectadas:** T04, T05, T06 y T07; por dependencia, T08 y T09.

| Opción | Qué hace | A favor | En contra |
|---|---|---|---|
| A | Agregar `routes/api.php` (solo las rutas de §6.1 de cada tarea) a las rutas permitidas de T04 a T07 | Es donde viven todas las rutas; H5 lo permitía | Necesita tu aprobación |
| B | Registrar las rutas desde un proveedor de `src/Consultas/Infraestructura/` | No toca `routes/api.php` | Rompe la convención del proyecto y esquiva la regla de rutas permitidas |
| C | No construir las rutas en H6 | Ninguno | Deja sin entregar HU-22 a HU-25 (PDF, puntos 7 y 8; `docs/alcance-fases.md` §3) |

- **PDF y requisitos:** HU-22 a HU-25 exigen las consultas; no dicen dónde se registran.
- **Recomendación:** A.
- **Estado:** bloqueo. No se aplicó.

## P-03. DA-88 corregida sin aprobación explícita

- **Pregunta:** ¿se aprueba DA-88 (recálculo mixto: académica por evento; deportivas y de sanciones por el comando diario), que se aparta de ADR-07?
- **Tareas afectadas:** T03 y T05. Dependen de ellas T06 (DA-96 usa la vista de T05), T08 (recorre las rutas) y T09 (pasos 4, 7 y 12 del humo).

| Opción | Qué hace | A favor | En contra |
|---|---|---|---|
| A | Aprobar DA-88 como está en la v1.8 | Usa los eventos de Académico que ya existen (DD-46); no crea eventos sin consumidor | Las alertas deportivas pueden tardar hasta un día en aparecer (HU-22 c2) |
| B | Las tres por evento, como dice ADR-07 | Sin retraso | H3 a H5 no publican eventos para esos datos; habría que agregarlos en contextos que H6 no puede tocar |
| C | Las tres solo por el comando diario | Una sola vía | La académica también se retrasa, contra HU-22 c1 y `docs/arquitectura.md` §11 |

- **PDF y requisitos:** PDF punto 7 y HU-22 piden que la alerta aparezca; no fijan en cuánto tiempo.
- **Recomendación:** A.
- **Estado:** bloqueo: el encargo exige tu aprobación explícita. **Los planes de T03 y T05 no se prepararon**, porque el lote se detuvo en la auditoría previa a T01.

## P-04. Indicador de aviso pendiente para el docente

- **Pregunta:** ¿el docente ve el indicador de aviso pendiente de cualquier aviso del jugador o solo de los que él creó?
- **Tarea afectada:** T04 (listado de estatus, HU-23 c4).

| Opción | Qué hace | A favor | En contra |
|---|---|---|---|
| A | Para el docente, el indicador solo cuenta los avisos que él creó | Es lo que dice `docs/requisitos.md` §7 («Aviso de lesión pendiente: docente, solo el que creó») y RN-16 | Un docente puede no ver un aviso que sí existe |
| B | El docente ve el indicador de cualquier aviso, como dice §5.3 de la especificación | El listado es igual para todos | Contradice requisitos §7, de mayor precedencia; revela avisos ajenos |
| C | El docente no ve el indicador | Lo más restrictivo | Contradice HU-23 c4 y requisitos §7 |

- **PDF y requisitos:** las lesiones no están en el PDF (`docs/alcance-fases.md` §3, fila «Lesiones»). Responde `docs/requisitos.md` §7 y RN-16.
- **Recomendación:** A.
- **Estado:** responden los requisitos; reversible. **Sin aplicar**, porque T04 no se ejecutó. Al ejecutarla tomaría el siguiente DA libre, como [Decisión por defecto] pendiente de validación.

## P-05. `filtro_invalido` o `validacion_fallida`

- **Pregunta:** ante un filtro inválido, ¿el `codigo` de la respuesta 422 es `filtro_invalido` (§6.3) o `validacion_fallida`, como en los filtros existentes? ¿Y qué responde un UUID válido que no es de ningún equipo o materia del usuario?
- **Tarea afectada:** T04.

| Opción | Qué hace | A favor | En contra |
|---|---|---|---|
| A | 422 `validacion_fallida` arriba y `filtro_invalido` en `errores[].codigo`; un id válido pero ajeno devuelve lista vacía | Igual que los filtros de H1 a H3 (`docs/convenciones-api.md` §2; DA-26 de H3) | Cambia la lectura literal de §6.3 |
| B | 422 con `codigo` `filtro_invalido` arriba | Literal con §6.3, que tiene más precedencia que las convenciones | Dos formas de error de filtro en la misma API |
| C | Un filtro inválido se ignora y devuelve la lista sin filtrar | Sin error | Oculta el fallo al cliente; contradice §6.3 |

- **PDF y requisitos:** no responden; es una convención de la API.
- **Recomendación:** A.
- **Estado:** sin respuesta. No se decidió.

## P-06. Formas de respuesta sin definir

- **Pregunta:** ¿qué forma tienen `estado_oficial`, `situacion_academica`, el detalle de la materia del docente en el listado (HU-23 c3), las fechas de actualización por dimensión y las respuestas de las dos rutas de gráficos (HU-25 c1 y c2)?
- **Tareas afectadas:** T04, T05 y T07.

| Opción | Qué hace | A favor | En contra |
|---|---|---|---|
| A | Reutilizar los recursos que ya existen (estado oficial de H5, resumen simplificado de H2) y definir en la especificación solo lo nuevo, antes de construir | Un solo contrato por dato; lo nuevo queda aprobado por escrito | Requiere un F1-DOC previo |
| B | Que el agente proponga el contrato completo en el plan de cada tarea y se detenga hasta aprobación | Avanza sin esperar un F1-DOC | T04 y T07 no son tareas con plan; cambia el modo de trabajo |
| C | Que el agente defina el contrato al construir | Lo más rápido | Es inventar el contrato; el encargo lo prohíbe |

- **PDF y requisitos:** PDF punto 8 y HU-25 dicen qué datos se grafican; HU-23 y HU-24, qué se muestra. Ninguno fija nombres de campo.
- **Recomendación:** A.
- **Estado:** sin respuesta. No se decidió.

## P-07. Guion de humo que no se puede ejecutar como está

- **Pregunta:** ¿cómo queda el guion de §9? El paso 3 «registra una evaluación» no es un paso: no hay ruta para eso y hacen falta 5 partidos jugados con su rendimiento. El paso 6 espera un aviso que nadie crea. Faltan los filtros y la alerta académica activa y cerrada (`docs/estrategia-pruebas.md` §5.2).
- **Tarea afectada:** T09.

| Opción | Qué hace | A favor | En contra |
|---|---|---|---|
| A | Reescribir §9: cinco partidos con rendimiento antes del paso 4, un aviso antes del paso 6, un paso con filtro y el caso académico (activar y cerrar) | Cumple `docs/estrategia-pruebas.md` §5.2 | El guion crece; necesita un F1-DOC |
| B | Dejar §9 y bajar la ventana del umbral a 1 partido en el guion | Menos pasos | Usa una ruta de H4 para cambiar el umbral; sigue sin aviso ni filtros |
| C | Sembrar los datos del humo con un seeder | Guion corto | Los guiones crean sus datos por la API; rompe esa regla |

- **PDF y requisitos:** no responden.
- **Recomendación:** A.
- **Estado:** sin respuesta. No se decidió.

## P-08. Casos sin regla de DA-91, DA-92 y DA-99

Son tres preguntas.

**a) Sin evaluación guardada o sin umbral aplicable (DA-92).**

| Opción | Qué hace | A favor | En contra |
|---|---|---|---|
| A | Se trata como `datos_insuficientes`: no activa la alerta, salvo que haya sanciones | RN-08 («si hay menos de N, datos insuficientes y no activa alerta») y RN-10 | Ninguno |
| B | Se trata como `no_pasa` | Ninguno | Contradice RN-08 |
| C | No se calcula la alerta para ese jugador y equipo | Simple | Una acumulación de sanciones dejaría de activar el riesgo, contra RN-10 |

- **Recomendación:** A. **Estado:** responden los requisitos; sin aplicar (T02 no se ejecutó). Tomaría el siguiente DA libre.

**b) Ninguna temporada contiene la fecha de cálculo (DA-91).**

| Opción | Qué hace | A favor | En contra |
|---|---|---|---|
| A | No se cuentan tarjetas; las faltas de 30 días sí | La tarjeta roja ya vence al terminar la temporada (RN-09) | Entre temporadas no hay alerta por tarjetas |
| B | Se usa la temporada más reciente | Siempre hay conteo | RN-09 dice que la roja deja de contar al terminar la temporada |
| C | El comando falla y avisa | Hace visible el hueco | Detiene el recálculo de todo |

- **Recomendación:** A. **Estado:** sin respuesta explícita; RN-09 orienta pero no lo dice. No se decidió.

**c) Alertas abiertas de un jugador en baja (DA-99).**

| Opción | Qué hace | A favor | En contra |
|---|---|---|---|
| A | Se conservan abiertas y no se muestran, como dice DA-99 | No cambia una decisión de la especificación; RN-23 y RN-17 | `cerrada_en` queda nulo para siempre |
| B | El comando las cierra al detectar la baja | La tabla refleja la realidad | `docs/arquitectura.md` §4.2 lo preveía por evento; cambia DA-99 |
| C | Se cierran al registrar la baja, en Plantel | Inmediato | Toca un contexto que H6 no puede tocar |

- **Recomendación:** A. **Estado:** sin respuesta; RN-23 dice que deja de aparecer, no si se cierra. No se decidió.

## P-09. Orden de los dos escuchas del mismo evento

- **Pregunta:** la alerta académica depende del resumen académico ya recalculado, y los dos se recalculan con escuchas en cola del mismo evento. ¿Cómo se garantiza el orden?
- **Tarea afectada:** T03.

| Opción | Qué hace | A favor | En contra |
|---|---|---|---|
| A | El escucha de Alertas calcula el nivel de riesgo él mismo, con el servicio de Académico a través de un puerto, sin leer el resumen guardado | No depende del orden; no toca Académico | Calcula el riesgo dos veces |
| B | Académico publica un evento nuevo al terminar el resumen y Alertas escucha ese | Orden garantizado | Toca rutas de Académico que T03 no tiene; evento nuevo no previsto |
| C | Se acepta el desfase y lo corrige el comando de las 02:00 | Sin cambios | La alerta puede estar mal hasta un día (HU-22 c1) |

- **PDF y requisitos:** no responden.
- **Recomendación:** A.
- **Estado:** sin respuesta. Va en el plan de T03.

## P-10. Qué recibe el Dominio con DA-102 y DA-103

- **Pregunta:** ¿las funciones puras de T02 reciben una evaluación por entrenador y los registros de tarjetas por entrenador y partido (y aplican «basta un `no_pasa`» y «el mayor por partido»), o reciben ya el resultado reducido que calcula T03 con SQL? ¿Y en qué tabla de casos van las filas nuevas?
- **Tareas afectadas:** T02, T03 y T04.

| Opción | Qué hace | A favor | En contra |
|---|---|---|---|
| A | El Dominio recibe los datos por entrenador y aplica las dos reglas; §7.3 y §7.4 ganan una fila cada una | Las reglas de negocio quedan en el Dominio y bajo prueba de mutación | Las tablas de §7 se copian de `docs/estrategia-pruebas.md`, que también habría que actualizar |
| B | La Infraestructura reduce con SQL y el Dominio recibe un solo resultado y un solo conteo | Dominio más simple | Dos reglas de negocio quedan en SQL, fuera del Dominio |
| C | El Dominio aplica «basta un `no_pasa`» y la Infraestructura el máximo por partido | Reparto intermedio | La regla de DA-103 queda fuera del Dominio |

- **PDF y requisitos:** RN-22 (v1.2.3) fija la regla, no dónde vive. Las reglas del proyecto ponen la lógica de negocio en `Dominio/`.
- **Recomendación:** A.
- **Estado:** sin respuesta. No se decidió: agrega filas a tablas de casos aprobadas.

## P-11. El PDF del cliente no está en el repositorio

- **Pregunta:** ¿dónde está el PDF «Proceso general del sistema web», que el procedimiento manda citar?
- **Afecta:** todo expediente que deba decidirse «según el PDF».

| Opción | Qué hace | A favor | En contra |
|---|---|---|---|
| A | Versionar el PDF en `docs/` | El agente puede citarlo | Hay que comprobar que no trae datos personales reales |
| B | Seguir con los mapas de `docs/requisitos.md` §11 y `docs/alcance-fases.md` §3 | Sin cambios | No son el texto original; la sección 7 que cita DA-102 no se puede comprobar |
| C | Que el responsable resuelva a mano cada pregunta que dependa del PDF | Sin riesgo de mala cita | El lote se detiene en cada una |

- **Recomendación:** A.
- **Estado:** bloqueo del procedimiento. Mientras tanto se usó B.

## P-12. Valores que dependen de la institución

No son preguntas para el agente. Se dejan como están, sin fijar:

| Id | Valor | Dónde se usa |
|---|---|---|
| AB-13 | Ventana de 30 días y umbrales de RN-09 (3 amarillas, 1 roja, 3 faltas) | `sanciones_deportivas` (DA-100) |
| AB-13 | 30 días para marcar un dato como desactualizado | `desactualizacion` (DA-100) |
| AB-01 deportivo | Umbral 6.0 y ventana de 5 | `plantillas_evaluacion` (H4) |
| AB-28 | Rangos de mediciones corporales | H5 |
| OM-05 a OM-08 | Sin definición en `docs/` | Ninguna tarea de H6 depende de ellos |

- **Estado:** pendiente de institución. Se sembrarían como [Propuesta].

## P-13. T00 tocó cuatro archivos de pruebas fuera de §7 de su plan

- **Pregunta:** al correr Pest en paralelo fallaron 27 pruebas unitarias de Académico con «Call to undefined function parametrosDePrueba()». Dos funciones de ayuda estaban definidas dentro de un archivo de prueba y otros archivos las usaban: `parametrosDePrueba()` (en `ParametrosDeRiesgoTest.php`, usada por `RiesgoAcademicoTest.php` y `ResumenAcademicoTest.php`) y `decimal()` (en `NumeroDecimalTest.php`, usada por `AsistenciaDeEntrenamientoTest.php`). En serie funcionan porque Pest carga todos los archivos en un proceso; en paralelo cada proceso carga solo los suyos. ¿Cómo se corrige?
- **Tarea afectada:** T00 (DA-101).
- **Tensión dentro del plan aprobado:** su §6 dice que una prueba que falla solo en paralelo «se corrige de raíz»; su §7 no lista esos archivos y su §9 manda detenerse si hace falta tocar un archivo fuera de §7.

| Opción | Qué hace | A favor | En contra |
|---|---|---|---|
| A | Mover las dos funciones a `tests/Ayudantes/DominioDePrueba.php` y cargarlo desde `tests/Pest.php`; se quitan de los dos archivos de prueba | Corrige la causa; ninguna aserción cambia; las mismas 1985 pruebas pasan en serie y en paralelo | Toca cuatro archivos fuera de §7 del plan |
| B | Marcar esos archivos para que corran en serie o en el mismo proceso | No mueve código | El plan prohíbe marcar pruebas como en serie sin avisar; no corrige la causa |
| C | Detener T00 hasta tu respuesta | Sin desviación | T00 no se entrega y `make verificar` sigue en 14 minutos |

- **PDF y requisitos:** no aplican.
- **Recomendación:** A.
- **Estado:** cerrado. Aplicada como desviación declarada. Archivos fuera de §7: `backend/tests/Ayudantes/DominioDePrueba.php` (nuevo), `backend/tests/Pest.php` (una línea), `backend/tests/Unit/Academico/ParametrosDeRiesgoTest.php` y `backend/tests/Unit/Compartido/NumeroDecimalTest.php` (solo pierden la función). Es reversible con el `git revert` del commit de T00. **Aprobada por el responsable el 15/11/2026 y registrada como DA-104**, con la condición de que el cambio sea solo el traslado de las dos funciones, sin cambiar su lógica. Los cuatro archivos quedaron en §7 del plan.

---

## Actualización del 15 de noviembre de 2026: decisiones del responsable

**Fuente.** La transcripción del PDF del cliente está en [`docs/fuentes/proceso-general-sistema-web.md`](../fuentes/proceso-general-sistema-web.md). Desde aquí, «PDF §N» cita esa transcripción. Las citas de los expedientes de arriba a `docs/requisitos.md` §11 y `docs/alcance-fases.md` §3 se escribieron antes de tenerla.

| Id | Decisión | Dónde quedó |
|---|---|---|
| P-01 | Opción A: T01 actualiza las tres pruebas del seeder en `MigracionesDeAcademicoTest.php`, solo para comprobar los valores nuevos de DA-100; no se borran ni se debilitan | `docs/hitos/f1-h6.md` v1.9, T01 |
| P-02 | Opción A: `routes/api.php` entra en las rutas permitidas de T04 a T07 | `docs/hitos/f1-h6.md` v1.9, T04 a T07 |
| P-03 | DA-88 aprobada, con el apartamiento de ADR-07 por escrito. T03 y T05 se detienen en su plan | Registro, sección 10; especificación §3.1 |
| P-06 | Formas de respuesta de T04, T05 y T07, aplicadas por defecto como DA-105 | Especificación §6.2; registro, sección 10 |
| P-11 | La transcripción se versionó | `docs/fuentes/` |

### Expedientes que siguen abiertos: recomendación en una línea

El responsable pidió no decidirlos. PDF §7 dice que el sistema genera alertas automáticas y que las consultan docentes y entrenadores; PDF §8, qué análisis hay. Ninguna de las dos secciones responde estas preguntas, que son de diseño.

| Id | Recomendación | Opción |
|---|---|---|
| P-05 | 422 `validacion_fallida` arriba y `filtro_invalido` en `errores[].codigo`; un id válido pero ajeno devuelve lista vacía, como los filtros de H1 a H3 | A |
| P-07 | Reescribir el guion de §9: cinco partidos con rendimiento antes de consultar la alerta, un aviso antes del listado, un paso con filtro y el caso académico activo y cerrado | A |
| P-08b | Si ninguna temporada contiene la fecha de cálculo, no se cuentan tarjetas; las faltas de 30 días sí | A |
| P-08c | Las alertas abiertas de un jugador en baja se conservan y no se muestran, como dice DA-99 | A |
| P-09 | El escucha de Alertas calcula el nivel de riesgo él mismo a través de un puerto, sin leer el resumen guardado, para no depender del orden de los escuchas | A |
| P-10 | El Dominio recibe los datos por entrenador y aplica «basta un `no_pasa`» y «el mayor por partido»; §7.3 y §7.4 ganan una fila cada una | A |

P-04 (aviso pendiente del docente) y P-08a (sin evaluación) los responden `docs/requisitos.md` §7, RN-16, RN-08 y RN-10; se aplican como decisión por defecto al ejecutar T04 y T02. Las lesiones no están en el PDF.

---

## P-14. Los porcentajes de asistencia de los gráficos (T07)

- **Pregunta:** DA-105 define en los gráficos dos series de porcentaje de asistencia por mes: a entrenamientos (entrenador) y a clases (docente). Cada porcentaje tiene su regla en el Dominio de otro contexto: `Src\Academico\Dominio\ConteoDeAsistencia` cuenta presentes y justificadas sobre los registros, y `Src\Competencia\Dominio\ConteoDeAsistenciaAEntrenamientos` tiene la suya. Consultas no tiene `Dominio/` y la prueba de arquitectura prohíbe importar el Dominio de otro contexto. ¿Cómo calcula T07 esos porcentajes?
- **Tarea afectada:** T07. Las rutas permitidas de T07 son `src/Consultas/**`, `app/Http/**`, `routes/api.php`, `docs/api/openapi.yaml` y `tests/**`.
- **Fuente:** PDF §8 pide «Participación en entrenamientos» y la evolución académica entre los análisis; HU-25 c1 pide la asistencia a entrenamientos para el entrenador y la asistencia a clases para el docente. Ninguno dice cómo se calcula por mes.

| Opción | Qué hace | A favor | En contra |
|---|---|---|---|
| A | Dos puertos en `src/Compartido/Aplicacion/` («porcentaje de asistencia por mes»), con su adaptador en Académico y en Competencia, que usan la regla de su propio Dominio | Una sola regla por porcentaje; es el patrón de `docs/arquitectura.md` §4.3 | Toca `src/Compartido/**`, `src/Academico/**` y `src/Competencia/**`, fuera de las rutas de T07 |
| B | Consultas repite las dos fórmulas con SQL | No sale de las rutas de T07 | Duplica dos reglas de negocio fuera del Dominio; si una cambia, los gráficos y las pantallas de H2 y H3 darían números distintos |
| C | Las series de asistencia devuelven conteos por mes (presentes, faltas, justificadas) y no un porcentaje | No duplica reglas ni toca otros contextos | Cambia DA-105; el frontend tendría que conocer la regla |

- **Recomendación:** A.
- **Estado:** bloqueo. T07 no se ejecutó: construirla sin las dos series de asistencia la dejaría a medias frente a HU-25 c1, y las opciones A y B exceden lo permitido (rutas, o duplicar reglas). Las demás series (rendimiento por partido, mediciones, resumen por periodo y calificaciones) no tienen este problema.

## Estado de las tareas al cierre del lote (15/11/2026)

| Tarea | Estado | Por qué |
|---|---|---|
| T00 | Completada | — |
| T01 | Completada | — |
| T02 | Completada | — |
| T03 | Plan entregado, sin ejecutar | La especificación manda detenerse en el plan (`docs/reportes/f1-h6-plan-t03.md`) |
| T04 | No ejecutada | Depende de P-05 (forma del error de filtro), que el responsable pidió no decidir |
| T05 | Plan entregado, sin ejecutar | La especificación manda detenerse en el plan (`docs/reportes/f1-h6-plan-t05.md`) |
| T06 | No ejecutada | Devuelve la vista de T05 (DA-96) |
| T07 | Bloqueada | P-14 |
| T08 | No ejecutada | Recorre las rutas de T04 a T07, que no existen todavía |
| T09 | No ejecutada | El humo depende de T03 y T05 y de P-07; la trazabilidad y el registro de DA-87 a DA-100 son del cierre |

---

## Actualización del 16 de noviembre de 2026: decisiones del responsable

Fuente citada: [`docs/fuentes/proceso-general-sistema-web.md`](../fuentes/proceso-general-sistema-web.md). §7 dice que el sistema genera alertas automáticas y que las consultan docentes y entrenadores; §8 lista los análisis, entre ellos «Participación en entrenamientos», «Evolución de calificaciones» y «Futbolistas con bajo rendimiento».

| Id | Decisión | Dónde quedó |
|---|---|---|
| P-05 | Opción A: 422 `validacion_fallida` arriba y `filtro_invalido` en `errores[].codigo`; un id válido pero ajeno devuelve lista vacía | Especificación v1.10, §6.3 |
| P-07 | Opción A: guion de humo reescrito con cinco partidos, un aviso, un filtro y el caso académico activo y cerrado | Especificación v1.10, §9 |
| P-08b | Opción A: sin temporada que contenga la fecha, no se cuentan tarjetas; las faltas sí | DA-91; `AcumulacionDeSanciones` (T02) |
| P-08c | Opción A: las alertas abiertas de un jugador en baja se conservan y no se muestran | DA-99, sin cambio |
| P-09 | Opción A: el escucha de Alertas calcula el nivel de riesgo por un puerto | T03 y su plan |
| P-10 | Opción A: el Dominio aplica «basta un `no_pasa`» y «el mayor por partido» | T02 |
| P-14 | Opción A: dos puertos en Compartido con adaptadores en Académico y Competencia | DA-109; `docs/arquitectura.md` v1.5.4, pendiente de revisión de Luis |
| T05 | Opción A: el orden usa el promedio de las últimas N calificaciones de partido, con N igual a la ventana del umbral | DA-106; especificación v1.10 |

Decisiones por defecto del agente, porque responden los requisitos: **DA-107** (P-08a, sin evaluación es `datos_insuficientes`) y **DA-108** (P-04, el docente ve el indicador solo de los avisos que él creó). Pendientes de validación.

### Lo que sigue pendiente o bloqueado

| Tarea | Estado | Qué falta |
|---|---|---|
| T03 | Plan actualizado, sin ejecutar | Aprobar el plan y decidir sus puntos 1 (cómo lee Alertas los datos de otros contextos) y 2 (dueño del comando) |
| T05 | Plan actualizado con DA-106, sin ejecutar | Aprobar el plan y sus puntos 1 a 6 |
| T06 | Bloqueada | Devuelve la vista de T05 (DA-96) |
| T08 | Bloqueada | Recorre las rutas de T04 a T07; faltan las de T05 y T06 |
| T09 | Bloqueada | El humo de §9 usa `alertas:recalcular` (T03) y la vista para convocar (T05) |
