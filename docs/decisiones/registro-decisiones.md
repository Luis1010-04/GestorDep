# Registro de decisiones tomadas por defecto tras la auditoría

**Fecha:** 19 de octubre de 2026 (DD-19 a DD-23 agregadas el 21 de octubre de 2026, versión 1.2 de la documentación; DD-24 a DD-27, decisiones técnicas de H0, el 21 de octubre de 2026; DD-28 a DD-41, AB-10 a AB-21 y PT-01 a PT-04, al cerrar H1, el 26 de octubre de 2026; DD-42 a DD-55, AB-22 a AB-27 y la sección 4, al cerrar H2, el 28 de octubre de 2026)
**Estado:** Pendiente de validación. Cambios del 26 de octubre de 2026 (F1-H1-T17): se registran DD-28 a DD-41 (decisiones de H1), las dudas abiertas de H1 (AB-10 a AB-21) y los pendientes técnicos posteriores a H1 (sección 3, PT-01 a PT-04). Cambios del 28 de octubre de 2026 (F1-H2-T15): se registran DD-42 a DD-55 (decisiones de H2), las validaciones pendientes con la institución y las dudas de H2 (AB-22 a AB-27) y las decisiones del agente en H2 (sección 4)

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
| DD-07 | Una temporada es un intervalo de fechas de un catálogo; el partido pertenece a la que contiene su fecha | Req 1.5, RN-09; Arq 7.5 | H3 | OM-05 |
| DD-08 | El docente no ve el riesgo deportivo; del lado deportivo ve solo el estado oficial de disponibilidad | Req RN-13, sección 7; Arq 10 | H6 | OM-07, DA-05 |
| DD-09 | La calificación general de partido es un entero de 1 a 10 | Req RN-06; Arq 7.6 | H4 | DA-08 |
| DD-10 | "Datos insuficientes" aparece con menos de N calificaciones y no activa alerta | Req RN-08, RN-10; Arq 7.6 | H4 | OM-05 |
| DD-11 | El riesgo académico se calcula sobre el periodo escolar vigente | Req RN-04; Arq 7.4 | H2 | OM-04 |
| DD-12 | "Mejor desempeño" es el promedio de las últimas N calificaciones generales; sin datos, al final | Req RN-18, 1.5; Arq 4.1 | H6 | OM-06 |
| DD-13 | Dar de baja a un jugador exige motivo y fecha, y lo saca de listados, convocatorias y alertas | Req RN-23, HU-29; Arq 7.3 | H1 | OM-03 |
| DD-14 | La Fase 1 es solo API; RNF-05 (captura en menos de 2 minutos) se verifica en la fase del frontend y RNF-07 en el despliegue | Req RNF-05, RNF-07; Arq ADR-13 | H0 | OM-09 |
| DD-15 | Un dato se marca como desactualizado tras 30 días sin captura (parámetro configurable); zona de presentación America/Mexico_City | Req RNF-04; Arq 7.1 y 7.9 | H6 | OM-08 |
| DD-16 | Seguridad de acceso: contraseña de al menos 10 caracteres, bloqueo de 15 minutos tras 5 intentos fallidos, sesión de 120 minutos de inactividad | Req RNF-10; Arq ADR-14 | H1 | OM-10 |
| DD-17 | Entre entrenadores de un mismo equipo se aplica la regla estricta de RN-14 (cada uno ve solo lo que registró) | Req RN-22; Arq 13 | H3 | Arq 13 |
| DD-18 | Un partido no incluye la categoría como campo propio (se toma del equipo) y el entrenamiento incluye hora de fin y responsable | Req HU-10, HU-12; Arq 7.5 | H3 | DA-06 |
| DD-19 | La ventana de 30 días de RN-09 es móvil y termina en la fecha de cálculo, inclusive; cuando las faltas salen de la ventana o termina la temporada, la acumulación deja de estar activa. *Validar con la coordinación deportiva* | Req RN-09; Arq 11; Pruebas RN-09 | H6 | v1.2 §3.1 |
| DD-20 | El promedio de RN-04 se redondea a dos decimales al medio hacia arriba (7.495 se vuelve 7.50). *Validar con la coordinación académica* | Req RN-04; Arq 7.1; Pruebas RN-04 | H2 | v1.2 §3.2 |
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
| DD-45 | Periodo vigente: el no eliminado cuyo rango contiene el día de negocio; con varios, el de `fecha_inicio` más reciente (desempate por `id`); sin ninguno, la situación responde con periodo y valores nulos | Req RN-04; H2 §3 | H2 | F1-H2 |
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

