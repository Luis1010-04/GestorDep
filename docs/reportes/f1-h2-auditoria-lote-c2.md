# Auditoría independiente del lote C2 de H2 (T04 a T11 y FIX intermedios)

**Fecha:** 28 de octubre de 2026 · **Rama:** `hito/f1-h2` en `10235d2` · **Base de comparación:** etiqueta `f1-h1`
**Alcance:** `docs/hitos/f1-h2.md` §0.5. Sesión de solo lectura: no se editó código, pruebas, migraciones ni documentos (este archivo es el único creado). Las pruebas de ruptura se ejecutaron contra la aplicación levantada y en copias temporales en `/tmp` del contenedor, que se borraron; la base de desarrollo quedó otra vez en `make fresh`. `git status` limpio al terminar (salvo este archivo).

## Resumen

| Clase | Hallazgos |
|---|---|
| BLOQUEANTE | ninguno |
| IMPORTANTE | 1 (I-1) |
| MENOR | 10 (M-1 a M-10) |

**Veredicto:** el lote C2 está **listo para el Lote D** una vez que el responsable resuelva I-1 (una corrección de una línea en la especificación; el código no cambia). Ningún intento de romper la privacidad tuvo éxito; los 404 son idénticos byte a byte en todas las rutas nuevas; no se debilitó ninguna prueba ni umbral; el FIX de Infection funciona.

---

## 1. `make fresh`, `make verificar` y `make humo`

Entorno en reposo antes de empezar (`docker stats`: app 0,69 %, db 0,00 %, worker 0,00 %, scheduler 0,12 % de CPU).

### `make fresh`

```
  2026_10_26_030100_crear_tabla_periodos_escolares ............. 550.14ms DONE
  ...
  2026_10_26_090800_crear_tabla_parametros_institucionales_historial  111.00ms DONE
  Database\Seeders\CriterioGeneralSeeder ......................... 395 ms DONE
  Database\Seeders\ParametrosInstitucionalesSeeder ............... 116 ms DONE
  Database\Seeders\UsuariosDemoSeeder .......................... 1,744 ms DONE
EXIT=0 SEG=43
```

### `make verificar` (pasa; 604 s)

Cada línea de la salida se marcó con los segundos transcurridos. Tiempos por etapa:

| Etapa | Desde → hasta | Duración | Resultado (salida real) |
|---|---|---:|---|
| Pint `--test` | 0 → 60 s | ~60 s (incluye el arranque de `docker compose exec` y Composer) | `PASS ... 356 files` |
| PHPStan nivel 7 | 60 → 72 s | 12 s | `[OK] No errors` |
| Infection (solo `Dominio/`) | 72 → 188 s | 116 s (Infection informa `Time: 1m 48s`) | `335 mutations were generated: 335 mutants were killed by Test Framework` · `MSI: 100%` · `Mutation Code Coverage: 100%` · `Covered Code MSI: 100%` |
| `bin/verificar-mutacion.php` | 188 s | < 1 s | `Mutación completa: 335 mutantes evaluados, ninguno omitido ni con tiempo agotado.` |
| Pest con cobertura | 188 → 577 s | 389 s (Pest: `Duration: 389.15s`) | `Tests: 783 passed (3975 assertions)`; ninguna omitida |
| Informe de cobertura y `verificar-cobertura.php` | 577 → 601 s | 24 s | ver tabla de capas |
| `composer audit` | 601 → 603 s | 2 s | `No security vulnerability advisories found.` |
| **Total** | | **604 s (10 min 4 s)** | `EXIT=0` |

```
Capa                      Cobertura   Umbral  Estado
Dominio                      100.0%      95%  ok
Aplicación                  100.0%      85%  ok
Infraestructura y HTTP        99.8%      70%  ok
```

Resumen de Infection (`build/infection/resumen.log`):

```
Total: 335
Killed by Test Framework: 335
Killed by Static Analysis: 0
Errored: 0
Syntax Errors: 0
Escaped: 0
Timed Out: 0
Skipped: 0
Ignored: 0
Not Covered: 0
```

Las pruebas que se saltan en ejecuciones parciales sí corrieron en la suite completa:

```
[ 233s]   ✓ RN-20: solo la declaración, el adaptador, su enlace y el caso de us… 0.80s
[ 373s]   ✓ RNF-01 (§7.4 fila 14): las lecturas de jugadores siguen pasando por… 1.03s
```

**Tiempo:** 604 s frente a los 344 s (5 min 44 s) que informa el resumen del lote. Durante mi corrida solo hice lecturas de archivos y un `git log`. Ver M-9.

### `make humo` (H0 y H1; el guion de H2 llega en T15)

```
HUMO H0 OK: GET /api/v1/salud -> 200
OK     paso 1: GET /api/v1/salud -> HTTP 200
...
OK     paso 14: la misma cookie responde 401 -> HTTP 401
HUMO H1 OK: 14 pasos
EXIT=0
```

---

## 2. Contraste fila por fila (§7.1 a §7.6 y filas de §7.10 de T09 a T11)

Leyenda: **=** la prueba afirma lo que dice la fila (o más); **<** la prueba es más débil que la fila; **—** sin prueba.

