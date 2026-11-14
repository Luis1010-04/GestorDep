# Reporte de cierre del hito F1-H5: Condición física y Salud

**Fecha:** 13 de noviembre de 2026 · **Rama:** `hito/f1-h5` (desde `main`, `a34283e`) · **Especificación:** `docs/hitos/f1-h5.md` v1.0.9
**Estado:** construcción terminada (T00 a T09, T03b y C01). La auditoría independiente de cierre, de solo lectura sobre `39cc400`, está hecha (sección 10). Falta la revisión del responsable (punto 11). Los commits de T09 están en `origin/hito/f1-h5` hasta `39cc400`; las correcciones de la auditoría de cierre (sección 10) son locales. Sin PR ni etiqueta.

## 1. Resumen

Por la API ya se puede:

- Crear avisos de molestia o lesión como entrenador o docente; quedan pendientes y no cambian el estado oficial (HU-19, RN-16).
- Confirmar un aviso como estado oficial (solo entrenadores), con lesión si no es `apto`; quien confirma queda como único registrante (HU-20, DD-63). Confirmar `apto` cierra la lesión propia del estado vigente (DA-76).
- Ver el mismo estado y la misma fecha estimada desde todos los entrenadores del jugador, con el detalle de la lesión solo para quien la registró (HU-21, RN-15).
- Cambiar el estado o la fecha, dar de alta y descartar avisos (HU-30).
- Registrar mediciones con IMC calculado y pruebas físicas, con corrección explícita y rangos leídos de tablas (HU-17, HU-18, RNF-09).
- Recibir la advertencia `jugador_lesionado` en la asistencia y la convocatoria (HU-11 c2, HU-12 c3).

T09 cerró el hito con el humo de H5 y los pendientes de la auditoría de estado del 13/11/2026 (`docs/reportes/auditorias/auditoria-estado-h5-20261113.md`).

## 2. Resultado de cada punto del plan de T09

Plan: `f1-h5-plan-t09.md`, aprobado por Luis el 13/11/2026 (alcance completo).

| Punto | Commit | Resultado |
|---|---|---|
| Paso 0: push de los documentos | — | `e51cb46` subido con avance rápido. Actions #91 (id 43500058870): **falla** en «Verificar»; causa probable, el aviso de `composer audit` (sección 4) |
| Plan | `a868503` | Hecho |
| Especificación v1.0.9 | `5ccf340` | `rango_no_configurado` en §6.3 (DA-70); DA-76 en §3.4 y §3.5; control de cambios |
| OpenAPI para DA-76 | `649eb87` | Solo la descripción de la confirmación. Las cuatro pruebas de contrato de H5 que había entonces (una por archivo: `ContratoDeAvisosTest`, `ContratoDeEstadoYLesionTest`, `ContratoDeMedicionesTest` y `ContratoDePruebasFisicasTest`) siguen en verde |
| Respaldo 409 en lesiones (DA-79) | `43ce232` | DA-79 cubría el descarte y la confirmación de avisos; T09 extiende su respaldo a lesiones: `RepositorioDeLesionesSql::guardarCambio` convierte el choque de índice único en `EstadoConcurrente` (409 `conflicto`). Lo usan la corrección (`PATCH /lesiones/{lesion}`), el alta y el cierre de DA-76. La prueba (disparador en `lesiones_historial` durante `PATCH /lesiones/{lesion}`) falló primero con «Expected response status code [409] but received 500.» y ahora pasa |
| Contrato de `conflicto` y `rango_no_configurado` | `fc0178d` | Dos pruebas nuevas validan las respuestas contra OpenAPI. Pasaron a la primera: el comportamiento y el contrato ya existían, y lo que faltaba era validarlos juntos |
| DA-33 | `e660649` | Con el choque activo, fechas inválidas dan 422 `rango_de_fechas_invalido`; con fechas válidas, 409 `conflicto`. Pasa a la primera porque fija un orden existente; la mutación Z1 (escribir antes de validar las fechas) la rompe con 409 en lugar de 422 |
| RN-22 | `07682c7` | E3, del mismo equipo A que E1, no ve sus mediciones ni sus pruebas y registra las suyas sin chocar. Pasan a la primera; las mutaciones R1 y R2 (sin el filtro por registrante) las rompen |
| Humo de H5 | `f60698e` | `humo-h5.sh` con los 18 pasos de §9, la línea en `make humo` y cuatro pruebas en `GuionDeHumoTest`, que fallaron con salida 127 sin el guion |
| Actualización por CVE-2026-128083 | `73b7bd0` | Ver sección 4 |
| Reporte de cierre | Este commit | Progreso §21, trazabilidad final y este reporte |

