# Reporte de cierre del hito F1-H0 (Cimientos)

**Fecha del reporte:** 22 de octubre de 2026
**Estado del código que se reporta:** `main` en `7dcecef` (fusión de la solicitud de cambio #1), con la etiqueta `f1-h0` sobre ese commit.
**Ruta del archivo:** `docs/reportes/f1-h0-reporte.md`, una excepción a la regla de rutas aprobada por el responsable.
**Formato por tarea:** el formato fijo de reporte vive en `backend/.ai/guidelines/proyecto.md` §6 (el texto original estaba en el `AGENTS.md` de la raíz y se movió a ese archivo en el commit `ed5fdf4`).

## 0. Cómo leer este reporte (límites de la evidencia)

- Las secciones de **T10, EXTRA y T11** salen del trabajo hecho en la sesión en que se cerraron: tengo los resultados y las desviaciones de primera mano.
- Las secciones de **T01 a T09** están **reconstruidas desde el repositorio**: los archivos de cada commit, los nombres de las pruebas y las diferencias de código. Los reportes originales de esas tareas **no están guardados en el repositorio** (los mensajes de commit no traen cuerpo), así que no puedo afirmar qué se dijo entonces sobre "lo que no se hizo", "fuera de alcance" o "dudas". Donde no hay evidencia, lo escribo como **"No consta"** y no lo invento.
- Lo que sí se puede ver en el repositorio, y se reporta como hallazgo, son los cambios de T03 a T09 en **rutas que la especificación de cada tarea no lista** (sección 1, "Desviaciones"). Que no haya registro de su aprobación no significa que no se aprobaran: lo confirma el responsable.
- Las demostraciones de que algo **falla** a propósito (T04: estilo, tipos y prueba rota; T05: violación de arquitectura) se hicieron en sesiones anteriores y **no dejaron rastro en el repositorio**. No las repetí en este reporte, porque exigen modificar archivos y se pidió no cambiar nada más.
- Los resultados de `make verificar` que se citan son los de **hoy, 22 de octubre de 2026**, sobre `main` en `7dcecef` (sección 7). Para las tareas cerradas antes de la sesión de T10 no consta el resultado que tuvo `make verificar` en su momento.

## 1. Reporte por tarea

Pruebas por tarea en el estado final (89 en total): T03 4, T05 23, T06 22, T07 11, T08 8, T09 20, EXTRA 1. T01, T02, T04, T10 y T11 no agregan pruebas de PHP.

### F1-H0-T01. Estructura del repositorio y documentos (commit `98cac2f`)

- **Archivos tocados:** `.gitattributes`, `.gitignore`, `AGENTS.md`, `CLAUDE.md`, `README.md`, `backend/.gitkeep`, `frontend/.gitkeep`, `scripts/humo/.gitkeep` y todos los documentos de `docs/` (`README.md`, `requisitos.md`, `arquitectura.md`, `alcance-fases.md`, `plan-fase-1.md`, `estrategia-pruebas.md`, `convenciones-api.md`, `criterios-aceptacion-fase-1.md`, `decisiones/registro-decisiones.md`, `hitos/f1-h0.md`, `trazabilidad.md`).
- **Pruebas agregadas:** ninguna.
- **`make verificar`:** no aplica (todavía no había aplicación).
- **Lo que no se hizo:** No consta.
- **Fuera de alcance:** No consta.
- **Desviaciones:** el encabezado de `f1-h0.md` registra que el `CLAUDE.md` con la línea `@AGENTS.md` quedó sustituido después de cerrar la tarea (nota v1.2). Los documentos de `docs/` figuran como prerrequisitos que entrega el responsable, pero T01 dice "no modificar los documentos de `docs/`" y aquí aparecen en el commit como alta inicial.
- **Dudas:** No consta.

### F1-H0-T02. Docker Compose, Makefile y roles de base de datos (commit `25a6194`)

- **Archivos tocados:** `docker-compose.yml`, `Makefile`, `.env.example`, `backend/docker/app/Dockerfile`, `backend/docker/db/init/01-roles-y-bases.sh`.
- **Pruebas agregadas:** ninguna (la aceptación es de entorno). Hoy se comprueba con los comandos de la sección 2, criterio 6.
- **`make verificar`:** no aplica a esta tarea.
- **Lo que no se hizo:** No consta. El `docker-compose.yml` de esta tarea tenía `app` y `db`; `worker` y `scheduler` se agregaron después (EXTRA).
- **Fuera de alcance:** No consta.
- **Desviaciones:** No se ven en el código.
- **Dudas:** No consta.

### F1-H0-T03. Proyecto Laravel 13 y ruta de salud (commit `0756b1e`)

- **Archivos tocados:** el esqueleto de Laravel en `backend/` (`artisan`, `bootstrap/`, `config/`, `public/`, `routes/`, `storage/`, `composer.json`, `composer.lock`, `phpunit.xml`, etc.), `backend/app/Http/Controllers/SaludController.php`, `backend/app/Providers/AppServiceProvider.php`, `backend/database/seeders/DatabaseSeeder.php` y `backend/tests/Feature/SaludTest.php`.
- **Pruebas agregadas (4 en el estado final):**
  - `F1-H0-T03: salud responde 200 con aplicacion y base de datos en ok`
  - `F1-H0-T03: la aplicacion usa utc, idioma es y postgresql`
  - `F1-H0-T03: las pruebas usan la base de datos de pruebas`
  - `F1-H0-T03: la unica ruta registrada es la de salud` (estas dos últimas se agregaron en la corrección de T05, commit `f003f48`).
- **`make verificar`:** no consta el de ese momento.
- **Lo que no se hizo:** No consta. Sanctum no se instala (corresponde a H1).
- **Fuera de alcance:** No consta.
- **Desviaciones (observadas):**
  - Las dependencias `laravel/pail`, `laravel/tinker`, `laravel/pao`, `nunomaduro/collision` y `fakerphp/faker` vienen en el esqueleto de Laravel 13 de ese commit (están en `composer.json` desde `0756b1e`). La tarea dice "paquetes de Laravel no listados: no". Se instalaron junto con el esqueleto y siguen en el proyecto.
  - El commit de T03 no contiene ninguna migración: las migraciones por defecto de Laravel (usuarios, caché y cola) no llegaron al repositorio. Por eso, al activar la cola `database`, hubo que crear sus tablas (DD-25) y cambiar el almacén de caché (DD-26).
- **Dudas:** No consta.

### F1-H0-T04. Herramientas de calidad y comando de verificación (commits `b2a7940`, `c57a3e7`)

- **Archivos tocados:** `Makefile`, `backend/composer.json`, `backend/composer.lock`, `backend/phpstan.neon`, `backend/tests/Pest.php`, `backend/tests/Feature/SaludTest.php`, `scripts/humo/humo-h0.sh`, `.gitattributes` (segundo commit, saltos de línea LF).
- **Qué quedó:** Pest 4.7, Pest Arch, Laravel Pint, Larastan 3.13 en nivel 6, cobertura con pcov (en el `Dockerfile` de T02), `composer verificar` (Pint, PHPStan, Pest con cobertura, `composer audit`) y los objetivos `make verificar`, `pruebas`, `migrar`, `fresh` y `humo`.
- **Pruebas agregadas:** ninguna propia; se ajustó `SaludTest`.
- **`make verificar`:** hoy pasa (sección 7).
- **Lo que no se hizo:** no hay Infection ni umbral de cobertura que bloquee, como pide la tarea.
- **Fuera de alcance:** No consta.
- **Desviaciones:** la demostración de que `make verificar` falla ante un error de estilo, de tipos y una prueba rota **no dejó rastro en el repositorio**. Se hizo la parte equivalente para CI en T10 (una prueba rota hace fallar el flujo).
- **Dudas:** No consta.

### F1-H0-T05. Estructura DDD y pruebas de arquitectura (commits `829c769`, `f003f48`)

- **Archivos tocados:** los diez contextos bajo `backend/src/` (carpetas `Dominio/`, `Aplicacion/`, `Infraestructura/`, con `.gitkeep`) y un proveedor de servicios por contexto, `backend/bootstrap/providers.php`, `backend/composer.json` (autocarga `Src\`), `backend/phpstan.neon`, `backend/phpunit.xml`, `backend/tests/Pest.php`, `backend/tests/Arch/ArquitecturaTest.php`. El segundo commit, de "correcciones de auditoría", toca además `backend/bootstrap/app.php`, `backend/config/filesystems.php`, `backend/tests/Feature/EntornoDePruebasTest.php` y `scripts/humo/humo-h0.sh`.
- **Pruebas agregadas (23):**
  - `F1-H0-T05: existen los contextos con sus capas`
  - `F1-H0-T05: cada contexto tiene su proveedor de servicios registrado`
  - `F1-H0-T05: el dominio no depende del framework`
  - Para cada uno de los diez contextos: `F1-H0-T05: la aplicacion de {Contexto} no depende de su infraestructura` y `F1-H0-T05: {Contexto} no importa el dominio de otro contexto`.
- **`make verificar`:** hoy pasa.
- **Lo que no se hizo:** no se creó la interfaz `AlcanceDeJugadores`, ni clases de negocio ni eventos, como indica la tarea.
- **Fuera de alcance:** No consta.
- **Desviaciones (observadas):** el título del segundo commit habla de "rutas no pedidas": se quitó la ruta de salud por defecto de Laravel (`health: '/up'`) en `bootstrap/app.php`, se puso `serve => false` en `filesystems.php` y se fijó la base de pruebas en `phpunit.xml` (`force="true"`). `bootstrap/app.php` y `filesystems.php` no están entre las rutas de T05. La demostración de que las pruebas **fallan** ante una violación de ejemplo no dejó rastro en el repositorio.
- **Dudas:** No consta.

### F1-H0-T06. Núcleo compartido (commit `c7b307b`)

- **Archivos tocados:** `backend/src/Compartido/Dominio/Identificador.php`, `Reloj.php`, `ReglaDeNegocioViolada.php`; `backend/src/Compartido/Aplicacion/UsuarioActual.php`; `backend/src/Compartido/Infraestructura/RelojDelSistema.php`, `RelojFijo.php`, `CompartidoServiceProvider.php`; y sus pruebas en `backend/tests/Unit/Compartido/` y `backend/tests/Feature/Compartido/`.
- **Pruebas agregadas (22):**
  - `IdentificadorTest` (7): UUID v7 válido, marca de tiempo en milisegundos, identificadores distintos, orden por tiempo, aceptación y normalización desde texto, rechazo de uno inválido, igualdad.
  - `RelojTest` (3): reloj fijable, fecha en UTC aunque se fije en otra zona, reloj del sistema.
  - `ReglaDeNegocioVioladaTest` (3): expone su código, se lanza y se captura, rechaza código que no es `snake_case`.
  - `RelojEnContenedorTest` (2): la aplicación resuelve el reloj del sistema y una prueba puede fijarlo.
  - Las 15 pruebas escritas se ejecutan como 22 en total porque algunas usan conjuntos de datos (casos adicionales).
- **`make verificar`:** hoy pasa; las tres clases de dominio y `UsuarioActual` tienen 100 % de cobertura.
- **Lo que no se hizo:** no hay implementación del usuario actual ni otros objetos de valor, como indica la tarea.
- **Fuera de alcance, desviaciones, dudas:** No consta.

### F1-H0-T07. Macros de migración y extensión `citext` (commit `8db3581`)

- **Archivos tocados:** `backend/src/Compartido/Infraestructura/Esquema/MacrosDeEsquema.php`, `CompartidoServiceProvider.php`, `backend/database/migrations/compartido/2026_10_20_000100_habilitar_extension_citext.php`, `backend/app/Providers/AppServiceProvider.php`, y las pruebas `MacrosDeEsquemaTest` y `MigracionesTest`.
- **Pruebas agregadas (11):**
  - `MacrosDeEsquemaTest` (6): `auditoriaBasica` agrega las seis columnas con sus tipos y crea sus claves foráneas sin cascada; `historial` crea la tabla, las claves foráneas, la clave primaria, la unicidad por versión, el índice por fecha descendente y la restricción de operación.
  - `MigracionesTest` (5): `migrate:fresh` habilita `citext`, la migración se revierte y se reaplica, las subcarpetas por contexto están registradas, el formato de nombre `AAAA_MM_DD_GGGSSS_descripcion`, y el rol de aplicación no puede migrar.
- **`make verificar`:** hoy pasa.
- **Lo que no se hizo:** no se crearon tablas de negocio, como indica la tarea.
- **Fuera de alcance:** No consta.
- **Desviaciones (observadas):** agregó la conexión `propietario` en `backend/config/database.php`, cambió `make migrar` y `make fresh` para usarla (`--database=propietario`), registró la suite `Integration` en `phpunit.xml` y `tests/Pest.php`, y vació el `DatabaseSeeder` (quitó el usuario de ejemplo del esqueleto). Esas rutas no están en la lista de T07, pero el título del commit lo menciona ("rol propietario para migrar") y T02 prevé que el propietario ejecute las migraciones.
- **Dudas:** No consta.

### F1-H0-T08. Registro de historial de solo inserción (commit `53bdd1b`)

- **Archivos tocados:** `backend/src/Compartido/Infraestructura/Historial/EscritorDeHistorial.php`, `backend/src/Compartido/Infraestructura/Esquema/MacrosDeEsquema.php` (disparador reutilizable) y `backend/tests/Integration/Compartido/EscritorDeHistorialTest.php`.
- **Pruebas agregadas (8):** el cambio genera su historial con versión consecutiva; la versión es consecutiva por entidad; si el cambio falla no queda ni el dato ni el historial; si el historial falla tampoco; el disparador rechaza `UPDATE` y `DELETE`; el rol de aplicación no tiene permiso de `UPDATE` ni de `DELETE` sobre el historial.
- **`make verificar`:** hoy pasa; `EscritorDeHistorial` tiene 100 % de cobertura.
- **Lo que no se hizo:** no hay historial de entidades de negocio ni consulta de historial, como indica la tarea.
- **Fuera de alcance, desviaciones, dudas:** No consta.

### F1-H0-T09. Convenciones de la API (commits `987463c`, `79f79d3`)

- **Archivos tocados:** `backend/app/Exceptions/FormatoDeError.php`, `ServicioNoDisponible.php`; `backend/app/Http/Controllers/SaludController.php`; `backend/app/Http/Resources/ColeccionPaginada.php`; `backend/app/Http/Respuestas/Respuesta.php`; `backend/bootstrap/app.php`; `backend/lang/es/errores.php`, `validation.php`; `docs/api/openapi.yaml`; y las pruebas `FormatoDeErrorTest`, `EstructuraDeRespuestaTest`, `ContratoTest`, `TextosEnLangTest`. El segundo commit ajusta `FormatoDeError.php`, `errores.php` y `FormatoDeErrorTest` (código propio para 405 y conservación de cabeceras HTTP).
- **Pruebas agregadas (20):**
  - `FormatoDeErrorTest` (12): 404 de ruta y de modelo, JSON siempre, 405 con `Allow`, código HTTP sin código propio, 401, 403, 409, 422 con detalle por campo, regla de negocio violada, 500 y producción sin trazas.
  - `EstructuraDeRespuestaTest` (3): `datos`, `advertencias` opcional y colección paginada con `meta`.
  - `ContratoTest` (3): rutas documentadas y existentes, respuesta 200 de salud contra el contrato, 503 con el formato de error contra el contrato.
  - `TextosEnLangTest` (2): el detector y la regla de que no hay texto visible fuera de `lang/es`.
- **`make verificar`:** hoy pasa.
- **Lo que no se hizo:** no hay endpoints de negocio.
- **Fuera de alcance:** No consta.
- **Desviaciones (observadas):**
  - Agregó la dependencia `osteel/openapi-httpfoundation-testing ^0.15` (versión instalada 0.15). La especificación exige proponerla y esperar aprobación antes de instalarla; **la aprobación ocurrió en la sesión de esa tarea y no consta en el repositorio.** DD-24 la registra como aprobada por el responsable; lo escribí así en T11 a partir de esa condición de la especificación, sin haber visto la aprobación.
  - Montó `./docs` en el contenedor `app` en `docker-compose.yml` (`:ro`) para que las pruebas lean el contrato, y eliminó el comando `inspire` de ejemplo de `backend/routes/console.php`. `docker-compose.yml` y `routes/console.php` no están entre las rutas de T09.
- **Dudas:** No consta.

### F1-H0-T10. Integración continua (commit `1f56f15`)

- **Archivos tocados:** `.github/workflows/verificar.yml` (nuevo).
- **Qué hace:** en cada `push` y `pull_request`: PostgreSQL 16 como servicio, crea los roles y las bases con el mismo guion que Docker (`01-roles-y-bases.sh`), PHP 8.4 con `pdo_pgsql`, `pgsql`, `zip` y `pcov`, `composer install`, `.env` y `key:generate`, `migrate:fresh`, `migrate:rollback`, `migrate` y `composer verificar`. Las claves son solo de prueba; no hay secretos.
- **Pruebas agregadas:** ninguna de PHP. La aceptación se demostró en GitHub:
  - verde en `f1-h0`: [ejecución 39661768512](https://github.com/Luis1010-04/GestorDep/actions/runs/39661768512);
  - rojo con una prueba rota en la rama temporal `ci-prueba-rota` (falló el paso "Verificar"): [ejecución 39661967498](https://github.com/Luis1010-04/GestorDep/actions/runs/39661967498). La rama se eliminó, local y remota.
- **`make verificar`:** pasó en CI y en local con 88 pruebas en ese momento.
- **Lo que no se hizo:** despliegue, publicación de paquetes y secretos (están en "No hacer").
- **Fuera de alcance (anotado):** `README.md` desactualizado; cobertura en 0 % en clases que sí se prueban; `main` en GitHub solo tenía el commit de T01.
- **Desviaciones:** agregué el paso `migrate:rollback` (y el `migrate` posterior) porque lo pide `docs/estrategia-pruebas.md` §3, aunque la especificación de T10 solo nombra `migrate:fresh`; el responsable lo aprobó. CI crea además una base de desarrollo que nadie usa (`gestordep_desarrollo_ci`), porque el guion de Docker siempre crea dos bases.
- **Dudas:** ninguna abierta.

### F1-H0-EXTRA. Servicios `worker` y `scheduler` (commit `bf86f7a`, tarea aparte aprobada por el responsable)

- **Por qué existe:** la versión 1.2 de la documentación hizo de estos dos servicios un criterio de H0 (`docs/criterios-aceptacion-fase-1.md`, `docs/arquitectura.md` §11, DD-21), pero T02 se cerró antes y ninguna tarea los tenía asignados.
- **Archivos tocados:** `docker-compose.yml` (servicios `worker` con `queue:work --tries=3` y `scheduler` con `schedule:work`, configuración común en un ancla YAML e imagen con nombre fijo `gestordep-app`), `backend/config/queue.php` (tablas `trabajos` y `trabajos_fallidos`), `backend/config/cache.php` (almacén por defecto `file`), dos migraciones nuevas en `backend/database/migrations/compartido/` (`2026_10_21_000200_crear_tabla_trabajos.php`, `2026_10_21_000300_crear_tabla_trabajos_fallidos.php`, generadas con `make:queue-table` y `make:queue-failed-table`), `backend/tests/Integration/Compartido/MigracionesTest.php` y `README.md` (la fila de `make levantar`).
- **Pruebas agregadas (1):** `F1-H0-EXTRA: migrate:fresh crea las tablas de la cola database que usa la configuracion`. Se comprobó que falla sin las migraciones (`Failed asserting that false is true`) y pasa con ellas.
- **`make verificar`:** 89 pruebas pasan; CI verde ([ejecución 39679324005](https://github.com/Luis1010-04/GestorDep/actions/runs/39679324005)).
- **Lo que no se hizo:** no se encoló un trabajo real (habría escrito datos en la base de desarrollo fuera de una prueba); no se programó ninguna tarea en el planificador (`alertas:recalcular` llega en H6).
- **Fuera de alcance:** la imagen de Docker no se pudo reconstruir una vez por falta de acceso a Docker Hub (problema de red del equipo, se resolvió al reintentar).
- **Desviaciones:**
  - Las columnas de las dos tablas de cola están en inglés y su `id` es numérico, no UUID v7, porque así lo exige el driver de cola de Laravel; solo los nombres de tabla están en español (DD-25).
  - Las tablas quedan en el grupo 000, que `docs/arquitectura.md` §8.2 reserva para `citext`.
  - Cambié el almacén de caché a `file` porque `queue:work` consulta la caché para `queue:restart` y fallaba con `relation "cache" does not exist` (la tabla `cache` se descartó en T03). Lo aprobó el responsable como opción A (DD-26).
  - Si se ejecuta `make levantar` antes de `make migrar`, el worker escribe errores en el registro hasta que existen las tablas, y se recupera solo.
- **Dudas:** ninguna abierta.

### F1-H0-T11. Guion de humo y cierre de H0 (commits `cfbf81a`, `da83457`)

- **Archivos tocados:** `docs/trazabilidad.md` (estructura de HU-01 a HU-30, RN-01 a RN-23 y RNF-01 a RNF-11, columna de pruebas vacía), `docs/decisiones/registro-decisiones.md` (DD-24 a DD-27, AB-05 y AB-06 resueltas), `README.md`. `scripts/humo/humo-h0.sh` ya existía desde T04 y no se tocó; `backend/.ai/guidelines/proyecto.md` no se tocó porque los comandos de su §7 coinciden con el `Makefile`.
- **Pruebas agregadas:** ninguna.
- **`make verificar`:** 89 pruebas pasan; CI verde ([ejecución 39682708100](https://github.com/Luis1010-04/GestorDep/actions/runs/39682708100) y [39683266448](https://github.com/Luis1010-04/GestorDep/actions/runs/39683266448)). `make humo`: `HUMO H0 OK: GET /api/v1/salud -> 200`. Además, los pasos del `README.md` se repitieron en un clon limpio del repositorio (otro proyecto de Docker y otro puerto, ya eliminado): `composer install`, `key:generate`, `make levantar`, `make fresh`, `make verificar` (89 pruebas) y `make humo`, con los cuatro servicios corriendo.
- **Lo que no se hizo:** no se llenó ninguna fila de pruebas en la trazabilidad (la tarea lo prohíbe), por lo que RNF-02 y RNF-08, que ya tienen pruebas con nombres `F1-H0-T08: …` y `F1-H0-T09: …`, aparecen sin pruebas en la tabla.
- **Fuera de alcance:** la cobertura en 0 % de clases que sí se prueban (sección 5); `main` solo tenía T01 antes de la fusión; `worker` y `scheduler` no arrancan en una máquina limpia si no se ejecutó antes `composer install` (el `README.md` ya lo indica).
- **Desviaciones:** el `README.md` no está en las rutas de T11; el responsable aprobó corregirlo. Los hitos de RN y RNF en la trazabilidad no los da ningún documento de forma explícita: los asigné con la regla del encabezado de la tabla (primer hito cuyas historias citan la regla, o el hito de su área). RN-13, RN-14, RN-17 y RN-22 son deducción por área, sin cita directa.
- **Dudas que necesitan aprobación:** ver sección 6.

### Commits que no son tareas

- **`ed5fdf4` F1-DOC:** actualización de la documentación a la versión 1.2 (detalle en la sección 3).
- **`4483b89` F1-BOOST:** instalación de Laravel Boost: `.mcp.json`, `backend/AGENTS.md`, `backend/CLAUDE.md`, `backend/boost.json` y la dependencia de desarrollo `laravel/boost ^2.10` (versión instalada 2.10.3).
- **`da83457` F1-H0-DOC:** marca AB-06 como resuelta (sin aplicación móvil).

## 2. Criterios de aceptación de H0 (`docs/hitos/f1-h0.md` §5)

Los comandos se ejecutaron el 22 de octubre de 2026 sobre `main` en `7dcecef`, salvo que se indique otra cosa. La salida completa de `make verificar` está en la sección 7.

| # | Criterio | Evidencia | Resultado |
|---|---|---|---|
| 1 | `make levantar`, `make verificar` y `make humo` funcionan en una máquina limpia siguiendo solo el `README.md` | `make levantar` dejó `app` (healthy), `db` (healthy), `worker` y `scheduler` arriba. `make verificar` terminó con `exit=0`: Larastan `[OK] No errors`, `Tests: 89 passed (377 assertions)`, cobertura `Total: 67.3 %`, `No security vulnerability advisories found`. `make humo`: `HUMO H0 OK: GET /api/v1/salud -> 200`. La simulación de máquina limpia (clon nuevo, otro proyecto de Docker, pasos exactos del `README.md`) se hizo en la sesión de T11 y dio los mismos resultados | Cumplido |
| 2 | La integración continua está en verde | `main` tras la fusión: [ejecución 39684054478](https://github.com/Luis1010-04/GestorDep/actions/runs/39684054478), `conclusion: success`. En la rama del hito: 39661768512, 39679324005, 39682708100 y 39683266448, todas `success`. Falla con una prueba rota: 39661967498, `failure` en el paso "Verificar" | Cumplido |
| 3 | Las pruebas de arquitectura impiden las tres violaciones principales | `pest tests/Arch`: pasan `el dominio no depende del framework`, diez pruebas `la aplicacion de {Contexto} no depende de su infraestructura` y diez `{Contexto} no importa el dominio de otro contexto`, más las de existencia de contextos y proveedores. La **prueba de que fallan ante una violación de ejemplo** (aceptación de T05) se hizo en su sesión y **no consta en el repositorio; no se repitió hoy** | Cumplido; la demostración de falla no es reproducible desde el historial |
| 4 | Existe una ruta de salud documentada en el contrato y probada, incluido el 503 | `ContratoTest`: `cada ruta de /api/v1 esta documentada y cada ruta documentada existe`, `la respuesta 200 de salud cumple el contrato` y `si la base no responde, salud devuelve 503 con el formato de error y cumple el contrato`, las tres pasan. Contrato en `docs/api/openapi.yaml` | Cumplido |
| 5 | Las pruebas de rutas documentadas y de textos fuera de `lang/es` están activas | `ContratoTest` (rutas) y `TextosEnLangTest` (`ningun texto visible esta escrito fuera de lang/es`) pasan dentro de `make verificar` | Cumplido |
| 6 | Los roles de base de datos existen y el historial rechaza `UPDATE` y `DELETE` | `psql`: roles `gestordep_app` y `gestordep_propietario` (ambos con `LOGIN`); ambas bases (`gestordep` y `gestordep_pruebas`) tienen como dueño a `gestordep_propietario`. `EscritorDeHistorialTest`: pasan `el disparador rechaza UPDATE/DELETE en el historial` y `el rol de aplicacion no tiene permiso de UPDATE/DELETE en el historial` | Cumplido |
| 7 | Docker Compose define los servicios `worker` y `scheduler` | `docker compose config --services` lista `db`, `app`, `scheduler` y `worker`; en `docker compose ps`, `worker` y `scheduler` están `Up` y el registro del worker no mostró errores tras cambiar la caché a `file` | Cumplido |
| 8 | Ninguna tabla de negocio fue creada | `pg_tables` del esquema `public`, en la base de desarrollo y en la de pruebas: solo `migrations`, `trabajos` y `trabajos_fallidos`. Las tablas de cola son infraestructura (DD-25), no de negocio | Cumplido |
| 9 | El reporte final incluye "Fuera de alcance" y no hay trabajo no pedido | Cada reporte por tarea tiene su "Fuera de alcance" (sección 1). Hallazgos sobre cambios fuera de las rutas de cada tarea: sección 1 ("Desviaciones") | Cumplido en lo que consta; las desviaciones de T03 a T09 las confirma el responsable |

## 3. Cambios en `docs/` y en `AGENTS.md`

### 3.1 Alta inicial (T01, `98cac2f`)

Se crearon `AGENTS.md` y `CLAUDE.md` en la raíz, `README.md` y todos los documentos de `docs/`. `AGENTS.md` contenía entonces las reglas completas del agente (108 líneas): fuentes de verdad, alcance, flujo por tarea y formato del reporte.

### 3.2 Documentación v1.2 (`ed5fdf4` F1-DOC; motivos en `docs/CAMBIOS-v1.2.md`)

Motivo declarado: corregir inconsistencias que el agente interpretaría mal, eliminar duplicados y reducir el contexto que se carga en cada tarea, para trabajar con Claude Code y Laravel Boost.

| Archivo | Qué cambió |
|---|---|
| `AGENTS.md` (raíz) | De 112 líneas de reglas a un puntero corto para Codex hacia `backend/AGENTS.md` (o `backend/.ai/guidelines/proyecto.md` si Boost no está instalado) |
| `CLAUDE.md` (raíz) | Ahora importa `@backend/CLAUDE.md` y trae los recordatorios (leer solo `docs/hitos/f1-hN.md`, precedencia, una tarea por sesión, no editar los archivos que Boost regenera) |
| `backend/.ai/guidelines/proyecto.md` (nuevo) | Las reglas del agente, ahora fuente de `backend/CLAUDE.md` y `backend/AGENTS.md` vía Boost |
| `docs/CAMBIOS-v1.2.md` (nuevo) | El resumen de cambios de la versión |
| `docs/README.md` | Orden de precedencia entre documentos (§1); regla de una sola fuente por dato; nuevas ubicaciones de las reglas del agente |
| `docs/requisitos.md` | RN-04: redondeo al medio hacia arriba. RN-09: ventana móvil de 30 días y vencimiento por tiempo. HU-17: la corrección se confirma con un motivo |
| `docs/arquitectura.md` | §5 todo bajo `backend/`, Consultas sin Dominio, Eloquent fuera de `app/Models`. §7.1 unicidad parcial y `citext`, decimales exactos. `numeric(4,2)` en plantillas, umbrales y evaluaciones. §7.9 única modificación permitida en `alertas`. §11 cola `database`, eventos después del commit, recálculo diario a las 02:00, reloj inyectable. §14 control de cambios |
| `docs/estrategia-pruebas.md` | Nivel de Larastan por hito, nota de decimales en RN-04, ventana de RN-09 con dos filas nuevas, `alertas:recalcular` programado, flujo de Sanctum en los guiones de humo |
| `docs/convenciones-api.md` | Precedencia vía `README.md`, decimales como cadena, 403 por función de otro rol, §4.1 flujo de confirmación (HU-17) |
| `docs/plan-fase-1.md` | Colas y planificador, reglas del agente en `.ai/guidelines`, especificaciones de hito autocontenidas, una tarea por sesión |
| `docs/alcance-fases.md` | La cola `database` y el planificador están permitidos; §8 remite a `plan-fase-1.md` §2 |
| `docs/criterios-aceptacion-fase-1.md` | H0: servicios `worker` y `scheduler`. H6: recálculo diario y cierre por tiempo |
| `docs/hitos/f1-h0.md` | Versión 1.2: reglas del agente en `backend/.ai/guidelines`, cola y planificador permitidos, nivel de Larastan según `estrategia-pruebas.md`, nota de T01 |
| `docs/decisiones/registro-decisiones.md` | DD-19 a DD-23 |
| `docs/trazabilidad.md` | Sin cambios (según `CAMBIOS-v1.2.md`) |

### 3.3 Laravel Boost (`4483b89` F1-BOOST)

Se agregaron `.mcp.json`, `backend/boost.json`, `backend/CLAUDE.md` y `backend/AGENTS.md` (estos dos últimos los genera Boost y no se editan a mano) y la dependencia de desarrollo `laravel/boost`. Estaba previsto en `docs/CAMBIOS-v1.2.md` §4 como "instalar Laravel Boost como tarea aprobada de H0".

### 3.4 Cambios hechos en las sesiones de T10, EXTRA y T11

| Archivo | Qué cambió y por qué |
|---|---|
| `docs/trazabilidad.md` | Se le dio estructura: tablas de 30 HU, 23 RN y 11 RNF, con hito y columna de pruebas vacía, y una explicación del origen de cada hito (lo pide T11) |
| `docs/decisiones/registro-decisiones.md` | DD-24 a DD-27 (decisiones técnicas de H0), AB-05 y AB-06 marcadas como resueltas, y la línea de fechas del encabezado (lo pide T11; AB-06 la resolvió el responsable) |
| `README.md` | Fila de `make levantar` con los cuatro servicios (EXTRA). En T11: se quitaron las referencias desactualizadas (`backend/` "llega en T03", reglas en `AGENTS.md`, columna "Disponible desde"), se documentó el puerto `5433` y se agregó la sección "Arranque en una máquina limpia" |

No se modificaron `docs/requisitos.md`, `docs/arquitectura.md` ni `backend/.ai/guidelines/proyecto.md` en estas sesiones.

## 4. Decisiones nuevas y librería de contrato

Las decisiones del registro (`docs/decisiones/registro-decisiones.md`):

| ID | Decisión | Estado |
|---|---|---|
| DD-19 | Ventana de RN-09: móvil, de 30 días, termina en la fecha de cálculo, inclusive | Por defecto; **validar con la coordinación deportiva antes de H6** |
| DD-20 | Redondeo del promedio de RN-04: dos decimales, al medio hacia arriba (7.495 pasa a 7.50) | Por defecto; **validar con la coordinación académica antes de H2** |
| DD-21 | Cola `database` con servicio `worker`; `alertas:recalcular` diario a las 02:00 America/Mexico_City con el servicio `scheduler` | Los servicios quedaron en H0; la programación llega en H6 |
| DD-22 | Los decimales viajan como cadena con dos decimales en el JSON | Aplica desde H2 |
| DD-23 | Las reglas del agente viven en `backend/.ai/guidelines/proyecto.md`; Boost genera `backend/CLAUDE.md` y `backend/AGENTS.md` | Aplicada |
| DD-24 | Librería de contrato OpenAPI: `osteel/openapi-httpfoundation-testing` (resuelve AB-05) | Aplicada; la aprobación de T09 no consta en el repositorio (sección 1, T09) |
| DD-25 | Cola `database` con las tablas `trabajos` y `trabajos_fallidos` en el grupo 000; columnas en inglés e `id` numérico por exigencia del driver | Aplicada |
| DD-26 | Almacén de caché por defecto `file`, sin tabla `cache` | Aplicada |
| DD-27 | CI reutiliza el guion de roles de Docker y ejecuta `migrate:fresh`, `migrate:rollback` y `migrate` antes de `composer verificar` | Aplicada |

**Resueltas en H0:** AB-05 (librería de contrato, DD-24) y AB-06 (no habrá aplicación móvil: Sanctum en modo SPA con cookies; resuelta el 21 de octubre de 2026).

**Librería de contrato elegida en T09:** `osteel/openapi-httpfoundation-testing` versión 0.15 (`^0.15` en `composer.json`). Valida las respuestas HTTP reales contra `docs/api/openapi.yaml`. Se usa en `ContratoTest`.

## 5. Cobertura

Medida con pcov dentro de `make verificar`; en H0 se reporta y **no bloquea** (`docs/estrategia-pruebas.md` §4). **Total: 67.3 %.** La configuración mide `app/` y `src/` (`backend/phpunit.xml`).

| Capa | Archivos | Cobertura | Umbral desde H1 |
|---|---|---|---|
| Dominio (`src/*/Dominio`) | `Identificador`, `Reloj`, `ReglaDeNegocioViolada` | 100 % cada uno | 95 % |
| Aplicación (`src/*/Aplicacion`) | `UsuarioActual` | 100 % | 85 % |
| Infraestructura (`src/*/Infraestructura`) | los diez proveedores de servicios, `MacrosDeEsquema`, `EscritorDeHistorial`, `RelojDelSistema`, `RelojFijo` | 100 % cada uno | 70 % |
| HTTP y aplicación Laravel (`app/`) | ver la lista de abajo | 2 de 7 clases en 100 %, 5 en 0 % | 70 % (Infraestructura y HTTP) |

**Archivos con 0 %:**

- `app/Exceptions/FormatoDeError`
- `app/Http/Controllers/SaludController`
- `app/Http/Resources/ColeccionPaginada`
- `app/Http/Respuestas/Respuesta`
- `app/Providers/AppServiceProvider`

**Hallazgo:** esas cinco clases **sí se ejecutan en las pruebas** (por ejemplo, `FormatoDeErrorTest`, `SaludTest` y `EstructuraDeRespuestaTest` pasan y recorren esas clases), así que el 0 % no es falta de pruebas. **No investigué la causa** y no tengo evidencia de cuál es; es plausible que sea un problema de medición (por ejemplo, de cómo pcov registra archivos que el framework carga antes de iniciar la cobertura), pero es una hipótesis sin comprobar. Importa porque, desde H1, la capa HTTP tiene un umbral de 70 % que bloquea: si la cifra real fuera esa, `make verificar` fallaría. Hay que aclararlo antes de H1 (sección 6). Nunca se bajó el umbral para que algo pase.

## 6. Pendientes abiertos y quién los decide

| # | Pendiente | Quién decide | Cuándo |
|---|---|---|---|
| 1 | Revisar los umbrales de cobertura "al cerrar H0" (`f1-h0.md` §6, decisión 2) y aclarar por qué cinco clases ejecutadas por las pruebas muestran 0 %, antes de que el umbral bloquee | Responsable (la investigación técnica la puede hacer el agente en una tarea aprobada) | Antes de H1 |
| 2 | Confirmar los hitos de RN y RNF en `docs/trazabilidad.md`, sobre todo RN-13, RN-14, RN-17 y RN-22 (deducidos por área) | Responsable | Antes de H1 |
| 3 | Confirmar que los cambios de T03 a T09 en rutas no listadas por cada tarea (sección 1, "Desviaciones") estaban aprobados, y que la dependencia `osteel/openapi-httpfoundation-testing` se aprobó en T09 | Responsable | Ahora |
| 4 | Decidir si se repiten las demostraciones de falla de T04 (estilo, tipos, prueba rota) y T05 (violación de arquitectura), que no dejaron rastro en el repositorio | Responsable | Ahora |
| 5 | Aceptación por escrito de la institución de que la Fase 1 es solo API (`CAMBIOS-v1.2.md` §4) | Responsable e institución | Antes de entregar la Fase 1 |
| 6 | Decidir si se agrega un visor del contrato OpenAPI (Swagger UI o Scramble) solo en desarrollo; hoy `alcance-fases.md` §5 no lo permite sin aprobación | Responsable | Cuando se quiera demostrar al cliente |
| 7 | Validar DD-19 (ventana de 30 días) con la coordinación deportiva y DD-20 (redondeo) con la coordinación académica | Institución | Antes de H6 y de H2 |
| 8 | Decisiones abiertas AB-01 a AB-04 y AB-07 a AB-09 del registro (validación de valores `[Propuesta]`, cifra de futbolistas, conservación del historial, respaldos, reglamento de elegibilidad, retícula, frontend) | Institución o responsable, según el registro | Según la columna "Antes de" del registro |
| 9 | Decidir si se aprueba por tarea o por grupos de tareas (`f1-h0.md` §6, decisión 4, "se revisa después de H1") | Responsable | Después de H1 |
| 10 | Redactar `docs/hitos/f1-h1.md` (Identidad y Plantel) | Responsable | Antes de empezar H1 |
| 11 | Borrar la rama `hito/f1-h0` en GitHub si ya no se necesita (la etiqueta `f1-h0` conserva el hito) | Responsable | Cuando quiera |
| 12 | Registrar en `docs/decisiones/` o corregir en la documentación que las tablas de cola quedan en el grupo 000 de `arquitectura.md` §8.2 (hoy solo está en DD-25) | Responsable | Cuando lo apruebe |
| 13 | El servidor MCP de Laravel Boost falló al conectarse varias veces durante las sesiones (`CONNECT_TIMEOUT`, `CONNECTION_CLOSED`); no bloquea el trabajo, que se hizo con las herramientas normales | Responsable | Opcional |

## 7. Resultados completos

### 7.1 `git log --oneline` (rama `main`, 22 de octubre de 2026)

```text
7dcecef Merge pull request #1 from Luis1010-04/f1-h0
da83457 F1-H0-DOC: AB-06 resuelta, sin aplicacion movil
cfbf81a F1-H0-T11: guion de humo, trazabilidad y decisiones de H0
bf86f7a F1-H0-EXTRA: servicios worker y scheduler con cola database
1f56f15 F1-H0-T10: integracion continua con GitHub Actions
4483b89 F1-BOOST: instalación de Laravel Boost
ed5fdf4 F1-DOC: actualización de documentación v1.2
79f79d3 F1-H0-T09: codigo propio para 405 y conservacion de cabeceras HTTP en errores
987463c F1-H0-T09: convenciones de la API (formato de error, respuestas, contrato OpenAPI)
53bdd1b F1-H0-T08: registro de historial de solo insercion
8db3581 F1-H0-T07: macros de migracion, extension citext y rol propietario para migrar
c7b307b F1-H0-T06: nucleo compartido (Identificador, Reloj, UsuarioActual, ReglaDeNegocioViolada)
f003f48 F1-H0-T05: correcciones de auditoria (base de pruebas y rutas no pedidas)
829c769 F1-H0-T05: estructura DDD y pruebas de arquitectura
c57a3e7 F1-H0-T04: .gitattributes para fijar saltos de linea LF
b2a7940 F1-H0-T04: herramientas de calidad y comando de verificacion
0756b1e F1-H0-T03: proyecto Laravel 13 y ruta de salud
25a6194 F1-H0-T02: Docker Compose, Makefile y roles de base de datos
98cac2f F1-H0-T01: estructura del repositorio y documentos
```

Etiqueta: `f1-h0` sobre `7dcecef`. Rama del hito: `hito/f1-h0` en `da83457`.

### 7.2 `make verificar` completo (22 de octubre de 2026, sobre `main` en `7dcecef`)

Salida literal, sin códigos de color; `exit=0` al final.

```text
docker compose exec -T app composer verificar

  ..............................................................

  ──────────────────────────────────────────────────────────────────── Laravel
    PASS   .......................................................... 62 files

Note: Using configuration file /var/www/html/phpstan.neon.

 [OK] No errors


   PASS  Tests\Unit\Compartido\IdentificadorTest
  ✓ F1-H0-T06: un identificador generado es un UUID v7 valido            0.93s
  ✓ F1-H0-T06: el identificador generado lleva la marca de tiempo actua… 0.07s
  ✓ F1-H0-T06: dos identificadores generados son distintos               0.01s
  ✓ F1-H0-T06: identificadores generados en milisegundos distintos se ordenan por tiempo
  ✓ F1-H0-T06: acepta un UUID v7 valido desde texto y lo normaliza a mi… 0.01s
  ✓ F1-H0-T06: rechaza un identificador invalido with dataset "texto cu… 0.03s
  ✓ F1-H0-T06: rechaza un identificador invalido with dataset "vacio"
  ✓ F1-H0-T06: rechaza un identificador invalido with dataset "uuid v4"
  ✓ F1-H0-T06: rechaza un identificador invalido with dataset "variante incorrecta"
  ✓ F1-H0-T06: rechaza un identificador invalido with dataset "sin guiones"
  ✓ F1-H0-T06: dos identificadores con el mismo valor son iguales        0.01s

   PASS  Tests\Unit\Compartido\ReglaDeNegocioVioladaTest
  ✓ F1-H0-T06: la excepcion de regla de negocio expone su codigo         0.45s
  ✓ F1-H0-T06: la excepcion de regla de negocio se puede lanzar y capturar con su codigo
  ✓ F1-H0-T06: rechaza un codigo que no esta en snake_case with dataset "vacio"
  ✓ F1-H0-T06: rechaza un codigo que no esta en snake_case with dataset "con espacios"
  ✓ F1-H0-T06: rechaza un codigo que no esta en snake_case with dataset "con mayusculas"
  ✓ F1-H0-T06: rechaza un codigo que no esta en snake_case with dataset "con tilde"

   PASS  Tests\Unit\Compartido\RelojTest
  ✓ F1-H0-T06: el reloj se puede fijar                                   0.32s
  ✓ F1-H0-T06: el reloj fijo devuelve la fecha en UTC aunque se fije en otra zona
  ✓ F1-H0-T06: el reloj del sistema devuelve la hora actual en UTC       0.02s

   PASS  Tests\Arch\ArquitecturaTest
  ✓ F1-H0-T05: existen los contextos con sus capas                       7.36s
  ✓ F1-H0-T05: cada contexto tiene su proveedor de servicios registrado  0.23s
  ✓ F1-H0-T05: el dominio no depende del framework                      66.46s
  ✓ F1-H0-T05: la aplicacion de Compartido no depende de su infraestruc… 0.64s
  ✓ F1-H0-T05: Compartido no importa el dominio de otro contexto         0.51s
  ✓ F1-H0-T05: la aplicacion de Identidad no depende de su infraestruct… 0.30s
  ✓ F1-H0-T05: Identidad no importa el dominio de otro contexto          0.37s
  ✓ F1-H0-T05: la aplicacion de Plantel no depende de su infraestructur… 0.28s
  ✓ F1-H0-T05: Plantel no importa el dominio de otro contexto            0.35s
  ✓ F1-H0-T05: la aplicacion de Academico no depende de su infraestruct… 0.50s
  ✓ F1-H0-T05: Academico no importa el dominio de otro contexto          0.34s
  ✓ F1-H0-T05: la aplicacion de Competencia no depende de su infraestru… 0.30s
  ✓ F1-H0-T05: Competencia no importa el dominio de otro contexto        0.43s
  ✓ F1-H0-T05: la aplicacion de Rendimiento no depende de su infraestru… 0.23s
  ✓ F1-H0-T05: Rendimiento no importa el dominio de otro contexto        0.42s
  ✓ F1-H0-T05: la aplicacion de CondicionFisica no depende de su infrae… 0.33s
  ✓ F1-H0-T05: CondicionFisica no importa el dominio de otro contexto    0.37s
  ✓ F1-H0-T05: la aplicacion de Salud no depende de su infraestructura   0.36s
  ✓ F1-H0-T05: Salud no importa el dominio de otro contexto              0.36s
  ✓ F1-H0-T05: la aplicacion de Alertas no depende de su infraestructur… 0.24s
  ✓ F1-H0-T05: Alertas no importa el dominio de otro contexto            0.37s
  ✓ F1-H0-T05: la aplicacion de Consultas no depende de su infraestruct… 0.26s
  ✓ F1-H0-T05: Consultas no importa el dominio de otro contexto          0.31s

   PASS  Tests\Arch\TextosEnLangTest
  ✓ F1-H0-T09: el detector reconoce texto visible y deja pasar codigos…  0.34s
  ✓ F1-H0-T09: ningun texto visible esta escrito fuera de lang/es        0.39s

   PASS  Tests\Integration\Compartido\EscritorDeHistorialTest
  ✓ F1-H0-T08: un cambio genera su registro de historial con version co… 1.13s
  ✓ F1-H0-T08: la version es consecutiva por entidad                     0.34s
  ✓ F1-H0-T08: si el cambio falla no queda ni el dato ni el historial    0.35s
  ✓ F1-H0-T08: si el historial falla no queda ni el dato ni el historia… 0.43s
  ✓ F1-H0-T08: el disparador rechaza UPDATE en el historial              0.48s
  ✓ F1-H0-T08: el disparador rechaza DELETE en el historial              0.35s
  ✓ F1-H0-T08: el rol de aplicacion no tiene permiso de UPDATE en el hi… 0.36s
  ✓ F1-H0-T08: el rol de aplicacion no tiene permiso de DELETE en el hi… 0.40s

   PASS  Tests\Integration\Compartido\MacrosDeEsquemaTest
  ✓ F1-H0-T07: auditoriaBasica agrega las seis columnas con sus tipos    0.39s
  ✓ F1-H0-T07: auditoriaBasica crea las claves foraneas a usuarios con…  0.30s
  ✓ F1-H0-T07: historial crea la tabla con las columnas y tipos de la s… 0.44s
  ✓ F1-H0-T07: historial crea las claves foraneas a la entidad y a usua… 0.27s
  ✓ F1-H0-T07: historial crea la clave primaria, la unica por version y… 0.27s
  ✓ F1-H0-T07: historial restringe la operacion a crear, actualizar, el… 0.30s

   PASS  Tests\Integration\Compartido\MigracionesTest
  ✓ F1-H0-T07: migrate:fresh con el rol propietario habilita citext      1.13s
  ✓ F1-H0-T07: la migracion de citext se revierte y se vuelve a aplicar  0.77s
  ✓ F1-H0-EXTRA: migrate:fresh crea las tablas de la cola database que…  0.45s
  ✓ F1-H0-T07: las migraciones de las subcarpetas por contexto estan re… 0.24s
  ✓ F1-H0-T07: las migraciones siguen el formato AAAA_MM_DD_GGGSSS_desc… 0.31s
  ✓ F1-H0-T07: el rol de aplicacion no puede ejecutar migraciones        0.25s

   PASS  Tests\Feature\Api\ContratoTest
  ✓ F1-H0-T09: cada ruta de /api/v1 esta documentada y cada ruta docume… 0.46s
  ✓ F1-H0-T09: la respuesta 200 de salud cumple el contrato              0.95s
  ✓ F1-H0-T09: si la base no responde, salud devuelve 503 con el format… 0.54s

   PASS  Tests\Feature\Api\EstructuraDeRespuestaTest
  ✓ F1-H0-T09: una respuesta lleva sus datos en datos y omite advertenc… 0.22s
  ✓ F1-H0-T09: una respuesta incluye el arreglo de advertencias cuando…  0.22s
  ✓ F1-H0-T09: una coleccion paginada responde con datos y meta          0.23s

   PASS  Tests\Feature\Api\FormatoDeErrorTest
  ✓ F1-H0-T09: 404 en una ruta inexistente de la api con el formato de…  0.25s
  ✓ F1-H0-T09: 404 cuando un modelo no se encuentra                      0.22s
  ✓ F1-H0-T09: la api responde json aunque la peticion no lo pida        0.27s
  ✓ F1-H0-T09: 405 con metodo no permitido y la cabecera Allow           0.33s
  ✓ F1-H0-T09: un codigo http sin codigo propio usa solicitud_no_valida… 0.28s
  ✓ F1-H0-T09: 401 sin sesion con el formato de error                    0.22s
  ✓ F1-H0-T09: 403 sin permiso con el formato de error                   0.22s
  ✓ F1-H0-T09: 409 de conflicto con el formato de error                  0.25s
  ✓ F1-H0-T09: 422 de validacion con el detalle por campo en espanol     0.29s
  ✓ F1-H0-T09: una regla de negocio violada responde 422 con su codigo…  0.23s
  ✓ F1-H0-T09: 500 ante un error inesperado con el formato de error      0.23s
  ✓ F1-H0-T09: en produccion un error no filtra trazas, rutas de archiv… 0.29s

   PASS  Tests\Feature\Compartido\RelojEnContenedorTest
  ✓ F1-H0-T06: la aplicacion resuelve el reloj con el reloj del sistema  0.33s
  ✓ F1-H0-T06: una prueba puede fijar el reloj de la aplicacion          0.20s

   PASS  Tests\Feature\EntornoDePruebasTest
  ✓ F1-H0-T03: las pruebas usan la base de datos de pruebas              0.20s
  ✓ F1-H0-T03: la unica ruta registrada es la de salud                   0.22s

   PASS  Tests\Feature\SaludTest
  ✓ F1-H0-T03: salud responde 200 con aplicacion y base de datos en ok   0.24s
  ✓ F1-H0-T03: la aplicacion usa utc, idioma es y postgresql             0.21s

  Tests:    89 passed (377 assertions)
  Duration: 113.97s

  app/Exceptions/FormatoDeError ......................................... 0.0%
  app/Exceptions/ServicioNoDisponible ................................. 100.0%
  app/Http/Controllers/Controller ..................................... 100.0%
  app/Http/Controllers/SaludController .................................. 0.0%
  app/Http/Resources/ColeccionPaginada .................................. 0.0%
  app/Http/Respuestas/Respuesta ......................................... 0.0%
  app/Providers/AppServiceProvider ...................................... 0.0%
  src/Academico/Infraestructura/AcademicoServiceProvider .............. 100.0%
  src/Alertas/Infraestructura/AlertasServiceProvider .................. 100.0%
  src/Compartido/Aplicacion/UsuarioActual ............................. 100.0%
  src/Compartido/Dominio/Identificador ................................ 100.0%
  src/Compartido/Dominio/ReglaDeNegocioViolada ........................ 100.0%
  src/Compartido/Dominio/Reloj ........................................ 100.0%
  src/Compartido/Infraestructura/CompartidoServiceProvider ............ 100.0%
  src/Compartido/Infraestructura/Esquema/MacrosDeEsquema .............. 100.0%
  src/Compartido/Infraestructura/Historial/EscritorDeHistorial ........ 100.0%
  src/Compartido/Infraestructura/RelojDelSistema ...................... 100.0%
  src/Compartido/Infraestructura/RelojFijo ............................ 100.0%
  src/Competencia/Infraestructura/CompetenciaServiceProvider .......... 100.0%
  src/CondicionFisica/Infraestructura/CondicionFisicaServiceProvider .. 100.0%
  src/Consultas/Infraestructura/ConsultasServiceProvider .............. 100.0%
  src/Identidad/Infraestructura/IdentidadServiceProvider .............. 100.0%
  src/Plantel/Infraestructura/PlantelServiceProvider .................. 100.0%
  src/Rendimiento/Infraestructura/RendimientoServiceProvider .......... 100.0%
  src/Salud/Infraestructura/SaludServiceProvider ...................... 100.0%
  ────────────────────────────────────────────────────────────────────────────
                                                                 Total: 67.3 %

No security vulnerability advisories found.
exit=0
```
