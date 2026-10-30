# Reporte del F1-H4-FIX de la auditoría del Lote C

**Fecha:** 30 de octubre de 2026 · **Rama:** `hito/f1-h4` · **Especificación:** `docs/hitos/f1-h4.md` v1.0.8 · **Auditoría:** `docs/reportes/f1-h4-auditoria-lote-c.md` (archivo del auditor, que no forma parte de este commit)

Es un solo commit con las nueve correcciones que pidió el responsable antes de la etiqueta. Las corridas en serie posteriores al commit (`make verificar`, `make mutacion`, `migrate:rollback` y `make humo`) se entregan aparte, con sus logs.

## 1. M-1: la evaluación va en la misma transacción que la captura

- **Prueba:** `CapturaDeRendimientoTest` «HU-16 (M-1 de la auditoría del Lote C)».
  - Usa el mundo con las plantillas de fábrica, porque sin ellas no se insertaría evaluación y la prueba no forzaría nada.
  - Reemplaza `RepositorioDeEvaluaciones` por uno cuyo `insertar` lanza una excepción. La captura responde 500 `error_interno`.
  - Comprueba que no quedan rendimientos, valores, historiales ni evaluaciones.
- **Debe fallar sin la transacción:** en una copia temporal completa (`/tmp/copia-fix-c`, borrada después) se quitó la transacción exterior de `CapturarRendimiento` (mutación X1). La prueba falló:

  ```
  X1 (captura sin la transacción exterior (M-1))
    codigo=1 | Tests:    1 failed, 36 passed (276 assertions)
    falla: ⨯ HU-16 (M-1 de la auditoría del Lote C): si falla la inserción de la…
  ```

## 2. m-7 (privacidad): unicidad por partido, jugador y registrante

- **Migración `050300`, corregida en su lugar** (H4 no se ha fusionado): `uq_rendimientos_partido_partido_jugador_registrado_por` (`partido_id`, `jugador_id`, `registrado_por`) donde `eliminado_en is null`.
- **Código:**
  - `RepositorioDeRendimientos::existeDe` recibe el registrante.
  - `CapturarRendimiento` comprueba el duplicado del entrenador de la sesión.
  - `ConsultaDePendientesDeRendimientoSql` cuenta solo los rendimientos del entrenador que consulta.
- **Decisión:** DA-36, indicada por el responsable; sustituye a DA-13.
- **Especificación v1.0.8:** §3.3 (captura y pendientes), §5.1, §6.3 (`rendimiento_duplicado`) y §7.1 filas 2 y 9.
- **`openapi.yaml`:** el 409 de la captura dice «del mismo entrenador».
- **Pruebas:**
  - «RN-14 (v1.0.8)»: E1 y E3 capturan a P1 en PJ1 sin error (201 y 201), cada uno ve solo el suyo, y E3 al repetir recibe 409.
  - §7.1 fila 2: el mismo entrenador repite y recibe 409; sin cambios.
  - La carrera de T02 (DA-22) ahora dice «del mismo entrenador» y su decorador usa la firma nueva.
  - «HU-14 (v1.0.8)»: E1 captura a los cuatro convocados y no tiene pendientes, pero E3 sigue viendo PJ1 con 4.
  - `MigracionesDeRendimientoTest`: el índice nuevo; el mismo registrante choca (23505) y otro no.
- **Falla antes de implementar:**

  ```
  Declaration of RepositorioDeRendimientos@anonymous::existeDe(..., Identificador $registradoPor): bool must be compatible
  with RepositorioDeRendimientos::existeDe(Identificador $partidoId, Identificador $jugadorId): bool     ← CapturaDeRendimientoTest
  ⨯ RNF-03 (v1.0.8): un jugador tiene un solo rendimiento por partido y…   Failed asserting that '23505' is null.
  ⨯ RNF-03: los índices de §5.1 existen con sus columnas                    Undefined array key "uq_rendimientos_partido_partido_jugador_registrado_por"
  ```

- **Mutaciones** en la misma copia temporal:

  ```
  X2 (existeDe sin el filtro de registrante (m-7))
    codigo=1 | Tests:    1 failed, 36 passed (267 assertions)
    falla: ⨯ RN-14 (v1.0.8): E1 y E3 capturan al mismo jugador en el mismo parti…
  X3 (pendientes sin el filtro de registrante (m-7))
    codigo=1 | Tests:    1 failed, 36 passed (278 assertions)
    falla: ⨯ HU-14 (v1.0.8): los pendientes cuentan solo los rendimientos del en…
  copia temporal borrada
  ```

## 3. m-1: `plantilla_de_fabrica` reservado

