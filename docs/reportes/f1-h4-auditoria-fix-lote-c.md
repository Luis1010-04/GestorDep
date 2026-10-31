# Auditoría independiente del F1-H4-FIX de la auditoría del Lote C

**Fecha:** 30 de octubre de 2026 · **Rama:** `hito/f1-h4` en `0cccd14` · **Especificación:** `docs/hitos/f1-h4.md` v1.0.8
**Alcance:** el diff `2ac6155..0cccd14` (19 archivos) y lo que depende de él.
**Reglas:** sesión de solo lectura. No se modificó ningún archivo del repositorio salvo este informe, y no hubo commits ni push. Todas las corridas fueron en serie, sin nada más corriendo. Las mutaciones se aplicaron en una copia temporal dentro del contenedor (`/tmp/auditor-copia`), que se borró al terminar. `git status` al terminar muestra solo los dos informes de auditoría sin seguimiento.

## 1. Corridas del auditor

Los logs están en el directorio temporal de esta sesión (`C:\Users\luis_\AppData\Local\Temp\claude\c--laragon-www-GestorDep\e68a7674-79b5-488a-a36d-1a5b9a3e3533\scratchpad\aud-*.txt`, junto con `auditor.sh`, `indices.php` y `x1.php`).

| Corrida | Código | Tiempo | Resultado |
|---|---|---:|---|
| `make levantar` | 0 | 38 s | — |
| `make fresh` | 0 | 8 s | — |
| Índices y restricciones de `rendimientos_partido` (`indices.php`, PDO de solo lectura) | 0 | 0 s | Solo `uq_rendimientos_partido_partido_jugador_registrado_por` (`partido_id, jugador_id, registrado_por`) `WHERE (eliminado_en IS NULL)`; **0** índices con el nombre viejo en la base |
| `migrate:rollback --database=propietario` | 0 | 4 s | — |
| `migrate:status` | 0 | 1 s | 63 `Pending`, 0 `Ran` |
| Índices después del rollback | 0 | 1 s | La tabla no existe; 0 índices viejos; la tabla `migrations` está vacía |
| `make fresh` | 0 | 8 s | — |
| Índices después del segundo fresh | 0 | 1 s | Idénticos a los del primero (el `diff` solo cambia la línea del tiempo) |
| `RendimientoSeguridadTest`, solo, tres veces | 0, 0, 0 | 20, 20, 17 s | **`11 passed (336 assertions)`** las tres veces |
| Pest: `tests/Feature/Rendimiento`, `tests/Integration/Rendimiento`, `GuionDeHumoTest` y `RendimientoSeguridadTest` | 0 | 83 s | `159 passed (1440 assertions)`; incluye todas las pruebas nuevas del FIX |
| Mutación X1 del auditor: captura sin la transacción exterior | — | — | `1 failed, 36 passed (276 assertions)`; falla «HU-16 (M-1 de la auditoría del Lote C)» con `Failed asserting that two arrays are identical` |
| Mutación X5 del auditor: la UQ sin `registrado_por` (con el nombre nuevo) | — | — | `3 failed, 59 passed`: «RN-14 (v1.0.8)» y las dos de RNF-03 de `MigracionesDeRendimientoTest` |
| `make humo` | 0 | 63 s | H0 OK, H1 14 pasos, H2 16, H3 16, **H4 14** |
| `make bajar` | 0 | 15 s | — |

**Nota sobre X1.** La primera versión de mi script (`x1.sh`) tuvo un error de comillas y no aplicó el cambio: esa corrida probó el código sin modificar y dio 37 aprobadas. La repetí con `x1.php`, que muestra el `diff` aplicado (`$this->transaccion->ejecutar(function …` → `(function …)()`). El resultado coincide exactamente con la X1 del constructor.

No ejecuté `make verificar` ni `make mutacion` completos. Las cifras de la suite y de Infection salen de los logs del constructor (§3.11), que sí pude leer.

## 2. Hallazgos

### Bloqueantes

Ninguno.

### Mayores

