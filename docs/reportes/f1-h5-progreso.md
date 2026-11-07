# Progreso de H5

**Rama:** `hito/f1-h5` (desde `main`, `a34283e`) · **Especificación:** `docs/hitos/f1-h5.md` v1.0.4
**Estado:** Lote A terminado, a la espera de revisión del responsable. T00 aceptada con dos correcciones. La primera, el mínimo de nivel 8 en la prueba de configuración, está hecha. La segunda, la condición de parada, se activó en la laptop anterior (§1.8) y el responsable la cerró el 3/11/2026 sin cambios de configuración, con las corridas de la laptop nueva (§1.13). T01 terminada (§2). La rama se publicó por orden del responsable para seguir en otra computadora (§1.12); los commits hasta `18db0da` se publicaron después, también por orden suya (el último push es del 5/11/2026); lo posterior es local. Sin PR ni etiquetas.

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

Pest tardó 135 s más que la referencia de H4. La explicación, con datos, está en §1.9.

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

### 1.8 Mediciones de `make verificar` y cachés de PHPStan y Pint

Seis corridas de `make verificar`, todas en serie y sin nada en paralelo. De la 2 a la 4, por decisión del responsable, sin bajar los contenedores: así las cachés de PHPStan y de Pint de la corrida anterior seguían dentro del contenedor (§1.8.2). Las corridas A y B son la prueba que aprobó el responsable: `make bajar`, `make levantar`, `make verificar` y `make verificar`.

#### 1.8.1 Corridas y tiempos por etapa

Desde la corrida 2, cada línea de la salida llevó una marca de tiempo, y las fronteras de cada etapa salen de la primera y la última línea. La corrida 1 no tuvo marcas, así que solo tiene el total y la `Duration` de Pest.

| Corrida | Qué la precedió | Arranque y Pint | PHPStan | Pest | Informe de cobertura | Total |
|---|---|---:|---:|---:|---:|---:|
| 1 | `make bajar` y `make levantar`: contenedores recién creados, cachés vacías | — | — | 647 s | — | **990 s** |
| 2 | `make mutacion`, `make fresh`, `make humo` y la corrección de §1.6 | 78 s | **114 s** | 712 s | 50 s | **955 s** |
| 3 | Las corridas de diagnóstico de PHPStan de §1.8.3 | 16 s | **5 s** | 688 s | 47 s | **756 s** |
| 4 | `make mutacion`, inmediatamente antes | 9 s | **unos 4 s** | 641 s | 48 s | **702 s** |
| A | `make bajar` y `make levantar`: contenedores recién creados, cachés vacías, tras unas 4 horas sin uso | 63 s | **400 s** | 737 s, cortado | no llegó | **1 202 s, cortada por el tope** |
| B | La corrida A, inmediatamente antes | 11 s | **3 s** | 626 s | 46 s | **686 s** |

Salvo la corrida A, en todas `bin/verificar-cobertura.php` y `composer audit` tardaron menos de 1 s, y todas terminaron con código 0 y los mismos resultados: 1 432 pruebas; cobertura de 100 % en Dominio, 100 % en Aplicación y 99,5 % en Infraestructura y HTTP; sin avisos de seguridad.

**La corrida A no terminó.** `bin/con-candado.sh` la cortó a los 20 minutos («La corrida supero el tope de 20 minutos y se termino», `make: *** Error 124`, código 2), con 1 406 de 1 432 pruebas completadas y ninguna fallida. El corte fue solo por tiempo.

- **Con las cachés calientes (corridas 3, 4 y B), `make verificar` queda por debajo de 15 minutos:** 756, 702 y 686 s. PHPStan tardó entre 3 y 5 s, y el arranque más Pint, entre 9 y 16 s.
- **Las corridas 1, 2 y A pasaron de 15 minutos.**
  - En la 1 y en la A, las cachés frías eran lo esperado: empezaron poco después de crear el contenedor, con las dos cachés vacías.
  - En la 2, PHPStan (114 s) y Pint (unos 70 s) tardaron como en frío, aunque sus cachés de la corrida 1 estaban en el contenedor. Eso sigue sin explicarse (§1.8.4).
- **Pest es casi todo el tiempo:** en las corridas completas, entre 626 y 712 s, es decir, entre el 75 % y el 91 % del total.

#### 1.8.2 Dónde viven las cachés

- **PHPStan:** `tmpDir: /tmp/phpstan` y `resultCachePath: /tmp/phpstan/resultCache.php`, según `phpstan dump-parameters`.
- **Pint:** el proyecto no tiene `pint.json`, así que Pint usa su archivo por omisión. Según `ConfigurationResolverFactory`, el nombre es `/tmp/md5(rutas analizadas | versión de Prettier)`. Para todo el proyecto, como lo corre `verificar`, es `/tmp/56f20e94680473f6417d411c2a2def09` y cubre 704 archivos. Una corrida sobre un archivo suelto usa otro nombre.
- **Ninguna de las dos es persistente.** Las dos viven en la capa del contenedor `app`, sin volumen, así que `make bajar` (`docker compose down`) las borra.
- **El log de `make verificar` no muestra si se usó la caché.** `composer verificar` corre PHPStan con `--no-progress` y sin `-v`, y PHPStan solo informa de la caché en modo detallado. Por eso se comprobó de dos formas:
  - con corridas de diagnóstico con `-vv`;
  - comparando la hora y la sha1 de `resultCache.php` antes y después.

  En las corridas 3, 4 y B, `resultCache.php` no cambió. En B, la caché de Pint tampoco.

#### 1.8.3 Qué no enfría las cachés

| Paso | Evidencia |
|---|---|
| `pint --test` (como lo llama `verificar`) | No escribe archivos: la sha1 conjunta de los `.php` del backend es la misma antes y después, y `git status` sigue vacío. PHPStan con `-vv`, corrido justo después de Pint: «Result cache restored. 0 files will be reanalysed», 3,2 s. Sin Pint antes: 0 archivos, 3,1 s |
| `make fresh` | `/tmp/phpstan` sigue existiendo, y `resultCache.php` tiene la misma hora y la misma sha1. El contenedor no se reinicia: solo ejecuta `artisan migrate:fresh --seed` dentro de él |
| `make humo` | Lo mismo que `make fresh`. Solo ejecuta los guiones de `scripts/humo/`. H0 a H4 OK |
| `make mutacion` | El inventario de `/tmp/phpstan` (ruta, tamaño y hora de 5 479 entradas) es idéntico antes y después, y la caché de Pint tiene la misma sha1. Infection usa su propio `tmpDir`, `/tmp/infection`, y no configura PHPStan ni ningún análisis estático. La corrida 4, justo después, tuvo PHPStan en unos 4 s |
| Pest dentro de `verificar` | Pest de la corrida 3 se ejecutó antes que la corrida 4, y la 4 salió caliente |
| Cambiar la fecha de `phpstan.neon` (`touch`) | 0 archivos reanalizados |
| Correr PHPStan a través de Composer | 0 archivos reanalizados |
| Editar `ConfiguracionDePhpstanTest.php` | 1 archivo reanalizado, 11 s |
| Regenerar los manifiestos de `bootstrap/cache/` | Misma md5 antes y después |
| Que las cachés las haya creado la primera corrida tras `make levantar` | La corrida A las creó desde cero y la B, justo después, salió caliente: PHPStan 3 s, y `resultCache.php` y la caché de Pint con la misma hora y la misma sha1 |

Por eso los 990 s de la corrida 1 y los 955 s de la corrida 2 **no se atribuyen a `make fresh` ni a `make humo`**.

#### 1.8.4 Lo que sigue sin explicar: el enfriamiento de la corrida 2

Lo que se sabe:

- Antes de la corrida 2, `/tmp/phpstan/cache` tenía los 2 657 archivos que escribió la corrida 1 (de 18:01 a 18:03 UTC), y la corrida 2 no borró ninguno.
- A las 19:39:09–10, en plena etapa de PHPStan, PHPStan hizo dos cosas:
  - regeneró su contenedor de configuración, `Container_673c1dc0ac.php`, con el mismo nombre que a las 18:01;
  - escribió por primera vez el marcador `cache/PHPStan/cache-cleared`.
- Después reescribió solo 5 archivos `.dat`. Al terminar, reescribió `resultCache.php`.
- Que el contenedor conserve el nombre indica que la configuración era la misma y que nette lo dio por vencido porque cambió la fecha de un archivo del que depende. Eso coincide con el cambio temporal de nivel 8 → 7 → 8 en `phpstan.neon` de las 18:36:45 (§1.6). Pero `touch phpstan.neon` no tira la caché de resultados (§1.8.3): eso explica la regeneración del contenedor, no los 114 s.
- Pint también tardó como en frío en la corrida 2: unos 70 s, frente a 9–16 s con caché.

**Hipótesis descartada.** Se pensó que las cachés creadas por la primera corrida tras `make levantar` no servían para la siguiente. La prueba A/B la descarta: la corrida B salió caliente (§1.8.3).

**Hipótesis abierta, sin demostrar: el entorno, no las cachés.** Los datos que la apoyan:

- La corrida A fue más lenta que cualquier otra corrida en frío, en todas sus etapas. PHPStan tardó 400 s, frente a unos 100 s en la corrida 1, deducidos de las horas de sus archivos de caché (18:01–18:03).
- Con el mismo código, los 105 archivos de prueba que terminaron en A y en B tardaron un 19 % más en A (717 s frente a 603 s). `ArquitecturaTest`, que no usa la base, tardó 50,2 s en A y 35,6 s en B.
- Las dos corridas anómalas empezaron tras un rato largo sin uso: la 2 tras una hora (18:37 a 19:37) y la A tras unas 4 horas (22:57 a 02:53).
- Antes de la corrida 2 solo había cambiado `ConfiguracionDePhpstanTest.php`. Que PHPStan reescribiera `resultCache.php` cuadra con haber reanalizado solo ese archivo. Los 114 s pudieron ser lentitud del entorno y no una caché descartada. El log de esa corrida no permite distinguirlo, porque `verificar` no corre PHPStan con `-v`.

No se midió fuera de las corridas, y dos casos no bastan para demostrarlo.

#### 1.8.5 Tope del candado

`bin/con-candado.sh` corta `make verificar` a los 20 minutos (1 200 s, PT-07). **El tope ya se alcanzó en la corrida A.** Con su ritmo, esa corrida habría necesitado unos 1 260 s para terminar; es una estimación, porque faltaron 26 pruebas y el informe de cobertura.

- **Con las cachés calientes:** el resto de etapas suma unos 60 s (corridas 4 y B). Con el Pest más lento medido, 798 s, el total quedaría en unos 860 s: por debajo de 15 minutos y a unos 5,7 minutos del tope.
- **Con las cachés frías:** depende del entorno. La corrida 1 dio 990 s y la A no cupo en 1 200 s.

Después de `make bajar`, la primera `make verificar` puede cortarse sin que falle ninguna prueba. Un corte así (Error 124) no dice nada del código: hay que volver a correrla sin bajar los contenedores.

#### 1.8.6 Medición de DA-57 (7/11/2026)

Una corrida de `make verificar` completa, con la laptop en carga normal y sin auditor activo, sobre el commit «F1-H5-T05: límite efectivo del rango frente a la columna (AU-09)». Los contenedores llevaban unos 45 minutos arriba y ya habían corrido Pint, PHPStan, `make mutacion` y pruebas sueltas, así que las cachés no estaban frías. La salida no lleva marcas de tiempo por línea, así que no hay tiempos por etapa.

| Dato | Valor |
|---|---|
| Código | 0 |
| Tiempo total | **753 s** (12 min 33 s), 27 s por debajo del aviso de 13 minutos |
| Pest | 684,54 s |
| Pruebas | 1 839 (11 587 aserciones) |
| Cobertura | Dominio 100 %, Aplicación 100 %, Infraestructura y HTTP 99,5 % (total 99,6 %) |
| PHPStan | «No errors» |
| Auditoría de dependencias | Sin avisos de seguridad |

Log: `docs/reportes/logs/verificar/f1-h5-da57-20261107-1022.log`.

El encabezado del log dice commit base fea05b1. Ese hash ya no existe en la rama porque se reescribió el committer. El contenido del log no cambió. La medición corresponde al árbol de 371bf36 y no incluye la prueba de estatura de 0deda12; la CI del tip valida el estado final.

**Comparación, sin conclusiones todavía:** los cierres de T05 dieron 651, 564 y 661 s con 1 831 a 1 835 pruebas (§11 y `f1-h5-t05.md` §6). Esta corrida tiene 4 pruebas más y tardó entre 92 y 189 s más, casi todo en Pest. Una sola corrida no separa la carga de la laptop del código, y la parte de las cachés que pide DA-57 sigue sin hacerse.

#### 1.8.7 Medición de DA-57 con la caché persistente (7/11/2026)

Opción (i) de DA-57: volúmenes nombrados para `/tmp/phpstan` y `/tmp/pint`, y `backend/pint.json` solo con `cache-file`. Sobre el commit «F1-H5: caché de PHPStan y de Pint en volumen (DA-57, opción i)», con la laptop en carga normal y sin auditor activo. Secuencia: `make bajar`, `make levantar` (volúmenes nuevos y vacíos), corrida fría, corrida caliente y, al final, otro `make bajar` y `make levantar` sin correr nada.

| Corrida | Estado de las cachés | Código | Total | Pest | Total menos Pest | Pruebas |
|---|---|---|---:|---:|---:|---|
| Fría | No existían antes de empezar | **2** | **836 s** | 678,65 s | 157 s | 1 840 pasadas y **1 fallida** (11 594 aserciones) |
| Caliente | Las creó la fría | 0 | **788 s** | 735,55 s | 52 s | 1 841 (11 594 aserciones) |
| Referencia de §1.8.6 | No estaban frías | 0 | 753 s | 684,54 s | 68 s | 1 839 (11 587 aserciones) |

La caliente dio PHPStan «No errors», cobertura Dominio 100 %, Aplicación 100 %, Infraestructura y HTTP 99,5 % (total 99,6 %) y sin avisos de seguridad.

**Las dos corridas pasaron el aviso de 13 minutos** (780 s), sin llegar al tope de 15.

**La caché se usa y persiste.** `resultCache.php` de PHPStan y `pint.cache` de Pint se crearon en la fría y no cambiaron en la caliente: mismo tamaño, misma hora y misma sha1 (`1caa1ad7d99b` y `4d356477d7e5`). Después de otro `make bajar` y `make levantar` seguían iguales. Lo que no es Pest bajó de 157 s en frío a 52 s en caliente.

**El total no bajó.** Pest varió entre 678 y 735 s con casi el mismo código, y esa variación pesa más que lo que ahorran las cachés. Frente a los 753 s de §1.8.6, la caliente tardó 35 s más, y su parte fuera de Pest, 16 s menos.

**Fallo en la corrida fría:** `RepositorioDeAvisosTest > DD-56: delJugador ordena por fecha y por id descendentes…` (línea 146). Es el mismo defecto que se corrigió en la prueba de RN-15: los dos avisos tienen la misma `fecha_aviso` y sus ids comparten el milisegundo (`01a1b721-843b-…`), así que su orden por id es aleatorio. En la caliente pasó. No se corrigió en esta tarea.

**Logs:**

- `docs/reportes/logs/verificar/f1-h5-da57-fria-20261107-1143.log`
- `docs/reportes/logs/verificar/f1-h5-da57-caliente-20261107-1157.log`
- `docs/reportes/logs/verificar/f1-h5-da57-caches-20261107-1143.log`: secuencia completa, con el estado de las cachés antes y después de cada paso

