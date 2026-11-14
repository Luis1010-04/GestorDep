# Auditoría de la especificación de H6

**Fecha:** 14 de noviembre de 2026
**Documento auditado:** `docs/hitos/f1-h6.md` v1.0
**Revisión:** `a5e6134b82c36e16e86e3dd60cd0075f6f37a2c8` (`hito/f1-h6-especificacion` = `origin/hito/f1-h6-especificacion`), árbol limpio.
**Modo:** solo lectura. No se editó código ni documentación, no se ejecutó `make` y no hay commit de este reporte.

**Criterio usado.** El encargo cita un «prompt de auditor de especificación de H6» que no está en el repositorio. Se auditó con este criterio: contradicciones con los documentos de mayor precedencia (`docs/README.md` §1), contradicciones internas, huecos que obligarían al agente a inventar, y afirmaciones de la especificación sobre el código que no coinciden con el código en `a5e6134`. Si el prompt pedía algo más, falta cubrirlo.

## 1. Veredicto

La especificación no está lista para empezar a codificar. Hay **6 bloqueantes**: dos impiden T01, uno impide T02 y tres afectan a T03, T04, T08 y T09. Hay además **7 hallazgos mayores** y **8 menores**.

| Id | Hallazgo | Tarea que detiene |
|---|---|---|
| B1 | DA-81 (Pest en paralelo) no está cerrada y §0 la exige antes de T01 | T01 |
| B2 | Faltan parámetros de fábrica: el número de faltas de RN-09 y el nivel que activa RN-21 | T01, T02 |
| B3 | T02 pide RN-08 y RN-18 en `src/Alertas/Dominio/`, contra la arquitectura y la prueba de arquitectura | T02 |
| B4 | El entrenador no ve la alerta académica, contra requisitos §7, la definición de resumen simplificado y arquitectura §10 | T04, T05, T08 |
| B5 | §7.6 espera 404 por alerta en una ruta que devuelve una lista | T04, T08 |
| B6 | DA-88 es incoherente con el guion de humo y con el estado actual de la alerta académica | T03, T09 |

## 2. Bloqueantes

### B1. La precondición de DA-81 no se cumple

- **Especificación:** §0, «DA-81 […] debe estar cerrada antes de T01»; §3.1 la da por «confirmada».
- **Fuente:** `docs/reportes/f1-h5-decisiones-del-agente.md`, DA-81: «Estado: decidida por Luis (14/11/2026) […] **La tarea aún no existe**». En `backend/composer.json` línea 86, `verificar` llama a `vendor/bin/pest` sin modo paralelo.
- **Problema:** «confirmada» significa que se decidió hacerla, no que esté hecha. Con el texto actual, T01 no puede empezar.
- **Opciones:** (a) hacer primero la tarea de DA-81; (b) cambiar §0 para que DA-81 pueda cerrarse durante H6, con un límite de tiempo para `make verificar` mientras tanto. Recomiendo (a), porque H6 agrega pruebas y `make mutacion` a una corrida que en H5 ya llegó a 868 s.

### B2. Faltan parámetros de fábrica que las reglas necesitan (RNF-09)

- **Especificación:** DA-100 y §5.2 siembran cuatro claves: `sanciones_tarjetas_amarillas` = 3, `sanciones_tarjetas_rojas` = 1, `sanciones_faltas_ventana_dias` = 30 y `dias_desactualizado` = 30. DA-100 afirma que «ninguna regla usa constantes del código para estos valores».
- **Fuente:** RN-09 (`docs/requisitos.md` §3.2) activa con **tres** inasistencias injustificadas en 30 días. `docs/arquitectura.md` §7.9 dice que `parametros_institucionales` guarda también el «nivel que activa la alerta académica (RN-21)».
- **Problema:**
  1. No hay clave para el número de faltas (3). T02 y T03 solo podrían escribirlo como constante, contra RNF-09 y la regla 1 del proyecto.
  2. No hay clave para el nivel que activa la alerta académica. Hoy la tabla solo tiene `riesgo_academico` (`backend/database/seeders/ParametrosInstitucionalesSeeder.php`), sin ese dato.
- **Opciones:** agregar a DA-100 una clave para el número de faltas y decidir si el nivel de RN-21 se siembra (como dice arquitectura §7.9) o se deja fijo con una nota que corrija §7.9. Recomiendo sembrar los dos en T01.

