# API pública de `src/Compartido`, formato de respuesta y configuración de sesión (estado al cierre de F1-H0)

**Fecha:** 22 de octubre de 2026
**Estado del código:** `main` en `7dcecef` (etiqueta `f1-h0`).
**Naturaleza del documento:** descripción de lo que existe. No contiene propuestas ni cambios. Las firmas se copiaron de los archivos; los valores de configuración se leyeron del contenedor en ejecución (`php artisan config:show`).

## 1. `src/Compartido`

Namespace raíz: `Src\Compartido`. Todo el código está en `backend/src/Compartido/`.

### 1.1 `Identificador` (Dominio)

`Src\Compartido\Dominio\Identificador` — `final class`. Objeto de valor de un UUID v7 (RFC 9562: 48 bits de marca de tiempo en milisegundos, versión 7, variante RFC 4122, bits aleatorios).

| Miembro | Firma | Comportamiento |
|---|---|---|
| Constructor | `private function __construct(private readonly string $valor)` | No es público; se obtiene con `generar()` o `desdeTexto()` |
| `generar` | `public static function generar(): self` | Crea un UUID v7 con la hora actual del sistema (`microtime`) y 10 bytes de `random_bytes` |
| `desdeTexto` | `public static function desdeTexto(string $texto): self` | Pasa el texto a minúsculas y lo valida con `/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/`. Si no coincide lanza `InvalidArgumentException` con el mensaje `identificador_invalido` |
| `valor` | `public function valor(): string` | El UUID en minúsculas |
| `igualA` | `public function igualA(self $otro): bool` | Compara por valor |

Observación: `generar()` usa `microtime(true)` y no el `Reloj`.

### 1.2 `Reloj` (Dominio) y sus implementaciones

`Src\Compartido\Dominio\Reloj` — interfaz.

| Miembro | Firma |
|---|---|
| `ahora` | `public function ahora(): DateTimeImmutable` — fecha y hora actuales en UTC |

Implementaciones (Infraestructura):

| Clase | Firma pública | Comportamiento |
|---|---|---|
| `Src\Compartido\Infraestructura\RelojDelSistema` (`final`) | `public function ahora(): DateTimeImmutable` | `new DateTimeImmutable('now', new DateTimeZone('UTC'))` |
| `Src\Compartido\Infraestructura\RelojFijo` (`final`) | `public function __construct(DateTimeImmutable $momento)`; `public function ahora(): DateTimeImmutable` | Convierte `$momento` a UTC en el constructor y siempre devuelve ese mismo instante |

Registro en el contenedor (`CompartidoServiceProvider::register`): `$this->app->singleton(Reloj::class, RelojDelSistema::class)`.

### 1.3 `UsuarioActual` (Aplicación)

`Src\Compartido\Aplicacion\UsuarioActual` — interfaz.

| Miembro | Firma |
|---|---|
| `identificador` | `public function identificador(): Identificador` |

No tiene ninguna implementación ni registro en el contenedor. El comentario de la interfaz dice que la implementación llega con la autenticación (H1).

### 1.4 `ReglaDeNegocioViolada` (Dominio)

`Src\Compartido\Dominio\ReglaDeNegocioViolada` — `class` (no `final`), extiende `DomainException`.

| Miembro | Firma | Comportamiento |
|---|---|---|
| Constructor | `public function __construct(private readonly string $codigo)` | Valida el código con `/^[a-z][a-z0-9]*(_[a-z0-9]+)*$/` (`snake_case`). Si no coincide lanza `InvalidArgumentException` con el mensaje `codigo_de_regla_invalido`. Llama a `parent::__construct($codigo)`, así que el mensaje de la excepción es el propio código |
| `codigo` | `public function codigo(): string` | El código estable |

### 1.5 `EscritorDeHistorial` (Infraestructura)

`Src\Compartido\Infraestructura\Historial\EscritorDeHistorial` — `final class`.

```php
public function __construct(
    private readonly ConnectionInterface $conexion,
    private readonly Reloj $reloj,
    private readonly UsuarioActual $usuarioActual,
) {}

/**
 * @param  array<string, mixed>|null  $datosAnteriores  nulo al crear
 * @param  array<string, mixed>|null  $datosNuevos  nulo al eliminar
 * @param  callable(): mixed  $cambio  escribe el cambio en la tabla de la entidad
 */
public function registrar(
    string $tabla,
    string $columnaEntidad,
    Identificador $entidadId,
    string $operacion,
    ?array $datosAnteriores,
    ?array $datosNuevos,
    callable $cambio,
    ?string $motivo = null,
): void
```