### §7.1 Periodos escolares (`tests/Feature/Academico/PeriodosEscolaresTest.php`)

| Fila | Prueba | Veredicto |
|---|---|---|
| 1 | `HU-28 (§7.1 fila 1)` (l. 43): 201, `vigente` falso, `actualizado_en` del reloj, claves exactas, `creado_por` | = |
| 2 | `HU-28 (§7.1 fila 2)` (l. 55) | = |
| 3 | `HU-28 (§7.1 fila 3)` (l. 60): `'  2026-a  '` → 409 con cuerpo exacto; una sola fila | = |
| 4 | `HU-28 (§7.1 fila 4)` (l. 70): igual y anterior | = |
| 5 | `HU-28 (§7.1 fila 5)` (l. 79): 6 casos con `errores.0.campo` | = |
| 6 | `HU-28 (§7.1 fila 6)` (l. 94): afirma el orden **P2, P1, P0** | = respecto de `-fecha_inicio`, **≠ respecto del texto de la fila** (ver I-1) |
| 7 | `DD-45 (§7.1 fila 7)` (l. 119) | = |
| 8 | `HU-28 (§7.1 fila 8)` (l. 154): 204, deja de listarse; «una materia que lo usa lo sigue mostrando» se comprueba con un `join` en la base, no por la API | < en su propia prueba; la parte de la API está en `§7.3 fila 8` (MateriasTest l. 185, periodo y asignatura eliminados). Ver M-3 |
| 9 | `HU-28 (§7.1 fila 9)` (l. 171) | = |
| 10 | `HU-28 (§7.1 fila 10)` (l. 181): inexistente, ya eliminado, no v7 y no UUID | = |
| 11 | `DD-49 (§7.1 fila 11)` (l. 195): las tres rutas, `sin_permiso` | = |
| 12 | `DD-49 (§7.1 fila 12)` (l. 206) | = |
| 13 | `HU-28 (§7.1 fila 13)` (l. 211) | = |

### §7.2 Asignaturas (`AsignaturasTest.php`)

| Fila | Prueba | Veredicto |
|---|---|---|
| 1 | l. 28 | = |
| 2 | l. 38 (`'  mat-101 '`, cuerpo exacto) | = |
| 3 | l. 48: 0, 13, `"uno"`, 1.5, ausente; `errores.0.campo` = `grado` | = |
| 4 | l. 68: clave de 21, clave vacía, nombre vacío, nombre de 151 | = |
| 5 | l. 83: orden por `clave`, `meta`, orden inverso y orden inválido | = |
| 6 | l. 100: 204, se recrea la clave; «la materia la sigue mostrando» por `join` en la base | < en su propia prueba; cubierto por la API en `§7.3 fila 8`. Ver M-3 |
| 7 | l. 129 | = |
| 8 | l. 140 | = |

### §7.3 Materias (`MateriasTest.php`)

| Fila | Prueba | Veredicto |
|---|---|---|
| 1 | l. 56: crea S2+P1 (S1+P1 ya es M1 en el mundo de partida), `criterio_aplicado` 7.00, historial `crear` v1 con `datos_nuevos` | = |
| 2 | l. 82: 409 exacto, sin historial | = |
| 3 | l. 92 | = |
| 4 | l. 101: v7 inexistente, no v7 y eliminado, para asignatura y periodo | = |
| 5 | l. 123: 5 casos | = |
| 6 | l. 140: solo las de D1, filtro, filtro inexistente, filtro inválido | = |
| 7 | l. 161: ajena = inexistente = no v7, byte a byte | = (además ver §3: se demostró que puede fallar) |
| 8 | l. 185: asignatura **y** periodo eliminados, por la API | = |
| 9 | l. 199: las tres rutas | = |
| 10 | l. 210 | = |

### §7.4 Alcance del docente (`AlcanceDelDocenteTest.php`)

| Fila | Prueba | Veredicto |
|---|---|---|
| 1 | l. 17: `[J1, J3]` exacto | = |
| 2 | l. 24: J3 se agrega a EA para que una fuga se notara; `equipos` = `[]` | = (más fuerte que la fila) |
| 3 y 4 | l. 35: una prueba para las dos filas, byte a byte frente a un inexistente | = |
| 5 | l. 47: `estatus_academico` y `estatus_deportivo` = `baja` | = |
| 6 | l. 61: exactamente tres campos, sin inscripción creada | = |
| 7 | l. 67 | = |
| 8 | l. 74: una vez, `meta.total` 2 | = |
| 9 | l. 86: **inscribe además a J4 en M3** (`mundoInscribir($m['m3'], $m['j4'])`), así que J4 entra también por la rama docente | < (ver M-4) |
| 10 | l. 100 | = |
| 11 | l. 107: tres rutas, `sin_permiso`, sin filas nuevas | = |
| 12 | l. 118 | = |
| 13 | l. 128 | = |
| 14 | l. 137: lista cerrada exacta y ninguna consulta sin alcance; corre en la suite completa | = |

### §7.5 Inscripciones (`InscripcionesTest.php`)

