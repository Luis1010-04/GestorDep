# F1-H5-T00: plan para subir PHPStan a nivel 8

**Fecha:** 1 de noviembre de 2026
**Rama:** `hito/f1-h5` (`9f2bec8`). El código de `backend/` es idéntico al de `main` (`a34283e`): `git diff --stat main -- backend` está vacío, porque la rama solo tiene commits F1-DOC.
**Especificación:** `docs/hitos/f1-h5.md` v1.0.3, §8 (T00)
**Estado:** aprobado por el responsable el 1 de noviembre de 2026 con la opción A (`NoNulo`) y las condiciones de §7. Al commitear este plan todavía no se había corregido nada: `phpstan.neon` seguía en `level: 7`.

## 1. Cómo se midió

Dentro del contenedor `app` (`make levantar`), con la misma configuración que usa `composer verificar` y el nivel pasado por línea de comandos, sin editar `phpstan.neon`:

```
docker compose exec -T app php vendor/bin/phpstan analyse --memory-limit=1G --no-progress            # nivel 7 (línea base)
docker compose exec -T app php vendor/bin/phpstan analyse --level=8 --memory-limit=1G --no-progress --error-format=json
```

Rutas analizadas (las de `phpstan.neon`): `app`, `bootstrap`, `routes`, `src`, `tests`.

| Corrida | Errores | Tiempo de pared |
|---|---|---|
| Nivel 7 (actual) | **0** (código de salida 0) | 95 s (caché fría) |
| Nivel 8 | **309** en **38 archivos**, en **287 líneas distintas** | 58 s (caché ya caliente) |

## 2. Resultado

### 2.1 Por carpeta

| Carpeta | Errores |
|---|---|
| `app/`, `bootstrap/`, `routes/`, `src/` | **0** |
| `tests/Feature` | 236 |
| `tests/Integration` | 55 |
| `tests/Unit` | 18 |

**El código de producción ya cumple el nivel 8.** Todos los errores están en las pruebas.

### 2.2 Por contexto (carpeta de la prueba)

| Contexto | Errores |
|---|---|
| Académico | 104 |
| Plantel | 87 |
| Competencia | 49 |
| Rendimiento | 36 |
| Identidad | 21 |
| Compartido | 12 |

### 2.3 Por tipo de error

| Identificador | Errores | Mensaje | Origen |
|---|---|---|---|
| `property.nonObject` | 284 | `Cannot access property $x on stdClass\|null.` | Leer una columna del resultado de `DB::table(...)->first()`, o de un ayudante local que lo devuelve con tipo `?stdClass` (por ejemplo, `calificacionesResumen()` en `CalificacionesTest.php`) |
| `method.nonObject` | 19 | `Cannot call method comoCadena()` (16) o `decimales()` (2) `on NumeroDecimal\|null`; `getParameters() on ReflectionMethod\|null` (1) | `ResumenAcademico::promedio()`, `porcentajeAsistencia()` y `ConteoDeAsistencia::porcentaje()` devuelven `?NumeroDecimal` (nulo cuando no hay datos, DD-43 y DD-44); `ReflectionClass::getConstructor()` devuelve `?ReflectionMethod` |
| `offsetAccess.notFound` | 6 | `Offset 'maximo'` (2), `'posiciones'` (2), `'tipo_dato'` (1) o `'minimo'` (1) `might not exist on array<string, mixed>\|null` | Un elemento de una lista de la respuesta buscado con `collect(...)->first(...)`, que puede ser nulo (`CapturaDeRendimientoTest.php`) |

Los tres tipos son la misma regla de nivel 8: **no usar un valor que puede ser nulo sin comprobarlo antes**. En todos los casos la prueba ya da por hecho que el valor existe (la fila se acaba de crear, el resumen se acaba de calcular). Si fuera nulo, la prueba ya falla hoy en tiempo de ejecución (`Attempt to read property on null` o `Call to a member function on null`). Ningún error apunta a un defecto del código de producción.

### 2.4 Por archivo