### B3. T02 está definida contra la arquitectura

- **Especificación:** T02, «Reglas de alerta en el Dominio (RN-21, RN-09, RN-10, RN-08, RN-18)», rutas `src/Alertas/Dominio/**` y `src/Rendimiento/Dominio/**` (solo lectura de tipos); pruebas «una por fila de §7.1 a §7.5» en `tests/Unit/Alertas/**`.
- **Fuente y problema:**
  1. **RN-08 ya existe.** `backend/src/Rendimiento/Dominio/CalculoDeDesempeno.php` la implementa desde H4 (`docs/arquitectura.md` §4.1). Pedir sus cinco filas en `tests/Unit/Alertas/` lleva a duplicar la regla en Alertas o a probar código de otro contexto desde Alertas.
  2. **Alertas no puede importar el dominio de Rendimiento.** `backend/tests/Arch/ArquitecturaTest.php` (líneas 30 a 44) prohíbe que un contexto importe el `Dominio` de otro, salvo Compartido. «Solo lectura de tipos» no dice si es consultar el archivo o importarlo; si es importarlo, `make verificar` falla.
  3. **RN-18 no es de Alertas.** Arquitectura §4.1 pone `CalculadorDeDesempeno` (RN-18) en Consultas, y Consultas no tiene `Dominio/` (`ArquitecturaTest.php` línea 17). T02 pide «el orden por desempeño» dentro de `src/Alertas/Dominio/**`.
- **Opciones:** (a) T02 queda solo con RN-21, RN-09 y RN-10 en Alertas; RN-10 recibe el resultado de la evaluación como valor primitivo (`pasa`, `no_pasa`, `datos_insuficientes`); RN-08 se cita como ya probada en H4; RN-18 pasa a T05, en `src/Consultas/Aplicacion/`. (b) Mantener T02 como está y registrar una excepción a la arquitectura. Recomiendo (a).

### B4. La alerta académica oculta al entrenador contradice tres fuentes

- **Especificación:** §3.1 («Visibilidad de alertas […] aprobada el 14/11/2026»), DA-89 («El entrenador del jugador no la ve como alerta […] sin el detalle de riesgo»), §5.3 (alerta académica, entrenador: «No») y §7.6 («Entrenador del jugador | alerta académica | 404»).
- **Fuente:**
  - `docs/requisitos.md` §7, fila «Riesgo académico»: Docente **Sí**, Entrenador del equipo **Sí**, Segundo entrenador **Sí**.
  - `docs/requisitos.md` §1.5: el resumen simplificado **incluye** el nivel de riesgo académico. DA-89 lo entrega «sin el detalle de riesgo».
  - `docs/arquitectura.md` §10: «Alertas académicas: docentes y entrenadores del jugador».
  - HU-22: «Como docente o entrenador […]»; el criterio 1 no limita la alerta académica al docente.
- **Problema:** la aprobación del 14/11 está en un documento de precedencia 4 y contradice a los de precedencia 1 y 3. Además, el entrenador vería `nivel_riesgo: en_riesgo` en el resumen simplificado y recibiría una lista de alertas sin la académica: el dato se oculta en un sitio y se muestra en otro.
- **Opciones:** (a) el entrenador del jugador sí ve la alerta académica, como dicen requisitos y arquitectura; se corrigen DA-89, §5.3 y §7.6. (b) Se mantiene la decisión del 14/11 y se abre un F1-DOC que cambie requisitos §7, §1.5 y arquitectura §10 antes de T04. Recomiendo (a): es lo que ya dicen los tres textos vigentes y evita la incoherencia con el resumen.

### B5. §7.6 espera 404 por alerta, pero la ruta devuelve una lista