| Fila | Prueba | Veredicto |
|---|---|---|
| 1 | l. 22: claves exactas del recurso, historial `crear` con autor y `datos_nuevos`, D1 ve a J4 | = |
| 2 | l. 44: `'  m-4  '` → 201 y luego 409 exacto | = |
| 3 | l. 55: cuerpo exacto, sin filas nuevas | = |
| 4 | l. 69 | = |
| 5 | l. 77: usa un jugador nuevo en baja (M-9) en lugar de J5, que ya está inscrito; cuerpo exacto | = |
| 6 | l. 88: POST con 4 cuerpos byte a byte; GET de M2 solo `assertNotFound()` | = en POST; < en GET (no compara bytes). Ver M-5 |
| 7 | l. 103 | = |
| 8 | l. 112: orden, sin J5, paginación | = |
| 9 | l. 126 | = |
| 10 | l. 139: J4 en un equipo **de A** (llamado «ED») para que una fuga se notara | = |
| 11 | l. 150: ausente, vacía, espacios, 31, número | = |
| 12 | l. 173 | = |

### §7.6 Criterio por materia (`CriterioPorMateriaTest.php`)

| Fila | Prueba | Veredicto |
|---|---|---|
| 1 | l. 27 | = |
| 2 | l. 35: recurso exacto, historial `crear` con autor e instante | = |
| 3 | l. 53: cierre `vigente_hasta` = hoy, uno vigente, `crear`+`actualizar` en el mismo instante, `datos_anteriores` y `datos_nuevos` | = |
| 4 | l. 77: `"6.00"` y `"6"`, sin filas, historial ni evento | = |
| 5 | l. 93: campo y código en `errores`; límites aceptados | = |
| 6 | l. 110: 8 casos (6, 6.5, vacío, espacios, `abc`, `6.555`, `6,5`, nulo) | = |
| 7 | l. 130: 4 cuerpos byte a byte, no v7 | = (además ver §3) |
| 8 | l. 144 | = |
| 9 | l. 153: `Event::fake` y una sola publicación con el id | < por sí sola: no distingue «después del commit» de «dentro de la transacción». Lo cubren la fila 10 (`assertNotDispatched` tras la reversión) y `arquitectura §11: el publicador despacha después del commit` (RecalculoDelResumenTest l. 283). Ver M-6 |
| 10 | l. 164: disparador real (`CHECK` sobre `actualizar`), 500 `error_interno`, sin criterio nuevo ni cierre, sin evento | = |

### §7.10, filas de T09, T10 y T11

| Fila | Tarea | Prueba | Veredicto |
|---|---|---|---|
| 5 | T11 | `CriterioPorMateriaTest` l. 196: 1 reprobada tras el `PUT`, por el escucha `sync`. La segunda mitad (final 8.00 = mínimo 8.00) corrige la nota en la base y usa `alertas:recalcular`, porque el `PUT` del mismo valor no publica nada | = |
| 6 | T11 | l. 212 | = |
| 15 | T09 | `RecalculoDelResumenTest` l. 143 (caso de uso: no escribe resumen) | = para el nivel que pide T09 (la lectura con nulos es de T14) |
| 16 | T09 | l. 152: comando, fila de P2 con nulos y la de P1 intacta | = |
| 17 | T09 | l. 166: dos corridas iguales, código 0, 4 filas | = |
| 18 | T09 | l. 183 | = |
| 19 | T09 | l. 197: mismo evento dos veces, una fila igual | = |
| 22 | T09 | l. 212: reloj con `.900` → `.000000` | = |
| 23 | T10 | `InscripcionesTest` l. 181 (evento con los tres ids) y l. 193 (despachador real: `materias_sin_calificar` 1, promedio y nivel nulos) | = |
| 24 | T09 | l. 222: `0 2 * * *` y `America/Mexico_City` | = |

**Filas sin prueba:** ninguna.
**Pruebas que no podrían fallar (aserciones triviales o vacías):** ninguna. Las 404 de privacidad, que pasan trivialmente si la ruta no existe, sí fallan si se quita el filtro por docente (§3).

---

## 3. Evidencia de «pruebas primero» en T05 a T07

| Tarea | Lo que dice el reporte | Análisis |
|---|---|---|
| T05 | «Failed asserting that 404 is identical to 422 / 204 / 201 / 200 / 403 / 401. FAILED ... (15 pruebas)», con una «nota de honestidad»: las capas internas se escribieron antes que las pruebas | Fallan por **ruta inexistente** (404), no por clase inexistente: la razón es aceptable para las pruebas de ruta. Pero de 27 pruebas nuevas solo se muestran 15 fallando; las que esperan 404 (fila 10, id no v7) y las unitarias de `PeriodoEscolar` **pasan** sin ruta y nunca se vieron fallar |
| T06 | «Tests: 19 failed ... Expected response status code [422] but received 404 (9) [201] ... 404 (5) ...», con la misma explicación | Igual: falla por ruta inexistente; 1 de 20 no se vio fallar (la de 404) |
| T07 | Rutas retiradas con `git stash`: «[422] ... 404 (6) [200] ... 404 (3) [201] ... 404 (2) [500] ... 404 (2)» | Sin total de pruebas. La prueba que fija el patrón de 404 para todo lo que sigue (§7.3 fila 7) **pasa trivialmente** sin ruta (tres 404 de ruta inexistente son idénticos), así que su evidencia nunca existió |

