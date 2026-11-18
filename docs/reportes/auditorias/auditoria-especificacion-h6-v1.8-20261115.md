# Auditoría de la especificación de H6, v1.8, antes de T01

**Fecha:** 15 de noviembre de 2026
**Documento auditado:** `docs/hitos/f1-h6.md` v1.8, en `33a9910` (`hito/f1-h6-especificacion`).
**Referencias:** auditorías de la v1.0 y de la v1.1 en esta carpeta.
**Modo:** documental, con lectura del código. La hizo el mismo agente que ejecuta el lote; no es una auditoría independiente.
**Criterio:** el de las auditorías anteriores, más las reglas del modo lote que ordenó el responsable: una ruta no permitida, una dependencia no prevista o un criterio inconsistente bloquean la tarea, y el agente no inventa reglas, valores ni contrato.

## 1. Veredicto

**Hay bloqueantes. El lote se detiene antes de T01**, como ordena el paso 2 del encargo. T00 (DA-101) no depende de ellos y se ejecuta aparte.

Con las reglas del modo lote, de T01 a T09 solo T02 podría ejecutarse completa, y la parte de la tabla de T01. Las demás quedan detenidas por una ruta no permitida, por una aprobación que falta o por depender de una tarea detenida.

| Id | Bloqueante | Tareas que detiene | Expediente |
|---|---|---|---|
| B1 | La reestructura del seeder rompe tres pruebas existentes que T01 no puede tocar | T01 (parámetros) | P-01 |
| B2 | `routes/api.php` no está en las rutas permitidas de ninguna tarea | T04, T05, T06, T07 | P-02 |
| B3 | T03 y T05 necesitan la aprobación explícita de DA-88 corregida | T03, T05 y, por dependencia, T06, T08 y T09 | P-03 |
| B4 | Faltan formas de respuesta del contrato (§6.2) | T04, T05, T07 | P-06 |
| B5 | El PDF del cliente no está en el repositorio | El procedimiento de decisión del lote | P-11 |

## 2. Bloqueantes

### B1. El seeder y las pruebas de Académico

- **Especificación:** T01 autoriza reestructurar `ParametrosInstitucionalesSeeder` para sembrar cada clave por separado (M3) y limita las rutas a `database/migrations/alertas/**`, los dos seeders, `tests/Integration/Alertas/**` y reportes.
- **Código:** `backend/tests/Integration/Academico/MigracionesDeAcademicoTest.php` fija que hay una sola clave:
  - línea 520, «siembra solo riesgo_academico»: espera 1 fila;
  - línea 538, «no pisa un parámetro vigente ya ajustado»: espera 1 fila;
  - línea 549, «DatabaseSeeder siembra el criterio general y el parámetro»: espera 1 fila.
- **Problema:** al sembrar los tres parámetros de DA-100 habrá 4 filas y las tres pruebas fallan. Ese archivo no está en las rutas de T01. Un seeder aparte no lo evita: la tercera prueba cuenta lo que siembra `DatabaseSeeder`.
- **Efecto:** T01 no puede cumplir «el seeder es idempotente y no sobrescribe claves vigentes» con `make verificar` en verde. La tabla y el disparador no dependen de esto.

### B2. Ninguna tarea puede registrar rutas

- **Especificación:** §6.1 define siete rutas nuevas. Las rutas permitidas de T04 a T07 listan `app/Http/**`, `src/Consultas/**`, `docs/api/openapi.yaml` y `tests/**`, pero no `backend/routes/api.php`.
- **Código:** todas las rutas de la API se registran en `backend/routes/api.php`.
- **Problema:** es el hallazgo M3 de las auditorías anteriores, sin corregir. En el modo lote una ruta no permitida bloquea la tarea. Registrar las rutas desde un proveedor de `src/Consultas/` esquivaría la regla y rompería la convención del proyecto.

### B3. DA-88 corregida, sin aprobación explícita

- **Encargo:** T03 y T05 no se ejecutan sin esa aprobación; solo se prepara su plan.
- **Dependencias dentro de la especificación:**
  - T06 devuelve «por cada convocado, la vista de DA-94» (DA-96), que construye T05.
  - T09: los pasos 4 y 12 del humo ejecutan `alertas:recalcular` y esperan `riesgo_baja_deportiva` (T03), y el paso 7 consulta la vista para convocar (T05).
  - T08 recorre «todas las rutas de H6»; sin T04 a T07 no hay rutas que recorrer.