**M-1. Quedan tres referencias vigentes a DA-13 que describen la regla anterior.**
- **Dónde:**
  - `docs/api/openapi.yaml:3949-3950` (`GET /partidos/pendientes-de-rendimiento`): «al menos un convocado visible (AlcanceDeJugadores, sin bajas) que no tiene **ningun rendimiento capturado (DA-13)**».
  - `backend/src/Consultas/Infraestructura/ConsultaDePendientesDeRendimientoSql.php:17-21`, comentario de la clase: «que no tienen ningún rendimiento no eliminado en el partido: **si otro entrenador ya lo capturó, no se puede capturar de nuevo y no queda pendiente (DA-13)**». El comentario nuevo del método `pendientes` (líneas 60-63) dice lo contrario.
  - `docs/reportes/f1-h4-decisiones-del-agente.md:21`, tabla de resumen: DA-13 sigue como «Un convocado deja de estar pendiente si tiene cualquier rendimiento, de cualquier entrenador | No (no revela de quién) | No». No dice que fue sustituida, y la tabla tampoco tiene fila para DA-36. Sí está marcada en el detalle (línea 142) y en `registro-decisiones.md:198`.
- **Evidencia:** `grep -rn "DA-13"` sobre `docs/`, `backend/src`, `backend/tests` y `scripts`. Las demás coincidencias son DA-13 de H2 o de H3, que son otras decisiones.
- **Impacto:** el contrato publicado describe una regla que el código ya no aplica, y es justo la regla de privacidad que corrigió el FIX. El comportamiento es correcto (§3.2). Lo que falla es el contrato y la documentación, que no cumplen el punto 2 del pedido («no quedan referencias vigentes a DA-13»).

**M-2. El texto de DD-17 respalda la visibilidad, pero no que dos entrenadores capturen al mismo jugador en el mismo partido.**
- **Dónde:** `docs/decisiones/registro-decisiones.md:28` (DD-17), `docs/requisitos.md:101` (RN-14) y `:112` (RN-22). DA-36 está en `f1-h4-decisiones-del-agente.md:269-283`.
- **Evidencia:**
  - DD-17 dice: «Entre entrenadores de un mismo equipo se aplica la regla estricta de RN-14 (cada uno **ve** solo lo que registró)». Su estado sigue diciendo «Se aplica a los datos con registrante; **en H3 no hay ninguno**», sin mencionar el rendimiento de H4.
  - RN-14 y RN-22 hablan de lo que cada entrenador **ve**. Ninguno dice que un jugador pueda tener varios rendimientos en un mismo partido, uno por entrenador. Tampoco HU-14 (`requisitos.md:217-221`).
  - DA-36 es una consecuencia razonable: si la unicidad fuera por partido y jugador, el 409 revelaría la captura ajena, como señaló la auditoría anterior (m-7). Pero es una regla de negocio nueva, porque la cantidad de rendimientos por jugador y partido pasa de uno a uno por entrenador. Su único respaldo escrito es «indicada por el responsable».
  - RN-22 condiciona la regla estricta «hasta que la institución decida otra cosa», y DA-36 no dice que sea provisional.
- **Impacto:** documental y de trazabilidad. El comportamiento es el más restrictivo en privacidad. Afecta a fases posteriores: los gráficos y estadísticas (HU-25) tendrán varios rendimientos del mismo jugador en el mismo partido.
- **Recomendación:** que el responsable lo deje escrito en DD-17, o en una DD nueva que la cite, con la fecha y la mención de H4 y del rendimiento. Opcionalmente, que se registre como duda a validar con la institución, igual que RN-22.

### Menores

**m-1. La arquitectura y el código difieren en la UQ de `rendimientos_partido`, y DA-36 no lo declara.**
- **Dónde:** `docs/arquitectura.md:219` (§7.6): «UQ parcial (`partido_id`, `jugador_id`)». El código (migración `050300`, línea 45) y la especificación v1.0.8 §5.1 usan (`partido_id`, `jugador_id`, `registrado_por`).
- **Evidencia:** la arquitectura no se modificó (§3.14). `docs/README.md` §1 pone la arquitectura por encima de la especificación del hito. `arquitectura.md:10` pide registrar la decisión y consolidarla al cerrar la fase. DA-36 no menciona la arquitectura, a diferencia de DA-04, que sí declara su diferencia con §7.6 («La arquitectura queda por actualizar a decisión del responsable»).
- **Recomendación:** agregar a DA-36 la línea de desviación respecto de `arquitectura.md` §7.6. Esta auditoría no la corrige.