Todos los reportes lo explican con franqueza, y el resumen del lote B lo anota (punto 4). Para cerrar el hueco, comprobé que las pruebas de privacidad **sí pueden fallar**. En una copia temporal quité el filtro `where('materias.docente_id', …)` de `ConsultaDeMateriasSql::materiasDe`:

```
  ⨯ RNF-02 (§7.3 fila 7): D1 consulta M2 (de D2) y recibe un 404 idénti…
  ⨯ RNF-01 (§7.5 fila 6): D1 inscribe en M2 (de D2) y recibe un 404 idé…
  ⨯ RNF-01 (§7.6 fila 7): D1 define el criterio de M2 (de D2) y recibe…
  Expected response status code [404] but received 200.
  Expected response status code [404] but received 500.
  Expected response status code [404] but received 200.
  Tests:    3 failed, 1 passed (13 assertions)
```

Conclusión: la evidencia original es incompleta (M-1), pero las pruebas de privacidad son eficaces.

---

## 4. Intentos de romper la privacidad

Preparación por la API (`usuarios:crear` y casos de uso; nada por SQL): D1, D2 (docentes), DE (dos roles), A y E2 (entrenadores); EA de A con J1 y J5; EB de E2 con J2 y J3; ED de DE con J4; JB en baja sin inscribir; M1 (D1) con J1, J3 y J5; M2 (D2) con J2 y J3; M3 (DE) con J1; J5 dado de baja después de inscribirlo. Referencias: `V7` = UUID v7 inexistente, `V4` = `123e4567-e89b-42d3-a456-426614174000`.

**Todos los 404 de recurso son el mismo cuerpo de 92 bytes, md5 `6fba83ed…`:** `{"codigo":"recurso_no_encontrado","mensaje":"El recurso solicitado no existe.","errores":[]}`.

| Ataque | Resultado real |
|---|---|
| D1 `GET /materias/{M2}`, `{V4}`; DE `GET /materias/{M1}` | 404 idéntico byte a byte a `GET /materias/{V7}` |
| D1 `GET /materias/{M2}/inscripciones` (también con `?por_pagina=abc`, y `{V7}` con `?pagina=-1`) | 404 idéntico: la pertenencia se comprueba antes que los parámetros |
| D1 `POST /materias/{M2}/inscripciones` con `AUD-4`, `NO-EXISTE`, `AUD-B` (baja), `AUD-2`, `{}`, 40 caracteres, `5` y JSON malformado | 404 idéntico byte a byte a `{V7}` y `{V4}` con el mismo cuerpo, en los 8 casos: ni el 422, ni el 409 `jugador_en_baja`, ni el 404 `jugador_no_registrado` se filtran |
| D1 `PUT /materias/{M2}/criterio` con `"6.00"`, `"10.01"`, `6`, `"abc"`, `{}` | 404 idéntico a `{V7}`: no se filtra el 422 `calificacion_fuera_de_rango` |
| `DELETE /materias/{M2}` frente a `{V7}` | 405 idéntico (111 bytes): el método no permitido no distingue existencia |
| `GET /materias/no-es-uuid`, `GET /materias/{M2}/no-existe` | 404 idéntico |
| A y D1 `GET /inscripciones/{I1}/calificaciones`; A `GET /jugadores/{J1}/situacion-academica` | 404 de ruta (aún no existen en C2), idéntico al de recurso |
| A en `GET /materias`, `/materias/{M1}`, `/materias/{V7}`, `/materias/{M1}/inscripciones`, `/periodos-escolares`, `/asignaturas`, `PUT` criterio (con cuerpo válido y vacío), `POST` inscripción, `POST` periodo | 403 `sin_permiso` idéntico (98 bytes) en todos; el rol se comprueba antes de consultar |
| **DA-10:** DE `PATCH /jugadores/{J1}` (`nombres` válido, vacío y `matricula`), `POST /jugadores/{J1}/baja` (válido y `{}`), `PATCH /equipos/{ED}/jugadores/{J1}` | 404 idéntico al del mismo intento con `{V7}`; J1 conserva `nombres` = `N1`. Control: DE `PATCH /jugadores/{J4}` (su equipo) → 200; D1 `PATCH /jugadores/{J1}` → 403 |
| D1 inscribe a JB (en baja) en M1, con `AUD-B` y `'  aud-b '` | 409 `jugador_en_baja` «El jugador está en baja: no se puede asignar ni inscribir.»; D1 `GET /jugadores/{JB}` → 404 |
| D1 `GET /jugadores/{J2}` antes de inscribirlo | 404 idéntico a `{V7}` |
| D1 inscribe la matrícula ajena `AUD-4` en M2 | 404 idéntico |
| D1 inscribe la matrícula ajena `AUD-2` en M1 | 201; el jugador del recurso trae exactamente `id`, `nombres`, `apellidos` y `matricula`; después D1 ve a J2 con `equipos` `[]` (RN-20: la inscripción otorga el alcance) |
| D2 `GET /jugadores/{J1}` | 404 idéntico |
| Listados | D1: `AUD-1, AUD-2, AUD-3` (sin J5 en baja); D2: `AUD-2, AUD-3`; DE: `AUD-1, AUD-4` (unión sin duplicados) |
| Sin sesión, `GET`/`POST`/`PUT` | 401 `no_autenticado` idéntico (85 bytes) |

