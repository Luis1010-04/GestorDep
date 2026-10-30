# Registro de decisiones tomadas por defecto tras la auditoría

**Fecha:** 19 de octubre de 2026 (DD-19 a DD-23 agregadas el 21 de octubre de 2026, versión 1.2 de la documentación; DD-24 a DD-27, decisiones técnicas de H0, el 21 de octubre de 2026; DD-28 a DD-41, AB-10 a AB-21 y PT-01 a PT-04, al cerrar H1, el 26 de octubre de 2026; DD-42 a DD-55, AB-22 a AB-27 y la sección 4, al cerrar H2, el 28 de octubre de 2026; DA-17 y las confirmaciones de DA-14 y DA-15, el 29 de octubre de 2026; la enmienda a DD-53, PT-08 y la sección 5, al cerrar H3, el 29 de octubre de 2026)
**Estado:** Pendiente de validación. Cambios del 26 de octubre de 2026 (F1-H1-T17): se registran DD-28 a DD-41 (decisiones de H1), las dudas abiertas de H1 (AB-10 a AB-21) y los pendientes técnicos posteriores a H1 (sección 3, PT-01 a PT-04). Cambios del 28 de octubre de 2026 (F1-H2-T15): se registran DD-42 a DD-55 (decisiones de H2), las validaciones pendientes con la institución y las dudas de H2 (AB-22 a AB-27) y las decisiones del agente en H2 (sección 4). Cambios del 28 de octubre de 2026 tras la auditoría del Lote E: DA-16 (sección 4), la referencia de DD-45; los plazos vencidos en H2 de DD-20, AB-01, AB-08, AB-10, AB-14, PT-01 y PT-03; PT-02 resuelto (`b010636`); y PT-07 (lock de `make verificar` y procesos huérfanos), sin implementar. Cambios del 29 de octubre de 2026 (decisiones del responsable sobre la auditoría del Lote E): DA-17 (I-1, sección 4); DA-14 y DA-15 confirmadas (I-2), con lo que se cierra AB-27. Cambios del 29 de octubre de 2026 tras la auditoría corta del rango `1145c77..4fb2fc5`: DA-17 nombra también T10 y anota la frase de `docs/arquitectura.md` §4.3 que la versión 1.4 quitó y la 1.4.1 restituyó. Cambios del 29 de octubre de 2026 (F1-H3-T09): DD-07, DD-17 y DD-18 quedan confirmadas por el responsable (`docs/hitos/f1-h3.md` §3.1); AB-07 y AB-15 siguen por validar; se registra PT-08 (tiempo de `make verificar`), pendiente obligatorio antes de H4, y las decisiones del agente en H3 (sección 5)

La auditoría cruzada dejó seis decisiones que bloqueaban la corrección de los documentos. Para trabajar con lo que se tiene, el equipo de análisis aplicó la **recomendación de la auditoría** en cada una y las registró aquí. Ninguna cuenta como validada por la institución. Cada una indica antes de qué hito debe confirmarse; si se cambia, se corrige el documento afectado y se anota en este registro.

## 1. Decisiones aplicadas por defecto