Comportamiento de `registrar`, todo dentro de `$conexion->transaction(...)`:

1. Ejecuta `$cambio()`.
2. Calcula `version` como `max(version)` de la entidad en `{tabla}_historial` más 1.
3. Inserta en `{tabla}_historial`: `id` (nuevo `Identificador`), `{columnaEntidad}`, `version`, `operacion`, `datos_anteriores` y `datos_nuevos` (JSON, o `null`), `usuario_id` (de `UsuarioActual`), `ocurrido_en` (de `Reloj`, formato `Y-m-d H:i:s.uP`) y `motivo`.

Si el cambio o la inserción fallan, la transacción se revierte y no queda ninguno de los dos. No devuelve valor. La clase no está registrada en el contenedor; recibe sus dependencias por constructor. No valida `$operacion`: el valor lo restringe la base (ver `historial` abajo).

### 1.6 `MacrosDeEsquema` (Infraestructura)

`Src\Compartido\Infraestructura\Esquema\MacrosDeEsquema` — `final class`. Una sola función pública:

| Miembro | Firma |
|---|---|
| `registrar` | `public static function registrar(): void` |

La llama `CompartidoServiceProvider::boot()`. Registra tres macros:

**Macro `auditoriaBasica` — sobre `Illuminate\Database\Schema\Blueprint`. Sin parámetros.**
`$tabla->auditoriaBasica()` agrega:

| Columna | Tipo | Detalle |
|---|---|---|
| `creado_en` | `timestampTz` | `useCurrent()` |
| `actualizado_en` | `timestampTz` | `useCurrent()` |
| `creado_por` | `uuid` | nullable |
| `actualizado_por` | `uuid` | nullable |
| `eliminado_en` | `timestampTz` | nullable |
| `eliminado_por` | `uuid` | nullable |

Más tres claves foráneas, una por cada columna `*_por`, con nombre `fk_{tabla}_{columna}`, hacia `usuarios.id`, con `restrictOnDelete()`.

**Macro `historial` — sobre `Illuminate\Database\Schema\Builder`. Parámetros: `string $tablaEntidad, string $columnaEntidad`.**
`Schema::historial('jugadores', 'jugador_id')` crea `{tablaEntidad}_historial` con:

| Columna | Tipo | Detalle |
|---|---|---|
| `id` | `uuid` | clave primaria `pk_{tabla}` (se agrega con `alter table`) |
| `{columnaEntidad}` | `uuid` | clave foránea `fk_{tabla}_{columnaEntidad}` hacia `{tablaEntidad}.id`, `restrictOnDelete()` |
| `version` | `integer` | |
| `operacion` | `string(20)` | restricción `ck_{tabla}_operacion`: `crear`, `actualizar`, `eliminar` o `restaurar` |
| `datos_anteriores` | `jsonb` | nullable |
| `datos_nuevos` | `jsonb` | nullable |
| `usuario_id` | `uuid` | nullable; clave foránea `fk_{tabla}_usuario_id` hacia `usuarios.id`, `restrictOnDelete()` |
| `ocurrido_en` | `timestampTz` | |
| `motivo` | `string` | nullable |

Índices: único `uq_{tabla}_{columnaEntidad}_version` sobre `({columnaEntidad}, version)` e índice `ix_{tabla}_{columnaEntidad}_ocurrido_en` sobre `{columnaEntidad}, ocurrido_en desc`. Al final llama a la macro `soloInsercion` sobre la tabla creada.

**Macro `soloInsercion` — sobre `Illuminate\Database\Schema\Builder`. Parámetro: `string $tabla`.**
`Schema::soloInsercion('algo_historial')`:

1. Crea (o reemplaza) la función `rechazar_modificacion_historial()`, que lanza `historial_solo_insercion: {tabla}`.
2. Crea el disparador `tg_{tabla}_solo_insercion`, `before update or delete`, por fila.
3. Revoca `update` y `delete` sobre la tabla al rol `config('database.connections.pgsql.username')` (el rol de aplicación).

Las macros dependen de que exista la tabla `usuarios` (las claves foráneas apuntan a ella). Al cierre de H0 esa tabla no existe en el esquema real: solo las pruebas la crean de forma temporal.

## 2. Estructura de las respuestas HTTP

### 2.1 `Respuesta`

`App\Http\Respuestas\Respuesta` — `final class`.