Extracto de la salida:

```
d1   404 POST   /api/v1/materias/01a1838e-ee37-…/inscripciones {"matricula":"AUD-B"} {"codigo":"recurso_no_encontrado",…}
      == idéntico byte a byte a ref_post404 (92 bytes, md5 6fba83ed)
d1   404 PUT    /api/v1/materias/01a1838e-ee37-…/criterio {"calificacion_minima":"10.01"} {"codigo":"recurso_no_encontrado",…}
      == idéntico byte a byte a ref_put404 (92 bytes, md5 6fba83ed)
de   404 PATCH  /api/v1/jugadores/01a1838e-e4fa-… {"nombres":"Hack"} {"codigo":"recurso_no_encontrado",…}
      == idéntico byte a byte a ref_patch404 (92 bytes, md5 6fba83ed)
d1   409 POST   /api/v1/materias/01a1838e-ed09-…/inscripciones {"matricula":"  aud-b "} {"codigo":"jugador_en_baja",…}
```

**Resultado:** no se encontró ninguna fuga. La única información que un docente obtiene de un jugador ajeno es la que la especificación permite: la búsqueda RN-20 (nombres, apellidos, matrícula; también de un jugador en baja, como en H1) y el 409 `jugador_en_baja` al inscribirlo (DD-52). Ver M-8.

### Fragmentos de pruebas propuestos (no se agregaron al repositorio)

```php
test('RNF-01 (§7.5 fila 6): el GET de inscripciones de una materia ajena es byte a byte el de una inexistente', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    $inexistente = sesionesPeticion($this, 'GET', '/api/v1/materias/'.Identificador::generar()->valor().'/inscripciones', idDeSesion: $sesion)->assertNotFound();

    foreach (['', '?por_pagina=abc', '?pagina=-1'] as $consulta) {
        $ajena = sesionesPeticion($this, 'GET', '/api/v1/materias/'.$m['m2'].'/inscripciones'.$consulta, idDeSesion: $sesion)->assertNotFound();
        expect($ajena->getContent())->toBe($inexistente->getContent());
    }
});

test('RNF-01 (§7.4 fila 9, estricta): DE ve a J4 solo por su equipo ED y a J1 solo por M3', function () {
    /** @var TestCase $this */
    $m = mundoAgregarEquipoYMateriaDeDocenteEntrenador(mundoDeDocentes()); // sin inscribir a J4 en M3

    expect(alcanceIdsListados($this, 'de@x.com'))->toBe([$m['j1'], $m['j4']]);
});

test('DA-10: el 404 de escribir el expediente de un jugador que DE solo enseña es byte a byte el de uno inexistente', function () {
    /** @var TestCase $this */
    $m = mundoAgregarEquipoYMateriaDeDocenteEntrenador(mundoDeDocentes());
    $sesion = sesionesIniciar($this, 'de@x.com');
    $inexistente = sesionesPeticion($this, 'PATCH', '/api/v1/jugadores/'.Identificador::generar()->valor(), ['nombres' => 'X'], $sesion)->assertNotFound();

    foreach ([['nombres' => 'X'], ['nombres' => ''], ['matricula' => 'X']] as $cuerpo) {
        $respuesta = sesionesPeticion($this, 'PATCH', '/api/v1/jugadores/'.$m['j1'], $cuerpo, $sesion)->assertNotFound();
        expect($respuesta->getContent())->toBe($inexistente->getContent());
    }
    $baja = sesionesPeticion($this, 'POST', '/api/v1/jugadores/'.$m['j1'].'/baja', [], $sesion)->assertNotFound();
    expect($baja->getContent())->toBe($inexistente->getContent());
});

test('RNF-01: un 405 de una materia ajena es igual al de una inexistente', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');

    $ajena = sesionesPeticion($this, 'DELETE', '/api/v1/materias/'.$m['m2'], idDeSesion: $sesion)->assertStatus(405);
    $inexistente = sesionesPeticion($this, 'DELETE', '/api/v1/materias/'.Identificador::generar()->valor(), idDeSesion: $sesion)->assertStatus(405);
    expect($ajena->getContent())->toBe($inexistente->getContent());
});
```

---

## 5. Decisiones revisadas