**m-2. El encabezado de la sección 6 de `registro-decisiones.md` atribuye mal la sustitución y cita una versión vieja.**
- **Dónde:** `docs/decisiones/registro-decisiones.md:182`.
- **Evidencia:** dice «**DA-28 queda retirada** y DA-13 sustituida por DA-36 (F1-H4-FIX del Lote B: no hay fuente en `docs/requisitos.md`)». El paréntesis era la razón del retiro de DA-28. Ahora parece que DA-13 se sustituyó en el FIX del Lote B por falta de fuente, cuando se sustituyó en el FIX del Lote C (m-7). La misma oración sigue citando «v1.0.7».

**m-3. El m-2 original se corrigió solo en parte: el 409 `conflicto` del PUT del umbral sigue fuera de la prueba de contrato.**
- **Dónde:** `backend/tests/Feature/Rendimiento/ContratoDePlantillasYUmbralesTest.php:22-58`.
- **Evidencia:** la auditoría anterior señaló tres respuestas: el 409 `sin_umbral_aplicable` del GET y del PUT, y el 409 `conflicto` del PUT. Las dos primeras ya se validan contra el esquema (líneas 49-54). `conflicto` no aparece en ninguna prueba `Contrato*` de Rendimiento (`grep`). El reporte del FIX (§4) no lo menciona.

**m-4. En el reporte de T05, la nota absorbió una oración que no le corresponde.**
- **Dónde:** `docs/reportes/f1-h4-t05.md:9`.
- **Evidencia:** la oración «Es la única ruta tocada, más este reporte y el progreso; no hay código de producción», que describía T05, quedó al final de la cita de la nota de m-4.
- **Impacto:** de redacción.

**m-5. El reporte de cierre de H4 no refleja el FIX.**
- **Dónde:** `docs/reportes/f1-h4-reporte.md:23`, `:46` y `:147`. El archivo está fuera del diff, pero depende de él.
- **Evidencia:** sigue diciendo 1 424 pruebas, 9 738 aserciones y «DA-01 a DA-35». Tras el FIX hay 1 430 pruebas, 9 812 aserciones y DA-36.
- **Impacto:** el punto 8 de la compuerta (§10) se apoya en ese reporte. El reporte del FIX dice que las corridas «se entregan aparte», pero ningún documento del repositorio registra las cifras de `fixc-*.txt`.

**Observaciones (no son hallazgos):**
- **Corrección y umbral sin prueba de reversión:** la prueba de M-1 cubre la captura, que es lo que la condición pedía («en la captura, la corrección o el cambio de umbral»). `CorregirRendimiento` y `FijarUmbral` siguen sin una prueba que fuerce la falla de la evaluación.
- **Una aserción de la prueba de M-1 se cumple siempre:** `evaluaciones_rendimiento = 0` no puede fallar, porque el repositorio falso nunca inserta. Las aserciones que detectan la regresión son las del rendimiento, los valores y el historial, como demuestra X1.
- **Aprobación de m-3 sin registro aparte:** el repositorio no tiene un registro de la aprobación del responsable en la revisión del Lote B, salvo el texto que escribió el propio FIX. `f1-h4-fix-lote-b.md:10` sigue diciendo «decidida por mí». Si hubo aprobación verbal, conviene que el responsable la dé por buena.
- **El log no registra el commit:** `fixc-h4.sh` imprime `git log --oneline -1` en la salida estándar y no en un archivo. Que las corridas `fixc-*` sean de `0cccd14` se infiere de la hora (commit a las 14:43:17, `fixc-levantar.txt` a las 14:43) y de que mis corridas sobre `0cccd14` dan los mismos resultados.