Las mutaciones Z1, R1 y R2 se hicieron en una copia temporal dentro del contenedor, con controles antes y después; la copia se borró. Logs en `docs/reportes/logs/t09/`.

## 3. Corridas de cierre (sobre `73b7bd0`)

### 3.1 `make verificar`

En serie, sin nada más corriendo. Log: `docs/reportes/logs/verificar/f1-h5-t09-20261113-1525.log`.

| Etapa | Resultado | Tiempo |
|---|---|---:|
| Pint | Pasa, 852 archivos | Sin medir por separado |
| PHPStan nivel 8 | Sin errores | Sin medir por separado |
| Pest con cobertura | 1 981 pruebas (12 299 aserciones) | 728.6 s (lo informa Pest) |
| Umbrales de cobertura | Dominio 100 %, Aplicación 100 %, Infraestructura y HTTP 99.6 % (umbrales 95, 85 y 70) | Sin medir por separado |
| `composer audit` | Sin avisos | Sin medir por separado |
| **Total** | **Código 0** | **783 s** |

Las etapas que no son Pest suman unos 54 s en esta corrida. La medición por etapas de referencia es la de DA-57 sobre `75459b6` (`f1-h5-medicion-verificar.md`), con cada etapa corrida por separado:

| Etapa | Tiempo en `75459b6` |
|---|---:|
| Pint | 13 s |
| PHPStan | 5 s |
| Pest con cobertura | 718 s (Pest informa 681 s) |
| Umbrales de cobertura | 0 s |
| `composer audit` | 3 s |
| **Total** | **742 s** |

El total de la corrida de cierre queda por debajo del límite de 900 s de la orden.

La corrida anterior, sobre `f60698e` y antes de la actualización, tardó 781 s. Pasaron todas las etapas salvo `composer audit` (log `f1-h5-t09-20261113-1434.log`).

**Límite conocido: tiempo de `make verificar`.** La corrida tras las correcciones de la auditoría (`632ffbd`, sección 10) tardó 830 s y pasó el aviso de 13 min (780 s) de `docs/hitos/f1-h5.md`. En H5 lo pasaron seis corridas: 794 s (`7ba90ac`), 868 s (`4ab4592`), 799 s (`df068a2`), 781 s (`f60698e`, falló en `composer audit`), 783 s (`73b7bd0`) y 830 s (`632ffbd`). Todas quedaron por debajo del alto de 900 s. Las tres primeras están en `f1-h5-progreso.md`, «Corridas»; las otras tres, en los logs `f1-h5-t09-20261113-1434.log`, `f1-h5-t09-20261113-1525.log` y `f1-h5-t09-auditoria-20261113-2149.log`.

### 3.2 `make mutacion`

Código 0. 885 de 885 mutantes eliminados; MSI 100 %, MSI cubierto 100 %, igual que en T08. Infection muta solo `src/*/Dominio` (DD-53; DA-68), y T09 no tocó el dominio, así que no hay regresiones. 184 s. Log: `docs/reportes/logs/mutaciones/f1-h5-t09-infection-20261113-1539.log`.

### 3.3 `make fresh` y `make humo`

Código 0 en las dos. Humo de H0 a H5: 156 comprobaciones OK, que recorren H0 y los 78 pasos numerados de H1 a H5 (14, 16, 16, 14 y 18); un paso puede tener varias comprobaciones. El de H5, sus 18 pasos (`HUMO H5 OK: 18 pasos`). Log: `docs/reportes/logs/humo/f1-h5-t09-20261113-1542.log`. Antes hubo una corrida de prueba del guion, también en verde, con su log en `backend/build/t09/`.

### 3.4 Migración (§10 punto 4)

No se corrió `migrate:rollback` en local en T09. En CI, el paso «Migraciones (fresh y rollback)» pasó en #91 (`e51cb46`). T09 no agregó migraciones.

## 4. Actualización de `laravel/framework` (CVE-2026-128083)