| Errores | Archivo |
|---|---|
| 25 | `tests/Feature/Plantel/AsignacionDeJugadoresTest.php` |
| 18 | `tests/Feature/Plantel/BajaDeJugadorTest.php` |
| 18 | `tests/Integration/Academico/MigracionesDeAcademicoTest.php` |
| 17 | `tests/Feature/Rendimiento/DesempenoTest.php` |
| 15 | `tests/Feature/Academico/CriterioPorMateriaTest.php` |
| 15 | `tests/Feature/Academico/RecalculoDelResumenTest.php` |
| 14 | `tests/Feature/Plantel/JugadoresTest.php` |
| 13 | `tests/Feature/Academico/CalificacionesTest.php` |
| 13 | `tests/Integration/Identidad/CrearUsuarioComandoTest.php` |
| 12 | `tests/Feature/Rendimiento/CapturaDeRendimientoTest.php` |
| 12 | `tests/Integration/Compartido/EscritorDeHistorialTest.php` |
| 9 | `tests/Feature/Academico/AsistenciasTest.php` |
| 9 | `tests/Feature/Competencia/CatalogosTest.php` |
| 9 | `tests/Feature/Competencia/ConvocatoriaTest.php` |
| 9 | `tests/Feature/Plantel/EquiposTest.php` |
| 9 | `tests/Feature/Plantel/ExpedienteDeJugadorTest.php` |
| 8 | `tests/Feature/Competencia/AlineacionYResultadoTest.php` |
| 8 | `tests/Feature/Competencia/EntrenamientosTest.php` |
| 7 | `tests/Feature/Academico/MateriasTest.php` |
| 7 | `tests/Feature/Competencia/PartidosTest.php` |
| 7 | `tests/Unit/Academico/ResumenAcademicoTest.php` |
| 6 | `tests/Feature/Plantel/ActualizadoEnCoherenteTest.php` |
| 6 | `tests/Unit/Academico/ConteoDeAsistenciaTest.php` |
| 5 | `tests/Feature/Competencia/AsistenciaDeEntrenamientosTest.php` |
| 4 | `tests/Feature/Plantel/CategoriasTest.php` |
| 4 | `tests/Feature/Rendimiento/PlantillasYUmbralesTest.php` |
| 3 | `tests/Feature/Academico/AsignaturasTest.php` |
| 3 | `tests/Feature/Academico/InscripcionesTest.php` |
| 3 | `tests/Feature/Academico/PeriodosEscolaresTest.php` |
| 3 | `tests/Feature/Identidad/CerrarSesionYContrasenaTest.php` |
| 3 | `tests/Integration/Competencia/MigracionesDeCompetenciaTest.php` |
| 3 | `tests/Integration/Identidad/MigracionesDeIdentidadTest.php` |
| 3 | `tests/Integration/Rendimiento/MigracionesDeRendimientoTest.php` |
| 3 | `tests/Unit/Academico/CalificacionDeMateriaTest.php` |
| 2 | `tests/Integration/Plantel/MigracionesDePlantelTest.php` |
| 2 | `tests/Unit/Academico/MateriaEvaluadaTest.php` |
| 1 | `tests/Feature/Identidad/IniciarSesionTest.php` |
| 1 | `tests/Integration/Identidad/UsuariosDemoSeederTest.php` |

Los 309 errores salen de pocos puntos de origen. En los 38 archivos hay 68 llamadas a `->first()`; los ayudantes locales con tipo `?stdClass` concentran muchos errores, porque cada columna leída de su resultado cuenta como uno. Basta corregir la llamada o el ayudante, no cada lectura.

## 3. Propuesta de corrección

### 3.1 Opción recomendada (A): un ayudante `NoNulo` para las pruebas

Una clase nueva en `tests/Ayudantes/NoNulo.php` (espacio de nombres `Tests\Ayudantes`, como `MundoDeJugadores` y los demás ayudantes):

```php
/**
 * @template T
 * @param  T|null  $valor
 * @return T
 */
public static function exigir(mixed $valor, string $que): mixed
```

- Si el valor es nulo, la prueba falla con un mensaje claro (`$que`, por ejemplo «la fila de asignaturas recién creada»), en lugar del aviso genérico de PHP. Si no, lo devuelve con el tipo sin nulo, y PHPStan lo entiende por el `@template`.
- Se envuelve **el punto de origen**, no cada lectura:
  - `DB::table('asignaturas')->where('id', $id)->first()` → `NoNulo::exigir(DB::table('asignaturas')->where('id', $id)->first(), 'la asignatura creada')`.
  - Los ayudantes locales `?stdClass` (por ejemplo, `calificacionesResumen()`) pasan a devolver `stdClass` y usan `NoNulo::exigir` dentro. Si alguna prueba comprueba que el ayudante devuelve nulo, ese ayudante se deja como está y se corrige en la llamada.
  - `$resumen->promedio()->comoCadena()` → `NoNulo::exigir($resumen->promedio(), 'el promedio')->comoCadena()`.
  - `getConstructor()` y los elementos buscados con `collect(...)->first(...)`, igual.
- **Comportamiento:** ninguna aserción cambia. Una prueba que hoy pasa sigue pasando, y una que fallara por un nulo seguiría fallando, ahora con un mensaje mejor.
- **Tamaño estimado:** unos 90 puntos de origen en 38 archivos de prueba (68 `->first()`, 18 `NumeroDecimal|null`, 1 `ReflectionMethod|null` y los 6 accesos a arreglos), más la clase nueva y el cambio de `level: 7` a `level: 8` en `phpstan.neon`.

### 3.2 Alternativas descartadas

