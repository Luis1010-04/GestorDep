# Reporte de cierre del hito F1-H4: Rendimiento

**Fecha:** 30 de octubre de 2026 · **Rama:** `hito/f1-h4` (desde `main` en la etiqueta `f1-h3`, `1fa839a`) · **Especificación:** `docs/hitos/f1-h4.md` v1.0.7
**Estado:** construcción terminada (T00 a T06). Falta la **auditoría independiente del Lote C** (§0) y la revisión del responsable (compuerta, punto 11). No hubo push, PR ni etiquetas.

## 1. Resumen

Por la API ya se puede:

- **Pendientes:** consultar los partidos jugados con convocados sin rendimiento capturado.
- **Captura:** capturar por jugador los minutos, si fue titular, las tarjetas, la calificación general del 1 al 10 (con su categoría de RN-06), una observación y las métricas de su posición; corregir solo lo propio.
- **Plantillas:** consultar las plantillas de fábrica y las propias, y crear plantillas para cualquier posición.
- **Umbral:** fijar un umbral propio por jugador y equipo, con historial (valor anterior, autor y fecha).
- **Desempeño:** consultar el resultado de un jugador en un equipo (**pasa**, **no pasa** o **datos insuficientes**), calculado solo con los datos y el umbral del entrenador que consulta y guardado en cada captura, corrección o cambio de umbral.

**Privacidad:**

- El docente no ve rendimiento (403).
- Un entrenador no ve lo que registró otro, aunque comparta el equipo (RN-14, DD-17): recibe el 404 de un recurso inexistente.

| Métrica al cierre | Valor |
|---|---|
| Pruebas | **1 430** tras los FIX de la auditoría del Lote C (9 812 aserciones en `0cccd14`; ver `f1-h4-fix-auditoria-lote-c.md`). Las corridas de cierre de §2, sobre `51b5fe6`, dieron 1 424. H3 cerró con 1 224 |
| Cobertura por capa | Dominio **100,0 %** (umbral 95) · Aplicación **100,0 %** (85) · Infraestructura y HTTP **99,5 %** (70); total 99,6 % |
| Mutación (`make mutacion`, solo `src/*/Dominio/`) | **687** mutantes, 687 eliminados; MSI **100 %**, MSI cubierto **100 %**; ninguno omitido ni con tiempo agotado |
| PHPStan | Nivel 7, sin línea base ni `ignoreErrors` |
| `composer audit` | Sin avisos |
| `make verificar` | **660 s (11 min)**, Pest 510 s; código 0 |
| `make mutacion` | **154 s** |
| `make humo` (H0 a H4, tras un solo `make fresh`) | Todos con OK: H1 14 pasos, H2 16, H3 16 y H4 14; 39 s |

## 2. Corridas de cierre (sobre `51b5fe6`)

**Cómo se corrieron:**

- Un solo guion, en el directorio temporal de la sesión, las ejecutó una tras otra en segundo plano, sin nada más corriendo y sin otro comando mío mientras tanto: `make levantar`, `make verificar`, `make mutacion`, `make fresh`, `migrate:rollback`, `migrate:status`, `make fresh`, `make humo` y `make bajar`.
- Cada línea de salida lleva el segundo en que llegó, con la variable `SECONDS` de bash, sin procesos extra.
- `git status` al terminar mostraba solo este reporte, que aún no estaba confirmado.

### 2.1 `make verificar`

| Etapa | Llegó al segundo | Duración | Resultado |
|---|---:|---:|---|
| Arranque de `docker compose exec` y Pint | 47 | 47 s | `PASS … 702 files` |
| PHPStan nivel 7 | 121 | 73 s | `[OK] No errors` |
| Pest con cobertura, hasta el resumen | 631 | 510 s (`Duration: 509.58s`) | **1 424 pruebas** (9 738 aserciones) |
| Informe de cobertura y cobertura por capa | 658 | 27 s | 100,0 / 100,0 / 99,5 % (total 99,6 %) |
| `composer audit` | 659 | 1 s | `No security vulnerability advisories found.` |
| **Total** | | **660 s (11 min)**; código 0 | por debajo del aviso de 13 min |