```php
/**
 * @param  list<array{codigo: string, mensaje: string}>  $advertencias  el mensaje sale de lang/es
 */
public static function conDatos(mixed $datos, array $advertencias = [], int $estado = 200): JsonResponse
```

Devuelve un `JsonResponse` con este cuerpo y el código HTTP `$estado`:

```json
{ "datos": <$datos> }
```

Si `$advertencias` no está vacío, el cuerpo es:

```json
{
  "datos": <$datos>,
  "advertencias": [ { "codigo": "...", "mensaje": "..." } ]
}
```

La clave `advertencias` se omite cuando la lista está vacía. La clase no traduce los mensajes: los recibe ya resueltos.

### 2.2 `ColeccionPaginada`

`App\Http\Resources\ColeccionPaginada` — `class`, extiende `Illuminate\Http\Resources\Json\ResourceCollection`.

- `public static $wrap = 'datos'` — los elementos van bajo `datos`.
- `public function paginationInformation(Request $request, array $paginado, array $predeterminado): array` — devuelve solo `meta`; sustituye los `links` y la `meta` de Laravel.

Cuerpo de una colección paginada:

```json
{
  "datos": [ ... ],
  "meta": {
    "pagina": <current_page>,
    "por_pagina": <per_page>,
    "total": <total>,
    "total_paginas": <last_page>
  }
}
```

No lleva `links` ni `advertencias`.

## 3. Sesión y Sanctum

### 3.1 Sesión (valores en ejecución, `php artisan config:show session` en el contenedor `app`)

| Clave | Valor actual | Origen |
|---|---|---|
| `session.driver` | `database` | Valor por defecto del archivo: `env('SESSION_DRIVER', 'database')`. No hay `SESSION_DRIVER` en `backend/.env`, `backend/.env.example` ni en el entorno del contenedor |
| `session.lifetime` (`SESSION_LIFETIME`) | `120` (minutos) | Valor por defecto: `(int) env('SESSION_LIFETIME', 120)`. No hay `SESSION_LIFETIME` definido en ningún `.env` ni en el contenedor |
| `session.expire_on_close` | `false` | Por defecto |
| `session.encrypt` | `false` | Por defecto |
| `session.connection` | `null` | Por defecto (usa la conexión predeterminada, `pgsql`) |
| `session.table` | `sessions` | Por defecto |
| `session.cookie` | `gestordep-session` | Derivado del nombre de la aplicación |
| `session.path` / `domain` / `secure` | `/` / `null` / `null` | Por defecto |
| `session.http_only` / `same_site` / `partitioned` | `true` / `lax` / `false` | Por defecto |
| `session.serialization` | `json` | Por defecto |

Otras observaciones sobre la sesión:

- En pruebas, `backend/phpunit.xml` fija `SESSION_DRIVER=array`.
- La tabla `sessions` **no existe** en la base de desarrollo (`select to_regclass('public.sessions')` devuelve `NULL`). Las únicas tablas del esquema `public` son `migrations`, `trabajos` y `trabajos_fallidos`. El proyecto no tiene migración de sesiones.
- `config/session.php` lleva la `lifetime` en minutos de **inactividad**, como lo documenta el archivo de Laravel.

### 3.2 Autenticación

| Clave | Valor actual |
|---|---|
| `auth.defaults.guard` | `web` |
| `auth.guards.web` | driver `session`, proveedor `users` |
| `auth.providers.users` | driver `eloquent`, modelo `App\Models\User` |
| `auth.passwords.users` | tabla `password_reset_tokens`, `expire` 60, `throttle` 60 |
| `auth.password_timeout` | `10800` |

La clase `App\Models\User` no existe: no hay carpeta `backend/app/Models/`. La tabla `password_reset_tokens` tampoco existe en el esquema.

### 3.3 Sanctum

**No está instalado ni configurado.**

- No existe `backend/config/sanctum.php`.
- `laravel/sanctum` no aparece en `backend/composer.json` ni en `backend/composer.lock`.
- No hay ninguna referencia a Sanctum en `app/`, `bootstrap/`, `routes/`, `config/` ni `src/`.
- No hay variables `SANCTUM_*` (por ejemplo `SANCTUM_STATEFUL_DOMAINS`) en ningún `.env` ni en el contenedor.

La especificación de H0 (T03) dice que Sanctum se instala en H1. La decisión del modo (SPA con cookies, sin aplicación móvil) está registrada en AB-06 de `docs/decisiones/registro-decisiones.md`.
