# Conciliación de planes (solo lectura)

**Fuentes.** La guía no venía adjunta; la encontré en `C:\Users\LOQ\Downloads\guia-control-gestordep.md` (v1.0, 21/11/2026) y la leí como dato. Verifiqué el estado de las tareas con `git log` y `gh api` sobre el remoto: `main` está en `57e434d`, no hay PR abiertos y las etiquetas van de `f1-h0` a `f1-h6`.

**Incidente mío, para que lo registres.** En una consulta de solo lectura, una redirección de shell creó el archivo temporal `C:\Users\LOQ\AppData\Local\Temp\h` (441 bytes, solo una lista de nombres de tablas). Está fuera del repositorio y no lo borré, porque borrarlo también es escribir. Lo borro si me lo ordenas.

## 1. Estado real de H7 por tarea

| Tarea | Estado | PR | Punta → fusión | Fusionado (UTC, gh api) |
|---|---|---|---|---|
| T01 | Hecha | #25 | `c3f3870` → `9a5fe19` | 2026-11-20 04:16 |
| T02 | Hecha | #27 | `6d23f5d` → `f254728` | 2026-11-20 16:37 |
| T03 | Hecha | #29 | `29c26bc` → `8e87e5f` | 2026-11-20 21:06 |
| T04 | Hecha | #30, más #31 (riesgo 3) | `b4d89ab` → `c98da6d`; `3bbe9eb` → `f257f59` | 2026-11-20 21:52 y 22:14 |
| Resumen del lote A | Hecho | #32 | `bf3bc97` → `1ed39e8` | 2026-11-21 15:43 |
| T05 | Hecha | #33 | `eba0c86` → `57e434d` | 2026-11-21 19:47 |
| T06 | En curso: el plan se entregó en la conversación del 23/11 | — | Sin archivo, rama, commit ni PR | — |
| T07, T08, T09, resumen del lote B, compuerta, auditoría de sistema, etiqueta | Pendientes | — | Ningún commit `F1-H7-T07/08/09` ni ninguna rama | — |

- La especificación tiene sus propios PR: #24 (borrador), #26 (v0.4) y #28 (v0.5, fusionado el 2026-11-20 a las 15:58).
- El CI `verificar` de `57e434d`, `1ed39e8` y `f257f59` terminó en *success*. No revisé el CI de los demás.

## 2. Mapa de los hitos A a D de la guía

Ninguno de estos criterios está cubierto por T06 a T09, salvo donde se indica.

