# Plan de F1-H3-T09: humo de H3 y cierre documental

**Plan retrospectivo:** se escribió después de T09, a pedido del responsable tras la auditoría del Lote C (m-9). Describe lo que T09 hizo en `2aa6936` y en los F1-DOC de cierre; no se presentó antes de empezar.

**Rutas permitidas (§8):** `scripts/humo/humo-h3.sh`, `Makefile` (una línea), `tests/Unit/Herramientas/**` y `tests/Integration/**` para el guion, `docs/trazabilidad.md`, `docs/decisiones/registro-decisiones.md` y reportes.

## Archivos

| Archivo | Qué |
|---|---|
| `scripts/humo/humo-h3.sh` (nuevo) | Los 16 pasos de §9. Crea por la API los datos de §7.0 que usa (E1 con A, E2 con B, J1 a J5, categoría «Sub-13», matrículas `HUMO-H3-00n`). Con `set -euo pipefail` y `lib.sh`, y una función propia `humo_igual` para el 404 byte a byte del paso 12 (`lib.sh` no está entre las rutas permitidas) |
| `Makefile` | El guion en `make humo`. En `2aa6936` quedó como destino aparte, `humo-h3`; el FIX `6ae0a0b` (I-1) lo agregó al final de `humo` |
| `backend/tests/Unit/Herramientas/GuionDeHumoTest.php` | Pruebas `F1-H3-T09` |
| `docs/trazabilidad.md` | Filas de HU-10 a HU-13, HU-28, HU-29, RN y RNF de H3, con «Estado al cierre de H3» y los criterios diferidos de §2.2 |
| `docs/decisiones/registro-decisiones.md` | DD-07, DD-17 y DD-18 confirmadas; AB-07 y AB-15 por validar; PT-08; sección 5 con DA-01 a DA-34 |
| `docs/reportes/f1-h3-reporte.md` | Reporte de cierre con la compuerta §10 punto por punto |

## Pruebas (primero, con la falla)

- El guion real se detiene en el paso 1 si la salud responde 500, y en el paso 2 si el cuerpo del inicio de sesión no cumple (con un `curl` falso, sin servidor ni base).
- `humo_igual` se detiene si el cuerpo difiere o si el código no es 404, y sigue si son iguales.
- El guion tiene `set -euo pipefail`, carga `lib.sh` y recorre los pasos 1 a 16 en orden.
- Sin el guion deben fallar las seis nuevas, y las cuatro de H2 deben seguir pasando.

## Corridas

`make verificar` y `make mutacion` una vez cada uno, sin nada más corriendo. `make fresh`, `migrate:rollback` y `make fresh` (§10 punto 4). `make humo` tras `make fresh` (§10 punto 6).