#### 1.8.8 Corrida tras la corrección de DD-56 y resultado de DA-57 (7/11/2026)

Una corrida de `make verificar`, sobre el commit «F1-H5: pruebas de orden por id con ids explícitos (DD-56 y barrido)», con la laptop en carga normal y sin auditor activo. Los contenedores llevaban 20 minutos arriba y las dos cachés ya estaban en sus volúmenes, así que la corrida fue **en caliente**. PHPStan reescribió `resultCache.php`, porque cambiaron dos archivos de pruebas; Pint no cambió su caché.

| Dato | Valor |
|---|---|
| Código | 0 |
| Tiempo total | **721 s**, por debajo del aviso de 13 minutos |
| Pest | 662,80 s |
| Total menos Pest | 58 s |
| Pruebas | 1 841 (11 594 aserciones), sin fallos |
| Cobertura | Dominio 100 %, Aplicación 100 %, Infraestructura y HTTP 99,5 % (total 99,6 %) |
| PHPStan y auditoría | «No errors»; sin avisos de seguridad |

Log: `docs/reportes/logs/verificar/f1-h5-dd56-20261107-1231.log`.

**Resultado de DA-57:**

- La caché redujo de 157 a 52 s el tiempo sin Pest (§1.8.7).
- El tiempo total no bajó de forma fiable.
- Pest tardó entre 679 y 736 s en las corridas de §1.8.7; esa variación pesa más que lo que ahorra la caché.
- La ejecución en paralelo de Pest queda como pendiente, para decidirla aparte. No se implementa.

### 1.9 Por qué Pest subió de 512 s a 647 s

**Corridas de hoy**, todas con 1 430 o 1 432 pruebas y cobertura:

| Corrida | Código | Pest |
|---|---|---:|
| `make verificar` 1 (contenedores recién creados) | rama | 647 s |
| `make verificar` 2 | rama | 711 s |
| A: mismo comando de Pest que `verificar` | `main` (`a34283e`, la base de H5 sin T00) | **547 s** |
| B: el mismo comando, justo después de A | rama (`510a0e3`) | **798 s** |
| `make verificar` 3 | rama | 687 s |
| `make verificar` 4 | rama | 641 s |
| `make verificar` A | rama | cortada a los 737 s, con 1 406 pruebas |
| `make verificar` B | rama | 624 s |

Entre H4-T06 (512 s) y `main` solo cambió en `backend/` el guion `bin/con-candado.sh`. El guion no consume recursos durante la corrida: el vigilante se desactiva en cuanto ve la entrada cerrada. Las pruebas de `main` son las de H4.

**Separación por archivo.** Los tiempos por prueba del log se sumaron por archivo de prueba y se separaron en dos grupos:

- los 39 o 40 archivos que T00 cambió;
- los 80 que no tocó, con el mismo código en las dos ramas.

| Comparación | Archivos cambiados | Archivos sin cambios |
|---|---|---|
| A (`main`) → B (rama) | 341 → 505 s (×1,48) | 199 → 283 s (×1,42) |
| A (`main`) → `verificar` 1 | 341 → 390 s (×1,14) | 199 → 241 s (×1,21) |
| `verificar` 1 → `verificar` 2 (mismo código) | 390 → 431 s (×1,10) | 241 → 266 s (×1,11) |

**Conclusión, con estos datos:**

1. **Los archivos que T00 no tocó, con el mismo código, también se volvieron más lentos.** Subieron entre un 11 % y un 42 % de una corrida a otra; `ArquitecturaTest`, que solo recorre archivos y no usa la base, osciló entre 32,5 y 50,9 s. El aumento viene del entorno de ejecución (Docker Desktop sobre Windows, con el código en un montaje del anfitrión), no del código.
2. **Los archivos cambiados suben en la misma proporción que los demás:** ×1,48 frente a ×1,42 en A→B y ×1,14 frente a ×1,21 en A→`verificar` 1. Si siguieran el factor de los archivos sin cambios, el efecto de T00 queda entre −23 s y +21 s, dentro de lo que el mismo código varía de una corrida a otra. Las cinco corridas completas de `make verificar`, con el mismo código, van de 624 a 711 s.
3. `main` tardó 547 s hoy frente a 512 s en H4-T06 con el mismo código de pruebas. El entorno también explica esa parte.

No identifiqué qué hace más lento al entorno de una corrida a otra (carga del anfitrión, la máquina virtual de Docker o el montaje de Windows); eso requeriría medirlo fuera de Pest. §1.8.4 recoge lo que la prueba A/B agregó a esta duda.

### 1.10 Condición de parada: opciones para el responsable

La condición de parada se activó con la corrida 2 (955 s), así que **T01 no empieza** hasta que el responsable decida. Con las cachés calientes, las corridas 3, 4 y B quedaron por debajo de 15 minutos (756, 702 y 686 s). En frío, la corrida A no cupo en el tope de 20 minutos. Estas son las opciones que pidió el responsable, con los datos de §1.8 y §1.9; no elijo ninguna.

| Opción | Qué cambia | Efecto esperado según las mediciones | Qué hay que tener en cuenta |
|---|---|---|---|
| (i) Persistir la caché de PHPStan en un volumen del contenedor | Un volumen para el `tmpDir` de PHPStan en `docker-compose.yml` (o un `tmpDir` en `phpstan.neon` que apunte a él) | Con la caché caliente, PHPStan tardó 3–5 s, frente a unos 100 a 400 s en frío (corridas 1 y A). Es el ahorro por corrida tras `make bajar`, **solo si** la caché sigue siendo válida al volver a crear el contenedor, lo que no se ha probado | La prueba A/B muestra que la caché funciona entre corridas seguidas. El enfriamiento de la corrida 2 sigue sin explicarse (§1.8.4): si fue el entorno, el volumen no lo evita. La caché de Pint vive aparte, en `/tmp` (§1.8.2), y en frío tarda unos 60 s: un volumen solo para `/tmp/phpstan` no la cubre. Toca `docker-compose.yml`, que no está en las rutas de T00. No reduce Pest |
| (ii) Mover PHPStan a un job separado en CI | El workflow de CI corre PHPStan en paralelo con Pest | En CI, el tiempo de pared baja en lo que dura PHPStan en frío (no se midió en CI; en local, de unos 100 a 400 s). En local no cambia nada, salvo que también se separe `composer verificar` | `make verificar` dejaría de ser el árbitro único en local si PHPStan sale de él. Toca `.github/workflows/` y quizá `composer.json` |
| (iii) Subir el límite | La condición de parada de 15 minutos (y el aviso de 13) | Ningún ahorro. El total medido osciló entre 686 s (en caliente) y 990 s (en frío), y la corrida A se cortó a los 1 202 s | El tope de 20 minutos de `bin/con-candado.sh` ya se alcanzó en frío (§1.8.5). Subir el límite de 15 minutos no evita ese corte: habría que tocar también el tope o evitar las corridas en frío |

Ninguna de las tres ataca la causa principal: la lentitud variable del entorno. Pest fue de 547 a 798 s con casi el mismo código, y PHPStan en frío, de unos 100 a 400 s.

### 1.11 Incidencia durante la medición A/B