| Decisión | Veredicto |
|---|---|
| **`estatus_deportivo` visible al docente** (§7.4 fila 5) | **Aceptable.** La columna solo admite `activo` o `baja` (`ck_jugadores_estatus_deportivo`) y solo cambia con la baja, que pone ambos estatus en `baja` a la vez (DD-30, `RepositorioDeJugadoresEloquent.php:141`). Siempre es igual a `estatus_academico`: no revela nada deportivo (ni lesión ni disponibilidad). La fila 5 exige «estatus `baja`», y el recurso de H1 no cambia (§6.1). Recomendación para H5: cuando exista el estado de disponibilidad, decidir si `estatus_deportivo` se reemplaza para el docente |
| **DA-10: escrituras de plantel solo con el alcance de entrenador** | **Correcta y necesaria.** Sin ella, el docente-entrenador habría heredado permisos de escritura sobre los alumnos que solo enseña (escalada de privilegios). La implementación (`restringir(..., soloEntrenador: true)` en `buscarParaEscribir`) se verificó por la API: 404 idénticos (§4). La aprobó el responsable. Nota: tocó casos de uso de escritura de `src/Plantel/Aplicacion`, más allá de «solo lo que las lecturas necesiten» de T08; la autorización está en DA-10 |
| **Puerto `LocalizadorDeJugadores`** | **Correcto en el código.** `JugadorLocalizado` tiene solo `id` y `enBaja`; el puerto tiene un solo método; solo lo nombran el puerto, el adaptador, `PlantelServiceProvider` e `InscribirJugador`, y el adaptador reutiliza `buscarParaAsignar` (ya en la lista cerrada de H1), que lee solo `id` y `estatus_deportivo`. La lista cerrada de `AlcanceDeJugadores` no creció. Debilidad de la prueba de arquitectura en M-7 |
| **Nuevo texto de `jugador_en_baja`** | **Aceptable.** «El jugador está en baja: no se puede asignar ni inscribir.»: un mismo código para dos contextos, con texto en `lang/es`. El único cambio en H1 es la cadena esperada en `AsignacionDeJugadoresTest.php:160`, que sigue con `assertExactJson` (no se debilitó). Ningún otro documento o guion cita el texto anterior (`grep`) |
| **DA-11: `jugador_no_registrado` con 404** | **Correcto.** Es lo que dicen §6.3 y §7.5 fila 3 (precedencia de la especificación sobre una instrucción ambigua). El cuerpo no lleva datos y es el mismo para cualquier matrícula inexistente. No revela más que la búsqueda RN-20, que ya distingue existe / no existe. Además, en una materia ajena se responde el 404 genérico antes de mirar la matrícula (§4). **Pendiente:** que el responsable lo confirme por escrito, como pide el progreso |

Ninguna otra decisión `DA-01` a `DA-09` cambia privacidad ni permisos.

---

## 6. Archivos tocados por commit frente a las rutas permitidas

Fuente: `git log --stat f1-h1..HEAD` (excluidos `docs/reportes/**`, siempre permitidos).

| Commit | Fuera de las rutas de su tarea | Autorización |
|---|---|---|
| `70ceea1` T01 | `app/Exceptions/FormatoDeError.php`, `src/*/Infraestructura/**`, `tests/Feature/**`, `tests/Integration/**`, `tests/Ayudantes/**`, `composer.json` (`allow-plugins`), `composer.lock` | DA-01 (responsable), DA-02 (anotada). `composer.lock` es consecuencia de `require-dev` |
| `2aca9ed` T02 | `composer.lock` | Consecuencia de `ext-bcmath` (M-10) |
| `c84f4c5` T03 | ninguno (carpeta `alertas/`, corregida en `15df1f6` por decisión del responsable) | — |
| `a3f0c03` FIX | `.github/workflows/verificar.yml` | DA-07 (responsable) |
| `5db7870`, `3a82618`, `f58b835`, `d4c9bcf` T04 a T07 | ninguno | — |
| `e3e02be`, `143474e`, `752e4eb` FIX | `tests/**`; `app/Http/Controllers/CategoriaController.php` | Pedidos por el responsable (`f1-h2-fix-0N.md`) |
| `1a7b472` T08 | **`docs/api/openapi.yaml`** (solo descripciones); casos de uso de escritura de Plantel | openapi: no está en la lista de T08 (M-2). Plantel: DA-10 (responsable) |
| `324a977` T09 | ninguno (`src/Compartido/Aplicacion/PublicadorDeEventos.php` e `Infraestructura/PublicadorDeEventosLaravel.php` son «el mecanismo de eventos `afterCommit`» que permite T09) | — |
| `865c75f` T11 | ninguno | — |
| `952150b` T10 | `src/Compartido/Aplicacion/{LocalizadorDeJugadores,JugadorLocalizado}.php`, `src/Plantel/Infraestructura/{LocalizadorDeJugadoresPlantel,PlantelServiceProvider}.php` | Opción A del responsable |
| `aff1240` FIX | `bin/verificar-mutacion.php`, `composer.json`, `infection.json5` | Pedido del responsable |

Ningún commit toca `frontend/`, `docs/trazabilidad.md`, `docs/decisiones/registro-decisiones.md`, `docs/requisitos.md`, `docs/arquitectura.md` (salvo `c80ed22`, la especificación) ni migraciones de H0 o H1.

---

## 7. Umbrales y pruebas desde el inicio de H2

`git diff f1-h1..HEAD` de los archivos de calidad:

