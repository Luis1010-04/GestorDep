# Progreso de H5

**Rama:** `hito/f1-h5` (desde `main`, `a34283e`) · **Especificación:** `docs/hitos/f1-h5.md` v1.0.3
**Estado:** T00 aceptada con dos correcciones. La primera, el mínimo de nivel 8 en la prueba de configuración, está hecha. La segunda, la condición de parada, se activó: la segunda corrida de `make verificar` duró 955 s, más de 15 minutos. **T01 no empieza** hasta que el responsable elija una opción de §1.10. Sin push, PR ni etiquetas.

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

Cuatro corridas de `make verificar`, todas en serie y sin nada en paralelo. Desde la segunda, por decisión del responsable, sin bajar los contenedores: así las cachés de PHPStan y de Pint de la corrida anterior seguían dentro del contenedor (§1.8.2).

#### 1.8.1 Corridas y tiempos por etapa

Desde la corrida 2, cada línea de la salida llevó una marca de tiempo, y las fronteras de cada etapa salen de la primera y la última línea. La corrida 1 no tuvo marcas, así que solo tiene el total y la `Duration` de Pest.

| Corrida | Qué la precedió | Arranque y Pint | PHPStan | Pest | Informe de cobertura | Total |
|---|---|---:|---:|---:|---:|---:|
| 1 | `make bajar` y `make levantar`: contenedores recién creados, cachés vacías | — | — | 647 s | — | **990 s** |
| 2 | `make mutacion`, `make fresh`, `make humo` y la corrección de §1.6 | 78 s | **114 s** | 712 s | 50 s | **955 s** |
| 3 | Las corridas de diagnóstico de PHPStan de §1.8.3 | 16 s | **5 s** | 688 s | 47 s | **756 s** |
| 4 | `make mutacion`, inmediatamente antes | 9 s | **unos 4 s** | 641 s | 48 s | **702 s** |

En todas, `bin/verificar-cobertura.php` y `composer audit` tardaron menos de 1 s. Las cuatro terminaron con código 0 y los mismos resultados: 1 432 pruebas; cobertura de 100 % en Dominio, 100 % en Aplicación y 99,5 % en Infraestructura y HTTP; sin avisos de seguridad.

- **Con las cachés calientes (corridas 3 y 4), `make verificar` queda por debajo de 15 minutos:** 756 s y 702 s. PHPStan tardó entre 4 y 5 s, y el arranque más Pint, entre 9 y 16 s.
- **Las corridas 1 y 2 pasaron de 15 minutos.**
  - En la 1 era lo esperado: empezó 24 s después de crear el contenedor, con las dos cachés vacías.
  - En la 2, PHPStan (114 s) y Pint (unos 70 s) corrieron como en frío, aunque sus cachés de la corrida 1 estaban en el contenedor. Eso sigue sin explicarse (§1.8.4).
- **Pest es casi todo el tiempo:** entre 641 y 712 s, es decir, entre el 75 % y el 91 % del total.

#### 1.8.2 Dónde viven las cachés

- **PHPStan:** `tmpDir: /tmp/phpstan` y `resultCachePath: /tmp/phpstan/resultCache.php`, según `phpstan dump-parameters`.
- **Pint:** el proyecto no tiene `pint.json`, así que Pint usa su archivo por omisión. Según `ConfigurationResolverFactory`, el nombre es `/tmp/md5(rutas analizadas | versión de Prettier)`. Para todo el proyecto, como lo corre `verificar`, es `/tmp/56f20e94680473f6417d411c2a2def09` y cubre 704 archivos. Una corrida sobre un archivo suelto usa otro nombre.
- **Ninguna de las dos es persistente.** Las dos viven en la capa del contenedor `app`, sin volumen, así que `make bajar` (`docker compose down`) las borra.
- **El log de `make verificar` no muestra si se usó la caché.** `composer verificar` corre PHPStan con `--no-progress` y sin `-v`, y PHPStan solo informa de la caché en modo detallado. Por eso se comprobó de dos formas:
  - con corridas de diagnóstico con `-vv`;
  - comparando la hora y la sha1 de `resultCache.php` antes y después.

  En las corridas 3 y 4, `resultCache.php` no cambió.

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

Por eso los 990 s de la corrida 1 y los 955 s de la corrida 2 **no se atribuyen a `make fresh` ni a `make humo`**.

#### 1.8.4 Lo que sigue sin explicar: el enfriamiento de la corrida 2

Lo que se sabe:

- Antes de la corrida 2, `/tmp/phpstan/cache` tenía los 2 657 archivos que escribió la corrida 1 (de 18:01 a 18:03 UTC), y la corrida 2 no borró ninguno.
- A las 19:39:09–10, en plena etapa de PHPStan, PHPStan hizo dos cosas:
  - regeneró su contenedor de configuración, `Container_673c1dc0ac.php`, con el mismo nombre que a las 18:01;
  - escribió por primera vez el marcador `cache/PHPStan/cache-cleared`.
- Después reescribió solo 5 archivos `.dat`. Al terminar, reescribió `resultCache.php`.
- Que el contenedor conserve el nombre indica que la configuración era la misma y que nette lo dio por vencido porque cambió la fecha de un archivo del que depende. Eso coincide con el cambio temporal de nivel 8 → 7 → 8 en `phpstan.neon` de las 18:36:45 (§1.6). Pero `touch phpstan.neon` no tira la caché de resultados (§1.8.3): eso explica la regeneración del contenedor, no los 114 s.
- Pint también corrió como en frío en la corrida 2: unos 70 s, frente a 9–16 s con caché.