- **Aviso:** PKSA-yxbx-3c9s-cjt9 / CVE-2026-128083, «Laravel: Task Output Injection - Concurrency Process», severidad baja. Afecta a `>=13.0.0,<13.40.0`. Publicado el 13/11/2026 a las 18:54 UTC, durante T09.
- **Versión anterior:** v13.38.0. **Nueva:** v13.42.0, la última 13.x disponible.
- **Alcance:** `composer update laravel/framework` actualizó solo ese paquete; la corrida en seco lo confirmó antes. `composer.json` no cambió (`^13.17` ya la admite); solo `composer.lock`.
- **`composer audit` después:** «No security vulnerability advisories found.» (log `docs/reportes/logs/t09/composer-audit.log`).
- **Efecto en las pruebas:** ninguno. Las mismas 1 981 pasan, y PHPStan y la mutación siguen igual.

## 5. Compuerta de H5 (§10), punto por punto

| Punto | Estado |
|---|---|
| 1. `make verificar` en local y CI, `make mutacion` | En local, pasa (sección 3). En CI, #91 falló antes de la actualización; con los commits de T09 subidos, #92 (`23e2b1d`) y #93 (`39cc400`) pasaron en verde |
| 2. Historias con pruebas; HU-04 c3, HU-11 c2 y HU-12 c3 cerrados | Cumplido (`docs/trazabilidad.md`) |
| 3. OpenAPI con las rutas de §6.1 | Cumplido; contrato de DA-70, DA-79 y DA-76 completado en T09. El 409 `conflicto` de `PATCH /lesiones/{lesion}` quedó documentado y validado contra OpenAPI tras la auditoría de cierre (M1, sección 10) |
| 4. `make fresh` y `migrate:rollback` | `make fresh` en local; rollback en CI (sección 3.4) |
| 5. Tablas de §7, una prueba por fila | Cumplido por prueba propia o por equivalencia (tablas en `docs/trazabilidad.md`) |
| 6. `make humo` de H0 a H5 | Cumplido |
| 7. Criterios propios de H5 | Cumplidos; los recorre el humo de H5 |
| 8. Reporte con los tiempos por etapa | Este documento; solo Pest y el total se midieron en la corrida de cierre |
| 9. DD-56 a DD-63, rangos por validar, AB-28 | Sin cambios en T09 |
| 10. Auditoría independiente del Lote C | Hecha: auditoría de solo lectura de `39cc400` (sección 10) |
| 11. Revisión, etiqueta y fusión | Esperan la orden del responsable |

## 6. Disposición de los 27 pendientes de la auditoría de estado

| # | Pendiente | Disposición | Dónde |
|---|---|---|---|
| 1 | DA-20 a DA-79 en el registro | Cerrado | `9cb63b3`, registro §9 |
| 2 | Trazabilidad de H5 | Cerrado | `9cb63b3` y este commit |
| 3 | Guion de humo de H5 | Cerrado | `f60698e` |
| 4 | Reporte de cierre y auditoría del Lote C | Cerrado: reporte y auditoría de solo lectura de `39cc400` | Este documento, sección 10 |
| 5 | DA-65 en el progreso | Cerrado | Ya estaba correcto |
| 6 | DA-45 y DA-76 | Cerrado | `df068a2` |
| 7 | Lesiones que ninguna ruta cierra | Anotado para H7 | AB-30, con HU-26 |
| 8 | DA-72, desempate por id | Anotado para H7 | DA-72 |
| 9 | Baja simultánea en H5 | Anotado para H7 | AB-29 (`9cb63b3`) |
| 10 | AB-02 y AB-03 | Anotado para H7 | `docs/plan-fase-1.md` §3 |
| 11 | DA-57, Pest en paralelo | Pendiente de decisión | Después de H5; medición en `75459b6` |
| 12 | DA-30, mismo segundo | Anotado para H7 | Nota de DA-30 en el registro: se reabre en H7 solo si aparece un problema real |
| 13 | DA-33, par fechas y `conflicto` | Cerrado | `e660649` |
| 14 | El historial guarda la nota del aviso | Anotado para H7 | HU-26 debe respetar DD-58 |
| 15 | Symfony: proxies y prefijos; `main` con el lock anterior | Anotado para H7 | Y en la fusión |
| 16 | Catálogos sin historial y restauración de rangos | Anotado para H7 | DA-78 |
| 17 | `make levantar` no reconstruye; `make humo` exige `make fresh` | Anotado para H7 | En T09 se levantó con `--no-build` y se corrió `make fresh` antes del humo |
| 18 | Desviación de `Respuesta.php` | Cerrado | `f1-h5-t07.md` |
| 19 | Infection no muta `Aplicacion` | Cerrado | Progreso corregido en `9cb63b3` (DA-68) |
| 20 | DA-42 frente a `requisitos.md` | Pendiente de decisión | F1-DOC posterior |
| 21 | DA-64, idempotencia | Pendiente de decisión | Aplazada a Fase 2; sin dueño en Fase 1 |
| 22 | F1-DOC de arquitectura, rangos [Propuesta], AB-28 | Pendiente de decisión | Después de H5; institución |
| 23 | HU-23 c4, HU-24, HU-25, DD-08, HU-12 c2, `posiblemente_desactualizado` | Anotado para H6 | `docs/hitos/f1-h5.md` §11 |
| 24 | HU-26 | Anotado para H7 | `docs/hitos/f1-h5.md` §11 |
| 25 | Rama local `auditoria/f1-h5` | Cerrado | Borrada con `-D` el 13/11/2026, con el diff vacío |
| 26 | Corridas #85 y #86 en cola | Pendiente de decisión | Siguen `queued` el 13/11/2026; no se cancelaron |
| 27 | Lentitud variable del entorno | Pendiente de decisión | Ligada a DA-57 |