| ID | Decisión aplicada | Dónde quedó | Validar antes de | Hallazgo |
|---|---|---|---|---|
| DD-01 | Los usuarios los crea el equipo del proyecto con el comando `usuarios:crear` hasta que exista el perfil de administración | Req RN-19, HU-27; Arq 7.2 | H1 | IC-02 |
| DD-02 | Un jugador no asignado se busca por matrícula y devuelve solo nombres, apellidos y matrícula; asignarlo o inscribirlo otorga acceso. Matrícula duplicada responde 409 sin datos | Req RN-20, HU-03, HU-04, HU-06; Arq 10 | H1 | IC-01 |
| DD-03 | Cualquier entrenador del jugador confirma un aviso pendiente y queda como registrante de la lesión (**cambia una decisión previa**: antes solo "el que registró la lesión") | Req RN-16, HU-20; Arq 7.8 y 10 | H5 | IC-03 |
| DD-04 | Categorías, periodos, asignaturas, lugares, torneos, rivales y temporadas son catálogos con alta por quien los usa | Req HU-28, RN-12; Arq 7.3 a 7.5 | H1 (categorías), H2, H3 | OM-01 |
| DD-05 | La posición es de cada equipo (`jugador_equipo.posicion_id`); la categoría se deduce del equipo | Req HU-03, HU-05; Arq 7.3 | H1 | IC-09 |
| DD-06 | La alerta de riesgo de baja académica se activa en el nivel "en riesgo"; "en observación" no genera alerta | Req RN-21; Arq 7.9 | H2 | OM-04 |
| DD-07 | Una temporada es un intervalo de fechas de un catálogo; el partido pertenece a la que contiene su fecha | Req 1.5, RN-09; Arq 7.5 | **Confirmada** por el responsable el 29/10/2026 (`docs/hitos/f1-h3.md` §3.1). Aplicación en H3: el alta de una temporada que se cruza con otra no eliminada responde 409 `temporada_solapada` (DA-05, DA-06) | OM-05 |
| DD-08 | El docente no ve el riesgo deportivo; del lado deportivo ve solo el estado oficial de disponibilidad | Req RN-13, sección 7; Arq 10 | H6 | OM-07, DA-05 |
| DD-09 | La calificación general de partido es un entero de 1 a 10 | Req RN-06; Arq 7.6 | H4 | DA-08 |
| DD-10 | "Datos insuficientes" aparece con menos de N calificaciones y no activa alerta | Req RN-08, RN-10; Arq 7.6 | H4 | OM-05 |
| DD-11 | El riesgo académico se calcula sobre el periodo escolar vigente | Req RN-04; Arq 7.4 | H2 | OM-04 |
| DD-12 | "Mejor desempeño" es el promedio de las últimas N calificaciones generales; sin datos, al final | Req RN-18, 1.5; Arq 4.1 | H6 | OM-06 |
| DD-13 | Dar de baja a un jugador exige motivo y fecha, y lo saca de listados, convocatorias y alertas | Req RN-23, HU-29; Arq 7.3 | H1 | OM-03 |
| DD-14 | La Fase 1 es solo API; RNF-05 (captura en menos de 2 minutos) se verifica en la fase del frontend y RNF-07 en el despliegue | Req RNF-05, RNF-07; Arq ADR-13 | H0 | OM-09 |
| DD-15 | Un dato se marca como desactualizado tras 30 días sin captura (parámetro configurable); zona de presentación America/Mexico_City | Req RNF-04; Arq 7.1 y 7.9 | H6 | OM-08 |
| DD-16 | Seguridad de acceso: contraseña de al menos 10 caracteres, bloqueo de 15 minutos tras 5 intentos fallidos, sesión de 120 minutos de inactividad | Req RNF-10; Arq ADR-14 | H1 | OM-10 |
| DD-17 | Entre entrenadores de un mismo equipo se aplica la regla estricta de RN-14 (cada uno ve solo lo que registró) | Req RN-22; Arq 13 | **Confirmada** por el responsable el 29/10/2026 (`docs/hitos/f1-h3.md` §3.1). Se aplica a los datos con registrante; en H3 no hay ninguno, y la asistencia y las convocatorias las ven todos los entrenadores del equipo (§3.2, decisión del responsable antes de T04) | Arq 13 |
| DD-18 | Un partido no incluye la categoría como campo propio (se toma del equipo) y el entrenamiento incluye hora de fin y responsable | Req HU-10, HU-12; Arq 7.5 | **Confirmada** por el responsable el 29/10/2026 (`docs/hitos/f1-h3.md` §3.1) | DA-06 |
| DD-19 | La ventana de 30 días de RN-09 es móvil y termina en la fecha de cálculo, inclusive; cuando las faltas salen de la ventana o termina la temporada, la acumulación deja de estar activa. *Validar con la coordinación deportiva* | Req RN-09; Arq 11; Pruebas RN-09 | H6 | v1.2 §3.1 |
| DD-20 | El promedio de RN-04 se redondea a dos decimales al medio hacia arriba (7.495 se vuelve 7.50). *Validar con la coordinación académica* | Req RN-04; Arq 7.1; Pruebas RN-04 | **Pendiente**; el plazo H2 venció sin cierre. No bloquea H2 (§3 de H2); sin hito fijado, ver AB-14 y AB-25 | v1.2 §3.2 |
| DD-21 | Técnica: cola `database` con servicio `worker`, y `alertas:recalcular` programado a diario a las 02:00 America/Mexico_City con el servicio `scheduler` | Arq 11; Plan 1.1; Alcance 5; Criterios H0 y H6 | H0 (servicios) y H6 (programación) | v1.2 §3.3 |
| DD-22 | Técnica: los decimales viajan como cadena con dos decimales en el JSON de la API; los enteros, como número | Conv. API §1; Arq 7.1 | H2 | v1.2 §3.4 |
| DD-23 | Técnica: las reglas del agente viven en `backend/.ai/guidelines/proyecto.md`; Laravel Boost genera `backend/CLAUDE.md` y `backend/AGENTS.md`; el `CLAUDE.md` y el `AGENTS.md` de la raíz son punteros escritos a mano | README §2; Plan 1.1 | H0 | v1.2 §3.5 |
| DD-24 | Técnica: la validación de respuestas contra `docs/api/openapi.yaml` usa `osteel/openapi-httpfoundation-testing` (aprobada por el responsable en F1-H0-T09; resuelve AB-05) | `backend/composer.json`; pruebas de contrato | H0 | AB-05 |
| DD-25 | Técnica: la cola `database` usa las tablas `trabajos` y `trabajos_fallidos`, generadas con los comandos de Laravel, en el grupo 000 de migraciones. Sus columnas y su `id` numérico son los que exige el driver de cola; solo el nombre de la tabla está en español | `backend/config/queue.php`; `database/migrations/compartido/`; Arq 8.2 y 11 | H0 | F1-H0-EXTRA |
| DD-26 | Técnica: el almacén de caché por defecto es `file`, sin tabla `cache`; el framework lo usa para sus señales internas (por ejemplo, `queue:restart`). Las migraciones por defecto de Laravel (usuarios, caché y cola) se omitieron en F1-H0-T03 | `backend/config/cache.php` | H0 | F1-H0-EXTRA |
| DD-27 | Técnica: la integración continua crea los roles con el mismo guion que el contenedor de base de datos y ejecuta `migrate:fresh`, `migrate:rollback` y `migrate` antes de `composer verificar` | `.github/workflows/verificar.yml`; Pruebas §3 | H0 | F1-H0-T10 |
| DD-28 | Sesión en la tabla `sessions` (driver `database`), caducidad de 120 minutos de inactividad (`SESSION_LIFETIME=120`). La ruta `GET /sanctum/csrf-cookie` conserva su ruta estándar, fuera de `/api/v1`, y se documenta en OpenAPI | `database/migrations/compartido/` (`sessions`); `docs/api/openapi.yaml`; H1 §3 | H1 | F1-H1 |
| DD-29 | Una cuenta bloqueada (RNF-10) responde **429** con código `cuenta_bloqueada` y la cabecera `Retry-After`. Un usuario inexistente, desactivado o con contraseña incorrecta responde 401 `credenciales_invalidas`, siempre con el mismo cuerpo. Solo se revela el bloqueo después de 5 fallos | `docs/convenciones-api.md` §2; H1 §3 | H1 | F1-H1 |
| DD-30 | La baja (RN-23) es **global**: cambia `estatus_academico` y `estatus_deportivo` del jugador a `baja` y desaparece de los listados de todos sus entrenadores; sus asignaciones no se cierran (RN-17). El detalle sigue respondiendo 200 con estatus `baja` a quien ya lo tenía asignado. Un jugador en baja no se puede asignar a un equipo (409 `jugador_en_baja`). El estatus solo cambia por la baja. *Validar con la institución:* que la baja decidida por un entrenador afecte también al otro | H1 §3; `POST /jugadores/{jugador}/baja` | Institución (sin hito fijado) | F1-H1 |
| DD-31 | Asignar un jugador existente a un equipo se hace **por matrícula** (`POST /equipos/{id}/jugadores`), no por identificador, para no entregar el `id` de un jugador ajeno (RN-14) | H1 §3; `docs/api/openapi.yaml` | H1 | F1-H1 |
| DD-32 | Los eventos `JugadorAsignadoAEquipo` y `JugadorDadoDeBaja` (catálogo de `docs/arquitectura.md` §4.2) no se crean en H1: sin consumidores serían código muerto; `alertas:recalcular` (H6) concilia lo que ocurrió antes | H1 §3 | H6 | F1-H1 |
| DD-33 | El historial de `usuarios` no guarda `contrasena_hash`, `intentos_fallidos` ni `bloqueado_hasta`. Un cambio de contraseña escribe una fila `actualizar` con `datos_anteriores` vacío y `datos_nuevos = {"contrasena_modificada": true}`. Un usuario creado por consola se registra con `usuario_id` nulo | H1 §3; `usuarios_historial` | H1 | F1-H1 |
| DD-34 | En `usuarios_historial` la columna de la entidad se llama `usuario_afectado_id` y `usuario_id` es el autor del cambio (la macro `historial()` de H0 no admite dos columnas con el mismo nombre; error de la especificación v1.0 de H1) | `docs/arquitectura.md`; migración `090100` | H1 | F1-H1 |
| DD-35 | `UsuarioActual::identificador()` puede devolver nulo; el historial de cambios hechos sin sesión (consola) se escribe con `usuario_id` nulo. Los parámetros de acceso (5 intentos, 15 minutos, 10 caracteres) viven en `config/identidad.php`: son parámetros de seguridad (RNF-10), no umbrales ni plantillas de RNF-09 | `backend/config/identidad.php`; H1 §3 | H1 | F1-H1 |
| DD-36 | Las fechas de negocio que calcula el sistema (`desde` de `entrenador_equipo` y `jugador_equipo`) usan el día en America/Mexico_City mediante `HoyDeNegocio` (Compartido); la zona sale de `config('app.zona_presentacion')` (RNF-04) | `src/Compartido/Dominio/HoyDeNegocio.php`; H1 §3 | H1 | F1-H1 |
| DD-37 | La matrícula no distingue mayúsculas: índice único parcial sobre `lower(matricula)` (migración 020800); se guarda recortada y como la escribió el usuario. Toda comparación usa `lower(matricula) = lower(?)` | `database/migrations/plantel/`; `docs/arquitectura.md` | H1 | F1-H1 |
| DD-38 | La búsqueda por matrícula (RN-20) también encuentra jugadores en baja y devuelve solo `nombres`, `apellidos` y `matricula`. Un `filtro[equipo_id]` con formato inválido responde 422 `validacion_fallida`; con un equipo ajeno, lista vacía | H1 §3; `GET /jugadores/busqueda` | H1 | F1-H1 |
| DD-39 | En el historial, `datos_anteriores` y `datos_nuevos` de una acción `actualizar` llevan la instantánea completa de la fila (misma forma que `crear`), no solo los campos cambiados. Excepción: `usuarios_historial` (DD-33) | H1 §3 | H1 | F1-H1 |
| DD-40 | Un jugador en baja **sí** puede cambiar de posición y actualizar su expediente: ninguna regla lo impide y RN-17 conserva sus membresías. `PATCH /jugadores/{id}` rechaza los campos no permitidos (422 `prohibited`); `PATCH /equipos/.../jugadores/...` ignora los desconocidos. *Validar con la institución:* si un expediente en baja debe quedar congelado | H1 §3; `docs/convenciones-api.md` | Institución (sin hito fijado) | F1-H1 |
| DD-41 | En la baja: la `fecha` es la que envía el usuario (no usa `HoyDeNegocio`) y se acepta una fecha futura; el `motivo` se recorta y tiene un tope de 500 caracteres solo en la API; un motivo ausente o en blanco responde `motivo_obligatorio` solo cuando la fecha es válida (si la fecha falla, `validacion_fallida`) | H1 §3; `POST /jugadores/{jugador}/baja` | H1 | F1-H1 |
| DD-42 | La tabla `parametros_institucionales` se crea en H2 (grupo de migraciones 035, carpeta `academico/`), con el seeder `ParametrosInstitucionalesSeeder` que siembra solo la clave `riesgo_academico`. H6 solo crea `alertas` | H2 §3; Arq 7.9 y 11 | H2 | F1-H2 |
| DD-43 | La calificación de una materia es la final si existe; si no, la media de sus parciales, redondeada `HALF_UP` a dos decimales antes de compararla con el criterio. El promedio general es la media de las calificaciones de las materias calificadas, redondeada igual. `dato_incompleto` es verdadero si hay materias sin calificar o ninguna tiene calificación. *Validar con la coordinación académica* | Req RN-02, RN-04; H2 §3 | Institución (no bloquea H2) | F1-H2 |
| DD-44 | Porcentaje de asistencia = (presentes + faltas justificadas) ÷ registros × 100, a dos decimales `HALF_UP`, sumando todas las inscripciones del jugador en el periodo vigente; sin registros, nulo. *Validar con la coordinación académica* | Req RN-05; H2 §3 | Institución (no bloquea H2) | F1-H2 |
| DD-45 | Periodo vigente: el no eliminado cuyo rango contiene el día de negocio; con varios, el de `fecha_inicio` más reciente (con la misma `fecha_inicio`, el de `id` mayor: DA-16, no viene de la especificación); sin ninguno, la situación responde con periodo y valores nulos | Req RN-04; H2 §3; el desempate, DA-16 (sección 4) | H2 | F1-H2 |
| DD-46 | El resumen se recalcula con los eventos `CalificacionRegistrada`, `AsistenciaDeClaseRegistrada`, `CriterioDeMateriaModificado` y `JugadorInscrito` (escucha en cola, `afterCommit`, idempotente) y con `alertas:recalcular`, programado a diario a las 02:00 America/Mexico_City; en H2 el comando solo concilia resúmenes | Arq 11; H2 §3 | H2 | F1-H2 |
| DD-47 | La materia se evalúa con el criterio vigente al calcular; `calificaciones.criterio_id` guarda el vigente al capturar y no cambia después | Req RN-03, HU-08; H2 §3 | H2 | F1-H2 |
| DD-48 | El entrenador ve solo el resumen académico; el docente ve el resumen y el detalle solo de sus propias materias; un usuario con los dos roles recibe ambas cosas (ver DA-15) | Req RN-13, RN-14; Arq 10; H2 §3 | H2 | F1-H2 |
| DD-49 | Periodos escolares y asignaturas los gestionan los docentes; sin el rol docente, 403 (también en el listado) | Req HU-28; H2 §3 | H2 | F1-H2 |
| DD-50 | Solo el docente dueño de la materia captura y corrige calificaciones y asistencias; un recurso ajeno responde 404 idéntico al de uno inexistente, antes de validar el cuerpo | Req RNF-01; H2 §3 y §6.1 | H2 | F1-H2 |
| DD-51 | `numero_parcial` de 1 a 10; `grado` de 1 a 12; los decimales viajan como cadena con hasta dos decimales y un número JSON se rechaza | `docs/convenciones-api.md`; H2 §3 | H2 | F1-H2 |
| DD-52 | Inscribir o capturar calificaciones o asistencias de un jugador en baja responde 409 `jugador_en_baja`; un inscrito que pasa a baja conserva inscripción y resumen y sigue visible para quien ya lo tenía. **Consecuencia anotada (auditoría de C2, M-8):** el 409 al inscribir revela a cualquier docente que una matrícula ajena está en baja; es comportamiento exigido por esta decisión (y equivale al de la asignación de H1 para entrenadores). Se revisa en la seguridad de H7 (AB-26). Ver también DA-12 a DA-14 | Req RN-23; H2 §3 | H2 | F1-H2 |
| DD-53 | PHPStan nivel 7 (nunca se baja) e Infection con MSI ≥ 80 % y MSI de código cubierto ≥ 85 %, solo sobre `src/*/Dominio/` | `docs/estrategia-pruebas.md` §2 y §4; H2 §3 | H2 | F1-H2 |
| DD-54 | `NumeroDecimal` (Compartido, `bcmath`) es el único tipo para promedios, umbrales y porcentajes; nunca `float` | Arq 7.1; H2 §3 | H2 | F1-H2 |
| DD-55 | Calificaciones y asistencias admiten `fecha` opcional (por defecto, hoy de negocio) y no se valida que no sea futura | H2 §2.3 y §3 | H2 | F1-H2 |

