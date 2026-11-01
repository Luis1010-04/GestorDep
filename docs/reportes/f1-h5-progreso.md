# Progreso de H5

**Rama:** `hito/f1-h5` (desde `main`, `a34283e`) · **Especificación:** `docs/hitos/f1-h5.md` v1.0.3
**Estado:** T00 terminada; espera aprobación del responsable antes de T01. Sin push, PR ni etiquetas.

## 1. F1-H5-T00: PHPStan nivel 8

Plan aprobado (opción A, ayudante `NoNulo`) con las condiciones de `docs/reportes/f1-h5-plan-t00.md` §7.

### 1.1 Commits y cuenta de errores tras cada contexto

Cada cuenta es una corrida completa de PHPStan con `--level=8` dentro del contenedor, hecha después de corregir el contexto.

| Commit | Qué | Errores de nivel 8 |
|---|---|---:|
| `1b22df2` | F1-DOC: plan de T00 aprobado | 309 (medición del plan) |
| `52f8b6f` | Ayudante `Tests\Ayudantes\NoNulo` con su prueba unitaria; pruebas de Académico | 205 (−104) |
| `49d570d` | Pruebas de Plantel | 118 (−87) |
| `89527b8` | Pruebas de Competencia | 69 (−49) |
| `255f7c5` | Pruebas de Rendimiento | 33 (−36) |
| `3faebce` | Pruebas de Identidad | 12 (−21) |
| `8081e75` | Pruebas de Compartido | **0** (−12) |
| este | `phpstan.neon` en `level: 8`; este reporte | 0 |

### 1.2 Cómo se corrigió

- **Solo `tests/`.** `app/`, `src/`, `routes/` y `bootstrap/` ya daban 0 errores de nivel 8 y no cambiaron.
- **Orígenes envueltos con `NoNulo::exigir` (215 en total):**
  - 53 asignaciones `->first()` (o `collect(...)->firstWhere(...)`);
  - 133 lecturas por índice de una colección (`$historial[0]->operacion`);
  - 29 llamadas sobre un `?NumeroDecimal`, un `?stdClass` de un ayudante, un `?ReflectionMethod` o un `->first()` sin asignar.
- **Valores que alguna prueba espera nulos (condición 2): no se envolvieron en su origen.**
  - `recalculoResumen()` en `RecalculoDelResumenTest.php` sigue devolviendo `?stdClass`, porque la línea 191 espera nulo. Se envuelve en cada llamada que lee una columna.
  - `promedio()`, `porcentajeAsistencia()`, `porcentaje()`, `calificacion()` y `CalificacionDeMateria::calcular()` conservan su tipo `?NumeroDecimal` en el dominio (DD-43, DD-44, «sin registros»). Sus pruebas de nulo no cambiaron; solo se envolvieron las llamadas de las pruebas con datos.
  - Donde la prueba ya tenía un `expect($x)->not->toBeNull()` (la sesión autenticada en `IniciarSesionTest`, la calificación cero en `CalificacionDeMateriaTest` y el constructor del evento en `RecalculoDelResumenTest`), se envolvió el uso posterior y no la asignación. Así esa aserción sigue siendo la primera que falla.
- **Ninguna aserción cambió.** Antes de cada commit, un guion comparó el diff: quitando `NoNulo::exigir(` y `, '…')` de cada línea agregada y descontando el `use` nuevo, quedan exactamente las líneas quitadas. Dio OK en los seis contextos (82, 70, 27, 10, 13 y 11 líneas). Los únicos otros cambios son el reordenamiento de imports de Pint.

### 1.3 Cierre: `make verificar`, `make mutacion` y `make humo`, en serie y sin nada en paralelo

Antes de las corridas se bajaron los contenedores que habían quedado levantados desde la medición y se volvieron a levantar con `make levantar`.