Hallazgos de la sección 3 de la auditoría que T09 cerró: M1 (`5ccf340`), M2 (`649eb87`), B6 (`43ce232`) y B7 (`fc0178d`). M3 a M6 y B2 se cerraron en `9cb63b3`. B3 se cerró en parte en `9cb63b3`: las líneas 49 y 103 de `docs/plan-fase-1.md`. La compuerta del plan (§4) sigue sin pedir `make mutacion`, el reporte ni la auditoría; no se cambió porque `docs/plan-fase-1.md` no está en las rutas de T09. B4 se cerró con notas en `e51cb46`. M7 también quedó en parte: `5c8a115` versionó la auditoría de estado, pero las anteriores (AU-09, A-1, B-5 y la de T08) siguen sin documento, con una nota en el registro. B1, la nota de la migración `2026_11_03_060100`, no se toca, por orden. B5, el `enum` de `TipoDePrueba.codigo`, queda como observación para H7 en el registro.

## 7. Desviaciones de T09

- **Sin prueba que lea el `Makefile`.** El `Makefile` no está montado en el contenedor (`docker-compose.yml` monta `backend`, `docs` y `scripts`), y la orden prohíbe cambiar la configuración de Docker. La línea de H5 en `make humo` la comprueba la corrida de `make humo` (sección 3.3).
- **Pruebas que pasan a la primera.** Las de DA-33, RN-22 y contrato fijan un comportamiento existente. Se demostró con mutaciones que pueden fallar (sección 2), salvo las de contrato, que validan respuestas que ya estaban en el contrato.
- **Rutas fuera de §8 de T09.** `docs/hitos/f1-h5.md`, `docs/api/openapi.yaml`, `RepositorioDeLesionesSql.php`, las pruebas de `Feature/Salud`, `Feature/CondicionFisica` e `Integration/Salud` y `backend/composer.lock` se tocaron por la orden de Luis del 13/11/2026 (plan §2) y por la orden de la actualización.

## 8. Lo que no se cerró

- *Superado:* la auditoría independiente del Lote C ya está hecha (sección 10), y los commits de T09 ya están en `origin/hito/f1-h5` hasta `39cc400`, con #92 y #93 en verde.
- Los pendientes marcados «pendiente de decisión» en la sección 6.

## 9. Revisión de la compuerta de cierre (`docs/plan-fase-1.md` §4, v1.2.2; 13/11/2026)

Revisión hecha sobre `23e2b1d`, ya subido. Actions #92 (id 43548615304): **en verde**. Pasaron todos sus pasos, incluidos «Migraciones (fresh y rollback)», «Verificar» y «Mutacion». Esto actualiza lo que dicen las secciones 5 y 8 sobre CI y el rollback.