| Criterio | Auditoría del 23/11 | Documento que lo resuelve, lo acepta o lo contradice | Clasificación |
|---|---|---|---|
| **A1** La búsqueda no devuelve jugadores ajenos | A6-11: confirmado, lo exige un requisito | Lo contradice RN-20 ([requisitos.md:113](../requisitos.md#L113)) y la «excepción única» de [convenciones-api.md:40](../convenciones-api.md#L40) | Cambio de requisito (D1). Sigue abierta la parte de ocultar las bajas: RN-23 ([:116](../requisitos.md#L116)) habla de «listados» |
| **A2** Rama del docente con `inscripciones.jugador_id` | No está en A6. Lo anoté en mi plan (fuera de alcance) | Observado: dos llamadas pasan esa columna ([ConsultaDeInscripcionesSql.php:72-76](../../backend/src/Academico/Infraestructura/ConsultaDeInscripcionesSql.php#L72-L76), [ConsultaDeHistorialAcademicoSql.php:55](../../backend/src/Consultas/Infraestructura/ConsultaDeHistorialAcademicoSql.php#L55)), y [VisibilidadDeAlertasSql.php:85-88](../../backend/src/Consultas/Infraestructura/VisibilidadDeAlertasSql.php#L85-L88) repite el patrón sin alias. Inferido: hoy no hay fuga, porque la consulta externa ya filtra por `materias.docente_id` | Trabajo nuevo, sin dueño. Es candidato a un AB de seguridad dentro de H7 |
| **A3** Historial de T02: `por_pagina=101`, valor por defecto y desempate | No está en A6 | Observado: solo existe la prueba de `por_pagina=0` ([HistorialAcademicoTest.php:190](../../backend/tests/Feature/Academico/HistorialAcademicoTest.php#L190)). El orden y la paginación son decisiones de [f1-h7.md](../hitos/f1-h7.md) §3.3 | Hueco de pruebas. Ninguna tarea pendiente tiene esa ruta permitida |
| **A4** Cuatro escrituras comprueban el alcance en el caso de uso | A6-09: matizado, no es un defecto (`authorize()` llama a casos de uso de `Aplicacion`). El auditor cuenta seis, no cuatro | Ningún documento lo exige | Opcional (defensa en profundidad) |
| **A5** Mismo inicio de sesión para cuenta inexistente o bloqueada, y límite por IP | A6-02 y A6-03: confirmados y ya aceptados | DD-29 ([registro:40](../decisiones/registro-decisiones.md#L40)); excluido en [f1-h7.md:65](../hitos/f1-h7.md#L65); riesgos aceptados en [:373-374](../hitos/f1-h7.md#L373-L374) | Riesgo aceptado. Cambiarlo es cambiar DD-29 |
| **A6** Autoconcesión | A6-12: lo exige un requisito | RN-20 | Cambio de requisito (D2) |
| **B1** Toda tabla con `eliminado_en` tiene `_historial` | A6-07: matizado | Observado: 8 tablas con `eliminado_en` no tienen historial (`periodos_escolares`, `asignaturas`, `categorias`, `posiciones`, `roles`, `metricas`, `tipos_prueba` y `rangos_mediciones_corporales`). La arquitectura §7 no las marca con (H). H7 lo deja fuera ([f1-h7.md:54](../hitos/f1-h7.md#L54)) | Contradice la arquitectura (D3) |
| **B2** Ningún seeder escribe sin historial | A6-08: confirmado | Solo docblocks («Los seeders no escriben historial»); no hay decisión registrada | Decisión pendiente (D3) |
| **B3** La baja se aplica igual en «las cuatro operaciones acordadas» | Las carreras (A6-04, A6-05) están en el plan de T06, pero eso es otro aspecto | DD-40 ([registro:51](../decisiones/registro-decisiones.md#L51)) permite cambiar de posición en baja. DA-12 de H5 ([:258](../decisiones/registro-decisiones.md#L258)) permite dar de alta, descartar y corregir una lesión en baja | Lo contradicen DD-40 y DA-12. No sé cuáles son «las cuatro» |
| **B4** No se borra un catálogo en uso | A6-06: confirmado, pero la corrección choca con HU-28 | HU-28 c4 ([requisitos.md:322](../requisitos.md#L322)) permite el borrado lógico de un valor en uso | Cambio de requisito |
| **B5** `registrado_por` protegido en la base | Indirecto: el auditor vio que ningún `update` toca la autoría | Riesgo 3, pendiente de decisión ([f1-h7-t04.md:110](f1-h7-t04.md#L110), [lote-a.md:81](f1-h7-lote-a.md#L81)) | Decisión abierta (D6). Si se corrige, hace falta una migración |
| **C1** Lectura del historial deportivo (1.12) | A7-16: parcial, matizado por DA-123 | El alcance pone «historial» en la Fase 1 ([alcance-fases.md:30](../alcance-fases.md#L30)); DA-123 lo acota y es una desviación documentada ([f1-h7.md:368](../hitos/f1-h7.md#L368)) | Decisión D8. Hay tensión de precedencia (§4, punto 2) |
| **C2** Serie y comparación de pruebas físicas (6.13 y 6.b) | A7-88: cumple, con nota (las pruebas tienen listado e historial, no serie en gráficos) | HU-18 c2 ([:249](../requisitos.md#L249)) pide «evolución»; la comparación por temporada es de la Fase 2 ([alcance-fases.md:37](../alcance-fases.md#L37)) | 6.b es Fase 2. 6.13 solo es trabajo si decides que «evolución» exige una serie (D8) |
| **C3** Participantes registrados por sesión (4.10) | A7-60: cumple | HU-10 c1 ([:196](../requisitos.md#L196)): «los jugadores del equipo quedan listados» | Va más allá del requisito (D8) |
| **C4** Promedio académico por equipo o categoría (8.5) | A7-110: sin clasificar | Ambiguo: la Fase 1 incluye «gráficos… grupal: calificaciones» ([alcance-fases.md:37](../alcance-fases.md#L37)) | Decisión D7 |
| **C5** Fase asignada a las filas sin fase | A7 cuenta **6** filas sin clasificar; la guía dice **14** | Nada | Decisión D9 |
| **D1** Contradicción sobre c2 | No está en A6 | Observado y más amplio: §2.1 da «c1 a c3» a T03 **y** a T04 ([f1-h7.md:40-41](../hitos/f1-h7.md#L40-L41)), mientras que §8 y §11 dan c2 solo a T02 ([:354](../hitos/f1-h7.md#L354)). Pendiente en [lote-a.md:80](f1-h7-lote-a.md#L80) | Corrección de la especificación (v0.6) |
| **D2** Archivo de decisiones del agente | — | No existe, aunque §0 lo cita ([f1-h7.md:9](../hitos/f1-h7.md#L9)). Mi plan de T06 lo crea | Lo cubre T06 si se aprueba |
| **D3** El reporte de T02 cita la versión vigente | — | Observado: cita la v0.4 ([f1-h7-t02.md:3](f1-h7-t02.md#L3)), y T02 se fusionó 39 minutos después que la v0.5 | Documental. T09 tiene permitidos los «reportes» |
| **D4** `make levantar` con el Compose actual | — | Observado: Compose v5.6.1 y `--build` en [Makefile:8](../../Makefile#L8). No verifiqué que falle | Abierto (D13). Bloquea el humo de H7 |
| **D5** Convención de trailers | — | Solo DA-03 de H4 ([registro:191](../decisiones/registro-decisiones.md#L191)). En `main` se mezclan Haiku y Opus, con `Claude-Session` en unos commits y no en otros | Abierto (D12) |

## 3. Decisiones D1 a D13 de la guía

| # | Estado según los documentos | Cambio con la auditoría del 23/11 |
|---|---|---|
| D1 Búsqueda | Resuelta en lo principal por RN-20 y [convenciones-api.md:40](../convenciones-api.md#L40). Abierto: ocultar las bajas (RN-23) | Se le suman el 409 que revela una baja (primera mitad de AB-26, DD-52) y el límite de la búsqueda (A6-03) |
| D2 Autoconcesión | Resuelta por RN-20; solo se reabre si cambias el requisito | A6-12 la confirma como diseño |
| D3 Historial en catálogos y seeders | Abierta: la arquitectura §7 contradice la regla de `CLAUDE.md` §1 | A6-07 y A6-08. Dato nuevo: los catálogos de Competencia sí tienen historial sin estar marcados (H) |
| D4 Inicio de sesión | Riesgo aceptado para H7 (DD-29, §2.3 y §12) | A6-01 (contador no atómico) no es D4: es AB-17 y va en T06. A6-18 (cambio de contraseña sin límite) es nuevo |
| D5 Baja en posición y alta médica | Resuelta: DD-40 (con AB-12 pendiente de la institución) y DA-12 de H5 | Las carreras con la baja van a T06 (AB-20, AB-26 y AB-29) |
| D6 `registrado_por` | Abierta (riesgo 3) | Sin cambio |
| D7 Fila 8.5 | Abierta | A7-110 la marca «sin clasificar» |
| D8 Filas 1.12, 6.13, 6.b y 4.10 | 1.12: DA-123 (desviación documentada). 4.10 y 6.13: cumplen según A7. 6.b: Fase 2 | Se reduce a 1.12 y, quizá, a la serie de 6.13 |
| D9 Filas sin fase | Abierta | A7 cuenta 6, no 14 |
| D10 Correo como usuario | Resuelta de hecho por el contrato ([f1-h1.md:327](../hitos/f1-h1.md#L327)); falta registrarla como decisión | A7-01 lo anota |
| D11 RN-13 | Abierta ([f1-h7-t05.md:169](f1-h7-t05.md#L169)). Además, §2.1 asigna RN-13 a T05, pero §8 y §11 no le nombran prueba | Sin cambio |
| D12 Trailers | Abierta | Sin cambio |
| D13 Docker Compose | Abierta | Sin cambio |

**Decisiones nuevas que la guía no tiene:**
- S1 a S4 del plan de T06: 429 o 401 en AB-17, el título de AB-20 frente a DD-30, el puerto y el ayudante de pruebas, y AB-31.
- A6-06 (eliminar el periodo vigente) y A6-13 (periodos que se solapan).
- A6-17 y A6-18 (contraseña por consola y cambio de contraseña sin límite).
- T06b (A6-10 y A6-16).
- A2 y A3.

## 4. Discrepancias entre los tres planes

La guía no está en la lista de precedencia ([docs/README.md:5-15](../README.md#L5-L15)): ante cualquier conflicto, prevalecen los documentos del repositorio. La propia regla pide detenerse y preguntar.

1. **Hitos A a D frente al plan.** El plan solo tiene H0 a H7, y la Fase 2 se planifica al cerrar H7 ([plan-fase-1.md:108](../plan-fase-1.md#L108)). Además, la Fase 1 termina con la etiqueta `f1-h7` (`criterios-aceptacion-fase-1.md` §6). Los hitos A a D no caben en ningún plan vigente, y siete de sus criterios (A1, A5, A6, B1, B3, B4 y C3) contradicen requisitos o decisiones registradas. **Prevalecen** los requisitos y la arquitectura.
2. **C1 frente a DA-123.** El alcance (nivel 2) pone el historial en la Fase 1; la especificación de H7 (nivel 4) lo acota. Prevalece el alcance mientras no se resuelva, pero §12 de H7 lo registra como una desviación que tú decidiste. Conviene llevar DA-123 a `alcance-fases.md`.
3. **Compuerta de H7.** A la guía (§5) le faltan los puntos §10.3 a §10.5, §10.8 y §10.9 de la especificación. La guía exige «sin hallazgos altos» y «medios 0» (§6), mientras que la especificación exige «sin hallazgos abiertos», sin contar los riesgos aceptados (§3.1). **Prevalece `f1-h7.md`.**
4. **Cobertura y tiempo.** La guía (§6) fija 100 %, 100 % y 99,5 %, que son los valores actuales según el log de T05. `estrategia-pruebas.md` §4 fija mínimos de 95, 85 y 70. Los 900 s de la guía no aparecen en ningún documento del repositorio (el log de T05 marca 369 s). **Prevalece la estrategia de pruebas.**
5. **Ramas y commits.** El plan dice «una rama por hito, un commit por tarea» ([plan-fase-1.md:32](../plan-fase-1.md#L32)) y §4.11 habla de «fusionar la rama del hito». En la práctica hay una rama por tarea, con varios commits (T05 tiene tres) y PR directo a `main`, que es lo que describe la guía (§3). Prevalece el plan, pero la práctica ya se apartó de él.
6. **Contradicción interna de la guía.** §1 dice que el agente «puede abrir PR en borrador»; §2.1 de la guía y §0 de H7 exigen una orden. **Prevalece `f1-h7.md` §0.**
7. **Colisión de nombres.** La especificación de H7 ya usa D-1 a D-6 para otras cosas (§3.1). Las D1 a D15 de la guía no están en el registro.
8. **§3.4 de H7 dice «Ninguna» duda abierta** ([f1-h7.md:114](../hitos/f1-h7.md#L114)), pero hay al menos tres: c2, RN-13 y el riesgo 3.
9. **El front no es la Fase 2.** Es una precondición de ella (DA-122, [registro:386](../decisiones/registro-decisiones.md#L386); [alcance-fases.md:93](../alcance-fases.md#L93)) y ya empezó: #16 (PT-12) fusionó tres rutas web en `main` ([trazabilidad.md:203-212](../trazabilidad.md#L203-L212)).

## 5. Propuesta de fases, corregida

**Correcciones a tu propuesta:**
- Llamarlas **etapas**, para no chocar con «Fase 1» y «Fase 2» del proyecto.
- Llevar a las etapas 0 y 1 lo que es defecto de la especificación de H7 y no deuda posterior: c2, RN-13, §3.4, el archivo de decisiones del agente, A2, A3 y la decisión sobre el riesgo 3.
- La etapa 2 necesita un vehículo: un H8, que exige enmendar `plan-fase-1.md` §2, o entrar en la planificación de la Fase 2.
- El front no es la Fase 2 y ya empezó.

| Etapa | Qué entra | Depende de | La bloquean | Criterio de salida |
|---|---|---|---|---|
| **0. Conciliación** | Este informe; tus decisiones; la actualización de la guía | — | Nada | S1 a S4 decididos; destino asignado a cada criterio A a D (H7, deuda, riesgo aceptado o descartado); guía v1.1 |
| **1. Cierre de H7** | Plan formal de T06 y su corrección (con AB-31 si se aprueba); especificación v0.6 si hay T06b o se corrigen c2, RN-13 y §3.4; T07; resumen del lote B; T08; T09; `make fresh` y `rollback` con tu autorización; humo de H0 a H7; auditoría de sistema; etiqueta `f1-h7` | Etapa 0 | S1 a S4 (T06); **D13**, sin la cual no hay humo ni `fresh`; D11 y c2 (§10.2 y §10.5); D6 y cada hallazgo del grupo 3, que deben quedar corregidos o aceptados por escrito (§10.7 y §10.9) | `f1-h7.md` §10 completo, `plan-fase-1.md` §4 y `criterios-aceptacion-fase-1.md` §6 |
| **2. Deuda posterior a H7** | Los riesgos aceptados que decidas corregir (A1, A4, A5, A6, B1 a B5, A6-06, A6-13, A6-17, A6-18, A6-10, A6-16) y los criterios C1 a C5 que decidas sean de la Fase 1 | Etiqueta `f1-h7`; decisión del vehículo (H8 o planificación de la Fase 2) | D1 a D9 por criterio; los cambios de requisito (RN-20, HU-28, DD-29, DD-40 y DA-12) | Cada punto corregido con su prueba o aceptado por escrito; A7 repetida sin «no cumple» |
| **3. Front** | Inventario de pantallas sobre lo que ya existe (PT-12: `/`, `/ingreso`, `/panel`) en `frontend/blade-niceadmin` | DA-122. No depende de la etapa 2, salvo en las pantallas cuyas rutas cambien por D1 o D4 (búsqueda e inicio de sesión) | `alcance-fases.md` §7.3 (quién y cuándo); D10 | Inventario aprobado. Para planificar la Fase 2 hacen falta además las condiciones 1, 2, 4 y 5 de §7 |

## 6. Qué partes de la guía hay que actualizar

- **Encabezado.** Su lugar frente a `docs/README.md` §1, porque hoy no figura en la lista de precedencia.
- **§1.** El permiso de «abrir PR en borrador».
- **§2.** La regla 8 cita una convención de trailers que no existe (D12). Falta la regla de los riesgos aceptados (§3.1 de H7).
- **§3.** Los pasos 7, 8 y 11 frente a `plan-fase-1.md` §1.2 (ramas y commits).
- **§4.** La compuerta «Fase completa» frente a `criterios-aceptacion-fase-1.md` §6, y «sin hallazgos altos» frente a «sin hallazgos abiertos».
- **§5, hito H7.** El estado de T01 a T05; el plan de T06; AB-31 y T06b; los puntos de §10 que faltan.
- **§5, hitos A a D.** Rehacerlos con los IDs del 23/11. Marcar como «cambio de requisito» A1, A5, A6, B1, B3, B4 y C3. Corregir el número de A4 (cuatro frente a seis), definir «las cuatro operaciones» de B3, ajustar C2 y C3 a A7, cambiar C5 a 6 filas y extender D1 a T03 y a §2.1. Precisar que la prueba de A2 «falla hoy» solo a nivel unitario (inferido). Ubicar cada criterio en una etapa.
- **§6.** Las metas de cobertura y de tiempo frente a la estrategia de pruebas; «medios 0» frente a los riesgos aceptados; «parcial con decisión escrita» con DA-123 como ejemplo.
- **§7.** La prueba de arquitectura de B1 depende de D3. Las mutaciones de la guía (manuales, sobre reglas de acceso) son distintas de `make mutacion` (Infection sobre `Dominio/`, DD-53).
- **§9.** Renumerar o enlazar con el registro (AB y PT); actualizar los estados de D2, D4, D5, D8 y D10; agregar las decisiones nuevas de §3; citar las fuentes de D14 y D15.
- **§10.** El incidente de hoy (archivo temporal).
- **§8 y §11.** Sin cambios.

## 7. Lo que no pude verificar

- Que `make levantar` falle. No ejecuté `make` ni Docker más allá de `docker compose version`.
- Cuáles son «las cuatro escrituras» (A4), «las cuatro operaciones acordadas» (B3) y «las 14 filas sin fase» (D9). Vienen de las auditorías perdidas.
- La numeración 1.12, 4.10, 6.13, 6.b y 8.5: la inferí por el orden de las viñetas del PDF; 6.b es la menos segura.
- Que la prueba de A2 falle hoy, y que no haya fuga en otros usos del alcance distintos de los dos que revisé.
- El CI de las tareas T01 a T04. La cobertura y el tiempo salen de un log local de T05, no del CI de `main`.
- El estado actual de `make humo` y `make mutacion`.
- Los incidentes de §10 de la guía (solo vi las horas de fusión), si existen pruebas de arquitectura entre contextos (§7.1) y si hay protecciones de rama en GitHub.
- El contenido de `frontend/NiceAdmin/` (sin rastrear) y si la guía de `Downloads` es la versión vigente.

Me detengo aquí y espero tu orden.

## Decisiones del responsable

Se copian literalmente las dos órdenes del 23/11/2026; solo cambia el nivel de los encabezados.

### Orden del 23/11/2026, 11:31

#### Cierre de la etapa 0

##### Orden del responsable: 23/11/2026, 11:31 am

Apruebo las recomendaciones de la etapa 0, con estas decisiones:

1. Se llaman **etapas** (0 conciliación, 1 cierre de H7, 2 deuda posterior a H7, 3 front).
2. La guía queda subordinada a los documentos del repositorio; ante conflicto prevalece `docs/README.md` §1.
3. Se retiran los hitos A a D de la guía y se reemplazan por una tabla de destino por criterio (H7, deuda, riesgo aceptado o descartado), según tu informe de conciliación.
4. T06: S1 (a) 429 sin contar el intento; S2 se acepta el título nuevo de AB-20; S3 sí (puerto, ayudante con proceso aparte y CambiarEstado en AB-29); S4 (a) sí, se registra AB-31.
5. c2, RN-13, §3.4, A2 (hallazgo 5) y A3 (paginación de T02) entran en T06b, con la especificación v0.6. T06b incluye A6-10 y A6-16; A6-09 no.
6. Riesgo 3 (`registrado_por`): riesgo aceptado en el cierre de H7; se corrige en la etapa 2.
7. D13 (Docker Compose): tarea corta antes de T09.
8. Se ratifica la práctica de ramas: una rama por tarea y PR a `main`.
9. Trailers: `Co-Authored-By` con el modelo que realmente hizo el commit; `Claude-Session` solo si la sesión puede verificar su enlace.
10. El correo es el nombre de usuario (D10), sin cambios de código.
11. No se crea un H8: la deuda de la etapa 2 entra en la planificación de la Fase 2 al cerrar H7.
12. El inventario de pantallas del front (solo lectura) puede correr en paralelo con H7; la construcción espera a `alcance-fases.md` §7.3.
13. Riesgos aceptados para la lista del cierre de H7: M-3, el 429 de DD-29, D1, D2, D3, A6-06, A6-13, A6-17, A6-18 y la carrera residual de AB-17. A6-14, A6-15 y A6-19 quedan evaluados y sin cambio.

##### Lo que autorizo

**A. Fuera del repositorio**
1. Borrar `C:\Users\LOQ\AppData\Local\Temp\h`. Solo ese archivo.
2. Copiar `C:\Users\LOQ\Downloads\guia-control-gestordep.md` como `guia-control-gestordep-v1.0.md` en la misma carpeta, sin cambiarla.
3. Actualizar `guia-control-gestordep.md` a la **v1.1** con las decisiones de arriba y los cambios de tu §6 (encabezado, §1, §2, §3, §4, §5, §6, §7, §9 y §10). En §10 registra el incidente del archivo temporal. §8 y §11 no cambian.

**B. En el repositorio**
1. Crear la rama `docs/etapa-0-conciliacion` desde `57e434d`.
2. En `docs/decisiones/registro-decisiones.md`, agregar como decisiones del responsable las de los puntos 4 (S1, S2 y AB-31), 6, 8, 9, 10 y 11, con la siguiente numeración libre. Agrega la fila AB-31 en la sección de pendientes. **No toques** las filas existentes; el DA-20 duplicado (líneas 168 y 208) solo se anota en el reporte.
3. Escribir `docs/reportes/etapa-0-conciliacion.md` con tu informe de conciliación tal como lo entregaste, más una sección final «Decisiones del responsable» que copie esta orden.
4. Ejecutar `make verificar` si el entorno lo permite; si no, dilo y no lo fuerces.
5. Un commit local con el mensaje `DOC: cierre de la etapa 0 (conciliación y decisiones)` y los trailers del punto 9.

##### Lo que no autorizo

- Push, PR, fusión ni etiquetas.
- Cambios en `docs/hitos/f1-h7.md` (la v0.6 va con T06b), en `requisitos.md`, `arquitectura.md`, `alcance-fases.md`, `plan-fase-1.md` ni en código o pruebas.
- Corregir el DA-20 duplicado.
- Empezar T06, T06b o cualquier otra tarea.

##### Reglas

- Si la hora de esta orden sigue como `[hh:mm]`, no hagas nada y pídemela.
- Si una decisión choca con un documento de mayor precedencia, detente y pregunta antes de escribir.
- Al terminar entrega: archivos tocados (con ruta), hash del commit, diff resumido del registro, resultado de `make verificar` y lo que no hiciste. Después detente.

### Orden del 23/11/2026, 11:48

Orden: 23/11/2026, 11:48 am

1 (b): autorizo cambiar solo plan-fase-1.md §1.2 (línea 32) y §4.11 (línea 100) para reflejar una rama por tarea y un PR a main, en el mismo commit. Ratifico varios commits por tarea, todos con el formato F1-HN-TNN; regístralo junto con la decisión 8 y anota que f1-h7.md §0 se ajusta en la v0.6.

2 (a): registra S2 y AB-31 con la nota «se incorporan a f1-h7.md en la v0.6». La v0.6 será la primera tarea de la etapa 1, antes de corregir T06, e incluirá S2, AB-31, c2, RN-13, §3.4, T06b y el cambio de §0 sobre commits.

Sin make verificar: aceptado; lo verificará el CI en el PR. Trailers: solo Co-Authored-By: Claude Opus 5.5, como propones.

Ejecuta A1 a A3 y B1 a B5 en ese orden, entrega el reporte y detente.

### Anotación pedida por la orden (punto B2)

El registro tiene DA-20 duplicado: en `57e434d` aparece en la línea 168 (sección 5, H3) y en la línea 208 (sección 6, H4). Por esta orden no se corrige. Con la fila AB-31 agregada en la sección 2, esas filas pasan a las líneas 169 y 209.

Las citas de líneas del informe se refieren a `57e434d`. En el registro, las que están después de la línea 111 se desplazan una línea por la fila AB-31: 191 pasa a 192, 258 a 259 y 386 a 387. Las líneas 32 y 100 de `docs/plan-fase-1.md` ya muestran el texto de DA-130.