Pint y PHPStan tardaron más que en las corridas de las tareas (47 y 73 s). Fue la primera corrida tras levantar el entorno de cero, sin caché de análisis, como observó H3 en su T09. Pest tardó lo mismo que en T06 (512 s).

### 2.2 `make mutacion`

| Etapa | Llegó al segundo | Resultado |
|---|---:|---|
| Pruebas iniciales | 20 | PHPUnit 12.5.33, cuatro hilos |
| Mutantes y resumen | 153 | 687 generados, 687 eliminados; MSI 100 %, MSI cubierto 100 % (`Time: 2m 31s`) |
| `bin/verificar-mutacion.php` | 153 | «Mutación completa: 687 mutantes evaluados, ninguno omitido ni con tiempo agotado.» |
| **Total** | **154 s**; código 0 | |

### 2.3 Migración (§10 punto 4)

1. `make fresh`: código 0, 5 s.
2. `php artisan migrate:rollback --database=propietario --force`: código 0, 3 s. Revierte las 63 migraciones (11 de Rendimiento: siete tablas y cuatro historiales), sin errores.
3. `migrate:status`: las 63 aparecen como `Pending`, ninguna como `Ran`.
4. `make fresh`: código 0, 6 s. Deja la base limpia y sembrada, con `MetricasSeeder` y `PlantillasDeFabricaSeeder`.

### 2.4 Humo (§10 punto 6)

`make humo`, tras el `make fresh` del punto 4: código 0, 39 s. H0 llegó en el segundo 2, H1 en el 9, H2 en el 17, H3 en el 29 y H4 en el 39, todos con OK. La salida de H4 está en §8.

## 3. Por tarea

| Tarea | Commit | Pruebas de la suite | Pest | `make verificar` |
|---|---|---:|---:|---:|
| Especificación v1.0 tal como llegó | `a83777a` | — | — | — |
| T00 `synchronous_commit = off` (PT-08) | `7b4303e` | 1 225 | 410 y 417 s | 482 y 451 s |
| F1-DOC: correcciones de T00 | `dd2a7fe` | — | — | — |
| T01 Migraciones y seeders | `fe42743` | 1 255 | 476 s | 526 s |
| T02 Captura (HU-14) | `3494cd0` | 1 314 | 504 s | 546 s |
| F1-DOC: resumen del Lote A | `779e79a` | — | — | `make mutacion` 141 s (614 de 614) |
| F1-DOC: v1.0.4 y plan de T03 | `e6e0345` | — | — | — |
| T03 Plantillas y umbrales (HU-15) | `c0d36cf` | 1 381 | 495 s | 538 s; `make mutacion` 663 de 663 |
| F1-H4-FIX: DA-28 retirada | `245c44c` | 1 381 | 595 s | 686 s |
| F1-DOC: v1.0.5 y plan de T04 | `11256b2` | — | — | — |
| T04 Pasa, no pasa y datos insuficientes (HU-16) | `b6aa48e` | 1 407 | 531 s | 574 s; `make mutacion` 687 de 687 |
| F1-H4-FIX del Lote B | `f900c6b` | 1 410 | 508 s | 562 s |
| T05 Seguridad y alcance | `c0f1601` | 1 421 | 530 s | 610 s (una corrida previa falló en PHPStan) |
| F1-DOC: v1.0.7 (paso 9 del humo) | `4b891a5` | — | — | — |
| T06 Humo y cierre documental | `51b5fe6` | 1 424 | 512 s | 675 s; `make mutacion` 166 s (687 de 687) |

Línea base al empezar H4 (`1fa839a`): 1 224 pruebas, Pest 618 s, `make verificar` 689 s.

