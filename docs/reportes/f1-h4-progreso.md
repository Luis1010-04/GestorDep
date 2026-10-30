# Progreso de H4: resumen del Lote A (T00, T01 y T02)

**Fecha:** 30 de octubre de 2026 · **Rama:** `hito/f1-h4` (desde `f1-h3`, `1fa839a`) · **Especificación:** `docs/hitos/f1-h4.md` v1.0.3
**Estado:** Lote A terminado. Alto para revisión del responsable antes del Lote B. Sin push, PR ni etiquetas.

## 1. Commits

| Commit | Qué |
|---|---|
| `a83777a` | F1-DOC: especificación v1.0 tal como llegó |
| `7b4303e` | F1-H4-T00: `synchronous_commit = off` en las conexiones de prueba (PT-08); especificación v1.0.1 |
| `dd2a7fe` | F1-DOC: correcciones de T00 (ventana de unos 600 ms en DA-02, variación fuera de Pest, `MSYS_NO_PATHCONV`) |
| `fe42743` | F1-H4-T01: siete tablas de Rendimiento y cuatro historiales; `MetricasSeeder` y `PlantillasDeFabricaSeeder`; v1.0.2 |
| `3494cd0` | F1-H4-T02: captura, corrección, lista por partido, pendientes (Consultas) y catálogo de métricas; v1.0.3 |
| este | F1-DOC: este resumen |

## 2. Tiempos (todas las corridas con código 0)

| Corrida | Pruebas | Pest | `make verificar` |
|---|---:|---:|---:|
| Línea base (`1fa839a`) | 1 224 | 618 s | 689 s |
| T00, corridas 1 y 2 | 1 225 | 410 y 417 s | 482 y 451 s |
| T01 | 1 255 | 476 s | 526 s |
| T02, corrida 1 | 1 312 | 509 s | 545 s |
| T02, corrida 2 (la del commit) | 1 314 | 504 s | 546 s (9 min 6 s) |

**`make mutacion` al cerrar el lote:** código 0, 141 s. 614 mutantes, todos eliminados; MSI 100 % y MSI cubierto 100 %, ninguno omitido ni con tiempo agotado.

El tiempo está por debajo del aviso de 13 minutos. Pest creció 94 s desde T00 con 89 pruebas más.

### Tiempo de Pest por tarea (Lote B)

Lo pidió el responsable al aprobar el Lote A: el tiempo de Pest de la corrida de `make verificar` del commit de cada tarea.

| Tarea | Pruebas | Pest | `make verificar` | Diferencia de Pest frente a la tarea anterior |
|---|---:|---:|---:|---:|
| T02 (referencia) | 1 314 | 504 s | 546 s | — |
| T03 | 1 381 | 495 s | 538 s (8 min 58 s) | −9 s con 67 pruebas más (variación de la medición) |

En T03 hubo además una corrida fallida (`TextosEnLangTest`, Pest 516 s) y una correcta antes de la del commit (Pest 507 s); el detalle está en `f1-h4-t03.md` §4. `make mutacion` en T03: 663 de 663 mutantes eliminados.

## 3. Cobertura al cierre del lote

Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,4 % (total 99,5 %). Sin cubrir en T02: la carrera de dos capturas simultáneas, líneas 64 y 65 de `RepositorioDeRendimientosEloquent`.

## 4. Cambios de la especificación en el lote

- **v1.0.1 (T00):** se cambió la palanca por `synchronous_commit` y se agregó `ConexionesDePruebaTest` a las rutas.
- **v1.0.2 (T01):**
  - rangos de fábrica de las métricas;
  - columnas copiadas de la evaluación y `plantilla_id`;
  - dos `CHECK` de consistencia;
  - nombre `PlantillasDeFabricaSeeder`, con la diferencia frente a la arquitectura pendiente.
- **v1.0.3 (T02):**
  - pendientes en Consultas;
  - puerto `PosicionEnEquipo`;
  - `rendimiento_no_editable` reservado;
  - §7.1: fila 12 eliminada, fila 13 nueva y fila 10 precisada;
  - §7.4 fila 3 y paso 9 del humo con E3.

## 5. Decisiones del agente

DA-01 a DA-21 en `f1-h4-decisiones-del-agente.md`. Estas son las que restringen permisos o privacidad:

- DA-04: umbral y evaluación por entrenador.
- DA-11: 404 antes que `jugador_no_convocado`.
- DA-12: no se captura a un jugador en baja.
- DA-18: un rendimiento ajeno da 404.
- DA-19: las respuestas revelan lo mínimo.
- DA-20: un convocado sin membresía vigente da 404.

## 6. Pendientes para el responsable

1. **Rutas base de H4:** `f1-h4.md` no las define. Se usó la definición de H3 con `src/Rendimiento/**` (reporte de T02, §8). Pide confirmación.
2. **DD-09:** ¿las categorías de texto de RN-06 se muestran en alguna respuesta de H4? Sugerencia: en el desempeño de T04, leídas de `parametros_institucionales`.
3. **`docs/arquitectura.md`:** quedan por actualizar el nombre del seeder (§8.4) y las columnas de §7.6 (`entrenador_id`, columnas copiadas y `plantilla_id`, y los dos `CHECK`).
4. **Plantillas propias de T03:** la UQ (`nombre`, `posicion_id`) abarca a todos los entrenadores, así que un 409 revelaría que otro entrenador tiene una plantilla con ese nombre. Conviene decidirlo en el plan de T03.
5. **Desviación de T02:** ejecuté un comando de terminal (`cat >>` sobre un documento) durante una corrida de `make verificar`. La corrida del commit no tuvo nada en paralelo.
6. **Coautoría:** los commits llevan `Co-Authored-By: Claude Haiku 5.5`, por indicación del responsable; la sesión es Opus 5.5 (DA-03).