```
backend/phpstan.neon:   -    level: 6   +    level: 7
backend/composer.json:  + "ext-bcmath", + "infection/infection": "^0.35.8"
                        + "@php vendor/bin/infection --no-progress --with-uncovered",
                        + "@php bin/verificar-mutacion.php build/infection/resumen.log",
                        + "infection/extension-installer": false
backend/infection.json5 (nuevo): "minMsi": 80, "minCoveredMsi": 85, "timeout": 60, excludes Aplicacion e Infraestructura
backend/bin/verificar-mutacion.php (nuevo)
backend/bin/verificar-cobertura.php: sin cambios
```

- Ningún umbral bajó: PHPStan subió de 6 a 7, sin `ignoreErrors` ni línea base; MSI 80/85 como pide DD-53; cobertura por capa sin cambios. El paso de `timeout` de 20 a 60 s **no** debilita nada: un límite mayor produce menos `Skipped`, y cualquier `Skipped` o `Timed Out` hace fallar `verificar`.
- `git diff --diff-filter=D f1-h1..HEAD`: **ningún archivo eliminado**.
- Pruebas de H1 modificadas (15 archivos): solo `artisan(...)` → `consolaArtisan(...)`, `unprepared(...)` → `statement(...)`, una anotación `@var list<string>`, `config(['session.lottery' => [0, 100]])` en `TestCase` (PT-05), `require_once` del mundo de docentes y el texto de `jugador_en_baja`. Ninguna aserción se quitó ni se relajó. `tests/Arch/AlcanceDeJugadoresTest.php` no cambió.

---

## 8. FIX de Infection (`aff1240`)

Reproducido en una copia temporal (`/tmp/copia`, borrada después): una prueba unitaria de 1,5 s que cubre `NumeroDecimal`, `timeout` en 1 s y un script de Composer con la misma cadena que `verificar` más un paso centinela.

```
=== Infection sola
72 mutations were generated:
      50 mutants were killed by Test Framework
      22 mutants required more time than configured
         Mutation Score Indicator (MSI): 100%
codigo de salida de Infection: 0
Skipped: 22
=== Cadena como en composer verificar
Infection no evaluó todos los mutantes: 22 mutantes fueron omitidos (Skipped). …
Script @php bin/verificar-mutacion.php build/infection/resumen.log handling the mut-auditoria event returned with error code 1
codigo de salida de composer: 1
```

El paso centinela («NO DEBERIA LLEGAR AQUI») no se imprimió: Composer detiene la cadena. Con un resumen fabricado con `Timed Out: 1` → código 1. **El FIX funciona como se describe.** Observaciones en M-7 y M-10.

---

## 9. Hallazgos

### BLOQUEANTE

Ninguno.

### IMPORTANTE

**I-1. La fila 6 de §7.1 se contradice a sí misma y el agente la resolvió sin detenerse ni anotarlo.**
- **Dónde:** `docs/hitos/f1-h2.md:369` («P1, P2, P0 en ese orden (`-fecha_inicio`)») frente a `backend/tests/Feature/Academico/PeriodosEscolaresTest.php:100` (`toBe([$ids['p2'], $ids['p1'], $ids['p0']])`).
- **Evidencia:** con las fechas de §7.0 (P0 2025-08-01, P1 2026-01-12, P2 2026-08-03), `-fecha_inicio` da P2, P1, P0, no P1, P2, P0. La prueba implementa la regla normativa (§6.1, «orden por defecto `-fecha_inicio`»), lo que es razonable, pero §0.4 pide detenerse ante «una fila de las tablas de casos que parezca incorrecta». Ni `f1-h2-t05.md` ni `f1-h2-decisiones-del-agente.md` lo mencionan (`grep "P1, P2"` en `docs/reportes` no encuentra nada). La compuerta (§10.5) exige que cada fila pase tal como está escrita.
- **Cómo reproducirlo:** comparar la línea 369 de la especificación con la línea 100 de la prueba.
- **Qué hacer:** que el responsable corrija el texto de la fila («P2, P1, P0») en la especificación. El código no cambia. Además, recordarle al agente que una fila contradictoria es una condición de parada, aunque la salida obvia sea correcta.

### MENOR

**M-1. Evidencia de «pruebas primero» incompleta en T05 a T07.** `f1-h2-t05.md` («15 pruebas» de 27), `f1-h2-t06.md` (19 de 20), `f1-h2-t07.md` (sin total). Las pruebas que esperan 404, entre ellas el patrón de privacidad §7.3 fila 7, pasan sin ruta y nunca se vieron fallar; las unitarias tampoco. Los reportes lo explican. Este auditor comprobó que las de privacidad sí fallan (§3). Recomendación: en el Lote D, pegar la evidencia de las pruebas de 404 obtenida con una mutación del control de pertenencia, no retirando rutas.

**M-2. T08 tocó `docs/api/openapi.yaml`, que no está entre sus rutas permitidas** (`git show --stat 1a7b472`: `docs/api/openapi.yaml | 20 +++++-------`). Solo cambian descripciones de rutas de H1 para reflejar la rama docente, coherente con «toda tarea con rutas HTTP actualiza el contrato» (§8). Ni el reporte de T08 ni el progreso lo marcan como ruta fuera de la especificación («T08 solo actualizó descripciones de rutas de H1»). Que el responsable lo convalide y que T15 lo incluya en la lista de rutas fuera de la especificación.