**Detalle por tarea:** archivos, pruebas por identificador con la salida de la falla previa, lo que no se hizo, fuera de alcance y desviaciones están en el reporte de cada una:

- `f1-h4-plan-t00.md` y `f1-h4-t00.md`, `f1-h4-t01.md` y `f1-h4-t02.md`;
- `f1-h4-plan-t03.md` y `f1-h4-t03.md`, `f1-h4-plan-t04.md` y `f1-h4-t04.md`;
- `f1-h4-fix-lote-b.md`, `f1-h4-t05.md` y `f1-h4-t06.md`;
- el resumen de los lotes A y B y el tiempo de Pest por tarea, en `f1-h4-progreso.md`.

**Resumen**

- **T00:** `synchronous_commit = off` en las dos conexiones de prueba desde `tests/TestCase.php`; Pest pasó de 618 s a unos 410 s (DA-01 y DA-02).
- **T01:**
  - siete tablas de Rendimiento con sus cuatro historiales;
  - `MetricasSeeder` (nueve métricas y 15 relaciones con posición) y `PlantillasDeFabricaSeeder` («Desarrollo» 6.00 con ventana 5 por posición);
  - dos `CHECK` de consistencia.
- **T02:**
  - captura, corrección, lista por partido, pendientes (en Consultas) y catálogo;
  - puertos `PosicionEnEquipo` y `ConvocatoriaDePartido`, con sus adaptadores en Plantel y Competencia;
  - RN-14 por `registrado_por`.
- **T03:**
  - plantillas propias y umbral con cierre e historial;
  - dos UQ parciales en plantillas (v1.0.4);
  - `categoria_calificacion` (RN-06);
  - prueba de la carrera de la captura.
- **FIX `245c44c`:** se retiró DA-28 (no hay fuente en `docs/requisitos.md`).
- **T04:**
  - cálculo en el Dominio con el promedio redondeado;
  - orden por fecha del partido con el puerto `InicioDePartidos`;
  - filtro por equipo;
  - evaluación persistida en la misma transacción (puerto `Transaccion`);
  - lectura del desempeño.
- **FIX `f900c6b`:**
  - 409 `sin_umbral_aplicable` (DA-33, y DA-26 alineada);
  - DA-34 acotada a partidos eliminados lógicamente, que H3 no produce;
  - las dos líneas sin cubrir, probadas directamente sobre la clase.
- **T05:**
  - recorrido de seguridad de las 10 rutas;
  - 18 mutaciones de filtros de alcance en copia temporal: 17 eliminadas y una equivalente (M11).
- **T06:** humo de 14 pasos (paso 9 con E2, v1.0.7), trazabilidad, registro de decisiones y este reporte.

## 4. Rutas tocadas que la especificación no permitía

1. **Rutas base de H4.** La especificación de H4 no las define.
   - **Qué se tocó:** en T02, T03, T04 y el FIX del Lote B, `app/Http/Controllers/**`, `app/Http/Requests/**`, `app/Http/Resources/**`, `app/Exceptions/FormatoDeError.php`, `lang/es/errores.php` y `routes/api.php`.
   - **Por qué:** se usó la definición de H3 (reporte de T02, §8).
   - **Estado:** **pendiente de confirmación del responsable** desde el Lote A.
2. **`src/Compartido/Aplicacion/InicioDePartidos.php`, su adaptador en `src/Competencia/Infraestructura/` y el enlace en `CompetenciaServiceProvider` (T04):** autorizados por el responsable y agregados a §8 en v1.0.5.
3. **`docs/hitos/f1-h4.md`:** se modificó en cada versión (1.0.1 a 1.0.7), siempre por decisión del responsable y con fila en §12.

No se tocó `docs/arquitectura.md`, `docs/requisitos.md`, `frontend/`, `backend/CLAUDE.md` ni `bin/verificar-cobertura.php`.

## 5. Decisiones del agente (DA-nn)

