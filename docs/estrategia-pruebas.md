# Estrategia de pruebas y evaluación (Fase 1)

**Versión:** 1.2
**Fecha:** 21 de octubre de 2026
**Origen:** sección 6 del Documento de Arranque v1.0, ampliada con pruebas de consola, casos de ejemplo por regla y la corrección de cobertura en H0.

Este documento es una fuente de verdad del agente. Define cómo se demuestra que algo está terminado.

## 1. Principios

1. **Primero la prueba.** Cada criterio de aceptación de una historia se traduce en al menos una prueba antes de escribir el código.
2. **Una sola verdad.** `make verificar` decide si algo está terminado. Si falla, la tarea no está terminada.
3. **PostgreSQL real.** Nunca SQLite, porque el diseño depende de funciones propias de PostgreSQL.
4. **Las reglas se prueban con casos, no con interpretación.** Cada regla de negocio tiene una tabla de casos de ejemplo (sección 5); cada fila es una prueba.
5. **Nunca se debilita una prueba, una regla de análisis ni un umbral para que algo pase.**

## 2. Herramientas

| Qué se verifica | Herramienta | Notas |
|---|---|---|
| Pruebas unitarias, de integración y de API | Pest sobre PHPUnit | PostgreSQL real en contenedor |
| Reglas de arquitectura | Pest Arch | Dominio sin framework, dirección de dependencias, aislamiento entre contextos |
| Análisis estático | Larastan (PHPStan) | Nivel 6 en H0 y H1; nivel 7 de H2 a H4; nivel 8 desde H5. Se sube al abrir el hito y nunca se baja |
| Estilo | Laravel Pint | En modo de comprobación |
| Calidad de las pruebas del dominio | Infection (mutación) | Desde H2, solo sobre `Dominio/` |
| Cobertura | Cobertura de Pest | Umbral por capa (sección 4) |
| Dependencias | `composer audit` | Falla ante vulnerabilidades conocidas |
| Migraciones | `migrate:fresh` y `migrate:rollback` | En CI y en cada compuerta |
| Contrato de la API | Validación de respuestas contra OpenAPI | La librería se decide en T09 de H0, con aprobación |

Todo se agrupa en `make verificar` (en el contenedor: `composer verificar`).

## 3. Niveles de prueba

| Nivel | Qué prueba | Dónde vive | Cómo se ejecuta |
|---|---|---|---|
| Unitarias de dominio | RN-01 a RN-23 sin base de datos | `backend/tests/Unit/` | `make pruebas` |
| Integración | Repositorios, historial en la misma transacción, `CHECK`, índices parciales, migraciones `up` y `down` | `backend/tests/Integration/` | `make pruebas` |
| Funcionales (API) | Cada criterio de aceptación de HU-01 a HU-30 a través de HTTP | `backend/tests/Feature/` | `make pruebas` |
| Arquitectura | Dependencias entre capas y contextos; consultas de jugadores por `AlcanceDeJugadores` | `backend/tests/Arch/` | `make pruebas` |
| Seguridad | Un usuario no accede a jugadores no asignados ni al detalle de lesiones de otro | `backend/tests/Feature/Seguridad/` | `make pruebas` |
| Consola | Comandos de Artisan del proyecto | `backend/tests/Feature/Consola/` | `make pruebas` |
| Humo (consola) | El hito funciona de extremo a extremo contra la aplicación levantada | `scripts/humo/` | `make humo` |

## 4. Umbrales de calidad

| Capa | Cobertura mínima | Desde |
|---|---|---|
| Dominio | 95 % (y pruebas de mutación desde H2) | H1 |
| Aplicación | 85 % | H1 |
| Infraestructura y HTTP | 70 % | H1 |

En H0 la cobertura **se mide y se reporta, pero no bloquea**, porque todavía no hay código de negocio que cubrir. Desde H1 es exigible. Son valores iniciales: se ajustan con experiencia y **nunca se bajan para que una tarea pase**.

## 5. Pruebas de consola

Se llaman "de consola" porque se ejecutan desde la terminal, dentro de Docker, sin interfaz.

### 5.1 Comandos de Artisan del proyecto

Cada comando tiene pruebas Pest que lo invocan con `$this->artisan(...)` y comprueban su salida y su efecto en la base de datos.

