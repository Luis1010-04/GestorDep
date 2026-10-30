# Reporte del FIX de I-1: `make humo` incluye H3

**Commit:** `6ae0a0b` (`F1-H3-FIX: make humo incluye H3 (I-1)`). **Origen:** auditoría independiente del Lote C, hallazgo I-1 (`docs/hitos/f1-h3.md` §10 puntos 6 y 11). **Indicación del responsable:** agregar `humo-h3.sh` al final del destino `humo`, sin cambiar los guiones.

## Qué cambia

| Archivo | Cambio |
|---|---|
| `Makefile` | Una línea al final del destino `humo`: `docker compose exec -T app bash /scripts/humo/humo-h3.sh`, después de H0, H1 y H2 |

**Sin cambios:** los guiones de `scripts/humo/`, las pruebas y el destino `humo-h3`, que se conserva para correr solo H3.

## Comprobación

Antes de correr se revisaron los procesos de `app` (`/proc`) y de Windows (`tasklist`): no había ningún `pest`, `composer`, `phpstan`, `infection` ni `php -S`. Los cuatro contenedores estaban arriba; `app` y `db`, sanos.

1. `make fresh`: código 0.
2. `make humo`: código 0, 30 s. H0, H1 (14 pasos), H2 (16 pasos) y H3 (16 pasos) pasan uno tras otro, **sin `make fresh` intermedio**. H3 no falla después de H2: usa datos propios (categoría «Sub-13», matrículas `HUMO-H3-00n`, sus lugares, rival y temporada) que no chocan con los de H1 y H2.

El reporte de cierre suponía que hacía falta un `make fresh` entre `humo-h3` y `humo`. Eso solo vale si H3 corre antes de H1, porque `humo-h1.sh` exige una base recién sembrada. Con H3 al final, una sola base limpia basta.

## Salida completa de `make humo` (tras `make fresh`)