DA-01 a DA-36 en `f1-h4-decisiones-del-agente.md`, resumidas en la sección 6 de `docs/decisiones/registro-decisiones.md`. **DA-28 está retirada**, y DA-13 está sustituida por DA-36 (DD-17; F1-H4-FIX de la auditoría del Lote C, v1.0.8).

Las que tocan permisos o privacidad son:

- DA-04: umbral y evaluación por entrenador.
- DA-11: el 404 va antes que `jugador_no_convocado`.
- DA-12: la captura excluye a los jugadores en baja.
- DA-18: un rendimiento ajeno da 404.
- DA-19: las respuestas revelan lo mínimo.
- DA-20: un convocado sin membresía da 404.
- DA-23: las UQ de plantillas no revelan plantillas ajenas.
- DA-29: cualquier entrenador crea plantillas de cualquier posición.

## 6. Compuerta de H4 (§10), punto por punto

| # | Punto | Estado |
|---|---|---|
| 1 | `make verificar` en local y en la integración continua; `make mutacion` al cerrar el lote | Local: cumplido (§2.1 y §2.2). **Integración continua: no ejecutada**, porque no hay push; la ejecuta el responsable |
| 2 | Cada historia con pruebas con su identificador; diferidos de §2.2 en la trazabilidad | Cumplido: HU-14, HU-15, HU-16 y HU-04 c2; los diferidos están en «Estado al cierre de H4» |
| 3 | `openapi.yaml` con las rutas de §6.1 y respuestas reales que coinciden | Cumplido: cuatro pruebas de contrato (`ContratoDeRendimientoTest`, `ContratoDePlantillasYUmbralesTest`, `ContratoDeDesempenoTest`) más la de T02 |
| 4 | `make fresh` y `migrate:rollback` sin errores | Cumplido (§2.3) |
| 5 | Tablas de §7, una prueba por fila | Cumplido: §7.1 filas 1 a 11 y 13 (la 12 se eliminó en v1.0.3); §7.2 filas 1 a 3 y 5 a 7 (la 4 está reservada en v1.0.4); §7.3 filas 1 a 7; §7.4 filas 1 a 5 |
| 6 | `make humo` (H0 a H4) sin fallas | Cumplido (§2.4 y §8) |
| 7 | Pasa, no pasa y datos insuficientes; cada entrenador ve solo lo suyo; el docente no ve rendimiento | Cumplido: `CalculoDeDesempenoTest`, `DesempenoTest`, `RendimientoSeguridadTest` y pasos 8 a 12 del humo |
| 8 | Este reporte, con lo que pide §10 de H3 y los tiempos por etapa | Este documento: tiempos por etapa en §2 y por tarea en §3 |
| 9 | DD-09 y DD-10 confirmadas; AB-01 por validar | Cumplido en `registro-decisiones.md` |
| 10 | Auditoría independiente del Lote C, sin bloqueantes | **Pendiente**: es el siguiente paso |
| 11 | Revisión y aprobación del responsable; etiqueta y fusión | Pendiente (responsable) |

## 7. Pendientes que H4 deja

1. **Para el responsable:**
   - confirmar las rutas base de H4 (§4);
   - actualizar `docs/arquitectura.md`: el nombre del seeder (§8.4), las columnas de §7.6 (`entrenador_id`, columnas copiadas y procedencia de la evaluación, `CHECK`), las dos UQ de plantillas y los puertos `PosicionEnEquipo` e `InicioDePartidos`;
   - la redundancia de M11 (T05, fuera de alcance).
2. **AB-01 (deportivo):** validar con la institución la plantilla «Desarrollo», los rangos de las métricas y los de tarjetas.
3. **Para H5:** HU-04 c3 (estado oficial de disponibilidad).
4. **Para H6:**
   - sanciones (RN-09) y riesgo de baja deportiva (RN-10);
   - alertas por bajo rendimiento;
   - mejor desempeño en la convocatoria (DD-12, RN-18);
   - HU-25 y gráficos;
   - DD-08;
   - revisar si `docs/arquitectura.md` §4.2 exige eventos de Rendimiento.