| Opción | Por qué no |
|---|---|
| (B) `assert($x !== null)` después de cada origen | Funciona (`zend.assertions = 1` en el contenedor y PHPStan entiende el `assert`), pero suma una línea por origen, no da un mensaje propio y depende de una opción de `php.ini`. Es aceptable si el responsable prefiere no agregar un ayudante |
| (C) Cambiar `->first()` por `->sole()` | Cambia el comportamiento de las pruebas: `sole()` falla si hay más de una fila, y algunas consultas (por ejemplo, de `jugador_equipo` o de historiales) pueden devolver varias. No es solo una comprobación de nulos |
| (D) Operador nulo seguro (`?->`) | Esconde el nulo: `expect(null)` fallaría con un mensaje engañoso y cambia la intención de la prueba |
| `baseline`, `ignoreErrors`, `@phpstan-ignore`, bajar el nivel | Prohibido por la especificación (§8, T00) y por las reglas del proyecto |

### 3.3 Orden de trabajo (después de la aprobación)

1. Agregar `tests/Ayudantes/NoNulo.php` con su prueba unitaria (devuelve el valor; falla con el mensaje si es nulo).
2. Corregir por contexto, de mayor a menor (Académico, Plantel, Competencia, Rendimiento, Identidad, Compartido), y volver a correr PHPStan con `--level=8` después de cada contexto para ver la cuenta bajar.
3. Cambiar `level: 7` por `level: 8` en `phpstan.neon` solo cuando la cuenta llegue a 0.
4. `make verificar` y `make mutacion`. Infection corre solo sobre `src/*/Dominio/`, que no cambia, así que se espera el mismo MSI.
5. Reporte `f1-h5-t00.md` con la cuenta antes y después, los tiempos, la lista de archivos tocados y la comprobación de que no cambió ninguna aserción (`git diff` filtrado por `expect(` y `->assert`).
6. Commit `F1-H5-T00: PHPStan nivel 8`.

### 3.4 Errores que podrían exigir un cambio de comportamiento

**Ninguno** según la medición: `src/` y `app/` no tienen errores, y en las pruebas solo hay comprobaciones de nulos sobre valores que la prueba ya da por existentes. Si al corregir aparece un caso en que el nulo es un resultado esperado (una prueba que comprueba que algo **no** existe), no se envuelve con `NoNulo`; se anota en el reporte y se consulta antes de tocarlo.

## 4. Riesgos y tiempo

- **Tiempo de `make verificar`:** PHPStan en nivel 8 tardó 58 s con la caché caliente, frente a 95 s del nivel 7 con la caché fría; no se espera un aumento apreciable. Pest no cambia de cantidad de pruebas salvo la unitaria del ayudante.
- **Rutas:** todo cae dentro de lo que permite T00 (`phpstan.neon` solo para el nivel, `tests/**`, reportes). No se toca `app/`, `src/`, `routes/` ni `bootstrap/`.

## 5. Nota para la auditoría: catálogos sin historial (RNF-02)

Por indicación del responsable: los catálogos `rangos_mediciones_corporales` y `tipos_prueba` (H5, T01; `docs/hitos/f1-h5.md` §5.1) **no tienen tabla de historial**, igual que `metricas` y `metricas_posicion` en H4. Se siembran, no hay ruta de la API que los modifique (§2.3) y la institución los cambia en la base de datos (RNF-09, DD-62). Es una **excepción a RNF-02** («todo alta, cambio o baja lógica registra autor y fecha»): un cambio de rango o de unidad hecho en la base no queda registrado con autor, fecha ni valor anterior. Queda **para revisión de la auditoría independiente de H5**, que decide si basta con la excepción, como en H4, o si estos catálogos necesitan historial antes de que exista un perfil de administración que los edite.

## 6. Lo que se pide al responsable

1. Aprobar la opción A (ayudante `NoNulo`) o elegir la B (`assert` en cada origen).
2. Confirmar que la clase nueva en `tests/Ayudantes/` cabe en las rutas de T00 (`tests/**`).

Los datos crudos de la medición (JSON de nivel 8, resumen agregado y salida de nivel 7) quedaron fuera del repositorio, en el directorio temporal de la sesión.

## 7. Aprobación y condiciones

Aprobado el 1 de noviembre de 2026 con la opción A, con estas condiciones:

1. `tests/Ayudantes/` y `tests/**` entran en las rutas de T00. `phpstan.neon` solo cambia cuando la cuenta llegue a 0.
2. Antes de envolver un origen, se comprueba si alguna prueba espera nulo (DD-43, DD-44, promedio sin calificaciones, sin registros). Si lo espera, no se envuelve: se tipa el valor como `?T` y se prueba el nulo de forma explícita. No se cambia ninguna aserción.
3. Un commit por contexto, con prefijo `F1-H5-T00`, y una nueva medición después de cada uno.
4. `level: 8` en `phpstan.neon` solo cuando la cuenta llegue a 0.
5. Este plan se commitea en el primer commit de T00, con prefijo `F1-DOC`.
6. Al cerrar T00: `make verificar`, `make mutacion` y `make humo`, en serie y sin nada en paralelo. Los tiempos de Pest se anotan en `f1-h5-progreso.md`.
7. Antes de cualquier corrida de `make`, se bajan los contenedores que quedaron levantados o se confirma que su estado es el esperado.