- **`openapi.yaml`:** la descripción de `POST /plantillas-evaluacion` lo da como reservado, igual que `rendimiento_no_editable` en el PATCH.
- **§6.3:** ya lo marcaba reservado desde v1.0.4; ahora remite también a `openapi.yaml`.

## 4. m-2: contratos de los 409 `sin_umbral_aplicable`

`ContratoDePlantillasYUmbralesTest` valida contra el esquema el 409 del GET del umbral y el del PUT vacío: un portero sin umbral propio con la plantilla de fábrica de portero borrada lógicamente. Pasaron desde la primera corrida, porque `openapi.yaml` ya los documentaba.

## 5. m-3: atribución de la v1.0.6 y «Cómo se revierte» de DA-33

- **Fila 1.0.6 de §12:** dice ahora que el 409 del desempeño es corrección del responsable, y que la extensión al GET y al PUT del umbral la propuso el agente y la aprobó el responsable en la revisión del Lote B.
- **DA-33:** recupera su línea «Cómo se revierte», con las dos reversiones posibles.

## 6. m-4: aserciones del reporte de T05 — **no se cambió la cifra**

- **Qué pidió el responsable:** corregirla a 335.
- **Qué medí:** ejecuté `RendimientoSeguridadTest` solo, sobre el archivo de `c0f1601`, tres veces, y las tres dieron `11 passed (336 assertions)`.
- **De dónde sale el 335 de la auditoría:** de la diferencia de la suite completa (9 724 − 9 389), así que alguna otra prueba cambió una aserción entre esas dos corridas.
- **Qué hice:** dejé 336 y agregué al reporte de T05 una nota con las dos cifras y su origen. Escribir 335 habría sido anotar una cifra que no puedo reproducir. **Queda para la decisión del responsable.**

## 7. m-5: logs de T06

El reporte de T06 agrega lo que hay:

- **Humo:** la duración (45 s) salió del propio comando y la salida está en `humo.txt`, sin tiempos por línea.
- **Mutación:** las cuatro líneas de la corrida de 166 s, que quedaron en la sesión y no en un archivo.

El reporte remite a las corridas de cierre, que sí tienen log con tiempos.

## 8. m-6: prueba propia de `humo_igual` de H4

- `funcionHumoIgual()` recibe el nombre del guion, con `humo-h3.sh` por omisión.
- Dos pruebas `F1-H4-T06 (m-6)` extraen la función de `humo-h4.sh`: se detiene con FALLA si el cuerpo no es igual o si el código no es 404, e imprime OK si es igual.
- Pasaron desde la primera corrida, porque la copia de H4 es idéntica a la de H3.

## 9. m-8: RN-07 con identificador

- **Prueba:** `PlantillasYUmbralesTest` «RN-07: cada posición tiene su plantilla de fábrica, y el umbral de un jugador sin umbral propio es la de su posición en ese equipo». Cubre las cuatro posiciones y F1 en B visto por E3.
- **Trazabilidad:** la fila de RN-07 apunta a esa prueba, y el cierre de H4 ya no lista RN-07 entre las reglas sin identificador.
- **Primera corrida:** pasó, porque el comportamiento ya existía.

## 10. Archivos

**Código**

- `database/migrations/rendimiento/2026_10_30_050300_crear_tabla_rendimientos_partido.php`.
- `src/Rendimiento/Dominio/RepositorioDeRendimientos.php`.
- `src/Rendimiento/Infraestructura/RepositorioDeRendimientosEloquent.php`.
- `src/Rendimiento/Aplicacion/CapturarRendimiento.php` y `RendimientoDuplicado.php` (comentario).
- `src/Consultas/Infraestructura/ConsultaDePendientesDeRendimientoSql.php`.

**Pruebas**

- `tests/Feature/Rendimiento/CapturaDeRendimientoTest.php`, `ContratoDePlantillasYUmbralesTest.php` y `PlantillasYUmbralesTest.php`.
- `tests/Integration/Rendimiento/MigracionesDeRendimientoTest.php`.
- `tests/Unit/Herramientas/GuionDeHumoTest.php`.

**Documentos**

- `docs/api/openapi.yaml` y `docs/hitos/f1-h4.md` (v1.0.8).
- `docs/trazabilidad.md` y `docs/decisiones/registro-decisiones.md`.
- `docs/reportes/f1-h4-decisiones-del-agente.md` (DA-13, DA-33 y DA-36).
- `docs/reportes/f1-h4-t05.md` y `f1-h4-t06.md`, y este reporte.

## 11. Desviaciones

- **m-4:** no se aplicó como se pidió (§6).
- **Corridas en serie:** según lo pedido, van después del commit. Antes del commit corrieron Pint, PHPStan y las pruebas de Rendimiento, Consultas, seguridad, herramientas y arquitectura.
