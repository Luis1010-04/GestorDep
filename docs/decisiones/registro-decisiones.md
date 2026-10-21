# Registro de decisiones tomadas por defecto tras la auditoría

**Fecha:** 19 de octubre de 2026 (DD-19 a DD-23 agregadas el 21 de octubre de 2026, versión 1.2 de la documentación; DD-24 a DD-27, decisiones técnicas de H0, el 21 de octubre de 2026)
**Estado:** Pendiente de validación

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
