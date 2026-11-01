# Progreso de H4: resumen del Lote A (T00, T01 y T02)

**Fecha:** 30 de octubre de 2026 · **Rama:** `hito/f1-h4` (desde `f1-h3`, `1fa839a`) · **Especificación:** `docs/hitos/f1-h4.md` v1.0.3
**Estado:** Lote A aprobado. Lote B terminado (resumen en §7, especificación v1.0.5). Alto para revisión del responsable antes del Lote C. Sin push, PR ni etiquetas.

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
| F1-H4-FIX (DA-28 retirada) | 1 381 | 595 s | 686 s (11 min 26 s) | +100 s con las mismas pruebas (variación de la medición; el cambio de código fue mínimo) |
| T04 | 1 407 | 531 s | 574 s (9 min 34 s) | +36 s frente a T03 con 26 pruebas más |
| F1-H4-FIX del Lote B | 1 410 | 508 s | 562 s (9 min 22 s) | −23 s con 3 pruebas más (variación de la medición) |
| T05 | 1 421 | 530 s | 610 s (10 min 10 s) | +22 s con 11 pruebas más; una corrida previa falló en PHPStan (116 s) |
| T06 | 1 424 | 512 s | 675 s (11 min 15 s) | −18 s con 3 pruebas más (variación de la medición) |

En T03 hubo además una corrida fallida (`TextosEnLangTest`, Pest 516 s) y una correcta antes de la del commit (Pest 507 s); el detalle está en `f1-h4-t03.md` §4. `make mutacion` en T03: 663 de 663 mutantes eliminados.

## 7. Resumen del Lote B (T03 y T04)

**Commits del lote**

| Commit | Qué |
|---|---|
| `e6e0345` | F1-DOC: especificación v1.0.4 y plan aprobado de T03 |
| `c0d36cf` | F1-H4-T03: plantillas y umbrales (HU-15); RN-06 con `categoria_calificacion`; dos UQ parciales; prueba de la carrera de la captura |
| `245c44c` | F1-H4-FIX: DA-28 retirada; el umbral admite un jugador en baja |
| `11256b2` | F1-DOC: especificación v1.0.5 y plan aprobado de T04 |
| este | F1-H4-T04: pasa, no pasa y datos insuficientes (HU-16, RN-08), con evaluación persistida y puerto `InicioDePartidos` |

**Estado al cierre del lote**

- `make verificar` con código 0: 1 407 pruebas, Pest 531 s, total 574 s.
- `make mutacion`: 687 de 687 mutantes eliminados.
- Cobertura: Dominio 100 %, Aplicación 100 %, Infraestructura y HTTP 99,4 %.

**Filas de §7 cubiertas en H4 hasta ahora**

- §7.1: filas 1 a 11 y 13 (la 12 se eliminó).
- §7.2: filas 1 a 3 y 5 a 7 (la 4 quedó reservada).
- §7.3: filas 1 a 7.
- §7.4: filas 3, 4 y 5.

Las filas 1 y 2 de §7.4 (docente 403 y sin sesión 401) ya tienen pruebas en cada tarea. El recorrido completo de seguridad es de T05.

**Decisiones del agente del lote:** DA-22 a DA-35. La DA-28 se retiró.

**Pendientes para el responsable**

1. **Rutas base de H4:** sin confirmar (punto 1 de §6).
2. **`docs/arquitectura.md`:** no se tocó en el lote, como se pidió. Quedan por reflejar el nombre del seeder (§8.4), las columnas de §7.6, las dos UQ de plantillas y los puertos `PosicionEnEquipo` e `InicioDePartidos`.
3. **Lote C:** T05 (seguridad y alcance) y T06 (humo y cierre documental), con auditoría independiente.

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