**M-3. §7.1 fila 8 y §7.2 fila 6 comprueban «la materia lo sigue mostrando» con un `join` en la base, no por la API** (`PeriodosEscolaresTest.php:168`, `AsignaturasTest.php:113`). La comprobación por la API existe en `MateriasTest.php:185` (§7.3 fila 8, periodo y asignatura eliminados), así que la fila queda cubierta en conjunto. Sugerencia: citar esa prueba en la trazabilidad de T15.

**M-4. §7.4 fila 9 es más débil que la fila.** `AlcanceDelDocenteTest.php:89` inscribe además a J4 en M3, así que J4 entra también por la rama del docente. Con una implementación que, para un usuario con dos roles, usara solo la rama docente, la prueba seguiría pasando. El riesgo real es bajo: la rama del entrenador para un usuario con dos roles la ejercitan las pruebas de H1 (`ad@x.com` en `MundoDeJugadores.php:73`, `JugadoresTest.php:157`) y los ataques de §4 (DE ve `AUD-1, AUD-4` sin esa inscripción extra). Fragmento estricto en §4.

**M-5. Algunos 404 se comparan solo por código, no byte a byte:** el `GET` de §7.5 fila 6 (`InscripcionesTest.php:98`) y los 404 de DA-10 (`EscrituraDeUsuarioConDosRolesTest.php:16, 28, 39`). Los ataques de §4 confirman que son idénticos. Conviene endurecerlas con los fragmentos de §4; el recorrido de seguridad de T14 es el lugar natural.

**M-6. §7.6 fila 9 por sí sola no prueba «después del commit»** (`CriterioPorMateriaTest.php:153`, `Event::fake` sin transacción observable). La propiedad la garantizan la fila 10 (`assertNotDispatched` tras la reversión, l. 180) y la prueba del publicador (`RecalculoDelResumenTest.php:283`). Sin acción, salvo citarlas juntas en la trazabilidad.

**M-7. La prueba de arquitectura del localizador se puede eludir.** `tests/Arch/LocalizadorDeJugadoresTest.php:28` solo detecta tokens que **terminan** en `LocalizadorDeJugadores`. Un archivo que use directamente el adaptador `LocalizadorDeJugadoresPlantel`, o que resuelva el puerto con una cadena (`app('Src\\Compartido\\Aplicacion\\LocalizadorDeJugadores')`), no se detecta. La prueba de arquitectura de H0 sobre dependencias entre contextos podría atrapar el primer caso; no lo verifiqué. Sugerencia: incluir `LocalizadorDeJugadoresPlantel` en el detector y buscar también el nombre en cadenas.

**M-8. El 409 `jugador_en_baja` al inscribir revela a cualquier docente si una matrícula ajena está en baja.** Es lo que pide DD-52 y equivale al comportamiento de H1 para entrenadores; la búsqueda RN-20 ya devuelve nombres de jugadores en baja (§4: `GET /jugadores/busqueda?matricula=AUD-B` → 200). No es un defecto, pero es una decisión de privacidad que conviene registrar en T15 (DD-52) y revisar en H7.

**M-9. La holgura de tiempo de `make verificar` es menor que la informada.** El progreso informa 344 s «sin otros procesos»; esta auditoría midió **604 s** (Pest 389 s frente a 260 s), con solo lecturas ligeras en paralelo. Ya hubo corridas de 667 s (T10) y 1036 s (T08). Con las pruebas de T12 a T14, el límite de 15 minutos de §0.4 puede alcanzarse en el Lote D. Recomendación: que el responsable decida desde ahora qué hacer si una corrida supera los 15 minutos (por ejemplo, repetirla una vez y detenerse solo si se repite).

**M-10. Detalles menores del FIX de Infection y de rutas.** (a) `bin/verificar-mutacion.php` no comprueba que `build/infection/resumen.log` sea de la corrida actual. Si Infection dejara de escribirlo (por ejemplo, al quitar `logs.summary` de `infection.json5`), un resumen viejo limpio pasaría (`grep filemtime` → «no hay comprobación de vigencia del resumen»). Se puede borrar el resumen antes de Infection o comparar su fecha. (b) `Errored` e `Ignored` no hacen fallar la verificación (resumen fabricado con `Errored: 3`, `Ignored: 4` → código 0). Es el criterio de Infection, aceptable, pero conviene documentarlo. (c) `composer.lock` cambió en T01 y T02 sin estar listado: es consecuencia inevitable de los cambios permitidos de `composer.json`.

---

## 10. Qué debe corregirse primero

1. **I-1:** que el responsable corrija la fila 6 de §7.1 de la especificación (sin cambio de código).
2. Confirmar **DA-11** por escrito (pendiente en el progreso) y convalidar **M-2** (openapi en T08).
3. Decidir la regla para corridas lentas de `verificar` antes del Lote D (**M-9**).
4. Los demás menores (M-1, M-3 a M-8, M-10) pueden ir en un `F1-H2-FIX` de pruebas o quedar para el recorrido de seguridad de T14 y la trazabilidad de T15.