### B4. Formas de respuesta sin definir (M4 de la v1.0, sin cambio)

- §6.2 no define `estado_oficial: {...}`, `situacion_academica`, el detalle de la materia del docente en el listado (HU-23 c3), las respuestas de las dos rutas de gráficos ni cómo viaja «el jugador contra el promedio del equipo» (HU-25 c2).
- T04 y T07 exigen un contrato en `docs/api/openapi.yaml`. Escribirlo obliga a inventar campos, y el encargo prohíbe inventar.

### B5. El PDF del cliente no está en el repositorio

- El procedimiento del lote decide cada pregunta «según el PDF del cliente y `docs/requisitos.md`» y pide citar la sección del PDF.
- No hay ningún PDF en el repositorio. Lo único disponible es el mapa de `docs/requisitos.md` §11 y de `docs/alcance-fases.md` §3, que resume los puntos del PDF.
- Los expedientes citan esos mapas y marcan «sin respuesta» lo que dependería del texto del PDF.

## 3. Mayores

| Id | Hallazgo | Tarea | Expediente |
|---|---|---|---|
| M2 | §5.3 da al docente el indicador de aviso pendiente sin límite; `docs/requisitos.md` §7 dice «solo el que creó» | T04 | P-04 |
| M5 | `filtro_invalido` frente a `validacion_fallida`, la convención vigente; id de filtro válido pero ajeno, sin regla | T04 | P-05 |
| M6 | Humo: el paso 3 no es un paso (hacen falta 5 partidos con rendimiento); el paso 6 espera un aviso que nadie crea; faltan los filtros y la alerta académica activa y cerrada | T09 | P-07 |
| M7 | Casos sin regla: sin evaluación o sin umbral aplicable (DA-92); sin temporada que contenga la fecha de cálculo (DA-91); alertas abiertas de un jugador en baja (DA-99) | T02, T03 | P-08 |
| N1 | Dos escuchas en cola sobre el mismo evento, sin orden: la alerta académica puede decidir con el resumen anterior | T03 | P-09 |
| N2 (nuevo) | DA-102 y DA-103 no dicen qué recibe el Dominio: T02 debe saber si le llega una evaluación por entrenador y un conteo de tarjetas ya reducido por partido, o los registros crudos | T02, T03 | P-10 |
| N3 (nuevo) | §7.4 (RN-10) no tiene fila para dos entrenadores con resultados distintos, y §7.3 (RN-09) no tiene fila para DA-103; la fila de DA-103 quedó en §7.6, que es de visibilidad | T02, T04 | P-10 |

## 4. Menores

- **m9.** §4.1 copia RN-09 («con los datos que registró su entrenador») y RN-10 («con el umbral del entrenador de ese equipo») en singular. RN-22 ya tiene la excepción de DA-102, pero RN-09 y RN-10 no la mencionan.
- **m10.** §10, punto 9: «DA-87 a DA-100 registradas». Faltan DA-101 a DA-103, que ya están en el registro.
- **m11.** El encabezado dice «Borrador» y que las DA de §3.3 se registran «al aprobar esta versión»; T09 las registra al cierre. No queda dicho cuáles de DA-87 a DA-100 están aprobadas.
- Siguen abiertos n2 a n4 de la v1.1 y m1 a m8 de la v1.0.

## 5. Revisado sin hallazgos

- DA-101: su plan (`docs/reportes/f1-h6-plan-da101.md`) y sus rutas no dependen de ningún bloqueante.
- `docs/requisitos.md` v1.2.3 (RN-22), DD-17 y `docs/arquitectura.md` v1.5.3 (§7.6, §10 y §13) dicen lo mismo que DA-102 y DA-103.
- `database/migrations/alertas/` no existe todavía; el grupo 080 de `docs/arquitectura.md` §8.2 está libre para `alertas`.
- `backend/src/Alertas/` solo tiene el proveedor; `Dominio/` y `Aplicacion/` están vacíos, como supone T02.

## 6. No revisado

- `docs/api/openapi.yaml` ruta por ruta.
- El texto del PDF del cliente.
- `docs/estrategia-pruebas.md` y `docs/trazabilidad.md` frente a DA-102 y DA-103.