La única diferencia que queda sin reproducir es el punto de partida: la corrida 1 fue la primera después de `make levantar` y creó desde cero las cachés de PHPStan y de Pint. Que las dos salieran frías en la corrida siguiente, y no después, apunta a eso, pero no está demostrado. El responsable aprobó la prueba que lo resuelve: `make bajar`, `make levantar`, `make verificar` y `make verificar`.

#### 1.8.5 Tope del candado

`bin/con-candado.sh` corta `make verificar` a los 20 minutos (1 200 s, PT-07). El margen depende de dos cosas: las cachés y la variación de Pest (§1.9).

- **Con las cachés calientes:** el resto de etapas suma unos 61 s (corrida 4). Con el Pest más lento medido, 798 s, el total quedaría en unos 860 s: por debajo de 15 minutos y a unos 5,7 minutos del tope.
- **Con las cachés frías:** PHPStan y Pint suman unos 170 s más, así que el total podría llegar a unos 1 030 s. Eso deja menos de 3 minutos de margen.

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
2. **Los archivos cambiados suben en la misma proporción que los demás:** ×1,48 frente a ×1,42 en A→B y ×1,14 frente a ×1,21 en A→`verificar` 1. Si siguieran el factor de los archivos sin cambios, el efecto de T00 queda entre −23 s y +21 s, dentro de lo que el mismo código varía de una corrida a otra. Las cuatro corridas de `make verificar`, con el mismo código, van de 641 a 711 s.
3. `main` tardó 547 s hoy frente a 512 s en H4-T06 con el mismo código de pruebas. El entorno también explica esa parte.

No identifiqué qué hace más lento al entorno de una corrida a otra (carga del anfitrión, la máquina virtual de Docker o el montaje de Windows); eso requeriría medirlo fuera de Pest.

### 1.10 Condición de parada: opciones para el responsable

La condición de parada se activó con la corrida 2 (955 s), así que **T01 no empieza** hasta que el responsable decida. Con las cachés calientes, las corridas 3 y 4 quedaron por debajo de 15 minutos (756 y 702 s). Estas son las opciones que pidió el responsable, con los datos de §1.8 y §1.9; no elijo ninguna.

| Opción | Qué cambia | Efecto esperado según las mediciones | Qué hay que tener en cuenta |
|---|---|---|---|
| (i) Persistir la caché de PHPStan en un volumen del contenedor | Un volumen para el `tmpDir` de PHPStan en `docker-compose.yml` (o un `tmpDir` en `phpstan.neon` que apunte a él) | Con la caché caliente, PHPStan tardó 4–5 s, frente a 114 s en frío. Ahorro de hasta unos 110 s por corrida tras `make bajar`, **solo si** la caché sigue siendo válida de una corrida a la siguiente | El enfriamiento de la corrida 2 no está explicado (§1.8.4): hasta saber la causa, un volumen podría no ahorrar nada. La caché de Pint vive aparte, en `/tmp` (§1.8.2), y corrió en frío en la misma corrida (unos 70 s): un volumen solo para `/tmp/phpstan` no la cubre. Toca `docker-compose.yml`, que no está en las rutas de T00. No reduce Pest |
| (ii) Mover PHPStan a un job separado en CI | El workflow de CI corre PHPStan en paralelo con Pest | En CI, el tiempo de pared baja en lo que dura PHPStan (unos 114 s en frío). En local no cambia nada, salvo que también se separe `composer verificar` | `make verificar` dejaría de ser el árbitro único en local si PHPStan sale de él. Toca `.github/workflows/` y quizá `composer.json` |
| (iii) Subir el límite | La condición de parada de 15 minutos (y el aviso de 13) | Ningún ahorro. El total medido osciló entre 702 s (en caliente) y 990 s (en frío), y con la variación de Pest (547–798 s) podría llegar a unos 1 030 s en frío | El tope de 20 minutos de `bin/con-candado.sh` queda a menos de 3 minutos en el peor caso en frío (§1.8.5). Un límite nuevo debería contar con esa variación, o tocar también el tope |

Ninguna de las tres ataca la causa principal: la variación de Pest según el entorno, de 547 a 798 s con casi el mismo código.

### 1.11 Incidencia durante la medición A/B

Para la medición A, la copia de trabajo se pasó a `a34283e` en modo detached y después se volvió a `hito/f1-h5`. En esa corrida, Git Bash convirtió la ruta `/tmp/ab-main.xml` del argumento `--coverage-clover` en una ruta de Windows y la cobertura se escribió en una carpeta nueva dentro de `backend/`. La carpeta se revisó (solo contenía ese XML) y se borró por su nombre exacto; la corrida B se hizo con `MSYS_NO_PATHCONV=1`. Al revisar la carpeta, un `du` con un glob mal formado empezó a recorrer la raíz `C:\` de Windows. Era solo lectura y se detuvo; no se borró nada fuera de esa carpeta.

Comprobación posterior, pedida por el responsable:
- `git status` está limpio y `git branch --show-current` da `hito/f1-h5`;
- `git diff --quiet HEAD` y `git diff --cached --quiet` dan 0;
- no hay archivos sin rastrear ni entradas en `stash`;
- el reflog muestra la vuelta de `a34283e` a `510a0e3`;
- el único commit posterior a la vuelta, `7306d09`, solo cambia este reporte;
- en `backend/` no queda ninguna carpeta extraviada.