- **Especificación:** §6.1 solo define `GET /jugadores/{jugador}/alertas` (lista). §7.6 espera 404 en «Docente con materia del jugador | alerta deportiva del jugador», «Entrenador de otro equipo del jugador | alerta deportiva del primero» y «Entrenador del jugador | alerta académica». §9, paso 5: el docente consulta las alertas de F → **200**, «sin la alerta deportiva».
- **Problema:** las tres filas describen a un usuario con alcance sobre el jugador. Con una ruta de lista, el resultado solo puede ser 200 sin esa alerta, que es lo que dice el paso 5 del humo. Como cada fila de §7 es una prueba (§10.5), T04 y T08 tendrían que escribir pruebas que se contradicen con el humo.
- **Opciones:** (a) cambiar esas filas a «200, la lista no incluye la alerta»; el 404 queda para quien no tiene alcance y para el jugador en baja. (b) Agregar una ruta por alerta (`GET /alertas/{alerta}`), que hoy no está en §6.1. Recomiendo (a).

### B6. DA-88 no cuadra con el humo ni con la alerta académica

- **Especificación:** DA-88: las tres alertas se recalculan con el comando diario; «la académica se mantiene como hoy». §9: paso 3 registra la evaluación, paso 4 espera ver `riesgo_baja_deportiva`, y `alertas:recalcular` se ejecuta hasta el paso 12.
- **Fuente:**
  - Hoy no existe ninguna alerta académica: `backend/src/Alertas/` solo tiene el proveedor vacío, y `alertas:recalcular` (`backend/src/Academico/Infraestructura/Consola/ConciliarResumenesComando.php`) solo concilia resúmenes. «Se mantiene como hoy» no describe nada.
  - Académico sí publica eventos (`CalificacionRegistrada`, `AsistenciaDeClaseRegistrada`, `CriterioDeMateriaModificado`, `JugadorInscrito`) y ya tiene un escucha (`RecalcularResumenAcademico`). El argumento de DA-88 («no emiten eventos») vale para H3 a H5, no para la académica.
  - `docs/estrategia-pruebas.md` §5.2 pide que el humo de H6 compruebe «alerta académica activa y cerrada, listado con filtros y vista para convocar». El guion de §9 no activa ni cierra una alerta académica y no usa filtros.
- **Problema:**
  1. Con DA-88, el paso 4 del humo falla: la alerta deportiva no existe hasta que corre el comando.
  2. No queda dicho si la alerta académica se abre y cierra por evento (posible hoy, y lo que pide ADR-07) o solo a las 02:00, con hasta un día de retraso frente a HU-22 criterio 1.
- **Opciones:** (a) la académica por evento, con el escucha que ya existe, y las deportivas y de sanciones por comando; el humo ejecuta `alertas:recalcular` antes del paso 4 y agrega el caso académico y un filtro. (b) Las tres por comando; se reescribe la frase de DA-88, se acepta el retraso de la académica por escrito y se reordena el humo. Recomiendo (a). En cualquier caso, T03 necesita esta respuesta junto con la aprobación de DA-88.

## 3. Mayores

### M1. Dos entrenadores del mismo equipo: la alerta no distingue de quién son los datos

- **Especificación:** DA-87 y §5.1: una alerta abierta por (`tipo`, `jugador_id`, `equipo_id`), sin columna de entrenador. DA-90: el entrenador ve «solo las que calculó con sus propios datos (RN-14)». DA-92 y §3.4: la última evaluación «de **ese** entrenador».
- **Fuente:** DD-17 y `docs/arquitectura.md` §7.6: `evaluaciones_rendimiento` y `rendimientos_partido` son por entrenador; dos entrenadores de un mismo equipo califican por separado y registran tarjetas por separado.
- **Problema:** con dos entrenadores en un equipo no se sabe qué evaluación ni qué tarjetas alimentan la única alerta del equipo, y la alerta mostraría a uno el resultado de los datos del otro. La tabla sigue arquitectura §7.9, así que el hueco viene de origen.
- **Atenuante:** en la Fase 1 ninguna ruta agrega un segundo entrenador a un equipo (RN-22 lo deja en el backlog).
- **Recomendación:** decidirlo por escrito antes de T01, porque afecta al esquema: o se declara fuera de alcance con la regla de qué entrenador cuenta, o se agrega `entrenador_id` a la tabla y a la unicidad.

### M2. El indicador de aviso pendiente para el docente

- **Especificación:** §5.3, «Aviso pendiente (indicador) | Docente: Sí».
- **Fuente:** `docs/requisitos.md` §7, «Aviso de lesión pendiente | Docente: **Solo el que creó**»; RN-16.
- **Problema:** el indicador revelaría al docente avisos creados por otros.
- **Recomendación:** para el docente, el indicador solo cuenta sus propios avisos; agregar la fila a §7.6.