| Corrida | Resultado | Tiempo |
|---|---|---:|
| `make verificar` | Código 0. Pint sin cambios, PHPStan nivel 8 «No errors», 1 432 pruebas (9 821 aserciones), cobertura Dominio 100 %, Aplicación 100 %, Infraestructura y HTTP 99,5 %, sin avisos de seguridad | 990 s (16 min 30 s) |
| `make mutacion` | Código 0. 687 de 687 mutantes eliminados; MSI 100 %, MSI cubierto 100 % (los mismos umbrales) | 200 s |
| `make fresh` + `make humo` | Código 0. H0 a H4 OK (119 pasos OK) | 51 s el humo |

### 1.4 Tiempos de Pest

| Corrida | Pruebas | Pest | `make verificar` |
|---|---:|---:|---:|
| Referencia: última fila de `f1-h4-progreso.md` (T06) | 1 424 | 512 s | 675 s |
| Cierre de T00 | 1 432 | **647 s** | 990 s |

Pest tardó 135 s más que la referencia de H4. Las pruebas son las mismas salvo las 2 del ayudante y las que se hayan agregado al cierre de H4, y el cambio de T00 no agrega consultas: solo envuelve valores. La diferencia cae en la variación ya observada en H4, donde la misma suite osciló entre 495 y 595 s. Aun así, 990 s de `make verificar` superan el aviso de 13 minutos que usa `f1-h4-progreso.md`. La parte que no es Pest también subió (343 s frente a 163 s); ahí corre PHPStan, que esta vez arrancó sin caché tras recrear los contenedores. Queda para vigilar en T01.

Corridas parciales de Pest (con `bin/con-candado.sh`, cada una por separado), hechas después de corregir cada contexto:

| Contexto | Pruebas | Pest |
|---|---:|---:|
| Académico (más `NoNuloTest`) | 360 | 167,0 s |
| Plantel | 234 | 132,7 s |
| Competencia | 276 | 114,5 s |
| Rendimiento | 187 | 81,6 s |
| Identidad | 76 | 34,8 s |
| Compartido | 111 | 69,0 s |

### 1.5 Desviaciones e incidencias

1. **Tipo de origen que el plan no previó:** el plan contaba unos 90 orígenes, casi todos `->first()`. 135 de los 309 errores (133 lecturas) venían de leer por índice una colección de `DB::table()->get()`: PHPStan tipa `$coleccion[0]` como `stdClass|null` porque pasa por `ArrayAccess`. Se trató igual que los demás orígenes de la opción A, con `NoNulo::exigir($historial[0], 'la fila 0 de $historial')`, sin convertir la colección en arreglo y sin tocar las aserciones.
2. **Forma del ayudante:** los demás archivos de `tests/Ayudantes/` son funciones globales cargadas desde `tests/Pest.php`. `NoNulo` es la clase `Tests\Ayudantes\NoNulo` del plan aprobado y se carga por el PSR-4 de `autoload-dev`, sin tocar `tests/Pest.php`.
3. **`make bajar` falló la primera vez:** Docker no recibió el evento de salida del contenedor `db`. El segundo intento lo bajó y quitó la red. No afectó a ninguna corrida.
4. **`make humo` falló la primera vez:** la base de desarrollo conservaba los datos de una corrida anterior, y H1 paso 3 dio 409 `categoria_duplicada`, como ya documenta `f1-h1-reporte.md`. Se corrió `make fresh` y el humo pasó.

### 1.6 Corrección pedida al aceptar T00

`tests/Unit/Herramientas/ConfiguracionDePhpstanTest.php` exige ahora nivel 8 como mínimo, no 7. El cambio va en el mismo commit que `phpstan.neon`. Se comprobó que con `level: 8` pasan las tres pruebas del archivo y que con `level: 7`, puesto de forma temporal, la prueba del nivel falla con «Failed asserting that 7 is equal to 8 or is greater than 8». Después se volvió a `level: 8`.

### 1.7 Fuera de alcance

- Nota de RNF-02 para la auditoría: los catálogos `rangos_mediciones_corporales` y `tipos_prueba` no tienen historial (`f1-h5-plan-t00.md` §5).
