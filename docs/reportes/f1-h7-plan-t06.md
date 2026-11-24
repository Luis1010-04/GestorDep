# F1-H7-T06: plan formal (AB-17, AB-20, AB-21, AB-26, AB-29 y AB-31)

- **Rama:** `tecnico/f1-h7-t06`, rebasada sobre `main` en `ae680a7` (después de fusionar los PR #35 y #36).
- **Especificación:** `docs/hitos/f1-h7.md` v0.6, §8 (T06). La v0.6 está en `main` desde la fusión del PR #35 (`43007bf`).
- **Aprobación:** plan aprobado por el responsable el 24/11/2026 a las 09:31 (PR #37, punta `96f5508`).
- **Decisiones del responsable:** DA-126 a DA-128 y la orden del 23/11/2026 a las 11:31, punto 4 (S1 a S4).
- **Decisiones del agente:** las de este plan son DA-139 a DA-147; DA-134 a DA-138 son de las actividades 1.1 y 1.2. Todas están en `docs/reportes/f1-h7-decisiones-del-agente.md`.
- **Encargo:** actividad 1.3 de la orden del 23/11/2026 a las 13:52.

Es un plan sin código. Según §8, T06 se detiene aquí (**alto tras el plan**): la corrección empieza solo con orden del responsable.

## 1. Resumen

| AB | ¿Problema real? | Propuesta | Pruebas |
|---|---|---|---|
| AB-17 | Sí (observado) | Bloquear la fila del usuario solo en el camino del fallo y aplicar DA-126 | 2 |
| AB-20 | Sí (observado) | Puerto de candado (§3) en la asignación | 1 |
| AB-21 | Sí (observado). Es de consistencia, no de seguridad | Instantes de `usuarios` desde `Reloj`, truncados a segundos | 3 |
| AB-26 | Sí (observado) en la captura y en la inscripción | Puerto de candado en cinco casos de uso | 5 |
| AB-29 | Sí (observado) | Puerto de candado en cinco casos de uso, incluido `CambiarEstado` (S3) | 5 |
| AB-31 | Sí (observado) | Puerto de candado en cuatro casos de uso (DA-128) | 4 |

La primera mitad de AB-26 (el 409 al inscribir revela que una matrícula ajena está en baja, DD-52) no se corrige. Es un riesgo aceptado en `docs/hitos/f1-h7.md` v0.6, §12.

## 2. Un apartado por AB

### AB-17: carrera en el bloqueo por intentos

- **Registro:** «dos fallos simultáneos pueden contar uno; solución probable: incremento atómico en SQL».
- **Código:** `IniciarSesion` lee al usuario sin candado (`src/Identidad/Aplicacion/IniciarSesion.php:28`, `RepositorioDeUsuariosEloquent.php:35-40`). La cuenta la hace el dominio en memoria (`Usuario.php:55-67`) y `guardarAcceso` escribe un valor absoluto (`RepositorioDeUsuariosEloquent.php:77-83`). Dos fallos que leen el mismo valor escriben el mismo valor.
- **Reproducción:** §6.
- **Propuesta:** §4.1. El incremento atómico en SQL obligaría a repetir en SQL la regla de reinicio y de bloqueo, que vive en el dominio. Se prefiere bloquear la fila y dejar la regla en `Usuario` (DA-142).

### AB-20: asignación y baja simultáneas

- **Registro:** la asignación puede dejar una membresía de un jugador en baja (solución probable: `lockForUpdate` sobre el jugador).
- **Código:** `AsignarJugadorAEquipo.php:39-44` comprueba la baja y crea la membresía en pasos separados, sin bloquear al jugador. El propio docblock lo reconoce (`:17-18`). La baja sí bloquea la fila (`RepositorioDeJugadoresEloquent.php:113-118`).
- **Matiz (DA-127):** la baja no cierra membresías (DD-30), así que una membresía vigente de un jugador en baja es un estado válido. El defecto es **crearla después** de la baja.
- **Propuesta:** el puerto de §3.

### AB-21: desfase de reloj en `usuarios` (PT-04)

- **Código:**
  - `guardarContrasena` escribe `actualizado_en` con `now()` de la base (`RepositorioDeUsuariosEloquent.php:96`), mientras que su fila de historial toma el instante de `Reloj`.
  - `guardarNuevo` no escribe `creado_en` ni `actualizado_en`, así que se aplica `useCurrent()` (`:119-128`).
  - `guardarAcceso` guarda `bloqueado_hasta` con microsegundos (`:81`), cuando el resto del sistema trunca a segundos (`Instante::truncadoASegundos`).
- **Propuesta:** §4.2.

### AB-26: captura académica e inscripción con la baja simultánea

- **Código:** las comprobaciones se hacen fuera de cualquier transacción y la escritura llega después: `InscribirJugador.php:33-36` → `:42`; `RegistrarCalificacion.php:49-52` → `:64`; `CorregirCalificacion.php:37-38`; `RegistrarAsistencia.php:37-38` → `:48`; `CorregirAsistencia.php:33-34`.
- **Propuesta:** el puerto de §3.

### AB-29: Salud y Condición física con la baja simultánea

- **Código:**
  - `CrearAviso.php:29-31` comprueba sin transacción.
  - `ConfirmarAviso.php:51-57`, `CambiarEstado.php:41-45`, `RegistrarMedicion.php:51-54` y `RegistrarPrueba.php:53-56` comprueban dentro de su transacción, pero sin bloquear al jugador.
  - `CambiarEstado` entra por S3, aunque el registro no lo nombre.
- **Propuesta:** el puerto de §3.

### AB-31: Competencia y Rendimiento con la baja simultánea

- **Código:** `ConvocarJugador.php:34` → `:40`; `DefinirAlineacion.php:47` y `:75` → `:58`; `RegistrarAsistencias.php:42-48` → `:56`; `CapturarRendimiento.php:51` y `:111` → `:71-72`.
- **Respuesta:** estos casos de uso responden 404 (fuera del alcance sin bajas), salvo `RegistrarAsistencias`, que responde `JugadorNoPerteneceAlEquipo` con su campo.
- **Propuesta:** el puerto de §3, conservando esas respuestas.

## 3. El puerto de candado (AB-20, AB-26, AB-29 y AB-31)

Un solo puerto para todas las capturas (S3, DA-139):

```php
// src/Compartido/Aplicacion/CandadoDeJugadores.php
interface CandadoDeJugadores
{
    /**
     * Abre una transacción (o un punto de guardado, si ya hay una), toma FOR SHARE sobre los jugadores en orden de id
     * y ejecuta $escritura si todos siguen activos. Si alguno está en baja o no existe, lanza lo que devuelva
     * $siHayInactivos con la lista de esos jugadores.
     *
     * @template T
     * @param  non-empty-list<Identificador>  $jugadores
     * @param  callable(list<Identificador>): \Throwable  $siHayInactivos
     * @param  callable(): T  $escritura
     * @return T
     */
    public function conJugadoresActivos(array $jugadores, callable $siHayInactivos, callable $escritura): mixed;
}
```

- **Implementación:** `src/Plantel/Infraestructura/CandadoDeJugadoresPlantel.php`, enlazada en `PlantelServiceProvider` igual que `LocalizadorDeJugadores` (`PlantelServiceProvider.php:28`). Abre la transacción con la conexión y delega la consulta en un método nuevo, `RepositorioDeJugadores::bloquearActivos(list<Identificador>): list<Identificador>`, que devuelve los inactivos.
- **Consulta** (en `RepositorioDeJugadoresEloquent`): `select id from jugadores where id in (…) and eliminado_en is null and estatus_deportivo <> 'baja' order by id for share`. Los inactivos son los ids pedidos que no vuelven.
  - **Por qué en ese archivo (DA-139):** ya está en la lista cerrada del detector de RNF-01 (`tests/Ayudantes/DeteccionDeConsultasDeJugadores.php:17-21`) y ya bloquea la fila en la baja. Una clase nueva que nombrara `jugadores` obligaría a ampliar esa lista, y eso pide aprobación.
  - **Predicado (DA-140):** el mismo de `AlcanceDeJugadoresSql.php:35-37`. Así la comprobación rápida y la que se hace con el candado no pueden discrepar. La baja cambia los dos estatus a la vez (`RepositorioDeJugadoresEloquent.php:142-147`).
- **Por qué serializa:**
  - `FOR SHARE` choca con el `FOR UPDATE` de la baja (`:117`). Si la baja llega primero, la captura espera. Cuando la baja se confirma, PostgreSQL vuelve a evaluar la fila en READ COMMITTED; la fila ya no cumple el predicado y la captura recibe al jugador como inactivo.
  - Si la captura llega primero, la baja espera a que termine. El resultado es válido por DD-30.
  - Las capturas no se bloquean entre sí: `FOR SHARE` es compatible con `FOR SHARE` y con el `FOR KEY SHARE` de las claves foráneas.
  - Interbloqueos (inferido): la baja solo bloquea la fila del jugador y ninguna captura pide después un candado que la baja tenga. En los lotes se bloquea en orden de id.
- **Uso:** la comprobación rápida que ya existe se queda (mismo código de error y sin candado si ya está en baja). Solo la escritura entra en el puerto. Cada caso de uso conserva su excepción (DA-141):

| Caso de uso | Excepción con el candado | Transacción actual |
|---|---|---|
| `AsignarJugadorAEquipo` | `JugadorEnBaja` (409) | No |
| `InscribirJugador` | `JugadorEnBajaNoInscribible` (409) | No |
| `RegistrarCalificacion`, `CorregirCalificacion`, `RegistrarAsistencia`, `CorregirAsistencia` (Académico) | `JugadorEnBajaSinCaptura` (409) | No |
| `CrearAviso` | `JugadorEnBajaSinAviso` (409) | No |
| `ConfirmarAviso`, `CambiarEstado` | `JugadorEnBajaSinCambioDeEstado` (409) | Sí: el puerto queda como punto de guardado |
| `RegistrarMedicion` | `JugadorEnBajaSinMedicion` (409) | Sí |
| `RegistrarPrueba` | `JugadorEnBajaSinPrueba` (409) | Sí |
| `ConvocarJugador`, `CapturarRendimiento` | `RecursoNoEncontrado` (404) | `CapturarRendimiento`: sí |
| `DefinirAlineacion` | `RecursoNoEncontrado` (404), solo para los jugadores a los que la alineación da un rol (DA-146) | No |
| `RegistrarAsistencias` (entrenamiento) | `JugadorNoPerteneceAlEquipo` con el campo del registro (como hoy) | No |

- **Eventos:** salen con `DB::afterCommit` (`PublicadorDeEventosLaravel.php:15`). Meter la escritura en una transacción no cambia cuándo se disparan.
- **Pruebas de arquitectura:**
  - El puerto vive en `Compartido/Aplicacion`, así que Competencia y Rendimiento no importan Plantel (`CompetenciaSinPlantelTest`, `RendimientoSinPlantelTest`).
  - El puerto no es el `LocalizadorDeJugadores`, cuyos usos están acotados (`LocalizadorDeJugadoresTest`).
- **Sin cambios de contrato:** los códigos y textos de error ya existen. No cambian OpenAPI ni `lang/es`.

## 4. AB-17 y AB-21

### 4.1 AB-17 (DA-126 y DA-142)

- `RepositorioDeUsuarios` gana `modificarAccesoConCandado(Identificador $id, callable $cambio): void`. Abre una transacción, hace `select … for update` de la fila del usuario, la reconstruye, aplica `$cambio(Usuario)` y guarda como hoy `guardarAcceso`.
- En `IniciarSesion` solo cambia el camino del fallo (`:42-46`). La comparación del hash queda fuera del candado. Con el candado tomado:
  - si `estaBloqueado($ahora)`, se lanza `CuentaBloqueada` (429) y el intento no cuenta (DA-126);
  - si no, `registrarIntentoFallido(...)`.
- El acceso correcto (`:49-50`) no cambia. La carrera residual (un acceso correcto simultáneo puede reiniciar un bloqueo recién puesto) es riesgo aceptado (§12 de la v0.6).
- PT-14 ya aceptó que este `UPDATE` cambie el tiempo de respuesta. `IniciarSesionTiempoTest` debe seguir pasando sin cambios.

### 4.2 AB-21 (DA-143)

`RepositorioDeUsuariosEloquent` recibe `Reloj`:
- `guardarContrasena` escribe `actualizado_en` con `Instante::truncadoASegundos($reloj->ahora())` y pasa ese mismo instante al historial (`instante:`), como hace `RepositorioDeJugadoresEloquent.php:100`.
- `guardarNuevo` escribe `creado_en` y `actualizado_en` con ese instante.
- `IniciarSesion` trunca `$ahora` antes de calcular `bloqueado_hasta`, y `guardarAcceso` lo guarda sin microsegundos.

## 5. Ayudante de concurrencia (DA-145)

- **Por qué un proceso aparte:** las pruebas no corren dentro de una transacción. Cada proceso de Paratest usa su base y la vacía con `TRUNCATE` (`tests/TestCase.php:20-38`), así que otro proceso ve los datos de la prueba.
- **Archivos:**
  - `tests/Ayudantes/TransaccionConcurrente.php`: la función de Pest `conTransaccionConcurrente(array $sentencias, callable $accion): array`.
  - `tests/Ayudantes/scripts/sostener-transaccion.php`: el guion que corre aparte.
  - Una línea `require_once` en `tests/Pest.php`.
- **El guion:**
  1. Se lanza con `PHP_BINARY` mediante `Illuminate\Support\Facades\Process` (parte de `laravel/framework`; no es una dependencia nueva). Recibe las credenciales de la conexión `pgsql` de la prueba por variables de entorno, ya con el sufijo de `TEST_TOKEN`.
  2. Hace `BEGIN`, ejecuta las sentencias recibidas e imprime `LISTO`.
  3. Espera a que otra sesión quede bloqueada por él (`pg_blocking_pids`), con un máximo de 3 s.
  4. Hace `COMMIT` e imprime `ESPERA=1` o `ESPERA=0`.
- **La función de la prueba:** espera `LISTO`, ejecuta `$accion` (la petición HTTP real) y devuelve el resultado junto con `hubo_espera`. Cada prueba exige `hubo_espera === true`, para que no pueda pasar por casualidad.
- **Plan B**, si falla la verificación previa de §9: `SET lock_timeout` en la petición. Demuestra que la captura espera el candado de la baja (SQLSTATE 55P03), pero no el 409 final. Si hiciera falta, se pregunta antes.

## 6. Cómo se reproduce cada carrera

| AB | Qué hace el guion (la otra petición) | Hoy (antes de corregir) | Con la corrección |
|---|---|---|---|
| AB-17 (conteo) | `update usuarios set intentos_fallidos = intentos_fallidos + 1 where id = ?`, sin confirmar | El fallo de la prueba lee 0 sin candado; su `UPDATE` espera; el guion confirma 1 y el fallo escribe 1. Queda 1 (se pierde un incremento) | El fallo espera en el `FOR UPDATE`, lee 1 y escribe 2 |
| AB-17 (DA-126) | Deja `intentos_fallidos = 5` y `bloqueado_hasta = ahora + 15 min`, sin confirmar; el usuario parte de 4 | El fallo lee 4, escribe 5 con su propio bloqueo y responde 401 | El fallo espera, ve la cuenta bloqueada, responde 429 y deja 5 |
| AB-20, AB-26, AB-29 y AB-31 | Las sentencias de `darDeBaja`: `select … from jugadores where id = ? … for update` y `update jugadores set estatus_academico = 'baja', estatus_deportivo = 'baja'`, sin confirmar | La captura lee «activo» y escribe. En tablas con clave foránea a `jugadores`, el `INSERT` espera el candado y escribe después; en calificaciones y asistencias ni siquiera espera (inferido). Responde 201 o 200 | La captura espera en el `FOR SHARE`; la fila ya no cumple el predicado y responde como con el jugador en baja (409 o 404), sin escribir |

Antes de corregir, las pruebas en las que la captura no espera tardan hasta 3 s (el límite del guion). Después de corregir no hay espera innecesaria.

## 7. Pruebas por identificador

Todas en `tests/Feature/Seguridad/`, salvo las de AB-21:

| Identificador | Archivo |
|---|---|
| «AB-17: dos intentos fallidos simultáneos cuentan dos» | `AccesoConcurrenteTest.php` |
| «AB-17: un fallo que llega con la cuenta recién bloqueada por otro intento responde 429 y no cuenta» | `AccesoConcurrenteTest.php` |
| «AB-20: una asignación concurrente con la baja responde 409 `jugador_en_baja` y no crea la membresía» | `BajaConcurrenteTest.php` |
| «AB-26: una inscripción concurrente con la baja responde 409 `jugador_en_baja` y no crea la inscripción» | `BajaConcurrenteTest.php` |
| «AB-26: una calificación concurrente con la baja responde 409 y no se guarda» | `BajaConcurrenteTest.php` |
| «AB-26: la corrección de una calificación concurrente con la baja responde 409 y no la cambia» | `BajaConcurrenteTest.php` |
| «AB-26: una asistencia a clase concurrente con la baja responde 409 y no se guarda» | `BajaConcurrenteTest.php` |
| «AB-26: la corrección de una asistencia a clase concurrente con la baja responde 409 y no la cambia» | `BajaConcurrenteTest.php` |
| «AB-29: un aviso concurrente con la baja responde 409 y no se guarda» | `BajaConcurrenteTest.php` |
| «AB-29: la confirmación de un aviso concurrente con la baja responde 409 y no cambia el estado» | `BajaConcurrenteTest.php` |
| «AB-29: un cambio de estado concurrente con la baja responde 409 y no cambia el estado» | `BajaConcurrenteTest.php` |
| «AB-29: una medición concurrente con la baja responde 409 y no se guarda» | `BajaConcurrenteTest.php` |
| «AB-29: una prueba física concurrente con la baja responde 409 y no se guarda» | `BajaConcurrenteTest.php` |
| «AB-31: una convocatoria concurrente con la baja responde 404 y no se guarda» | `BajaConcurrenteTest.php` |
| «AB-31: una alineación concurrente con la baja responde 404 y no cambia» | `BajaConcurrenteTest.php` |
| «AB-31: una asistencia de entrenamiento concurrente con la baja se rechaza y no se guarda» | `BajaConcurrenteTest.php` |
| «AB-31: un rendimiento concurrente con la baja responde 404 y no se guarda» | `BajaConcurrenteTest.php` |
| «AB-21: `usuarios.actualizado_en` sale de `Reloj` al cambiar la contraseña y coincide con su historial» | `tests/Feature/Identidad/RelojDeUsuariosTest.php` |
| «AB-21: `bloqueado_hasta` sale de `Reloj`, truncado a segundos» | `tests/Feature/Identidad/RelojDeUsuariosTest.php` |
| «AB-21: `usuarios:crear` escribe `creado_en` y `actualizado_en` desde `Reloj`» | `tests/Feature/Identidad/RelojDeUsuariosTest.php` |

- Cada prueba de concurrencia exige además `hubo_espera === true`.
- Las pruebas que ya existen del 409 `jugador_en_baja` y del 404 por baja deben seguir pasando sin cambios.
- **Mutaciones manuales** (en copia temporal, con el árbol restaurado y verificado): quitar el `for share` del puerto; cambiar el predicado de `estatus_deportivo`; quitar el `for update` de `modificarAccesoConCandado`; quitar la comprobación de DA-126. Cada mutación debe hacer fallar al menos una prueba de la tabla.

## 8. Archivos y rutas que pide la corrección

- **Nuevos:**
  - `src/Compartido/Aplicacion/CandadoDeJugadores.php`
  - `src/Plantel/Infraestructura/CandadoDeJugadoresPlantel.php`
  - `tests/Ayudantes/TransaccionConcurrente.php`
  - `tests/Ayudantes/scripts/sostener-transaccion.php`
  - las tres pruebas de §7
- **Modificados:**
  - **Plantel:** `src/Plantel/Dominio/RepositorioDeJugadores.php`, `src/Plantel/Infraestructura/RepositorioDeJugadoresEloquent.php`, `src/Plantel/Infraestructura/PlantelServiceProvider.php` y `src/Plantel/Aplicacion/AsignarJugadorAEquipo.php` (sale la «limitación conocida» de `:17-18`).
  - **Académico:** `InscribirJugador`, `RegistrarCalificacion`, `CorregirCalificacion`, `RegistrarAsistencia` y `CorregirAsistencia`, en `src/Academico/Aplicacion/`.
  - **Salud:** `CrearAviso`, `ConfirmarAviso` y `CambiarEstado`, en `src/Salud/Aplicacion/`.
  - **Condición física:** `RegistrarMedicion` y `RegistrarPrueba`, en `src/CondicionFisica/Aplicacion/`.
  - **Competencia:** `ConvocarJugador`, `DefinirAlineacion` y `RegistrarAsistencias`, en `src/Competencia/Aplicacion/`.
  - **Rendimiento:** `src/Rendimiento/Aplicacion/CapturarRendimiento.php`.
  - **Identidad:** `src/Identidad/Aplicacion/IniciarSesion.php`, `src/Identidad/Dominio/RepositorioDeUsuarios.php` y `src/Identidad/Infraestructura/RepositorioDeUsuariosEloquent.php`.
  - `tests/Pest.php`: una línea.
- **Documentos:**
  - `docs/reportes/f1-h7-t06.md`
  - las filas AB-17, AB-20, AB-21, AB-26, AB-29 y AB-31, y PT-04, en `docs/decisiones/registro-decisiones.md`
  - `docs/reportes/f1-h7-decisiones-del-agente.md`
  - los logs en `docs/reportes/logs/f1-h7/`

La trazabilidad va en T09.

## 9. Verificación previa del entorno

Es la comprobación de solo lectura de la actividad 1.3, paso 4. Se hizo el 23/11/2026 con el entorno que dejó T06c (`make levantar` con el arreglo de T06c, que está en `main` desde la fusión del PR #36, `ae680a7`):

| Qué | Resultado | Estado |
|---|---|---|
| `proc_open` en el contenedor `app` | `function_exists('proc_open')` es verdadero y `disable_functions` está vacío | Verificado |
| `pg_blocking_pids` con el rol de la aplicación | `gestordep_app` la invoca sin error sobre la base de pruebas (devuelve `{}` para su propia sesión) | Verificado |
| `pg_stat_activity` y `pg_locks` con ese rol | Muestran las sesiones del propio rol (2 visibles) y las filas de `pg_locks` | Verificado |
| Que `pg_blocking_pids` devuelva al bloqueador durante una espera real entre dos sesiones del rol | Probarlo exige provocar un bloqueo, es decir, escribir | **No verificado.** Es lo primero que se comprueba al empezar la corrección; si falla, se aplica el plan B de §5 |

## 10. Lo que este plan no incluye

- Fuera del inventario de §3, con su motivo:
  - `CambiarPosicionDeJugador` y la actualización del expediente: DD-40 los permite con el jugador en baja.
  - `DarDeAlta`, `DescartarAviso` y `CorregirLesion`: DA-12 de H5 los permite en baja.
  - La corrección de rendimiento y `FijarUmbral`: no excluyen a los jugadores en baja (DA-12 de H4: «leer y corregir los incluye»).
  - `CorregirAsistencia` de Competencia: no comprueba la baja.
  - `ProgramarEntrenamiento` y `ModificarEntrenamiento`: solo listan jugadores en su respuesta.
- No se cambia la respuesta de ningún caso: los 409 y 404 de hoy se conservan.
- No hay rutas, tablas, migraciones, dependencias, comandos ni códigos de error nuevos.