## 3. Verificaciones pedidas

### 3.1 M-1: la evaluación en la misma transacción

- **Prueba:** `CapturaDeRendimientoTest`, «HU-16 (M-1 de la auditoría del Lote C): si falla la inserción de la evaluación, la captura se deshace completa, sin rendimiento, valores ni historial» (diff, líneas 151-174).
  - Usa `umbralMundo()`, con las plantillas de fábrica, para que la captura sí llegue a insertar la evaluación.
  - Reemplaza `RepositorioDeEvaluaciones` por uno cuyo `insertar` lanza `RuntimeException`.
  - Espera 500 `error_interno`.
  - Comprueba `rendimientos`, `valores`, `historial` e `historial_valores` en 0, y también `rendimientos_partido` (con los eliminados) y `evaluaciones_rendimiento` en 0.
- **Sin la transacción exterior, en una copia temporal (X1):** la prueba falla (§1). Funciona porque las pruebas confirman de verdad, sin transacción por prueba (DA-01): sin el envoltorio, el rendimiento y su historial quedan escritos.
- **Estado:** **Cumplido.**

### 3.2 Privacidad (m-7)

- **Migración:** `050300:45` crea `uq_rendimientos_partido_partido_jugador_registrado_por` sobre (`partido_id`, `jugador_id`, `registrado_por`) `where eliminado_en is null`. Confirmado en la base (§1).
- **Duplicado del mismo entrenador:** 409 `rendimiento_duplicado`. Lo prueban §7.1 fila 2, «RN-14 (v1.0.8)» (E3 repite y recibe 409, campo `jugador_id`) y la carrera de DA-22.
- **E1 y E3, mismo jugador y partido:** 201 y 201. Cada uno lista solo el suyo y hay 2 filas («RN-14 (v1.0.8)»). La integración (`MigracionesDeRendimientoTest`) inserta otra fila con otro registrante sin error.
- **Pendientes:** E1 captura a los cuatro convocados y su lista queda vacía; E3 sigue viendo PJ1 con `convocados_sin_rendimiento` 4 («HU-14 (v1.0.8)»). El código filtra por `registrado_por` (`ConsultaDePendientesDeRendimientoSql.php:77`) y devuelve vacío si no hay usuario (`:35`).
- **Mutaciones:** X2 y X3 del constructor (`mutaciones-fix-c.txt`) y X5 de esta auditoría, que deja la UQ sin `registrado_por`. Las tres son detectadas.

**Respuesta por respuesta: ¿revela una captura de otro entrenador?**

| Respuesta | Revisión | ¿Revela? |
|---|---|---|
| 409 `rendimiento_duplicado` (comprobación previa) | `existeDe` filtra por `registrado_por` (`RepositorioDeRendimientosEloquent.php:45`) | No |
| 409 `rendimiento_duplicado` (carrera, `UniqueConstraintViolationException` en `guardarNuevo:65`) | Con la UQ nueva, solo choca una fila del mismo registrante | No |
| 409 `partido_no_jugado` y 422 (`jugador_no_convocado`, métricas, rangos) | Ninguna comprobación de `CapturarRendimiento` (líneas 44-64) lee `rendimientos_partido` salvo `existeDe` | No |
| 404 (rendimiento por id, corrección) | `buscar` por id. La visibilidad por `registrado_por` no cambió: §7.1 fila 13 y `RendimientoSeguridadTest` (404 byte a byte) pasan | No |
| Lista por partido | `ConsultaDeRendimientosSql.php:46` filtra por `registrado_por` | No |
| Pendientes | Filtro de `:77` | No (antes sí: m-7) |
| Desempeño y evaluación | `CalificacionesDelEntrenadorSql.php:28` filtra por `registrado_por`; la evaluación es por `entrenador_id` (DA-04) | No |
| Umbral y plantillas | No leen `rendimientos_partido` | No |

Ninguna otra consulta de `src/` o `app/` lee `rendimientos_partido` (`grep`).

