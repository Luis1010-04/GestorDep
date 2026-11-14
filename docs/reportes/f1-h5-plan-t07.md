# Plan de F1-H5-T07: advertencias de jugador lesionado (HU-11 c2, HU-12 c3)

**Fecha:** 11 de noviembre de 2026
**Rama:** `hito/f1-h5` · **Especificación:** `docs/hitos/f1-h5.md` v1.0.8, §3.3 (DA-15), §7.7 y §8 (T07) · **Convención:** `docs/convenciones-api.md` §4 v1.3
**Estado:** **aprobado por Luis el 11/11/2026**, con DA-66 y DA-67 aprobadas. DA-15 ya estaba aprobada por el responsable (v1.0.2). T07 empieza con este plan.

## 1. Contrato

Dos rutas existentes cambian. Sus respuestas exitosas no cambian; solo agregan `advertencias` cuando hay un jugador lesionado.

| Ruta | Cuándo advierte | Advertencia | Código |
|---|---|---|---|
| `POST /entrenamientos/{entrenamiento}/asistencias` | Por cada jugador de `registros` con estado oficial `lesionado` | `jugador_lesionado`, `campo = registros.N.jugador_id` | 201 (sin cambio) |
| `POST /partidos/{partido}/convocatoria` | Si el jugador convocado tiene estado oficial `lesionado` | `jugador_lesionado`, `campo = jugador_id` | 201 (sin cambio); la convocatoria se guarda igual |

Sin advertencia: `apto`, `con_molestia`, jugador sin registro de estado y jugador dado de alta. Sin `advertencias` en la respuesta cuando no hay ninguna, como hoy.

Mensaje: texto fijo de `lang/es/advertencias.php`, sin datos del jugador (`docs/convenciones-api.md` §4). Texto aprobado en DA-66.

## 2. Reglas que se reutilizan

- **Estado oficial:** el puerto `EstadoDeDisponibilidadOficial` de T04 y su adaptador en Salud. Competencia no lee tablas de Salud.
- **Una sola consulta por petición:** el puerto recibe la lista y devuelve el estado de todos. Para la asistencia, se pide una vez con todos los `registros`.
- **Estado vigente en el momento de la petición** (DA-15), no el del día del entrenamiento o del partido.
- **Solo `lesionado` advierte;** `con_molestia` no (DA-15).
- **Orden de comprobación:** la advertencia se calcula después de guardar, sobre jugadores que ya pasaron la validación de equipo. Por eso una petición rechazada no genera advertencias.
- **Índices de `campo`:** `registros.N` usa la posición en la petición, desde 0, como en DA-15. La validación `distinct` de `registros.*.jugador_id` impide duplicados.

## 3. Decisiones que necesito

| Propuesta | Vacío | Recomendación |
|---|---|---|
| **DA-66.** Texto del mensaje (**aprobada**) | El plan y la especificación no fijan el texto de `jugador_lesionado` | «El jugador no está disponible por una lesión.» Sin nombre ni matrícula, y sin detalles de la lesión. **Aprobada** |
| **DA-67.** Corregir una asistencia (**aprobada**) | HU-11 c2 habla de «registrar» la asistencia y no dice nada de corregirla | La corrección no advierte en H5. La advertencia es para la captura. **Aprobada** |

## 4. Pruebas primero

| Archivo | Cubre | Falla esperada |
|---|---|---|
| `tests/Unit/Competencia/AdvertenciasDeLesionTest.php` | La clase nueva: índices de `campo` en orden de la petición, solo `lesionado`, lista vacía sin advertencias, y la consulta única al puerto | Clase no encontrada |
| `tests/Feature/Competencia/AdvertenciasDeLesionTest.php` | §7.7 filas 1 a 7 (una prueba por fila), DA-66 (mensaje sin datos del jugador) y DA-67 (la corrección no advierte) | Sin `advertencias`, o clase no encontrada |
| `tests/Feature/Competencia/ContratoDeAdvertenciasTest.php` | El campo `campo` de la advertencia de asistencia y de convocatoria, validado contra `docs/api/openapi.yaml` | Esquema sin `campo`, o respuesta sin advertencia |
| Pruebas de contrato de H2 y H3 | Sin cambios en sus aserciones. Una respuesta sin jugador lesionado es idéntica a la de H3 | Deben seguir en verde sin tocarlas |

**Lección de T05 y T06:** la lógica nueva va en una clase de `Aplicacion`, con pruebas en `Unit`, porque Infection solo corre esa suite.

## 5. Archivos de producción

- **Nuevo:** `src/Competencia/Aplicacion/AdvertenciasDeLesion.php`. Recibe el puerto y devuelve las advertencias con `codigo` y `campo`. No sabe de HTTP.
- **Controladores:** `AsistenciaDeEntrenamientoController::crear` y `ConvocatoriaController::crear`. Llaman a la clase nueva después de guardar y pasan las advertencias a `Respuesta::conDatos`.
- **`app/Http/Respuestas/Respuesta.php`:** el tipo del parámetro `$advertencias` admite `campo` opcional. El cambio es solo documental y de tipo.
- **`lang/es/advertencias.php`:** clave `jugador_lesionado` (DA-66).
- **`docs/api/openapi.yaml`:** confirmar que el esquema de advertencia ya tiene `campo` opcional en asistencias y convocatoria. Si falta, se agrega. No se cambian las demás rutas.

Sin migraciones, sin cambios en Salud ni en el puerto.

## 6. Cierre

- **Corridas, en serie:** PHPStan tras editar pruebas, `make verificar`, `make mutacion` (la clase nueva en `Unit`), `make fresh` y `make humo`.
- **Logs:** los que respaldan el reporte, en `docs/reportes/logs/`; los demás, en `backend/build/`. Ninguno se borra.
- **Tiempo:** aviso a los 13 minutos y alto a los 15, como en T06. La corrida de T06 quedó en 733 s.
- **Reporte:** `docs/reportes/f1-h5-t07.md`, con las desviaciones.
- **Commit:** `F1-H5-T07: advertencias de jugador lesionado`, local, sin push. Atribución como en T06: `Co-Authored-By` del modelo que trabaja; sin `Claude-Session` si la sesión es local.

## 7. Riesgos

- **Romper H2 o H3:** mitigado con las pruebas de contrato existentes, que no se tocan, y con la prueba de respuesta idéntica sin lesionados.
- **Consulta extra por petición:** una sola consulta al puerto por petición. Sin consultas por cada jugador.
- **Estado cambiado entre la captura y la lectura:** el estado que cuenta es el de la petición. Si cambia después, la advertencia ya no se repite; es lo que dice DA-15.
- **Corrección de asistencia sin advertencia:** decidida en DA-67.