| Punto | Estado | Evidencia |
|---|---|---|
| 1. `make verificar` en local y CI | Cumple | Local: sección 3.1. CI: #92 |
| 2. Historias con su identificador, criterios diferidos anotados, trazabilidad | Cumple (fila de HU-02 corregida) | Ver 9.1 |
| 3. OpenAPI | Cumple | Pruebas de contrato de H5 en verde en #92 |
| 4. `make fresh` y rollback | Cumple | Local: sección 3.3. CI: «Migraciones (fresh y rollback)» en #92 |
| 5. Tablas de casos | Cumple por equivalencia (DA-80) | Ver 9.2 |
| 6. `make humo` | Cumple | Sección 3.3 |
| 7. Criterios de aceptación | Cumple | Ver 9.3 |
| 8. Reporte de fuera de alcance | Cumple | Reportes de cada tarea y sección 7 |
| 9. Revisión del responsable | Pendiente | Espera a Luis |
| 10. `make mutacion` (desde H6) | No aplica a H5; se corrió igual | Sección 3.2 y paso «Mutacion» de #92 |
| 11. Etiqueta y fusión | Pendiente | Espera la orden de Luis |

### 9.1 Punto 2

- **Historias con su identificador en el nombre.** Las de H5 tienen todas pruebas con su identificador: HU-17 (6), HU-18 (5), HU-19 (6), HU-20 (7), HU-21 (3) y HU-30 (8). Las tres de HU-17, HU-18, HU-20 y HU-30 tienen criterios con su número en el nombre.
  - **HU-19 c3** («el estado oficial no cambia hasta que un entrenador lo confirme») no tiene prueba con «HU-19 c3» en el nombre. Lo cubre `Feature/Salud/AvisosTest.php:479` («DA-26 (§7.1 fila 1)…») y el paso 5 del humo.
  - **HU-21 c2** («no ve tipo, zona ni causa») tampoco. Lo cubren las pruebas RN-15 de `Feature/Salud/ConfirmacionYEstadoTest.php` (líneas 55, 512 y 1008) y el paso 9 del humo.
- **Criterios diferidos.** HU-04 c3 (`ConfirmacionYEstadoTest.php:55`), HU-11 c2 y HU-12 c3 (`Feature/Competencia/AdvertenciasDeLesionTest.php`) tienen prueba con su identificador y figuran así en `docs/trazabilidad.md`. Los que H5 difiere a H6 y H7 (§2.2) están en las filas de la trazabilidad y en §6 de este reporte.
- **Resuelto (13/11/2026):** la fila de HU-02 en `docs/trazabilidad.md`, línea 16, conservaba «**Diferido a H5:** el lado deportivo del docente (estado de disponibilidad, §2.2)». Ahora cita la prueba de H5 que lo cubre: `ConfirmacionYEstadoTest.php:72`, «§7.2 fila 3 (RN-13, DA-10): D1 ve el estado y la fecha, sin lesion_id».

### 9.2 Punto 5

Las tablas de `docs/hitos/f1-h5.md` §7 que copian a `docs/estrategia-pruebas.md`, y las propias del hito, tienen prueba para cada fila:

| Tabla | Filas | Con «§7.N fila M» en el nombre |
|---|---:|---|
| §7.1 Avisos | 12 | Las 12 |
| §7.2 Confirmación y estado | 23 | Las 23 |
| §7.3 RN-16 | 5 | Solo las filas 3 a 5, en una sola prueba (`RegistroRN16Test.php:10`) |
| §7.4 RN-15 | 7 | Solo la fila 3 (`RegistroRN15Test.php:12`) |
| §7.5 Mediciones | 17 | Las 17 |
| §7.6 Pruebas físicas | 13 | Las 13 |
| §7.7 Advertencias | 7 | Las 7 |
| §7.8 Alcance y privacidad | 6 | Solo la fila 1 (`RecorridoDeAlcanceH5Test.php:14`) |

- **Resuelto con DA-80:** las filas restantes de §7.3, §7.4 y §7.8 se cubren por equivalencia, en las tablas de `docs/trazabilidad.md` («Estado al cierre de H5»).
- Eso cumple el punto 5 del plan («las reglas… se demuestran con las tablas de casos»), pero no la letra de `docs/hitos/f1-h5.md` §10 punto 5 («una prueba por fila») ni de `backend/.ai/guidelines/proyecto.md` §10 («cada fila de una tabla de casos es una prueba»).
- Era la pregunta 6 de la auditoría de estado. DA-80 (13/11/2026) acepta la equivalencia para H5, acepta HU-19 c3 y HU-21 c2 sin número en el nombre, y fija la regla para H6.

### 9.3 Punto 7

Criterios de H5 en `docs/criterios-aceptacion-fase-1.md`, línea 32:

| Criterio | Prueba | Humo de H5 |
|---|---|---|
| Un aviso de docente o entrenador queda pendiente y no cambia el estado oficial | `AvisosTest.php:479` (§7.1 fila 1) y las filas de §7.1 que crean avisos | Pasos 4 y 5 |
| Cualquier entrenador del jugador lo confirma y queda como registrante | `ConfirmacionYEstadoTest.php:31` (§7.2 fila 1, HU-20 c1) | Paso 7 |
| El otro entrenador ve estado y fecha estimada, pero no tipo, zona ni causa | `ConfirmacionYEstadoTest.php:55` (§7.2 fila 2), `:512` (DA-18) y `:1008` (RN-15) | Pasos 8 y 9 |
| El IMC se calcula sin captura | `MedicionesTest.php:27` (§7.5 fila 1, HU-17 c1) | Paso 12 |
| La tabla de RN-16 pasa | §7.3, por las pruebas de 9.2 (filas 1 y 2 por equivalencia) | Pasos 7 y 10 |

Se cumple, con la misma salvedad de 9.2 para la tabla de RN-16.

### 9.4 Pendientes que deja esta revisión

1. Resuelto: la fila de HU-02 en `docs/trazabilidad.md` cita la prueba de H5 (punto 2).
2. Resuelto con DA-80: basta la equivalencia en H5 (puntos 5 y 7).
3. La revisión del responsable, la etiqueta y la fusión (puntos 9 y 11). La auditoría independiente del Lote C ya está hecha (sección 10).

**Para la especificación de H6 (DA-80):** una prueba por fila de cada tabla de casos, con el identificador de la fila en el nombre, desde el primer commit del hito. Es un cambio de proceso: queda en DA-80 y todavía no se agrega a `docs/plan-fase-1.md`.

## 10. Auditoría independiente de cierre (`39cc400`, 13/11/2026)

Auditoría de solo lectura sobre `39cc400`. Cuenta como la auditoría independiente del Lote C (§10 punto 10 de la especificación) y cierra el pendiente 4 de la sección 6. Su texto se versionará en `docs/reportes/auditorias/` cuando Luis lo entregue: **pendiente el texto**.

| Hallazgo | Disposición |
|---|---|
| M1. `PATCH /lesiones/{lesion}` respondía 409 `conflicto` sin documentarlo en OpenAPI | Cerrado en `e3f8c2d`: 409 documentado y validado contra OpenAPI en `ContratoDeEstadoYLesionTest`. Antes de documentarlo, la prueba falló con «OpenAPI spec contains no such operation [/lesiones/{lesion},patch,409]»; evidencia del antes: `docs/reportes/logs/t09/contrato-lesion-409-antes.log`, y del después: `contrato-lesion-409-despues.log`, ambos en `e3f8c2d` |
| B3. `CorregirLesion` no nombraba `EstadoConcurrente` en `@throws`, y el comentario de `EstadoConcurrente` solo hablaba del estado vigente | Cerrado en `76c7373`. Solo comentarios |
| B1. `RepositorioDeLesionesSql` solo captura el choque en `guardarCambio` | **Anotado para H7:** captura de cualquier índice único en lesiones, también en `guardarNueva`. No se cambia en H5 |
| Registro: el título de la sección 9 decía «DA-20 a DA-79» y «las 60» | Cerrado: «DA-20 a DA-80», «las 61» y la fila de DA-80, con referencia a `f1-h5-decisiones-del-agente.md` |
| Trazabilidad: la nota de HU-02 seguía diciendo «Diferido a H5»; DA-33 sin hito | Cerrado: nota corregida, y las cinco menciones de DA-33 dicen «de H4» o «de H5», sin renombrar decisiones |
| Este reporte: estado de los commits, DA-79 en lesiones, tiempos por etapa, «156 pasos», «134 pruebas de contrato» y el contrato de lesiones | Cerrado en esta corrección (encabezado y secciones 2, 3, 5, 6 y 8) |

Corridas de la corrección: PHPStan sin errores tras cada cambio de pruebas; `make verificar` completo sobre `632ffbd`: código 0 en 830 s, 1 982 pruebas (log `f1-h5-t09-auditoria-20261113-2149.log`, en `fbd4308`; tiempos en la sección 3.1). No se corrió `make mutacion`: no cambió código de producción, solo comentarios, y el cambio de OpenAPI no afecta a las mutaciones.
