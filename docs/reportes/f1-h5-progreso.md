# Progreso de H5

**Rama:** `hito/f1-h5` (desde `main`, `a34283e`) · **Especificación:** `docs/hitos/f1-h5.md` v1.0.3
**Estado:** T00 aceptada con dos correcciones. La primera, el mínimo de nivel 8 en la prueba de configuración, está hecha. La segunda, la condición de parada, se activó en la laptop anterior (§1.8) y el responsable la cerró el 3/11/2026 sin cambios de configuración, con las corridas de la laptop nueva (§1.13). **T01 queda destrabada:** su plan espera aprobación. La rama se publicó por orden del responsable para seguir en otra computadora (§1.12); sin PR ni etiquetas.

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