**DA-36 sustituye a DA-13:** sí, en `registro-decisiones.md:198` y `:221`, y en el detalle de `f1-h4-decisiones-del-agente.md:142` y `:269-283`. **Quedan referencias vigentes a DA-13: ver M-1.**

**DD-17:** respalda la visibilidad, no la captura por entrenador. Ver M-2.

**Estado:** comportamiento **cumplido**; documentación con M-1, M-2 y m-1.

### 3.3 Prueba de carrera de T02

- **Nombre:** «HU-14 (DA-22, v1.0.8): la carrera de dos capturas del mismo entrenador que choca con el índice único da 409 rendimiento_duplicado y no escribe nada» (`CapturaDeRendimientoTest.php:85`).
- **Comportamiento:**
  - La primera captura (`capturaDeP1`) y la segunda (`capturaPeticion` sin `correo`) usan `e1@x.com`, el valor por omisión de la línea 30.
  - El decorador hace que `existeDe` devuelva `false` con la firma nueva, así que la segunda llega a `guardarNuevo` y choca con la UQ.
  - Espera 409 con el campo `jugador_id` y comprueba que las filas no cambian.
- Pasó en mi corrida.
- **Estado:** **Cumplido.**

### 3.4 m-1: `plantilla_de_fabrica` reservado

- **`openapi.yaml:4330-4333`:** la descripción de `POST /plantillas-evaluacion` lo da como reservado y sin caso en H4.
- **§6.3:** lo marca «Reservado; sin caso en H4» y remite a `openapi.yaml` (v1.0.8).
- **Rutas:** ninguna edita ni borra plantillas (§2.3).
- **Estado:** **Cumplido.**

### 3.5 m-2: contratos del 409 `sin_umbral_aplicable`

- **Pruebas:** `ContratoDePlantillasYUmbralesTest.php:49-54` crea un portero sin umbral propio y borra lógicamente la plantilla de fábrica de portero. Luego valida contra el esquema el 409 del GET y el del PUT con cuerpo vacío (`[]`), cada uno con `codigo` `sin_umbral_aplicable`.
- **Corrida:** pasó.
- **Estado:** **Cumplido** para lo pedido. Queda el 409 `conflicto` fuera del contrato: ver m-3.

### 3.6 m-3: fila 1.0.6 y «Cómo se revierte» de DA-33

- **Fila 1.0.6 de §12:** atribuye al responsable el 409 del desempeño. Dice que la extensión al GET y al PUT sin cuerpo del umbral «la propuso el agente y el responsable la aprobó en la revisión del Lote B», con la nota de que la atribución se corrigió en v1.0.8.
- **DA-33** (`f1-h4-decisiones-del-agente.md:248-251`): tiene «Cómo se revierte», con las dos reversiones (lanzar en `RecalcularEvaluacion`; volver a `RecursoNoEncontrado` en los tres casos de uso).
- **Registro:** `registro-decisiones.md:218` también lo indica.
- **Estado:** **Cumplido.** Ver la observación sobre la falta de un registro aparte de la aprobación.

### 3.7 m-4: aserciones de T05

- **Reporte:** `f1-h4-t05.md` mantiene 336 y agrega la nota con las dos cifras y su origen.
- **Logs del constructor:** no encontré logs de sus tres corridas aisladas. En su directorio temporal no hay ningún archivo con `336 assertions` ni `335 assertions`. **No puedo verificar sus corridas.**
- **Corridas del auditor:** el archivo no cambió entre `c0f1601` y `0cccd14` (`git diff --stat` vacío). Lo ejecuté solo tres veces: `11 passed (336 assertions)` las tres (`aud-t05-1.txt` a `aud-t05-3.txt`). **La cifra de 336 se confirma.**
- **Diferencia de la suite:** también confirmé que `verificar-fix-b.txt` da `1410 passed (9389 assertions)` y `verificar-t05b.txt` da `1421 passed (9724 assertions)`: la diferencia es 335. La nota es correcta: alguna otra prueba perdió una aserción entre esas corridas. No se identificó cuál.
- **Estado:** **Cumplido**, con la decisión de 336 frente a 335 para el responsable. Ver también m-4 de este informe.