### M3. Rutas permitidas incompletas

| Tarea | Falta | Por qué hace falta |
|---|---|---|
| T04 a T07 | `routes/api.php` | Las siete rutas nuevas se registran ahí. H5 lo cubría con «rutas base»; H6 solo lista `app/Http/**` |
| T05 a T07 | `lang/es/**` (solo claves nuevas) | Mensajes de error de las rutas nuevas |
| T03 | `src/Compartido/Aplicacion/**` y los adaptadores en la Infraestructura de Académico, Competencia y Rendimiento | Alertas debe leer resúmenes, evaluaciones, tarjetas, faltas y temporadas de otros contextos. Arquitectura §4.3 solo autoriza a Consultas a leer tablas ajenas con SQL; Alertas necesita puertos |
| T03 | Decir qué contexto es dueño del comando | Hoy `alertas:recalcular` vive en Académico; T03 permite tocar ese archivo «para que conciliara también las alertas», lo que metería lógica de Alertas en Académico |
| T01 | Alcance real del cambio en el seeder | `ParametrosInstitucionalesSeeder` hace `return` si `riesgo_academico` ya existe; agregar claves exige reestructurarlo, no «solo las claves nuevas» |
| T02 | reportes | El modo de trabajo pide `f1-h6-t02.md` |

### M4. Formas de respuesta incompletas (§6.2)

- **Listado, docente:** HU-23 criterio 3 pide «el detalle de su materia». La fila del listado no tiene ningún campo de materia.
- **Campos sin forma:** `estado_oficial: {...}` y `situacion_academica` no se definen.
- **Fechas de actualización:** HU-24 criterio 1, RNF-04 y DA-94 piden fecha por dimensión. `estado_fisico` no tiene `actualizado_en`; `situacion_academica` y `progreso` no tienen ni fecha ni marca.
- **Contradicción interna:** «los campos no visibles se omiten, no se envían nulos» frente a `resumen_academico: {...} | null`.
- **Gráficos:** no se definen las respuestas de `GET /jugadores/{jugador}/graficos` ni de `GET /equipos/{equipo}/graficos`: qué series, con qué nombres, cómo se elige el equipo de un jugador con dos equipos del mismo entrenador, ni cómo viaja «el jugador contra el promedio del equipo» (HU-25 criterio 2).
- **HU-25 criterio 1:** para el entrenador incluye «el resumen académico simplificado por periodo»; DA-97 y §7.7 no lo mencionan.

T05 y T07 tendrían que inventar el contrato.

### M5. `filtro_invalido` choca con la convención vigente

- **Especificación:** §6.3, 422 `filtro_invalido` para tipo de alerta desconocido o UUID inválido.
- **Fuente:** los filtros existentes responden 422 `validacion_fallida` con el detalle en `errores[]` (`JugadorController`, `MateriaController`, `PartidoController`; `docs/api/openapi.yaml` línea 664). `PartidoController` devuelve lista vacía para un id de filtro que no existe (DA-26 de H3).
- **Problema:** no se dice si `filtro_invalido` es el `codigo` de la respuesta o el de `errores[]`, ni qué pasa con un UUID válido que no es de ningún equipo o materia del usuario.
- **Recomendación:** `validacion_fallida` arriba y `filtro_invalido` (si se quiere) en `errores[].codigo`; id válido pero ajeno o inexistente → lista vacía.

### M6. Guion de humo (§9)

Además de B6:

- **Paso 3:** «registra una evaluación […] (`no_pasa`)» no es un paso. No hay ruta para registrar evaluaciones: hacen falta 5 partidos jugados con su convocatoria y rendimiento (N = 5) para que el resultado no sea `datos_insuficientes`.
- **Paso 6:** espera «su indicador de aviso pendiente» sin que ningún paso cree un aviso.
- **Filtros:** ningún paso los usa, y estrategia §5.2 los pide.

### M7. Reglas con casos sin definir