**Enmienda a DD-53 (29/10/2026, F1-H3; aprobada por el responsable).**

- **Qué cambia:** Infection deja de correr dentro de `make verificar` (`composer verificar`) y pasa a `make mutacion` (`composer mutacion`). Ese comando corre Infection solo sobre `src/*/Dominio/` y después `bin/verificar-mutacion.php`.
- **Qué no cambia:** los umbrales de DD-53 (MSI ≥ 80 %, MSI de código cubierto ≥ 85 %, ninguno omitido ni con tiempo agotado), PHPStan nivel 7 e `infection.json5`.
- **Cuándo corre:** `make mutacion` al cerrar cada lote, y en la integración continua como paso propio después de `composer verificar`.
- **Motivo:** el tiempo. En H3, `make verificar` llegó a 745 s con Infection (unos 115 s) y Pest (unos 577 s), y la holgura frente al límite de 15 minutos de `docs/hitos/f1-h2.md` §0.4 bajó de 3 minutos.
- **Dónde:** `F1-H3-FIX` y `docs/hitos/f1-h3.md` v1.0.2.

## 2. Decisiones que siguen abiertas

Las debe tomar el responsable del proyecto o la institución. Ninguna se ha asumido.

| ID | Pregunta | Quién decide | Antes de |
|---|---|---|---|
| AB-01 | Quién valida los valores marcados [Propuesta] (calificación mínima 7.0, niveles de riesgo, sanciones, umbral 6.0 con ventana de 5, escala de partido) | Institución (sugerido: coordinación académica y deportiva) | H4 (deportivos). Académicos: **pendiente**; el plazo H2 venció sin cierre. No bloquea H2 (`docs/hitos/f1-h2.md` §3) y sigue en AB-22 a AB-24 |
| AB-02 | Cifra estimada de futbolistas para dimensionar | Institución | H7 |
| AB-03 | Plazo de conservación del historial tras la baja de un jugador | Institución | H7 |
| AB-04 | Respaldos y recuperación de la base (frecuencia y retención) | Responsable del proyecto | Antes de producción |
| AB-05 | Librería de validación contra OpenAPI. **Resuelta en DD-24** | Responsable, a propuesta del agente | T09 de H0 |
| AB-06 | Si habrá aplicación móvil (cambia el modo de Sanctum). **Resuelta el 21/10/2026: no habrá aplicación móvil; Sanctum en modo SPA con cookies** | Responsable | H1 |
| AB-07 | Si existe reglamento de elegibilidad para convocar | Institución | **Por validar**; no bloqueó H3: la convocatoria no valida elegibilidad (RN-18, `docs/hitos/f1-h3.md` §3.3). Sugerido: antes de H6, si la institución lo exige para convocar |
| AB-08 | Origen de la retícula y quién verifica la carga inicial | Institución | **Pendiente**; el plazo H2 venció sin cierre. No bloquea H2 (los docentes dan de alta las asignaturas por la API, §2.3 de H2); sin hito fijado, ver AB-25 |
| AB-09 | Quién construye el frontend y cuándo | Responsable | Planificación de la Fase 2 |
| AB-10 | Confirmar DD-24 (biblioteca `osteel/openapi-httpfoundation-testing` para validar contra OpenAPI) | Responsable | **Pendiente**; el plazo H2 venció sin cierre. La biblioteca se usa en las pruebas de contrato de H2, y DD-24 ya la da por aprobada en F1-H0-T09: el responsable puede cerrarla. Sugerido: cierre de H2 o H3 |
| AB-11 | Validar DD-30 (la baja es global y la decidida por un entrenador afecta al otro) | Institución | Sin hito fijado (antes de que se use la baja en convocatorias, H3) |
| AB-12 | Validar DD-40 (un expediente en baja se puede seguir actualizando y cambiar de posición) | Institución | Sin hito fijado |
| AB-13 | Validar DD-19 (ventana móvil de 30 días de RN-09) con la coordinación deportiva | Institución | H6 |
| AB-14 | Validar DD-20 (redondeo del promedio de RN-04 al medio hacia arriba) con la coordinación académica | Institución | **Pendiente**; el plazo H2 venció sin cierre. No bloquea H2 (§3 de H2: vive en el cálculo con `NumeroDecimal`); sin hito fijado, ver AB-25 |
| AB-15 | Aceptación escrita del cliente de que la Fase 1 es solo API (DD-14) | Responsable, con el cliente | **Por validar**; pendiente con el cliente. No bloqueó la construcción de H3 (`docs/hitos/f1-h3.md` §3.3) |
| AB-16 | Visor de OpenAPI (Swagger UI, Redoc u otro) para que el cliente lea `docs/api/openapi.yaml`: decidir si se agrega y cuál | Responsable | Sin hito fijado |
| AB-17 | Revisión de seguridad: carrera en el bloqueo por intentos (dos fallos simultáneos pueden contar uno; solución probable: incremento atómico en SQL) | Responsable (revisión de seguridad) | H7 |
| AB-18 | Revisión de seguridad: el tiempo de respuesta del inicio de sesión distingue cuentas existentes de inexistentes (no se calcula un hash ficticio) | Responsable (revisión de seguridad) | H7 |
| AB-19 | Revisión de seguridad: una petición sin `Origin` ni `Referer` de un dominio con estado no abre sesión y `regenerate()` falla con 500 en lugar de un error controlado | Responsable (revisión de seguridad) | H7 |
| AB-20 | Revisión de seguridad: asignación y baja simultáneas pueden dejar una membresía vigente de un jugador en baja y romper el 409 `jugador_ya_en_baja`/`jugador_en_baja` (solución probable: `lockForUpdate` sobre el jugador) | Responsable (revisión de seguridad) | H7 |
| AB-21 | Revisión de seguridad: desfase de reloj en `usuarios` (ver PT-04) | Responsable (revisión de seguridad) | H7 |
| AB-22 | Validar RN-01: calificación mínima aprobatoria general de 7.0 (sembrada en `CriterioGeneralSeeder`) | Institución (coordinación académica) | Sin hito fijado; no bloquea H2 |
| AB-23 | Validar RN-04: niveles y umbrales de riesgo académico (sembrados en `parametros_institucionales`, clave `riesgo_academico`) | Institución (coordinación académica) | Sin hito fijado; no bloquea H2 |
| AB-24 | Validar DD-43 (calificación de la materia, promedio y `dato_incompleto`) y DD-44 (fórmula del porcentaje de asistencia) | Institución (coordinación académica) | Sin hito fijado; no bloquea H2 |
| AB-25 | DD-20 (redondeo al medio hacia arriba) y AB-08 (origen de la retícula y quién verifica la carga inicial) siguen pendientes (ver AB-14 y AB-08) | Institución | Sin hito fijado; no bloquea H2 |
| AB-26 | Revisión de seguridad: el 409 `jugador_en_baja` al inscribir revela a un docente que una matrícula ajena está en baja (DD-52, auditoría de C2 M-8); y la captura académica no bloquea la fila del jugador ante una baja simultánea (`docs/hitos/f1-h2.md` §11) | Responsable (revisión de seguridad) | H7 |
| AB-27 | Confirmar DA-15 (la situación académica lista solo las materias del docente del periodo vigente) | Responsable | **Cerrada:** DA-15 confirmada por el responsable el 29/10/2026 (auditoría del Lote E, I-2) |