### 3.8 m-5: logs de T06

- **Reporte:** `f1-h4-t06.md:43-56` cita el humo (`humo=0 segundos=45`, salida en `humo.txt` sin tiempos) y las cuatro líneas de la mutación (`687 … killed`, `MSI 100%`, `codigo=0 segundos=166`).
- **Archivos:** `humo.txt` existe en el directorio temporal del constructor (13:15). Ningún archivo contiene el 166 ni el 45: el reporte dice que esas líneas quedaron en la sesión. Son citas sin archivo.
- **Estado:** **Cumplido como cita.** Las cifras siguen sin poder verificarse con un log, y no afectan a la compuerta.

### 3.9 m-6: pruebas propias de `humo_igual`

- **Función:** `funcionHumoIgual(string $guion = 'humo-h3.sh')` (`GuionDeHumoTest.php:91`).
- **Pruebas sobre `humo-h4.sh`:**
  - «F1-H4-T06 (m-6): … se detiene con FALLA y código 1 si el 404 no es igual byte a byte, o si el código no es 404». Cubre los dos casos y que el guion no siga.
  - «F1-H4-T06 (m-6): … imprime OK y el guion sigue si el 404 es igual byte a byte».
- Las dos pasaron en mi corrida.
- **Estado:** **Cumplido.**

### 3.10 m-8: RN-07

- **Prueba:** `PlantillasYUmbralesTest`, «RN-07: cada posición tiene su plantilla de fábrica, y el umbral de un jugador sin umbral propio es la de su posición en ese equipo».
  - Hay una plantilla de fábrica activa por cada posición.
  - P1, D1, M1 y F1 en A reciben la de su posición, y F1 en B, visto por E3, la de delantero.
  - Pasó.
- **Trazabilidad:**
  - `trazabilidad.md:56` apunta a esa prueba por su nombre.
  - `:132` ya no lista RN-07 entre las reglas sin identificador.
- **Estado:** **Cumplido.**

### 3.11 Cifras: logs `fixc-*.txt` frente al informe

Logs del constructor: `C:\Users\luis_\AppData\Local\Temp\claude\c--laragon-www-GestorDep\eba3ed55-c173-452a-b1e1-fc2f4400025d\scratchpad\fixc-*.txt` (corrida de las 14:43 a las 14:59, guion `fixc-h4.sh`).

| Cifra | Log | ¿Coincide? |
|---|---|---|
| 1 430 pruebas, 9 812 aserciones | `fixc-verificar.txt:1690` `Tests: 1430 passed (9812 assertions)` | Sí |
| Pint, PHPStan y auditoría | `:15` 702 archivos PASS; `:19` `[OK] No errors`; `:2179` `No security vulnerability advisories found` | Sí |
| Cobertura | `:2176-2178`: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,5 %; total 99,6 % (`:2173`) | Sí |
| `make verificar` | `codigo=0 segundos=678`; Pest `Duration: 530.44s` | — |
| Mutación | `fixc-mutacion.txt`: 687 generados, 687 eliminados, MSI 100 %, cobertura de código 100 %, «ninguno omitido ni con tiempo agotado», `codigo=0 segundos=165` | Sí: 687 de 687 |
| fresh, rollback, status, fresh y humo | Código 0 en todos; status con 63 `Pending` y 0 `Ran`; humo H4 de 14 pasos | Sí |
| X1, X2 y X3 | `mutaciones-fix-c.txt` coincide línea a línea con §1 y §2 del reporte del FIX | Sí |
| Fallas antes de implementar | `falla-fix-c.txt` (firma incompatible) y `falla-fix-c2.txt` (las dos RNF-03) coinciden con el reporte | Sí |

El reporte del FIX no da estas cifras en el texto: dice que las corridas «se entregan aparte». Ver m-5.

### 3.12 Migración corregida en el lugar