## 2. Decisiones que siguen abiertas

Las debe tomar el responsable del proyecto o la institución. Ninguna se ha asumido.

| ID | Pregunta | Quién decide | Antes de |
|---|---|---|---|
| AB-01 | Quién valida los valores marcados [Propuesta] (calificación mínima 7.0, niveles de riesgo, sanciones, umbral 6.0 con ventana de 5, escala de partido) | Institución (sugerido: coordinación académica y deportiva) | H2 (académicos) y H4 (deportivos) |
| AB-02 | Cifra estimada de futbolistas para dimensionar | Institución | H7 |
| AB-03 | Plazo de conservación del historial tras la baja de un jugador | Institución | H7 |
| AB-04 | Respaldos y recuperación de la base (frecuencia y retención) | Responsable del proyecto | Antes de producción |
| AB-05 | Librería de validación contra OpenAPI. **Resuelta en DD-24** | Responsable, a propuesta del agente | T09 de H0 |
| AB-06 | Si habrá aplicación móvil (cambia el modo de Sanctum). **Resuelta el 21/10/2026: no habrá aplicación móvil; Sanctum en modo SPA con cookies** | Responsable | H1 |
| AB-07 | Si existe reglamento de elegibilidad para convocar | Institución | H3 |
| AB-08 | Origen de la retícula y quién verifica la carga inicial | Institución | H2 |
| AB-09 | Quién construye el frontend y cuándo | Responsable | Planificación de la Fase 2 |
| AB-10 | Confirmar DD-24 (biblioteca `osteel/openapi-httpfoundation-testing` para validar contra OpenAPI) | Responsable | H2 |
| AB-11 | Validar DD-30 (la baja es global y la decidida por un entrenador afecta al otro) | Institución | Sin hito fijado (antes de que se use la baja en convocatorias, H3) |
| AB-12 | Validar DD-40 (un expediente en baja se puede seguir actualizando y cambiar de posición) | Institución | Sin hito fijado |
| AB-13 | Validar DD-19 (ventana móvil de 30 días de RN-09) con la coordinación deportiva | Institución | H6 |
| AB-14 | Validar DD-20 (redondeo del promedio de RN-04 al medio hacia arriba) con la coordinación académica | Institución | H2 |
| AB-15 | Aceptación escrita del cliente de que la Fase 1 es solo API (DD-14) | Responsable, con el cliente | H3 |
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
| AB-27 | Confirmar DA-15 (la situación académica lista solo las materias del docente del periodo vigente) | Responsable | Antes de H6 |

## 3. Pendientes técnicos tras H1

Anotados al cerrar H1; ninguno bloquea el hito. Los decide el responsable.

| ID | Pendiente | Antes de (sugerido) |
|---|---|---|
| PT-01 | Las acciones de GitHub Actions emiten advertencias de obsolescencia: `actions/checkout@v4` corre en Node 20 y `ubuntu-latest` cambia de imagen. Actualizar las versiones en `.github/workflows/verificar.yml` | H2 |
| PT-02 | `bin/verificar-cobertura.php` debe rechazar un `clover.xml` anterior a la ejecución de las pruebas. Se vio que leía uno antiguo cuando Pest falló por falta de memoria y el guion evaluó un reporte viejo | H2 |
| PT-03 | Un arranque con el servicio `db` en estado "unhealthy", visto una sola vez con un volumen nuevo. No se reprodujo; vigilar y, si reaparece, revisar el `healthcheck` y los tiempos de `docker-compose.yml` | H2 |
| PT-04 | `usuarios.actualizado_en` y `bloqueado_hasta` no salen de `Reloj` (usan `now()` de la base o microsegundos), a diferencia del resto de las tablas tras `F1-H1-FIX`. Es el mismo desfase que anota `docs/hitos/f1-h1.md` §11 | H7 |

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
| DA-14 | El docente dueño sigue viendo calificaciones y asistencias de un alumno ya en baja | **Sí** (lectura) | Vigente; para revisión del responsable |
| DA-15 | La situación académica lista solo las materias del docente **del periodo vigente**; sin periodo vigente, arreglo vacío | **Sí** (lectura, restrictiva) | **Pendiente de confirmación del responsable** (AB-27) |