## 3. Pendientes técnicos tras H1

Anotados al cerrar H1; ninguno bloquea el hito. Los decide el responsable.

| ID | Pendiente | Antes de (sugerido) |
|---|---|---|
| PT-01 | Las acciones de GitHub Actions emiten advertencias de obsolescencia: `actions/checkout@v4` corre en Node 20 y `ubuntu-latest` cambia de imagen. Actualizar las versiones en `.github/workflows/verificar.yml` | **Pendiente**; el plazo H2 venció sin cierre (el flujo sigue en `actions/checkout@v4`). Sugerido: H3 |
| PT-02 | `bin/verificar-cobertura.php` debe rechazar un `clover.xml` anterior a la ejecución de las pruebas. Se vio que leía uno antiguo cuando Pest falló por falta de memoria y el guion evaluó un reporte viejo | **Resuelto** en `b010636` (F1-H2-FIX, 28/10/2026): el guion lee el clover, lo borra antes de validarlo y una corrida que no lo regenere falla con «El reporte Clover no existe» (`docs/reportes/f1-h2-fix-pt02.md`) |
| PT-03 | Un arranque con el servicio `db` en estado "unhealthy", visto una sola vez con un volumen nuevo. No se reprodujo; vigilar y, si reaparece, revisar el `healthcheck` y los tiempos de `docker-compose.yml` | **Pendiente (vigilancia)**; no se reprodujo en H2 (ningún reporte de H2 lo registra). Sin hito fijado |
| PT-04 | `usuarios.actualizado_en` y `bloqueado_hasta` no salen de `Reloj` (usan `now()` de la base o microsegundos), a diferencia del resto de las tablas tras `F1-H1-FIX`. Es el mismo desfase que anota `docs/hitos/f1-h1.md` §11 | H7 |
| PT-07 | Anotado en H2 (28/10/2026, correcciones de la auditoría del Lote E). `make verificar` debe tomar un *lock* exclusivo: dos corridas a la vez comparten la base de pruebas `gestordep_pruebas` y se rompen el esquema entre sí (se vio una corrida contaminada con 570 fallos y 27 min 21 s; `docs/reportes/f1-h2-fix-e-m3.md`). Además, un `composer verificar` huérfano no debe seguir corriendo dentro del contenedor después de cerrarse el cliente de `docker compose exec` (causa probable de esa contaminación). No implementado: decisión pendiente del responsable | Sugerido: H3 |
| PT-08 | Anotado en H3 (29/10/2026). El tiempo de `make verificar` se acerca al límite de 15 min de `docs/hitos/f1-h2.md` §0.4 (aviso del responsable a los 13 min) y crece con cada tarea; Pest se lleva entre el 77 y el 79 % de la corrida con Infection y entre el 90 y el 92 % sin ella (auditoría del Lote C, m-1). Tiempos medidos, todos con código 0 y sin nada más corriendo: con Infection, T05 729 s (Pest 573 s), T06 719 s (Pest 567 s) y T07 745 s (Pest 577 s); sin Infection (enmienda a DD-53), el FIX de la compuerta 681 s (Pest 629 s), T08 696 s (Pest 639 s) y T09 615 s (Pest 555 s, 1 224 pruebas). La enmienda a DD-53 ganó unos 100 s, pero H4 suma pruebas. Hay que decidir cómo mantener la corrida por debajo del límite. No implementado: decisión del responsable | **Pendiente obligatorio antes de H4** |