- **Corridas:** `make fresh`, `migrate:rollback` (63 revertidas, 0 `Ran`) y `make fresh` terminaron con código 0, en las corridas del constructor y en las mías.
- **Base:** después de cada fresh solo existe el índice nuevo. **No queda ningún índice `uq_rendimientos_partido_partido_jugador`** (0 en `pg_indexes`). Las restricciones `CHECK` y `FK` de la tabla son las de §5.1.
- **Prueba:** `MigracionesDeRendimientoTest` comprueba lo mismo (`->has('uq_rendimientos_partido_partido_jugador')` es falso).
- **Alcance de la edición:** editar la migración en su lugar está permitido porque H4 no se fusionó a `main` (CLAUDE.md §1). El riesgo de una base local migrada antes del cambio está declarado en DA-36 («Riesgo»).
- **Estado:** **Cumplido.**

### 3.13 Alcance del diff

- **Archivos:** los 19 del diff son exactamente los 19 listados en §10 del reporte del FIX: 6 de código, 5 de pruebas y 8 documentos, contando el propio reporte. No toca `frontend/`, `app/`, `routes/`, `lang/`, `Makefile`, `scripts/` ni `docs/arquitectura.md`.
- **Dominio:** el único archivo es `src/Rendimiento/Dominio/RepositorioDeRendimientos.php`, una **interfaz**. Cambian la firma de `existeDe` y dos comentarios. Una interfaz no tiene sentencias ejecutables, así que Infection, configurado sobre `src` sin `Aplicacion` ni `Infraestructura` (`infection.json5`), no le genera mutantes. Por eso el total sigue en 687, igual que en el cierre (`cierre-mutacion.txt`).
- **Estado:** **Cumplido.**

### 3.14 `docs/arquitectura.md`

- **Sin cambios:** `git log 2ac6155..0cccd14 -- docs/arquitectura.md` está vacío. El último commit que lo tocó es `b19d545`, de H2.
- **Diferencias con el código, solo informadas:**
  - **§7.6, línea 219:** la UQ de `rendimientos_partido` es (`partido_id`, `jugador_id`) en la arquitectura y (`partido_id`, `jugador_id`, `registrado_por`) en el código. Es nueva en este FIX y no está declarada (m-1).
  - **§7.6:** `entrenador_id` en `umbrales_rendimiento` y `evaluaciones_rendimiento`. Es anterior y está declarada en DA-04.
  - **Pendientes de captura:** sin diferencia. La arquitectura los ubica en Consultas (líneas 57 y 83) y no define la regla de conteo.

## 4. Veredicto

**Aprobado con condiciones.** No hay bloqueantes.

- **M-1:** la prueba de reversión existe y demuestra lo que dice (X1 la rompe).
- **m-7:** la privacidad de la unicidad y de los pendientes está corregida en el código, en la base y en las pruebas. Ninguna respuesta revela una captura ajena.
- **Cifras:** las de los logs `fixc-*` coinciden: 1 430 pruebas, 9 812 aserciones, 687 de 687 mutantes, y cobertura de 100 % en Dominio, 100 % en Aplicación y 99,5 % en Infraestructura y HTTP.
- **Migración:** fresh, rollback y fresh pasan sin restricciones viejas.

Condiciones antes de la etiqueta `f1-h4`:

1. **M-1:** actualizar las tres referencias vigentes a DA-13: la descripción de `GET /partidos/pendientes-de-rendimiento` en `openapi.yaml`, el comentario de la clase `ConsultaDePendientesDeRendimientoSql` y la fila de resumen de `f1-h4-decisiones-del-agente.md`, más una fila para DA-36.
2. **M-2:** que el responsable deje escrito en DD-17, o en una DD que la cite, que la regla estricta incluye un rendimiento por entrenador para el mismo jugador y partido en H4.
3. **§10 punto 1:** la corrida de la integración continua tras el push, que sigue pendiente.

Los menores (m-1 a m-5) pueden atenderse en el mismo ajuste o quedar anotados, a criterio del responsable. Recomiendo al menos m-1, que es la diferencia con la arquitectura, y m-5, el reporte de cierre, porque los dos alimentan la compuerta.