Para la medición A, la copia de trabajo se pasó a `a34283e` en modo detached y después se volvió a `hito/f1-h5`. En esa corrida, Git Bash convirtió la ruta `/tmp/ab-main.xml` del argumento `--coverage-clover` en una ruta de Windows y la cobertura se escribió en una carpeta nueva dentro de `backend/`. La carpeta se revisó (solo contenía ese XML) y se borró por su nombre exacto; la corrida B se hizo con `MSYS_NO_PATHCONV=1`. Al revisar la carpeta, un `du` con un glob mal formado empezó a recorrer la raíz `C:\` de Windows. Era solo lectura y se detuvo; no se borró nada fuera de esa carpeta.

Comprobación posterior, pedida por el responsable:
- `git status` está limpio y `git branch --show-current` da `hito/f1-h5`;
- `git diff --quiet HEAD` y `git diff --cached --quiet` dan 0;
- no hay archivos sin rastrear ni entradas en `stash`;
- el reflog muestra la vuelta de `a34283e` a `510a0e3`;
- el único commit posterior a la vuelta, `7306d09`, solo cambia este reporte;
- en `backend/` no queda ninguna carpeta extraviada.

### 1.12 Traspaso a otra computadora

El responsable sigue el trabajo en otra laptop. Esta sección reúne lo que hace falta saber allá, tanto para el responsable como para el agente que continúe, que arrancará sin la memoria ni el contexto de la sesión anterior.

#### 1.12.1 Dónde estamos

- **Rama:** `hito/f1-h5`, publicada en `origin` por orden del responsable. No hay PR ni etiqueta. Todo lo anterior ya está en `main` (`a34283e`): las etiquetas `f1-h1` a `f1-h4`, PT-01 y PT-07.
- **Hecho:** T00. PHPStan en nivel 8 con 0 errores, sin línea base ni `ignoreErrors`, y `ConfiguracionDePhpstanTest` exige el nivel 8 como mínimo.
- **Detenido:** T01 (migraciones de Condición física y Salud, historiales y seeders, `docs/hitos/f1-h5.md`). No empieza hasta que el responsable elija.
- **Ninguna tarea está a medias.** No hay cambios sin commit ni `stash`.

#### 1.12.2 Lo que falta decidir

1. **El responsable elige entre (i), (ii) y (iii) de §1.10.** El agente no elige.
2. **Medir en la laptop nueva antes de decidir, si el responsable quiere.** Todos los tiempos de este reporte son de la laptop anterior. El umbral de 13 y 15 minutos está marcado «por validar» en `docs/hitos/f1-h5.md`. Si la laptop nueva es más rápida o más lenta, la decisión puede cambiar.
3. **Después de elegir,** la opción puede tocar `docker-compose.yml`, `.github/workflows/` o `bin/con-candado.sh`, que no están en las rutas de T00. Hay que autorizar esas rutas de forma explícita.

#### 1.12.3 Poner en marcha la laptop

Prerrequisitos (`README.md`): Git, Docker Desktop con WSL2 y el motor iniciado, y `make` (`winget install --id ezwinports.make -e --scope user`, y abrir una terminal nueva).

```bash
git fetch origin
git switch hito/f1-h5
cp .env.example .env
cp backend/.env.example backend/.env
make levantar
docker compose exec -T app php artisan key:generate
make fresh
make verificar
make humo
```

- Los dos `.env` no están en el repositorio. Las claves de los `.example` son solo de prueba y sirven tal cual.
- Si el puerto 5433 está ocupado, se cambia `DB_PUERTO_ANFITRION` en `.env`.
- `make levantar` construye la imagen e instala `vendor` en un volumen, así que la primera vez tarda más.
- Si `make levantar` se cuelga en el `--build`, `docker compose up -d --wait` arranca sin construir. Ya pasó una vez tras apagar la computadora.

**Resultado esperado de `make verificar`:** código 0, 1 432 pruebas (9 821 aserciones), PHPStan «No errors», cobertura de 100 %, 100 % y 99,5 %, sin avisos de seguridad.

**La primera corrida será en frío** (§1.8.5). Si el tope la corta con Error 124 y sin pruebas fallidas, no es un fallo del código: se vuelve a correr sin bajar los contenedores.

#### 1.12.4 Reglas vigentes de H5

Además de `backend/CLAUDE.md`:

- **Sin push, PR ni etiquetas** salvo orden expresa del responsable. La publicación de esta rama fue una orden puntual, no un permiso general.
- **Una tarea a la vez:** plan, aprobación, pruebas primero, implementación, `make verificar`, commit, reporte y alto. Cada lote y cada plan marcado «REVISIÓN DE PLAN» esperan aprobación.
- **Commits:** `F1-H5-TNN: descripción` para las tareas y `F1-DOC: descripción` para la documentación.
- **Documentos de cada tarea:** reporte `docs/reportes/f1-h5-tNN.md`, decisiones nuevas en `f1-h5-decisiones-del-agente.md` y tiempos de Pest en este archivo.
- **Tiempo:** si `make verificar` pasa de 13 minutos, avisar; si pasa de 15, detenerse.
- **Logs:** los completos van en `docs/reportes/logs/` solo si respaldan un reporte o el cierre de una tarea; las demás corridas quedan en `backend/build/`, con la ruta anotada en el reporte (§9.3). Ningún log se borra: si se reemplaza, se mueve a `backend/build/` o a `docs/reportes/logs/` (§11).
- **PHPStan:** nunca línea base, `ignoreErrors` ni `@phpstan-ignore`. Tras editar una prueba, correr PHPStan antes de `make verificar`.
- **Nulos en pruebas:** los valores que la prueba ya da por existentes se envuelven con `Tests\Ayudantes\NoNulo::exigir`. Si alguna prueba espera nulo, no se envuelve: se tipa `?T` y se prueba el nulo.
- **Corridas en serie:** nada en paralelo, y ningún comando mientras corre `make verificar`. Antes de cada corrida, bajar los contenedores que hayan quedado levantados o confirmar que su estado es el esperado.
- **Medición:** bajar los contenedores borra las cachés de PHPStan y de Pint. Para medir en caliente, no se bajan.
- **Al terminar:** `make bajar` si no se va a seguir trabajando.

#### 1.12.5 Trampas del entorno

- **`make humo`** necesita `make fresh` justo antes. Si no, H1 paso 3 da 409 `categoria_duplicada`.
- **`make` en PowerShell** usa `cmd.exe`, y en Git Bash, `sh`: cada receta del `Makefile` debe ser un solo comando.
- **`bin/con-candado.sh`:** si la entrada estándar se cierra, el vigilante se desactiva y solo protegen el candado y el tope. Es normal ver ese aviso. Para mantenerlo activo desde Git Bash: `make verificar < <(sleep 3600)`.
- **Antes de una corrida,** si hubo un corte o un cierre brusco, comprobar que no quede un proceso huérfano con `docker compose top`.
- **Git Bash convierte rutas:** usar `MSYS_NO_PATHCONV=1` si un argumento lleva una ruta del contenedor como `/tmp/...` (§1.11).
- **Nunca usar un glob que empiece por `C`** desde Git Bash: puede resolver a `C:/` (§1.11).
- **Herramienta Bash del agente:** `\\` se reduce a `\`. Para `sed` conviene `\x5c`, o escribir el guion a un archivo.
- **Pint `--dirty`** no funciona en el contenedor porque no hay Git: hay que pasarle la lista de archivos.
- **`docs/`** es de solo lectura dentro del contenedor: se edita desde el anfitrión.
- **El contenedor `app` corre como root:** para probar que un borrado falla, los permisos no sirven.
- **La hora del contenedor es UTC.**
- **Laravel Boost (MCP)** depende del contenedor `app` levantado. En la última sesión no conectó; si pasa, hay que avisar y no asumir que no está configurado.

#### 1.12.6 Lo que no viajó con la rama

- **La memoria del agente** de la laptop anterior. Lo que importa de ella está en esta sección.
- **Los logs de las mediciones** (`verificar*.txt`, `prueba-frio.txt`, inventarios de caché). Sus resultados están en §1.8 y §1.9.
- **Los `.env`,** `vendor` y las cachés: se regeneran con §1.12.3.

#### 1.12.7 Cómo retomar

1. Poner en marcha la laptop (§1.12.3).
2. Al agente: «Lee `docs/reportes/f1-h5-progreso.md` §1.12 y §1.10. T01 sigue detenida hasta que yo elija».
3. Decidir entre (i), (ii) y (iii), con o sin una medición en la laptop nueva.
4. Pedir el plan de T01 según `docs/hitos/f1-h5.md`.

### 1.13 Laptop nueva: corridas y cierre de la condición de parada (3/11/2026)

#### 1.13.1 Corridas

En serie y sin nada en paralelo.

| Corrida | Código | Qué la precedió | Pest | `make verificar` | Resultado |
|---|---|---|---:|---:|---|
| 1 | `main` (`a34283e`) | `make levantar` con imagen y contenedores recién creados, y `make fresh` | 413 s | **510 s** | Código 0; 1 430 pruebas (9 815 aserciones), sin avisos de seguridad |
| 2 | `hito/f1-h5` (`2853587`) | La corrida 1 y `make humo`, sin bajar los contenedores | 420 s | **507 s** | Código 0; PHPStan nivel 8 «No errors», 1 432 pruebas (9 821 aserciones), cobertura de 100 %, 100 % y 99,5 %, sin avisos de seguridad |

`make humo` se corrió tras la corrida 1, sobre `main`: código 0, 34 s. En esta rama todavía no se ha corrido en la laptop nueva.

**Límites de estas medidas:**

- La corrida 2 no fue en frío estricto: el contenedor conservaba las cachés de la corrida 1. No se midió cuánto de esas cachés aprovechó.
- La corrida 1 sí empezó con las cachés vacías, pero sobre `main`, sin el nivel 8 de PHPStan.
- No se hizo en esta rama la prueba `make bajar`, `make levantar` y `make verificar`. La primera corrida en frío de esta rama sigue sin medirse aquí.
- Son dos corridas: no demuestran que el entorno nuevo sea estable, ni explican la lentitud de la laptop anterior (§1.8.4 sigue sin demostrarse).

#### 1.13.2 Decisiones del responsable

1. **Condición de parada (§1.10): sin cambios.** No se aplica ninguna de las opciones (i), (ii) y (iii). Se atribuye la lentitud al entorno de la laptop anterior y siguen vigentes el aviso de 13 minutos, el alto de 15 y el tope de 20. **T01 puede empezar**, con su plan aprobado.
2. **Rutas base.** Se confirma la lista que H4 tomó de H3, pendiente desde el Lote A (`f1-h4-reporte.md` §4 y §7), y se usa igual en las tareas de H5 que citan «rutas base»: `app/Http/Controllers/**`, `app/Http/Requests/**`, `app/Http/Resources/**`, `app/Exceptions/FormatoDeError.php`, `lang/es/errores.php` y `routes/api.php`. `f1-h4-reporte.md` no se modificó.

#### 1.13.3 Incidencia

La primera `make levantar` falló al construir la imagen: `pecl install pcov` tardó unos 60 s y terminó con «Package "pcov" does not have REST info xml available». `pecl.php.net` respondía desde Windows y desde un contenedor, y el reintento pasó sin cambiar nada. La causa no se identificó; lo observado apunta a un fallo momentáneo de red.

## 2. F1-H5-T01: migraciones, historiales y seeders

Detalle en `docs/reportes/f1-h5-t01.md`.

| Corrida | Pruebas | Pest | `make verificar` |
|---|---:|---:|---:|
| Antes de T01 (§1.13, corrida 2) | 1 432 | 420 s | 507 s |
| Cierre de T01, sin bajar los contenedores | 1 504 | **440 s** | **487 s** |

`make mutacion`: 124 s, 687 de 687 mutantes eliminados. `make fresh` + `make humo`: código 0, 39 s el humo.

La validación de peso y estatura en la solicitud pasó a T05 por decisión del responsable (`docs/hitos/f1-h5.md` v1.0.4, T05).

### 2.1 Cierre de pendientes del Lote A (3/11/2026)

**Auditoría de los catálogos: verificada, sin cambios.** `f1-h5-t01.md` §3 dice que los seeders dejan con valor `creado_en` y `actualizado_en`, aunque no los escriben. Es cierto:

- **Macro:** `auditoriaBasica()` define las dos columnas con `timestampTz(...)->useCurrent()`. Las migraciones de `tipos_prueba` y `rangos_mediciones_corporales` solo llaman al macro.
- **Esquema, en la base de desarrollo `gestordep` tras `make fresh`:** en las dos tablas, `creado_en` y `actualizado_en` son `NOT NULL` con `column_default = CURRENT_TIMESTAMP`. `creado_por` y `actualizado_por` son opcionales y sin valor por defecto.
- **Datos, con una sesión de solo lectura del rol de aplicación:**

| Tabla | Filas | Con `creado_en` | Con `actualizado_en` |
|---|---:|---:|---:|
| `tipos_prueba` | 3 | 3 | 3 |
| `rangos_mediciones_corporales` | 2 | 2 | 2 |

Las cinco filas tienen `2026-11-04 04:56:22+00` en las dos columnas, y `creado_por` y `actualizado_por` nulos. Ese valor es el del momento de la siembra: no dice quién ni cuándo cambia un dato después, así que la excepción de RNF-02 sigue igual. No se cambió ninguna migración.

**DA-21 y DA-22:** confirmadas por el responsable el 3/11/2026 (`f1-h5-decisiones-del-agente.md`).

**Coautoría:** el responsable pidió confirmar el modelo. Los commits de esta sesión los hizo Claude Opus 5.5 (`claude-opus-5-5`), y su línea `Co-Authored-By` es la correcta.

**Identidad:** los commits F1-DOC de este cierre usan, con `-c` y por autorización expresa para ellos, la misma identidad de `0143d6c`. No se guardó configuración.

### 2.2 Identidad y modelo de cada commit de esta laptop

Por decisión del responsable, se documenta y no se reescribe: ningún autor ni hash cambia.

| Commit | Fecha (`-06:00`) | Autor y confirmador | Cómo se aplicó la identidad | Modelo |
|---|---|---|---|---|
| `0143d6c` | 3/11/2026 22:23:06 | Luis Angel Gonzalez Yudho `<a22021318@iteshu.edu.mx>` | `-c`, sin autorización previa; el responsable decidió después dejarlo así | Claude Opus 5.5 |
| `f41318a` | 3/11/2026 22:38:37 | La misma | `-c`, autorizada para los commits de T01 | Claude Opus 5.5 |
| `b9c75b6` | 3/11/2026 22:58:26 | La misma | `-c`, autorizada para los commits de T01 | Claude Opus 5.5 |
| `7cebe5d` | 3/11/2026 23:26:55 | La misma | `-c`, autorizada para ese commit y el siguiente | Claude Opus 5.5 |
| `a2e4b08` | 3/11/2026 23:26:58 | La misma | `-c`, autorizada para ese commit y el anterior | Claude Opus 5.5 |

**Evidencia:**

- **Autor, confirmador y fecha:** `git log`, sobre cada commit.
- **Línea de coautoría:** `%(trailers:key=Co-Authored-By)`. Los cinco dicen `Claude Opus 5.5 <noreply@anthropic.com>`.
- **Modelo:** el registro de la sesión en esta laptop, `29bb72b4-2578-49c6-93d8-a12ba4593eba.jsonl`, es el único de este proyecto aquí. Todas sus entradas con campo `model`, 275 al revisarlo, dicen `claude-opus-5-5`, y las fechas de los cinco commits caen dentro de la sesión.
- **Uso de `-c`:** los comandos están en ese registro. Ninguna identidad quedó guardada entonces: `git config` estaba vacío en ese momento.

**Lo que no se puede comprobar:**

- **Commits de la laptop anterior:** su registro no viajó. Eso incluye `2853587` y todos los anteriores. Solo se ve lo que dicen sus metadatos y su línea de coautoría; no el modelo que trabajó en ellos.
- **Que la línea de coautoría sea por sí misma una prueba:** es texto del commit. La prueba del modelo es el registro de la sesión.

Desde el commit que agrega esta sección, la identidad sale de `.git/config`, configurada por el responsable.

## 3. F1-H5-T02: avisos de lesión

Detalle en `docs/reportes/f1-h5-t02.md`.

| Corrida | Pruebas | Pest | `make verificar` |
|---|---:|---:|---:|
| Cierre de T01, sin bajar los contenedores | 1 504 | 440 s | 487 s |
| Cierre de T02, primera corrida completa tras `make levantar` | 1 569 | **628 s** | **747 s** |

`make mutacion`: 176 s, 724 de 724 mutantes eliminados. `make fresh` + `make humo`: código 0, 45 s el humo.

**Aviso:** 747 s queda a 33 s del aviso de 13 minutos. Pest subió 188 s. El contenedor estaba recién creado, pero PHPStan ya tenía caché por las corridas previas. §3.1 detalla el aumento.

### 3.1 Verificaciones antes de T03 (4/11/2026)

Pedidas por el responsable, sin cambiar código de producción.

#### Tiempos por archivo, T01 frente a T02

Salen de los tiempos por prueba del log de cada `make verificar`. Suman 435,5 s y 621,3 s, frente a los 440 s y 628 s de Pest.

| Grupo | Pruebas | T01 | T02 | Cambio |
|---|---:|---:|---:|---:|
| Archivos existentes, sin cambios en T02 | 1 504 | 435,5 s | 601,8 s | ×1,38 |
| Archivos nuevos de T02 | 65 | — | 19,4 s | — |

| Archivo que más creció | T01 | T02 | Diferencia |
|---|---:|---:|---:|
| `ArquitecturaTest`, que no usa la base | 31,4 s | 50,6 s | +19,3 s |
| `AvisosTest`, nuevo | — | 13,7 s | +13,7 s |
| `PartidosTest` | 10,1 s | 16,3 s | +6,1 s |
| `CalificacionesTest` | 13,9 s | 19,9 s | +6,0 s |
| `MigracionesDeAcademicoTest` | 18,4 s | 24,5 s | +6,0 s |

**Conclusión, con estos datos:** las pruebas nuevas explican 19,4 s de los 188 s. El resto está en archivos que T02 no cambió, incluido uno que no usa la base. Eso apunta al entorno o a que era la primera corrida tras recrear los contenedores, no al código. La causa no está demostrada: es una sola comparación. `f1-h5-t02.md` §7 daba unos 25 s como estimación; el dato medido es 19,4 s.

#### Mutación de los filtros de alcance de T02

En una copia temporal dentro del contenedor (`/tmp/mut`), borrada al terminar, con `tests/Feature/Salud/AvisosTest.php`. El repositorio no cambió. El control sin mutar dio 42 pruebas pasadas antes y después.

| Mutación | Cambio | Pruebas que fallan |
|---|---|---:|
| M1 | `ComprobarJugadorVisible`: `if (false)` en lugar de comprobar el alcance | 4 |
| M2 | `ComprobarAvisoDelEntrenador`: solo `$aviso === null` | 3 |
| M3 | `ListarAvisos`: `$creadoPor = null` | 2 |
| M4 | `VisibilidadDeJugadorSql::esVisibleComoEntrenador`: sin `soloEntrenador: true` | 3 |

M1 a M3 son los tres filtros; M4 es adicional, porque M2 y M3 dependen de él. Las pruebas de 404 que pasaban antes de crear las rutas (`f1-h5-t02.md` §3) fallan con estas mutaciones. T08 sigue debiendo la mutación de todos los filtros de H5.

#### Decisiones

- **Confirmadas por el responsable (4/11/2026):** DA-23, DA-25, DA-28 y DA-29.
- **Decidida por el responsable (5/11/2026):** DA-30, opción A, con su límite anotado para T08 o H7.

## 4. Actualización de Symfony por los avisos de seguridad del 4/11/2026

**Causa.** La corrida #63 de CI falló en `composer audit` sobre `9dccf68`, con Pest y la cobertura en verde. `composer audit` falló igual en local, con 8 avisos en 5 paquetes, todos con fecha 4/11/2026. `composer.lock` no cambió entre `a34283e` y `9dccf68`: el fallo vino de los avisos publicados, no de T02.

**Cambio.** Solo `backend/composer.lock`. `composer.json` y su `content-hash` no cambiaron. El `--dry-run` mostró 5 paquetes y la actualización cambió esos 5:

| Paquete | Sección | Antes | Después |
|---|---|---|---|
| `symfony/http-foundation` | `packages` | 8.1.9 | 8.1.11 |
| `symfony/http-kernel` | `packages` | 8.1.9 | 8.1.11 |
| `symfony/routing` | `packages` | 8.1.9 | 8.1.11 |
| `symfony/filesystem` | `packages-dev` | 8.1.9 | 8.1.11 |
| `symfony/yaml` | `packages-dev` | 8.1.9 | 8.1.11 |

### 4.1 Notas de versión de 8.1.9 a 8.1.11

Fuente: `CHANGELOG-8.1.md` de `symfony/symfony` (rama 8.1), secciones 8.1.10 y 8.1.11. Se revisó además qué archivos cambiaron en `vendor`: 19 en `http-foundation`, 16 en `http-kernel` y 6 en `routing`.

| Paquete | Qué cambió | Por qué no afecta al proyecto |
|---|---|---|
| `http-foundation` | `RequestMatcher` y `MethodRequestMatcher` coinciden con HEAD si listan GET (CVE-2026-118514) | El proyecto no usa `symfony/security` ni `access_control`: no está en `composer.lock` |
| `http-foundation` | Rangos privados para proxies de confianza (`private_ranges`) | `app/`, `src/`, `bootstrap/`, `routes/` y `config/` no configuran proxies de confianza |
| `http-foundation` | `Request::getBaseUrl()`: no empieza con un host del cliente, ni coincide con un nombre de script parcial, codificado o fuera de la ruta | **Sí se ejecuta en cada petición**, pero corrige casos con prefijo o alias. El repositorio no configura ninguno. Las pruebas de rutas siguen pasando |
| `http-foundation` | `HeaderUtils::split()` sin retroceso (CVE-2026-108082); nombres de cookies y análisis de cabeceras | **También se ejecuta**, por ejemplo en la cookie de sesión. Las pruebas de sesión siguen pasando |
| `http-foundation` | `BinaryFileResponse`, `UrlHelper`, `getPreferredLanguage()`, manejadores de sesión de Redis, Memcached, MongoDB y PDO, `MigratingSessionHandler` | El proyecto no los usa: solo devuelve JSON y sus sesiones van por el manejador `database` de Laravel |
| `http-kernel` | Contexto del router tras una subpetición; carga de CSV y YAML en `#[MapRequestPayload]`; `#[Cache]`, `#[RateLimit]`, perfilador, reconstrucción del contenedor | El código del proyecto solo importa de `HttpKernel` `HttpExceptionInterface`, `NotFoundHttpException` y `AccessDeniedHttpException`. Esas clases no aparecen en las notas |
| `routing` | Marcadores de host en los matchers compilados (CVE-2026-118516) | Ninguna ruta usa `domain()`, y el `Makefile`, el `Dockerfile` y `composer.json` no ejecutan `route:cache` |
| `routing` | Condiciones de ruta `"0"`, rutas solo HEAD, esquema JSON | El proyecto no define condiciones de ruta ni rutas solo HEAD |

`filesystem` y `yaml` están en `packages-dev`: los usan Infection, la validación de `docs/api/openapi.yaml` y Boost. No llegan a producción con una instalación sin dependencias de desarrollo.

**Límite de esta revisión.** Se revisaron las notas, los archivos cambiados y el uso en el repositorio, no cada línea del código de Symfony. El repositorio no define un servidor web de producción, así que la conclusión sobre prefijos y proxies vale mientras no se agreguen. Si el despliegue los usa, conviene revisarlo en la revisión de seguridad de H7.

### 4.2 Corridas

En serie, con `docker compose top` limpio antes de cada una, sin bajar los contenedores.

| Corrida | Resultado | Tiempo |
|---|---|---:|
| `composer audit` | Código 0, sin avisos | — |
| PHPStan | «No errors» | 53 s |
| `make verificar` | Código 0. 1 569 pruebas (10 374 aserciones), las mismas cifras que T02; cobertura de 100 %, 100 % y 99,5 %; sin avisos de seguridad | **677 s** (Pest 573 s) |
| `make mutacion` | Código 0. 724 de 724 mutantes eliminados; MSI 100 %, MSI cubierto 100 % | 160 s |
| `make fresh` + `make humo` | Código 0. H0 a H4 OK | 40 s el humo |

`main` tiene el `composer.lock` anterior: su próxima corrida de CI fallará igual hasta que reciba este cambio.

## 5. F1-H5-T03: confirmación, estado oficial y lesión

Detalle en `docs/reportes/f1-h5-t03.md`.

| Corrida | Pruebas | Pest | `make verificar` |
|---|---:|---:|---:|
| Actualización de Symfony (§4.2) | 1 569 | 573 s | 677 s |
| Cierre de T03, sin bajar los contenedores | 1 699 | **800 s** | **871 s** |

`make mutacion`: 230 s, 796 de 796 mutantes eliminados. `make fresh` + `make humo`: código 0, 85 s el humo.

**Aviso: 871 s pasa de 13 minutos y queda a 29 s del alto de 15.** Los archivos sin pruebas nuevas subieron ×1,33 y las 130 pruebas nuevas sumaron 42,4 s. `make humo` y `make mutacion` también subieron. Apunta al entorno, pero la causa no está demostrada (`f1-h5-t03.md` §7).

### 5.1 Revisión de T03 y lista de cierre de H5 (5/11/2026)

DA-31 a DA-34 confirmadas por el responsable. El orden completo de confirmar y los textos exactos están en `f1-h5-t03.md` §13 y §14.

**Pendientes para el cierre de H5, sin bloquear T04:**

- Concurrencia real con dos peticiones simultáneas.
- Lesión anterior tras DD-63 sin alta: anotada para la auditoría.

**T04 espera** la orden del responsable tras medir `make verificar` con la laptop sin carga.

### 5.2 Medición de `make verificar` con la laptop sin carga (5/11/2026)

Pedida por el responsable, con OneDrive en pausa y sin bajar los contenedores, sobre `c27d523`. Marca de tiempo en cada línea y `docker stats` cada 15 s (34 muestras).

**Resultado: 571 s (9 min 31 s), código 0.** 1 703 pruebas (11 048 aserciones), PHPStan «No errors», cobertura de 100 %, 100 % y 99,5 %, sin avisos de seguridad. No hizo falta una segunda corrida, porque no pasó de 13 minutos.

| Etapa | Tiempo |
|---|---:|
| Arranque y Pint | 50 s |
| PHPStan | 4 s |
| Pest | 489 s |
| Informe de cobertura | 27 s |
| `verificar-cobertura` y `composer audit` | 1 s |

**PHPStan usó la caché:** `resultCache.php` tuvo la misma sha1 (`b71aa29b3112`) y la misma hora antes y después, y la etapa tardó 4 s. Esa caché la había calentado una corrida previa de PHPStan el mismo día. Pint no tenía caché de todo el proyecto.

**Comparación:** 871 s en T03, con 4 pruebas menos, y 571 s aquí. Con casi las mismas pruebas, la diferencia de 300 s no viene del código, pero **su causa no está comprobada**. Que OneDrive influyera es una hipótesis no verificada: el responsable confirmó que el repositorio está en `C:\Users\LOQ\Documents\repos\GestorDep`, fuera de OneDrive y sin iconos de sincronización.

**Siguiente hipótesis, sin verificar:** el análisis en tiempo real de Microsoft Defender. No se cambió esa configuración.

**CPU y memoria de Docker** (100 % equivale a un núcleo; Docker tiene 8 CPU y 15,47 GiB):

| Contenedor | CPU promedio | CPU máxima | Memoria promedio | Memoria máxima |
|---|---:|---:|---:|---:|
| `app` | 41,5 % | 99,9 % | 387 MiB | 451 MiB |
| `db` | 23,4 % | 43,0 % | 125 MiB | 160 MiB |

Ni la CPU ni la memoria de Docker llegaron a su límite: `app` usó como máximo un núcleo de 8 y menos de 500 MiB.

## 6. F1-H5-T04: puerto `EstadoDeDisponibilidadOficial`

Detalle en `docs/reportes/f1-h5-t04.md`.

| Corrida | Pruebas | Pest | `make verificar` |
|---|---:|---:|---:|
| Medición sin carga (§5.2) | 1 703 | 489 s | 571 s |
| Cierre de T04, sin bajar los contenedores | 1 717 | **488 s** | **526 s** |

`make mutacion`: 117 s, 796 de 796 mutantes eliminados. `make fresh` + `make humo`: código 0, 32 s el humo.

## 7. Resumen del Lote B

| Tarea | Qué entrega | Commits |
|---|---|---|
| T02 | Crear, listar y descartar avisos | `04cfc9c` |
| T03 | Confirmación, estado oficial, cambio de estado, alta y lesión | `455720e`, `c27d523` |
| T04 | Puerto hacia Competencia | Este commit |

**Pendientes para el cierre de H5:** concurrencia real con dos peticiones simultáneas; lesión anterior tras DD-63 sin alta; revisión de DA-30 en T08 o H7; el par fechas y `conflicto` de DA-33 sin prueba combinada.

- Observación para T08: Identificador::generar() usa milisegundos y bytes aleatorios, sin contador monotónico. Dos registros creados en el mismo milisegundo con la misma fecha_inicio se listan en orden aleatorio por id (RN-15). Impacto: solo el orden, no la unicidad. Decidir en T08 si el desempate debe usar creado_en con microsegundos en lugar del id. No se corrige en H5.

- Observación para T08: el IMC se guarda en numeric(5,2), así que no puede pasar de 999.99. Con 200 kg, una estatura menor de 44.7 cm lo desborda y responde 500. Hoy el mínimo sembrado es 100 cm, así que no ocurre con los rangos actuales. Decidir en T08 si la validación debe comprobar el IMC antes de guardar.

- Pendiente: la ejecución en paralelo de Pest (DA-57), como decisión aparte. No se implementa sin esa decisión.

## 8. Correcciones tras la auditoría de los Lotes A y B (6/11/2026)

Pedidas por el responsable antes de continuar T05. T05 sigue detenida hasta que decida el límite de `motivo` y el caso de la lesión propia.

### 8.1 Pruebas de DA-28 y DA-32

Son 12 casos nuevos: 6 de DA-28 en `AvisosTest.php`, y 3 al confirmar y 3 en `PATCH` de lesión de DA-32 en `ConfirmacionYEstadoTest.php`.

**Pruebas primero: lo que se pudo y lo que no.** Pasaron en su primera corrida, porque fijan un comportamiento que el código ya tenía: `Tests: 12 passed`. No hubo una falla previa que pegar. Para demostrar que pueden fallar, se invirtió cada regla en una copia temporal dentro del contenedor (`/tmp/mut`), borrada al terminar. El repositorio no cambió y el control sin mutar pasó antes y después (12 pasadas, 76 aserciones).

| Mutación | Cambio | Resultado |
|---|---|---|
| Y1 (DA-28) | El estado del aviso se comprueba en `authorize()` | 6 fallan: `Expected response status code [422] but received 409` |
| Y2 (DA-32) | `nota` con `nullable` al confirmar | 3 fallan: `[422] but received 200` |
| Y3 (DA-32) | `nota` con `nullable` en `PATCH` de lesión | 3 fallan: `[422] but received 200` |

El primer intento de Y1 dio 500 por un error de escape en la mutación, no en las pruebas. Se descartó y se repitió.

### 8.2 Logs de `make verificar`

Desde el 6/11/2026, cada corrida se guarda completa en `docs/reportes/logs/verificar/f1-hN-{tarea}-{AAAAMMDD-HHMM}.log`, y el reporte de la tarea cita esa ruta. El log abre con el commit base y cierra con el código y los segundos.

Primera corrida: `docs/reportes/logs/verificar/f1-h5-postauditoria-20261106-1044.log`, código 0 en 588 s: 1 729 pruebas (11 146 aserciones), Pest 507 s, PHPStan «No errors», cobertura de 100 %, 100 % y 99,5 %, sin avisos de seguridad.

## 9. Órdenes del responsable antes de T05 (6/11/2026)

### 9.1 Decisiones

- **`motivo`:** opción A, hasta 255 (DA-44). La especificación pasó a la v1.0.5. `docs/api/openapi.yaml` no cambió porque las rutas de mediciones y pruebas físicas aún no existen: T05 y T06 documentan el límite con ellas y prueban 255 y 256.
- **Lesión propia:** queda sin alta, con el criterio de DD-63 (DA-45). Lo fijan dos pruebas en `ConfirmacionYEstadoTest.php`. **Pendiente para la revisión de T08.**
- **Condiciones de T04:** aceptadas por el responsable. Están en `f1-h5-t04.md` §5 desde `ed445cd`.
- **Puntos 2 a 8 del plan de T03:** registrados como DA-46 a DA-52. Solo DA-46 lleva «Privacidad o permisos: sí».

### 9.2 Mutaciones

En una copia temporal dentro del contenedor (`/tmp/mut`), borrada al terminar, con control antes y después de cada una. Las pruebas `DA-45` pasaron en su primera corrida, porque fijan un comportamiento que el código ya tenía.

| Mutación | Cambio | Control antes y después | Con la mutación |
|---|---|---|---|
| Y1 (DA-28) | El estado del aviso se comprueba en `authorize()` | 6 pasadas (31 aserciones) | 6 fallan: `Expected response status code [422] but received 409` |
| Y4 (DA-45) | Confirmar cierra la lesión propia anterior | 2 pasadas (14 aserciones) | 2 fallan: `Failed asserting that '2026-11-15' is null` |

Log completo: `docs/reportes/logs/mutaciones/f1-h5-y1-y4-20261106-1135.log`.

### 9.3 Regla de logs

Sustituye a §8.2. Los logs completos van en `docs/reportes/logs/` solo si respaldan un reporte o el cierre de una tarea. Las demás corridas quedan en `backend/build/`, con la ruta anotada en el reporte.

### 9.4 Corridas

| Corrida | Resultado | Tiempo |
|---|---|---:|
| `make verificar` | Código 0. **1 731 pruebas (11 159 aserciones)**, PHPStan «No errors», cobertura Dominio 100 %, Aplicación 100 %, Infraestructura y HTTP 99,5 %, sin avisos de seguridad | **561 s** (Pest 519 s) |

Log completo: `docs/reportes/logs/verificar/f1-h5-preT05-20261106-1138.log`.

CI: el push de `126a567` disparó el run #67, que terminó en `success`.

## 10. F1-H5-T05: mediciones corporales

Detalle en `f1-h5-t05.md`.

| Corrida | Pruebas | Pest | `make verificar` |
|---|---:|---:|---:|
| Cierre de T05 | 1 835 | 596 s | 661 s |

Log: `docs/reportes/logs/verificar/f1-h5-t05-20261106-1259.log`. Pendientes para T08: rango faltante (500) y DA-45.

## 11. Incidencia: logs borrados durante T05 (6/11/2026)

**Qué pasó.** Durante el cierre de T05, el agente borró los logs de dos rondas de `make verificar` y `make mutacion`. Los borró al repetir las corridas para cubrir los mutantes de `f1-h5-t05.md` §5, porque ya estaban reemplazados. No se pueden recuperar.

**Regla incumplida.** §9.3: las corridas que no respaldan un reporte quedan en `backend/build/`, con la ruta anotada en el reporte. El agente debió moverlos ahí.

| Ronda | Corrida | Resultado | Tiempo |
|---|---|---|---:|
| 1 | `make verificar` | Código 0. 1 831 pruebas (11 561 aserciones) | 651 s (Pest 605 s) |
| 1 | `make mutacion` | Código 0. 838 de 842 mutantes eliminados, 4 sin cubrir | 123 s |
| 2 | `make mutacion` | Código 0. 840 de 842 mutantes eliminados, 2 sin cubrir | 122 s |
| 2 | `make verificar` | Código 0. 1 835 pruebas (11 570 aserciones) | 564 s (Pest 511 s) |

Estas cifras vienen de la salida de cada corrida en la sesión, no de un log conservado.

**Efecto.** Las corridas finales sí tienen su log (`f1-h5-t05.md` §6). Lo perdido es la evidencia intermedia y la comparación de tiempos con el mismo código.

**Regla nueva, indicada por el responsable.** Ningún log se borra: si se reemplaza, se mueve a `backend/build/` o a `docs/reportes/logs/`.

## 12. Desviación de T05 corregida en la especificación (6/11/2026)

§8 decía que el rango se rechaza en la solicitud. T05 lo valida en el caso de uso, por el orden de DA-38. El responsable corrigió §8 en la v1.0.6. El código no cambió.

Queda desactualizado el comentario de la migración `060100`, que dice que «la solicitud valida el rango». No se modifica, por orden del responsable. La regla de no editar migraciones no es el motivo: esa regla se refiere a las integradas en `main`, y `060100` no está en `main`, solo en `hito/f1-h5`.

## 13. Antes de T06 (6/11/2026)

Orden del responsable:

1. Auditoría de T05 en una sesión de solo lectura.
2. Plan de T06 para su aprobación, con pruebas primero: `f1-h5-plan-t06.md`.

Mientras trabaje la sesión auditora no se ejecuta `make verificar`. No se publica, ni se abre PR, ni se etiqueta sin su orden expresa.

## 14. Committer de los commits de H5

Desde el commit de AU-09 (F1-H5-T05: límite efectivo del rango frente a la columna), el committer de los commits de H5 es noreply@anthropic.com. Esta regla se verifica con git log, no con una lista de hashes.

**Referencia histórica.** Antes de esa regla, por orden del responsable, estos commits ya publicados recibieron `noreply@anthropic.com` como correo del committer: `3b578f3`, `7be89b0`, `8a0dedf`, `9899254`, `87a66d5`, el commit "F1-DOC: correcciones de la auditoría de T05" y `2f468f2`. El autor sigue siendo el de `.git/config`, que no cambió. Se reescribieron antes de publicarlos, sin cambiar su contenido, y el push fue sin `--force`.

## 15. AU-09: límite efectivo del rango frente a la columna (7/11/2026)

El máximo efectivo de peso y estatura es el menor entre el del rango y lo que cabe en la columna, leído de `information_schema`. Columnas: `peso_kg` es `numeric(5,2)` (999.99), `estatura_cm` es `numeric(5,1)` (9999.9) y `rangos_mediciones_corporales.maximo` es `numeric(6,2)`.

- **Peso:** con el máximo del rango en 1500.00, 1200.00 daba 500 (`numeric field overflow`); ahora responde 422 `valor_fuera_de_rango`.
- **Estatura:** por HTTP admite un decimal, así que ningún valor llega a desbordar la columna sin superar el rango, y 9999.95 responde 422 `validacion_fallida`. La prueba que demuestra el límite va sobre el caso de uso `RegistrarMedicion`: sin el límite de columna, 9999.95 desborda la columna; con él, responde `valor_fuera_de_rango`. Se demostró en una copia temporal dentro del contenedor, con control antes y después.

**Logs:**

- Pruebas en rojo antes de la corrección: `docs/reportes/logs/pruebas/f1-h5-au09-rojo-*-20261107-1016.log`
- Pruebas en verde después de la corrección: `docs/reportes/logs/pruebas/f1-h5-au09-verde-*-20261107-101{7,8}.log`
- Estatura sin y con el límite de columna: `docs/reportes/logs/pruebas/f1-h5-au09-estatura-sin-y-con-limite-20261107-1112.log`