## 8. Salida del humo de H4

Corrida de cierre (§2.4), después de H0 a H3 en la misma base:

```
OK     paso 1: E1 inicia sesión -> HTTP 200
OK     paso 2: E1 consulta el catálogo: las nueve métricas sembradas -> HTTP 200
OK     paso 3: E1 crea la categoría Sub-11 -> HTTP 201
OK     paso 3: E1 crea el equipo A -> HTTP 201
OK     paso 3: registra a Pe Uno (HUMO-H4-001, portero) -> HTTP 201
OK     paso 3: registra a De Uno (HUMO-H4-002, defensa) -> HTTP 201
OK     paso 3: registra a Efe Uno (HUMO-H4-003, delantero) -> HTTP 201
OK     paso 3: ya hay una temporada que contiene hoy (la del humo de H3) -> HTTP 409
OK     paso 3: E1 crea un lugar -> HTTP 201
OK     paso 3: E1 crea un rival -> HTTP 201
OK     paso 3: E1 crea el amistoso PJ1 -> HTTP 201
OK     paso 3: E1 convoca a un jugador a PJ1 -> HTTP 201
OK     paso 3: E1 convoca a un jugador a PJ1 -> HTTP 201
OK     paso 3: E1 convoca a un jugador a PJ1 -> HTTP 201
OK     paso 3: E1 captura el resultado: PJ1 queda jugado -> HTTP 200
OK     paso 3: E1 captura a P1 en PJ1 con sus métricas -> HTTP 201
OK     paso 4: E1 captura a D1 sin recuperaciones: metrica_obligatoria_faltante -> HTTP 422
OK     paso 5: E1 consulta pendientes: PJ1 aparece porque le faltan D1 y F1 -> HTTP 200
OK     paso 6: E1 consulta el umbral de F1: la plantilla «Desarrollo» (6.00, ventana 5) -> HTTP 200
OK     paso 7: E1 cambia el umbral de F1 a 7.0 con ventana 3 -> HTTP 200
OK     paso 8: E1 consulta el desempeño de F1: datos_insuficientes con el umbral nuevo -> HTTP 200
OK     paso 9: E2 inicia sesión (otro archivo de cookies) -> HTTP 200
OK     paso 9: E2 lista los rendimientos de un partido inexistente (referencia) -> HTTP 404
OK     paso 9: E2 lista los rendimientos de PJ1: 404 igual byte a byte -> HTTP 404
OK     paso 10: E2 intenta corregir el rendimiento de E1: 404 igual byte a byte -> HTTP 404
OK     paso 11: el docente inicia sesión -> HTTP 200
OK     paso 11: el docente consulta pendientes: 403 -> HTTP 403
OK     paso 12: sin sesión, el desempeño responde 401 -> HTTP 401
OK     paso 13: E1 captura a F1 con 151 minutos: valor_fuera_de_rango -> HTTP 422
OK     paso 14: E1 cierra sesión -> HTTP 204
OK     paso 14: la misma cookie responde 401 -> HTTP 401
HUMO H4 OK: 14 pasos
```

## 9. Desviaciones del hito

- **T02:** ejecuté un `cat >>` sobre un documento mientras corría `make verificar`. La corrida del commit no tuvo nada en paralelo.
- **F1-H4-FIX del Lote B:** ejecuté un `cat` de la bitácora de la corrida, en el directorio temporal, mientras corría `make verificar`, para ver si había terminado. No tocó el contenedor ni el repositorio, y la corrida terminó con código 0.
- **T06:** escribí `humo-h4.sh` antes que sus pruebas, así que no tengo la salida de la falla previa (`f1-h4-t06.md` §7).
- **Coautoría:** los commits llevan `Co-Authored-By: Claude Haiku 5.5` por indicación del responsable; la sesión es Opus 5.5 (DA-03).
