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

### 1.8 Segunda medición de `make verificar`

Por decisión del responsable, esta corrida se hizo **sin bajar los contenedores**, para medir con la caché de PHPStan de la primera corrida. Bajarlos la habría borrado (§1.8.2). Antes se confirmó que los cuatro contenedores seguían arriba desde la primera corrida y que `/tmp/phpstan/cache` existía (43 MB, 2 657 archivos). No hubo nada en paralelo.

#### 1.8.1 Tiempos por etapa

Cada línea de la salida llevó una marca de tiempo. Las fronteras se toman de la primera y la última línea de cada etapa.

| Etapa | Desde → hasta | Tiempo |
|---|---|---:|
| Arranque (`docker compose exec`, candado) | inicio → aviso del vigilante | 8 s |
| Pint (`--test`, 704 archivos) | → `PASS … 704 files` | 70 s |
| **PHPStan** | → `[OK] No errors` | **114 s** |
| **Pest** (1 432 pruebas, con cobertura) | → `Duration: 710.60s` | **712 s** |
| Informe de cobertura de Pest (HTML, Clover y texto) | → tabla final | 50 s |
| `bin/verificar-cobertura.php` y `composer audit` | → fin | 0,4 s |
| **Total** | | **955 s (15 min 55 s)**, código 0 |

Pest es el 75 % del total y PHPStan el 12 %.

#### 1.8.2 Caché de PHPStan

- **El log de `make verificar` no dice si se usó la caché:** `composer verificar` corre PHPStan con `--no-progress` y sin `-v`, y PHPStan solo informa de la caché en modo detallado.
- **La caché no es persistente:** vive en `/tmp/phpstan` (`sys_get_temp_dir()`), dentro de la capa del contenedor `app`, sin volumen. `make bajar` (`docker compose down`) borra el contenedor y con él la caché, así que cada corrida que empieza después de bajar los contenedores arranca en frío.
- **Dentro de un mismo contenedor, la caché funciona.** Tras la segunda corrida, una ejecución de diagnóstico con `-vv` dio «Result cache restored. 0 files will be reanalysed» y tardó 5 s. Con un archivo cambiado, 11 s.
- **En la segunda corrida no sirvió:** PHPStan tardó 114 s, lo mismo que un análisis completo (95 s y 114 s en frío), aunque la caché de la primera corrida estaba en el contenedor. Durante esa etapa, a las 19:39:10, PHPStan escribió el marcador `cache/PHPStan/cache-cleared`, y al terminar reescribió `resultCache.php`. **No encontré qué la invalidó.** Descarté cuatro causas con corridas de diagnóstico:
  - cambiar la fecha de `phpstan.neon`: 0 archivos reanalizados;
  - correr PHPStan a través de Composer: 0;
  - editar `ConfiguracionDePhpstanTest.php`: 1;
  - regenerar los manifiestos de `bootstrap/cache/`: 1.

  Entre las dos corridas también pasaron `make mutacion`, `make fresh` y `make humo`, y no se probaron una por una.

#### 1.8.3 Tope del candado

`bin/con-candado.sh` corta `make verificar` a los 20 minutos (PT-07). Con la variación de Pest de §1.9, el total pudo haber llegado a unos 1 040 s: 798 s de Pest en la corrida B, más 245 s del resto. Eso deja menos de 3 minutos de margen.

### 1.9 Por qué Pest subió de 512 s a 647 s

**Corridas de hoy**, todas con 1 430 o 1 432 pruebas y cobertura:

| Corrida | Código | Pest |
|---|---|---:|
| `make verificar` 1 (contenedores recién creados) | rama | 647 s |
| `make verificar` 2 | rama | 711 s |
| A: mismo comando de Pest que `verificar` | `main` (`a34283e`, la base de H5 sin T00) | **547 s** |
| B: el mismo comando, justo después de A | rama (`510a0e3`) | **798 s** |

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
2. **Los archivos cambiados suben en la misma proporción que los demás:** ×1,48 frente a ×1,42 en A→B y ×1,14 frente a ×1,21 en A→`verificar` 1. Si siguieran el factor de los archivos sin cambios, el efecto de T00 queda entre −23 s y +21 s, dentro de lo que el mismo código varía de una corrida a otra (+64 s entre `verificar` 1 y 2).
3. `main` tardó 547 s hoy frente a 512 s en H4-T06 con el mismo código de pruebas. El entorno también explica esa parte.

No identifiqué qué hace más lento al entorno de una corrida a otra (carga del anfitrión, la máquina virtual de Docker o el montaje de Windows); eso requeriría medirlo fuera de Pest.

### 1.10 Condición de parada: opciones para el responsable

La segunda corrida duró 955 s, por encima de 15 minutos, así que **no empiezo T01**. Estas son las opciones que pidió el responsable, con los datos de §1.8 y §1.9; no elijo ninguna.

| Opción | Qué cambia | Efecto esperado según las mediciones | Qué hay que tener en cuenta |
|---|---|---|---|
| (i) Persistir la caché de PHPStan en un volumen del contenedor | Un volumen para el `tmpDir` de PHPStan en `docker-compose.yml` (o un `tmpDir` en `phpstan.neon` que apunte a él) | Hasta unos 110 s menos por corrida (de 114 s a 5–11 s), **solo si** la caché sigue siendo válida de una corrida a la siguiente | En la segunda corrida la caché no sirvió dentro del mismo contenedor y no se sabe por qué (§1.8.2). Hay que encontrar la causa, o el volumen no ahorra nada. Toca `docker-compose.yml`, que no está en las rutas de T00. No reduce Pest, que es el 75 % |
| (ii) Mover PHPStan a un job separado en CI | El workflow de CI corre PHPStan en paralelo con Pest | En CI, el tiempo de pared baja en lo que dura PHPStan (unos 114 s en frío). En local no cambia nada, salvo que también se separe `composer verificar` | `make verificar` dejaría de ser el árbitro único en local si PHPStan sale de él. Toca `.github/workflows/` y quizá `composer.json` |
| (iii) Subir el límite | La condición de parada de 15 minutos (y el aviso de 13) | Ningún ahorro. El total medido osciló entre 955 y 990 s, y con la variación de Pest (547–798 s) podría llegar a unos 1 050 s | El tope de 20 minutos de `bin/con-candado.sh` queda a unos 2,5 minutos. Un límite nuevo debería contar con esa variación, o tocar también el tope |

Ninguna de las tres ataca la causa principal: la variación de Pest según el entorno, de 547 a 798 s con casi el mismo código.

### 1.11 Incidencia durante la medición A/B

Para la medición A, la copia de trabajo se pasó a `a34283e` en modo detached y después se volvió a `hito/f1-h5`. En esa corrida, Git Bash convirtió la ruta `/tmp/ab-main.xml` del argumento `--coverage-clover` en una ruta de Windows y la cobertura se escribió en una carpeta nueva dentro de `backend/`. La carpeta se revisó (solo contenía ese XML) y se borró por su nombre exacto; la corrida B se hizo con `MSYS_NO_PATHCONV=1`. Al revisar la carpeta, un `du` con un glob mal formado empezó a recorrer la raíz `C:\` de Windows. Era solo lectura y se detuvo; no se borró nada fuera de esa carpeta. Al terminar, `git status` estaba limpio.