## 4. Decisiones del agente en H2 (DA)

Decisiones que tomó el agente donde la especificación de H2 callaba (`docs/hitos/f1-h2.md` §0.3). El detalle de cada una (qué faltaba, qué se eligió, por qué y cómo se revierte) está en `docs/reportes/f1-h2-decisiones-del-agente.md`. Se registran aquí las que afectan el comportamiento o la privacidad; DA-01 a DA-07 son de herramientas y configuración (rutas de T01, adaptador de Pest para Infection, `--with-uncovered`, fila 7 de §7.12 en dos niveles, carpeta de `parametros_institucionales`, `Identificador` sin `floor`, `bcmath` en CI).

| ID | Decisión | Privacidad o permisos | Estado |
|---|---|---|---|
| DA-08 | Respuestas a las preguntas del plan de T04: `AmbitoDeCriterio` y `NivelDeRiesgo` como enumeraciones; asistencia como conteos; invariantes de `ParametrosDeRiesgo`; escala 2 en el código; media de calificaciones ya redondeadas; `aprobada()` nula sin calificación | No | Aceptada por el responsable |
| DA-09 | `GET /materias` se ordena por clave de asignatura y `id`, sin parámetro `orden` | No | Vigente |
| DA-10 | Las escrituras de plantel (expediente, baja, posición, asignación) usan solo el alcance de entrenador; un docente-entrenador recibe 404 sobre un jugador que solo enseña | **Sí** (restringe escrituras) | Aprobada por el responsable |
| DA-11 | Matrícula sin expediente al inscribir: 404 `jugador_no_registrado` (§6.3), sin datos de nadie | No | Confirmada por el responsable |
| DA-12 | El texto de `jugador_en_baja` cubre asignar, inscribir y capturar; el código no cambia | No | Vigente |
| DA-13 | Corregir una calificación o una asistencia de un jugador en baja también da 409 | No (restringe escrituras) | Vigente |
| DA-14 | El docente dueño sigue viendo calificaciones y asistencias de un alumno ya en baja | **Sí** (lectura) | Confirmada por el responsable el 29/10/2026 (auditoría del Lote E, I-2) |
| DA-15 | La situación académica lista solo las materias del docente **del periodo vigente**; sin periodo vigente, arreglo vacío | **Sí** (lectura, restrictiva) | Confirmada por el responsable el 29/10/2026 (auditoría del Lote E, I-2; cierra AB-27) |
| DA-16 | Periodo vigente: entre varios con la misma `fecha_inicio`, el de `id` mayor (UUID v7: el creado más recientemente). Completa DD-45; en T05 se omitió como DA (auditoría del Lote E, M-4) | No | Vigente; ninguna prueba fija el desempate |
| DA-17 | La situación académica (`GET /jugadores/{jugador}/situacion-academica`, T14) vive en `src/Academico` (rutas base), no en `src/Consultas`. Usa el dominio de Académico (criterio y calificación de la materia, nivel de riesgo), y Consultas no puede importar dominio de otro contexto. Lo mismo vale para T10: `ConsultaDeInscripcionesSql` (`src/Academico/Infraestructura/`) une `jugadores` y la restringe con `AlcanceDeJugadores::restringir()`. Motivo y detalle: auditoría del Lote E, sección 6, I-1. `docs/arquitectura.md` §4.3 se ajustó en su versión 1.4; esa versión quitó, sin que nadie lo decidiera, la frase «Consultas puede leer las tablas de otros contextos con SQL de solo lectura», que se restituyó en la 1.4.1 (auditoría corta, I-B) | No: la lectura del jugador pasa por `AlcanceDeJugadores::restringir()` | Aceptada por el responsable el 29/10/2026 |

