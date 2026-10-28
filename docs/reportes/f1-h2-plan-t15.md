# Plan F1-H2-T15: humo de H2 y cierre documental

**Rutas permitidas (§8):** `scripts/humo/humo-h2.sh`, `scripts/humo/lib.sh` (solo agregar funciones; no hará falta tocarlo), `database/seeders/UsuariosDemoSeeder.php` (segundo docente), `Makefile` (solo `humo`), `docs/trazabilidad.md`, `docs/decisiones/registro-decisiones.md`, `docs/reportes/f1-h2-reporte.md`, `tests/**` y los reportes.

## Archivos

- `UsuariosDemoSeeder`: constante `CORREO_DOCENTE_2` y el usuario `docente2@example.test` (rol docente), idempotente. Prueba de consola: se amplía `UsuariosDemoSeederTest` (4 usuarios, 2 docentes, el segundo con su rol).
- `scripts/humo/humo-h2.sh`: los 16 pasos de §9. El servidor arranca con `QUEUE_CONNECTION=sync` (lo declara el guion). Reutiliza `lib.sh` sin cambiarlo. La credencial del segundo docente se lee de la constante del seeder, como las demás. Datos propios de H2 que no chocan con los de `humo-h1.sh`: categoría `Sub-15`, matrícula `HUMO-H2-001` y un periodo que contiene hoy de negocio.
- `Makefile`: una línea más en `humo`.
- `tests/Unit/Herramientas/GuionDeHumoTest.php`: el guion falla si se rompe un paso (`humo_paso` sale con 1, sin seguir, ante un código o un cuerpo distinto) y `humo-h2.sh` tiene `set -euo pipefail`, declara la cola `sync` y recorre los pasos 1 a 16. Además, una corrida real de `make humo` con un paso roto (sin `make fresh` previo) se pega como evidencia.
- `docs/trazabilidad.md`: la columna Pruebas de las historias, reglas y RNF de H2 con los nombres reales; los criterios diferidos de §2.2; M-3 y M-6 citados.
- `docs/decisiones/registro-decisiones.md`: DD-42 a DD-55; las validaciones pendientes con la institución; las DA de H2 pertinentes (DA-08 a DA-15; DA-15 pendiente de confirmación); M-8 como comportamiento exigido por DD-52.
- `docs/reportes/f1-h2-reporte.md`: el reporte de cierre con la compuerta §10 punto por punto y evidencia.

## Dudas

Ninguna que obligue a detenerse.