| Comando | Hito | Qué debe cumplir |
|---|---|---|
| `usuarios:crear` | H1 | Crea un usuario con uno o dos roles; la contraseña se pide por consola o por opción y se guarda solo como hash; rechaza correo duplicado, rol inexistente y contraseña que no cumple RNF-10; no imprime la contraseña |
| `alertas:recalcular` | H6 | Concilia resúmenes y alertas abiertas; es idempotente (dos ejecuciones dejan el mismo estado); no reescribe `activada_en` ni `cerrada_en` de alertas cerradas ni borra evaluaciones anteriores; cierra las alertas cuya condición venció con el tiempo; una prueba confirma que está programado a diario en el planificador (`arquitectura.md` §11) |

Ningún otro comando se crea sin aprobación.

### 5.2 Guion de humo

Un script por hito en `scripts/humo/humo-hN.sh` (Bash y `curl`, sin dependencias nuevas) que se ejecuta con `make humo` contra la aplicación levantada con `make levantar` y `make fresh`.

- Recorre el flujo principal del hito por HTTP (inicio de sesión con cookie, creación, consulta) y termina con código distinto de cero si alguna respuesta no es la esperada.
- Imprime una línea por paso con `OK` o `FALLA` y el código HTTP recibido.
- No usa datos reales ni credenciales fuera de las del seeder de desarrollo.
- El inicio de sesión sigue el flujo de Sanctum en modo SPA: `GET /sanctum/csrf-cookie`, guardar las cookies en un archivo (`curl -c`/`-b`), enviar el valor de la cookie `XSRF-TOKEN` (decodificado de URL) en la cabecera `X-XSRF-TOKEN`, y enviar `Origin` y `Referer` con un dominio incluido en `SANCTUM_STATEFUL_DOMAINS`. Una función de ayuda en `scripts/humo/lib.sh` lo resuelve una vez para todos los guiones.
- Se escribe en la última tarea del hito y se ejecuta en la compuerta; el responsable puede correrlo para repetir la demostración.

| Hito | El guion de humo comprueba |
|---|---|
| H0 | `GET /api/v1/salud` responde 200 con aplicación y base de datos en `ok` |
| H1 | Inicio de sesión, alta de equipo y jugador, búsqueda por matrícula, asignación y un 404 sobre un jugador ajeno |
| H2 | Alta de materia, inscripción, captura de calificaciones y nivel de riesgo esperado |
| H3 | Programar entrenamiento, registrar asistencia, programar partido, convocar y alinear |
| H4 | Capturar rendimiento por posición y obtener `pasa`, `no_pasa` o `datos_insuficientes` |
| H5 | Registrar medidas, crear aviso, confirmarlo y ver el estado desde el otro entrenador |
| H6 | Alerta académica activa y cerrada, listado con filtros y vista para convocar |
| H7 | Consulta de historial con la visibilidad correcta |

## 6. Nombres de las pruebas

El nombre de cada prueba empieza con el identificador de lo que cubre, para poder buscarla:

- `HU-07: rechaza calificación fuera de rango`
- `RN-04: promedio 7.49 sin materias reprobadas es en_observacion`
- `RNF-01: un entrenador no ve a un jugador no asignado`
- `CONSOLA usuarios:crear: rechaza correo duplicado`

Una prueba que cubre más de una historia o regla lleva todos los identificadores. `docs/trazabilidad.md` relaciona cada HU, RN y RNF con sus pruebas; **una historia sin prueba bloquea la compuerta**.

## 7. Casos de ejemplo por regla

Cada fila es una prueba. Los valores marcados [Propuesta] viven como datos de fábrica (seeders y `parametros_institucionales`); las pruebas los leen de ahí y no los escriben fijos en el código, salvo en los casos de unidad que reciben los parámetros como entrada.

### RN-04 Riesgo académico [Propuesta]

| Promedio | Materias reprobadas | Resultado esperado |
|---|---|---|
| 8.00 | 0 | sin_riesgo |
| 7.50 | 0 | sin_riesgo |
| 7.495 (se redondea a 7.50) | 0 | sin_riesgo |
| 7.49 | 0 | en_observacion |
| 7.00 | 0 | en_observacion |
| 8.50 | 1 | en_observacion |
| 6.99 | 0 | en_riesgo |
| 8.50 | 2 | en_riesgo |
| 7.20 | 2 | en_riesgo |
| 6.50 | 1 | en_riesgo |