- **DA-92:** no dice qué pasa si no hay ninguna evaluación guardada o no hay umbral ni plantilla aplicable (`SinUmbralAplicable` en H4). Se supone `datos_insuficientes`, pero no está escrito.
- **DA-91:** «las tarjetas se cuentan por temporada» no dice cuál: se supone la que contiene la fecha de cálculo. Tampoco dice qué pasa si ninguna temporada contiene esa fecha.
- **DA-99:** las alertas abiertas de un jugador en baja nunca se cierran. `cerrada_en` quedará nulo para siempre y ocupará el lugar de la unicidad. Conviene confirmar que es lo querido, porque arquitectura §4.2 preveía recalcular con `JugadorDadoDeBaja`.

## 4. Menores

- **m1.** §7 dice que copia las tablas «tal cual», pero el encabezado de §7.5 cambia «promedio de la ventana» (`docs/estrategia-pruebas.md`) por «promedio de la última evaluación».
- **m2.** DA-95 ordena por el `promedio` de la última evaluación guardada; DD-12 y requisitos §1.5 definen el desempeño como el promedio de las últimas N calificaciones. Coinciden mientras la evaluación esté al día (H4 la recalcula al capturar, corregir y fijar umbral), pero la equivalencia no está escrita.
- **m3.** DA-89 limita la alerta académica al docente con materia «del periodo vigente»; el alcance del docente (arquitectura §10) es cualquier inscripción. Un docente de un periodo anterior tendría alcance y lista vacía; conviene decirlo.
- **m4.** OM-05 a OM-08 sí aparecen en el registro, como columna de origen de DD-07, DD-10, DD-12 y DD-15; lo que falta es su definición. La nota de §3.2 es correcta.
- **m5.** DA-98 usa «hoy de negocio» sin decir la zona; se supone America/Mexico_City (RNF-04).
- **m6.** `GET /equipos/{equipo}/jugadores/para-convocar` es paginada (§6.1) y ordenada con empates «en orden de entrada» (DA-95). No se define cuál es el orden de entrada de una consulta SQL; sin eso, la paginación no es estable.
- **m7.** Las claves de DA-100 son cuatro valores sueltos; el parámetro existente (`riesgo_academico`) es un objeto. No es un error, pero conviene decidir el estilo antes de sembrar.
- **m8.** AB-07: el registro sugiere validarla «antes de H6»; la especificación la deja abierta sin fecha nueva.

## 5. Revisado sin hallazgos

- Las HU-22 a HU-25, HU-12 c2 y HU-29 c3, y las RN-08, RN-09, RN-10, RN-13, RN-14, RN-18, RN-21 y RN-23 de §4 coinciden con `docs/requisitos.md`.
- Las tablas de §7.1 a §7.4 son idénticas a las de `docs/estrategia-pruebas.md` §7 (§7.5: ver m1).
- La tabla `alertas` de §5.1 coincide con `docs/arquitectura.md` §7.9, con su disparador y su unicidad parcial.
- El alcance de §2 coincide con `docs/alcance-fases.md` §3 y §4 y con `docs/plan-fase-1.md` §2 y §2.1; §2.3 no anticipa la Fase 2.
- Los criterios propios de H6 en §10.7 coinciden con `docs/criterios-aceptacion-fase-1.md` §2.
- DD-08, DD-12 y DD-15 son las que `docs/plan-fase-1.md` §3 exige antes de H6.
- Las siete rutas de §6.1 no chocan con ninguna de `backend/routes/api.php`.
- `alertas:recalcular` ya está programado a las 02:00 America/Mexico_City (`backend/routes/console.php` línea 6).
- `database/migrations/alertas/` se cargará sola: `AppServiceProvider` registra todas las subcarpetas con un `glob`.
- `entrenamientos.estado` admite `realizado`, `partidos` tiene `temporada_id` y `asistencias_entrenamiento.estado` admite `falta` y `falta_justificada`, como supone DA-91.
- H3, H4 y H5 no publican eventos, como afirma DA-88 (solo Académico lo hace).
- El 403 del paso 8 del humo (docente en una ruta exclusiva del entrenador) es coherente con `docs/convenciones-api.md` §2.

## 6. No revisado

- `docs/api/openapi.yaml` no se comparó ruta por ruta: H6 aún no agrega nada.
- No se ejecutó `make verificar`, `make mutacion` ni `make humo`: la auditoría es documental.
- No se comprobó el estado de la integración continua de `a5e6134`.
- El prompt de auditor citado en el encargo no se encontró (ver «Criterio usado»).