```text
docker compose exec -T app bash -s < scripts/humo/humo-h0.sh
HUMO H0 OK: GET /api/v1/salud -> 200
docker compose exec -T app bash /scripts/humo/humo-h1.sh
OK     paso 1: GET /api/v1/salud -> HTTP 200
OK     paso 2: el entrenador 1 inicia sesión -> HTTP 200
OK     paso 3: crea la categoría Sub-17 -> HTTP 201
OK     paso 4: crea el equipo Tigres Sub-17 -> HTTP 201
OK     paso 4: el equipo aparece en GET /equipos -> HTTP 200
OK     paso 5: registra un jugador en ese equipo -> HTTP 201
OK     paso 6: la misma matrícula responde 409 sin datos del primero -> HTTP 409
OK     paso 7: el entrenador 2 inicia sesión con otro archivo de cookies -> HTTP 200
OK     paso 8: el jugador del entrenador 1 responde 404 al entrenador 2 -> HTTP 404
OK     paso 9: la búsqueda por matrícula devuelve solo nombres, apellidos y matrícula -> HTTP 200
OK     paso 10: el entrenador 2 crea su equipo -> HTTP 201
OK     paso 10: asigna al jugador por matrícula -> HTTP 201
OK     paso 11: GET /jugadores del entrenador 2 devuelve al jugador con solo su equipo -> HTTP 200
OK     paso 12: el docente inicia sesión -> HTTP 200
OK     paso 12: el docente recibe 403 al crear un equipo -> HTTP 403
OK     paso 12: el docente recibe una lista vacía en GET /jugadores -> HTTP 200
OK     paso 13: el entrenador 1 da de baja al jugador -> HTTP 201
OK     paso 13: deja de aparecer en GET /jugadores del entrenador 1 -> HTTP 200
OK     paso 13: deja de aparecer en GET /jugadores del entrenador 2 -> HTTP 200
OK     paso 14: el entrenador 1 cierra sesión -> HTTP 204
OK     paso 14: la misma cookie responde 401 -> HTTP 401
HUMO H1 OK: 14 pasos
docker compose exec -T app bash /scripts/humo/humo-h2.sh
Servidor del guion con QUEUE_CONNECTION=sync
OK     paso 1: GET /api/v1/salud -> HTTP 200
OK     paso 2: el docente 1 inicia sesión -> HTTP 200
OK     paso 3: crea un periodo escolar que contiene hoy y sale vigente -> HTTP 201
OK     paso 4: crea la asignatura MAT-101 -> HTTP 201
OK     paso 5: crea la materia con esa asignatura y ese periodo -> HTTP 201
OK     paso 6: el entrenador 1 inicia sesión (otro archivo de cookies) -> HTTP 200
OK     paso 6: crea su categoría Sub-15 -> HTTP 201
OK     paso 6: crea su equipo -> HTTP 201
OK     paso 6: registra un jugador -> HTTP 201
OK     paso 7: el docente 1 busca la matrícula: solo nombres, apellidos y matrícula -> HTTP 200
OK     paso 7: lo inscribe por matrícula -> HTTP 201
OK     paso 8: captura la calificación parcial 8.50 -> HTTP 201
OK     paso 8: captura la calificación final 8.00 -> HTTP 201
OK     paso 9: captura la asistencia presente del 2026-10-27 -> HTTP 201
OK     paso 9: captura la asistencia presente del 2026-10-28 -> HTTP 201
OK     paso 9: captura la asistencia falta del 2026-10-29 -> HTTP 201
OK     paso 10: situación como docente: 8.00, sin_riesgo, 66.67 y con materias -> HTTP 200
OK     paso 11: define el criterio propio 8.50 -> HTTP 200
OK     paso 11: la situación pasa a 1 reprobada y en_observacion -> HTTP 200
OK     paso 12: el entrenador 1 ve el mismo nivel, sin materias -> HTTP 200
OK     paso 13: el docente 2 inicia sesión -> HTTP 200
OK     paso 13: el docente 2 recibe 404 en la situación -> HTTP 404
OK     paso 13: el docente 2 recibe una lista vacía en GET /jugadores -> HTTP 200
OK     paso 14: el entrenador 1 recibe 403 al crear un periodo -> HTTP 403
OK     paso 14: el docente 1 recibe 403 al crear un equipo -> HTTP 403
OK     paso 15: un valor fuera de rango responde calificacion_fuera_de_rango -> HTTP 422
OK     paso 16: el docente 1 cierra sesión -> HTTP 204
OK     paso 16: la misma cookie responde 401 -> HTTP 401
HUMO H2 OK: 16 pasos
docker compose exec -T app bash /scripts/humo/humo-h3.sh
OK     paso 1: GET /api/v1/salud -> HTTP 200
OK     paso 2: E1 inicia sesión -> HTTP 200
OK     paso 2: E1 crea la categoría Sub-13 -> HTTP 201
OK     paso 2: E1 crea el equipo A -> HTTP 201
OK     paso 2: registra a Jota Uno (HUMO-H3-001) -> HTTP 201
OK     paso 2: registra a Jota Dos (HUMO-H3-002) -> HTTP 201
OK     paso 2: registra a Jota Tres (HUMO-H3-003) -> HTTP 201
OK     paso 2: E2 inicia sesión (otro archivo de cookies) -> HTTP 200
OK     paso 2: E2 crea el equipo B -> HTTP 201
OK     paso 2: registra a Jota Cuatro (HUMO-H3-004) -> HTTP 201
OK     paso 2: E2 asigna a J3 al equipo B -> HTTP 201
OK     paso 3: E1 crea el lugar L2 -> HTTP 201
OK     paso 3: E1 crea la temporada que contiene hoy -> HTTP 201
OK     paso 4: E1 crea un torneo en esa temporada -> HTTP 201
OK     paso 5: E1 crea un entrenamiento de A con J1, J2 y J3 listados -> HTTP 201
OK     paso 6: E1 crea otro entrenamiento en L2 que se cruza: advierte empalme_de_lugar -> HTTP 201
OK     paso 7: E1 marca el primero realizado -> HTTP 200
OK     paso 7: E1 captura la asistencia de J1, J2 y J3 -> HTTP 201
OK     paso 8: E1 consulta el porcentaje de J1: 100.00 -> HTTP 200
OK     paso 8: el porcentaje del equipo A es 33.33 (la falta justificada cuenta en el denominador) -> HTTP 200
OK     paso 9: E1 crea el rival R1 -> HTTP 201
OK     paso 9: E1 crea un partido oficial con el torneo, en la temporada esperada -> HTTP 201
OK     paso 10: registra a Jota Cinco (HUMO-H3-005) -> HTTP 201
OK     paso 10: E1 convoca a J1 -> HTTP 201
OK     paso 10: E1 convoca a J5 -> HTTP 201
OK     paso 10: E1 define la alineación: J1 titular -> HTTP 200
OK     paso 11: E1 captura el resultado 2-1 -> HTTP 200
OK     paso 11: el partido queda jugado -> HTTP 200
OK     paso 12: E2 consulta un entrenamiento inexistente (referencia) -> HTTP 404
OK     paso 12: E2 consulta el entrenamiento de A: 404 igual byte a byte -> HTTP 404
OK     paso 13: el docente inicia sesión -> HTTP 200
OK     paso 13: el docente recibe 403 al crear un lugar -> HTTP 403
OK     paso 14: antes de la baja, J5 está en la convocatoria -> HTTP 200
OK     paso 14: E1 da de baja a J5 -> HTTP 201
OK     paso 14: J5 desaparece de la convocatoria y J1 sigue -> HTTP 200
OK     paso 15: E1 crea un amistoso programado -> HTTP 201
OK     paso 15: 100 goles responde valor_fuera_de_rango -> HTTP 422
OK     paso 16: E1 cierra sesión -> HTTP 204
OK     paso 16: la misma cookie responde 401 -> HTTP 401
HUMO H3 OK: 16 pasos
```

## Lo que no se hizo

- No se cambió ningún guion ni prueba. `GuionDeHumoTest` no cubre el Makefile.
- No hubo push, PR ni etiquetas.