Los promedios se calculan con decimales exactos (`arquitectura.md` §7.1), nunca con `float`: con `float`, 7.495 se representa como 7.4949… y se redondearía a 7.49. La fila de 7.495 existe para detectar ese error. El redondeo al medio hacia arriba es **[Decisión por defecto]** (DD-20).

### RN-21 Alerta de riesgo de baja académica

| Nivel de riesgo | Alerta abierta antes | Resultado esperado |
|---|---|---|
| en_riesgo | no | se crea la alerta |
| en_riesgo | sí | no se duplica |
| en_observacion | no | no se crea |
| en_observacion | sí | se cierra (`cerrada_en`) |
| sin_riesgo | sí | se cierra |

### RN-08 Pasa o no pasa [Propuesta: N = 5, umbral 6.0]

| Calificaciones generales (más recientes primero) | Resultado esperado |
|---|---|
| 6, 6, 6, 6, 6 | pasa (el promedio 6.0 alcanza el umbral) |
| 7, 6, 6, 5, 6 | pasa (6.0) |
| 6, 6, 6, 6, 5 | no_pasa (5.8) |
| 9, 9, 9, 9 (solo 4) | datos_insuficientes |
| 5, 5, 5, 5, 5, 9 (seis calificaciones) | no_pasa (solo cuentan las cinco más recientes: promedio 5.0; la más antigua, 9, queda fuera de la ventana) |

### RN-09 Acumulación de sanciones deportivas [Propuesta]

La ventana de faltas es móvil: los 30 días naturales que terminan en la fecha de cálculo, inclusive (de `fecha_calculo − 29 días` a `fecha_calculo`). La fecha de cálculo sale del reloj inyectable, que las pruebas fijan.

| Caso (por jugador y equipo) | Resultado esperado |
|---|---|
| 1 tarjeta roja en la temporada | se activa |
| 2 tarjetas amarillas en la temporada | no se activa |
| 3 tarjetas amarillas en la temporada | se activa |
| 3 faltas en 30 días | se activa |
| 2 faltas y 1 falta justificada en 30 días | no se activa |
| 3 faltas repartidas en 31 días | no se activa |
| 1 tarjeta roja de la temporada anterior | no se activa |
| Alerta abierta por 3 faltas; en el recálculo diario la más antigua ya salió de la ventana | se cierra |
| Alerta abierta por 1 tarjeta roja; el recálculo ocurre después del fin de la temporada | se cierra |

### RN-10 Riesgo de baja deportiva

| Evaluación | Sanciones acumuladas | Resultado esperado |
|---|---|---|
| no_pasa | no | se activa |
| pasa | sí | se activa |
| pasa | no | no se activa |
| datos_insuficientes | no | no se activa |
| datos_insuficientes | sí | se activa |

### RN-18 Orden por desempeño

| Jugadores (promedio de la ventana) | Orden esperado |
|---|---|
| A 8.0, B 6.5, C 7.2 | A, C, B |
| A 7.0, B datos insuficientes, C 5.0 | A, C, B |
| A 7.0, B 7.0 | orden estable; el desempate no se interpreta: se conserva el orden de entrada |

### RN-16 Confirmación de un aviso

| Quién actúa | Acción | Resultado esperado |
|---|---|---|
| Entrenador del jugador | confirmar aviso pendiente | confirmado; queda como registrante |
| Docente | confirmar aviso pendiente | 403 |
| Segundo entrenador, tras la confirmación | modificar el estado | 403 |
| Segundo entrenador, tras la confirmación | pedir el detalle de la lesión | 404 |
| Segundo entrenador, tras la confirmación | ver estado y fecha estimada de regreso | 200 sin tipo, zona ni causa |

### Otras reglas

Las tablas de RN-02, RN-03, RN-06, RN-07, RN-11, RN-13, RN-14 y RN-15 se redactan en la especificación del hito que las usa (`docs/hitos/f1-hN.md`), con el mismo formato.

## 8. Qué hace el agente con las pruebas

- Escribe primero las pruebas, ve que fallan y luego implementa.
- Si una fila de una tabla de casos parece incorrecta, **se detiene y pregunta**; no la corrige ni la omite.
- Si una prueba exige algo que el requisito no dice, se detiene y pregunta.
- No agrega pruebas de funcionalidad que no esté en el alcance de la tarea.