## 5. Decisiones del agente en H3 (DA)

Decisiones que tomó el agente donde la especificación de H3 callaba (`docs/hitos/f1-h3.md` §0, con las reglas de `docs/hitos/f1-h2.md` §0.3). El detalle de cada una (qué faltaba, qué se eligió, por qué y cómo se revierte) está en `docs/reportes/f1-h3-decisiones-del-agente.md`. Se registran DA-01 a DA-34; **DA-27 queda fuera**, porque se retiró en el plan de T05. Las que tocan permisos o privacidad van marcadas en la tercera columna. Todas restringen salvo DA-09, que revela algo: que un lugar está reservado a esa hora, sin decir qué equipo ni quién (auditoría del Lote C, m-8).

| ID | Decisión | Privacidad o permisos | Estado |
|---|---|---|---|
| DA-01 | La columna del autor en los historiales es `usuario_id` (macro de Compartido), no `autor_id` | No | Aprobada por el responsable (Lote A) |
| DA-02 | El resultado de un partido existe si y solo si está `jugado`, y nunca un gol sin el otro (`CHECK`) | No | Aprobada (Lote A) |
| DA-03 | `convocatorias.posicion_id` admite nulo hasta que haya alineación | No | Aprobada (Lote A) |
| DA-04 | Código propio por catálogo duplicado (`lugar_duplicado`, `rival_duplicado`, `temporada_duplicada`, `torneo_duplicado`) | No | Aprobada (Lote A) |
| DA-05 | `temporada_solapada` responde 409 sin detalle por campo | No | Aprobada (Lote A) |
| DA-06 | Dos temporadas se cruzan si comparten un día; el alta va bajo un candado consultivo | No | Aprobada (Lote A) |
| DA-07 | Horario invertido de un entrenamiento: 422 en `termina_en` al crear, `rango_de_fechas_invalido` al modificar | No | Aprobada (Lote A) |
| DA-08 | Código nuevo `entrenamiento_no_programado` (409, sin campo): no se cambian hora, lugar ni responsable fuera de `programado` | No | Aprobada; en la especificación v1.0.1, §6.3 |
| DA-09 | Empalme de lugar: intervalos que se cruzan (tocarse no cuenta), de cualquier equipo, sin cancelados, al crear y al modificar; la advertencia no dice con cuál choca | **Sí** (revela que el lugar está reservado a esa hora, aunque sea por otro equipo, sin decir qué equipo ni quién; no restringe) | Aprobada y registrada por el responsable como decisión de privacidad (Lote A) |
| DA-10 | Los filtros del listado de entrenamientos son días locales de America/Mexico_City sobre `inicia_en` | No | Aprobada (Lote A) |
| DA-11 | Lugar inexistente o eliminado: 422 `lugar_inexistente` | No | Aprobada (Lote A) |
| DA-12 | `JugadoresDelEquipo` pasa por el alcance, exige además la asignación del usuario al equipo y devuelve solo id, nombres y apellidos | **Sí** (no revela que un jugador visible por otro equipo está en este) | Aprobada; en la especificación v1.0.1, §5.3 |
| DA-13 | «Entrenar un equipo» exige asignación vigente, rol `entrenador` y equipo y usuario no eliminados | **Sí** (quién accede al equipo y quién puede ser responsable) | Aprobada; en la especificación v1.0.1, §3.4 y §5.3 |
| DA-14 | El campo de la respuesta es `jugadores_del_equipo` | No | Aprobada; en la especificación v1.0.1, §6.1 y §6.2 |
| DA-15 | Los instantes se aceptan con desplazamiento o con `Z`; sin zona, 422 | No | Aprobada (Lote A) |
| DA-16 | El campo de `jugador_no_pertenece_al_equipo` es `registros.N.jugador_id` | No | Vigente; T04 aceptada |
| DA-17 | La captura de asistencia por lista es todo o nada, con un orden fijo de errores | No | Vigente; T04 aceptada |
| DA-18 | La corrección de una asistencia no exige que el jugador siga en el equipo | No (el registro ya era visible para el equipo) | Vigente; T04 aceptada |
| DA-19 | El listado de asistencias no trae datos del jugador | No (revela lo mínimo) | Vigente; T04 aceptada |
| DA-20 | El porcentaje de un jugador usa el alcance de entrenador: un docente recibe 404 aunque enseñe al jugador; un jugador en baja sigue visible para su entrenador | **Sí** (lectura) | Vigente; T04 aceptada |
| DA-21 | Forma del porcentaje por equipo: el total cuenta todos los registros; `por_jugador` lista solo a los jugadores vigentes del equipo | No | Vigente; T04 aceptada |
| DA-22 | Los conteos (`presentes`, `faltas`, `faltas_justificadas`) acompañan al porcentaje | No | Vigente; T04 aceptada |
| DA-23 | La zona del día local sale de `HoyDeNegocio` | No | Aprobada en el plan de T05 |
| DA-24 | `rival_inexistente` y `torneo_inexistente` (422, sin campo) | No | Aprobada en el plan de T05 |
| DA-25 | Al cambiar fecha, tipo o torneo de un partido se revalida todo; sin cambio de fecha se conserva la temporada aunque esté eliminada | No | Aprobada en el plan de T05 |
| DA-26 | Un filtro de temporada inexistente devuelve una lista vacía | No | Aprobada en el plan de T05 |
| DA-28 | Solo se convoca a un jugador vigente del equipo del partido; si no, 404 igual al de uno inexistente. No es la elegibilidad de AB-07 | **Sí** (quién puede ser convocado) | Vigente; T06 aceptada |
| DA-29 | El listado de la convocatoria no trae datos del jugador | No (revela lo mínimo) | Vigente; T06 aceptada |
| DA-30 | El campo de `jugador_no_convocado` es `alineacion.N.jugador_id` | No | Vigente; sin observación del responsable |
| DA-31 | La posición de la alineación se valida con la clave foránea `fk_convocatorias_posicion_id`; error 422 `posicion_inexistente` | No | Vigente; revisada en el FIX de DA-34 (el `catch` solo convierte esa clave) |
| DA-32 | «Convocado» es la convocatoria visible (sin bajas); la lista vacía quita toda la alineación | **Sí** (un convocado en baja no cuenta) | Corregida por DA-34 en lo que respondía a un jugador fuera del alcance; el resto sigue vigente |
| DA-33 | Orden de los errores del resultado: forma (422), estado (409), rango (422, `goles_favor` antes que `goles_contra`) | No | Vigente; sin observación del responsable |
| DA-34 | Un jugador fuera del alcance de entrenador sin bajas en la alineación da 404 igual al de uno inexistente; el 422 `jugador_no_convocado` queda para un jugador visible no convocado | **Sí** (no distingue «existe y no lo ves» de «no está convocado») | Indicada por el responsable (FIX `478330d`) |
