<laravel-boost-guidelines>
=== .ai/proyecto rules ===

# Reglas del proyecto (prevalecen sobre cualquier otra guía)

Sistema de seguimiento académico y deportivo de futbolistas. Fase 1: **solo API de backend** en `backend/`. Stack: Laravel 13, PHP 8.4, PostgreSQL 16 y Docker Compose; monolito modular con DDD. Este archivo es la fuente de las reglas del agente; Laravel Boost lo copia a `CLAUDE.md` y `AGENTS.md`. Para cambiar una regla se edita este archivo y se ejecuta `boost:update`; **nunca se editan `CLAUDE.md` ni `AGENTS.md` a mano**.

## 1. Prioridad frente a las guías de Laravel y de Boost

Este proyecto usa **DDD con dominio puro**, no el Laravel convencional. Si una guía de Laravel, de Boost o de un paquete contradice lo siguiente, gana este archivo y `docs/arquitectura.md`:

- La lógica de negocio vive en `src/{Contexto}/Dominio/`, en PHP puro: sin Eloquent, facades, helpers, `Carbon` ni nada del framework.
- Contextos: Compartido, Identidad, Plantel, Academico, Competencia, Rendimiento, CondicionFisica, Salud, Alertas y Consultas. Cada uno tiene `Dominio/`, `Aplicacion/` e `Infraestructura/`, salvo Consultas, que no tiene `Dominio/`.
- Dependencias: `Infraestructura` y `Http` dependen de `Aplicacion`; `Aplicacion` de `Dominio`; `Dominio` no depende del framework.
- Los contextos solo se referencian por identificadores y se comunican por eventos de dominio. Los eventos viven en `src/Compartido/Dominio/Eventos/` y llevan solo identificadores y valores primitivos.
- Los modelos Eloquent viven en `src/{Contexto}/Infraestructura/` y nunca son entidades de dominio. No se crean modelos en `app/Models/`.
- Los controladores solo traducen HTTP a casos de uso de `Aplicacion/` y devuelven un recurso; no contienen reglas.
- Toda consulta de jugadores pasa por `AlcanceDeJugadores`.
- Sin `Model::delete()` ni `forceDelete()` sobre tablas de negocio: solo borrado lógico con historial.
- Cada cambio de datos escribe su historial (`{tabla}_historial`, solo inserción) en la misma transacción.
- Las reglas de cálculo leen sus parámetros de tablas, nunca de constantes del código (RNF-09).
- Identificadores UUID v7.
- Nombres de dominio, tablas, columnas, rutas y campos JSON en español, `snake_case` donde aplique, sin tildes ni ñ. Los mensajes al usuario también van en español.
- Decimales con el objeto de valor decimal de Compartido, nunca `float`.
- Migraciones en `database/migrations/{contexto}/` con el formato de nombre de `docs/arquitectura.md` §8.1. Una migración ya integrada en la rama principal no se edita: se crea una nueva.

## 2. Documentos y precedencia

Lee primero la especificación del hito en curso (`docs/hitos/f1-hN.md`); es autocontenida. Consulta los demás documentos solo cuando haya una duda, y lee solo la sección que necesitas. El orden de precedencia está en `docs/README.md` §1. **Ante cualquier contradicción o ambigüedad, detente y pregunta.**

No modifiques `docs/requisitos.md`, `docs/arquitectura.md` ni este archivo salvo que la tarea lo pida. No cambies requisitos, arquitectura ni contrato para que el código quede más cómodo: propón el cambio y espera.

## 3. Alcance

Lo que no está en la tarea actual ni aparece como "Fase 1" en `docs/alcance-fases.md` no se construye, aunque parezca útil, fácil, "de paso" o esté en el PDF original o en el backlog.

- Haz exactamente lo que pide la tarea, ni menos ni más.
- Modifica solo las rutas que la tarea permite; cualquier otra requiere aprobación. Nunca toques `frontend/`.
- No agregues endpoints, rutas, campos, columnas, tablas, migraciones, comandos de consola, eventos, servicios, enumeraciones, valores ni dependencias que la tarea y los documentos no nombren.
- No anticipes la Fase 2: reportes, exportación, notificaciones, perfiles de administrador, coordinador, directivo, futbolista o preparador físico, árbitros, incidencias, alertas distintas de las tres del MVP, subida de archivos, métricas o pruebas físicas fuera de las sembradas, análisis comparativo.
- No refactorices, renombres ni "mejores" código que la tarea no toca.
- No agregues funcionalidad "por si acaso", opciones de configuración nuevas, cachés, colas externas ni optimizaciones. La cola `database` y el planificador de Laravel sí están permitidos (`docs/alcance-fases.md` §5).
- No dejes funcionalidad a medias detrás de banderas ni código comentado.
- Si notas algo útil fuera de alcance, no lo hagas: anótalo en el reporte, en "Fuera de alcance".

## 4. Cómo se trabaja una tarea

1. Lee la tarea completa en la especificación del hito, las rutas que puede tocar y los criterios de aceptación de las historias que cita.
2. Propón un plan breve (archivos que vas a crear o modificar y pruebas que vas a escribir) y espera aprobación.
3. Escribe primero las pruebas (una por criterio, con el identificador en el nombre: `HU-07: ...`, `RN-04: ...`) y comprueba que fallan por la razón correcta.
4. Implementa lo mínimo para que pasen.
5. Actualiza `docs/api/openapi.yaml` y `docs/trazabilidad.md` si la tarea lo exige.
6. Ejecuta `make verificar` hasta que pase, y `make humo` si el hito lo exige. **Nunca debilites una prueba, una regla de análisis, un umbral de cobertura ni una fila de una tabla de casos para que algo pase.**
7. Haz un commit con el formato `F1-HN-TNN: descripción breve` (por ejemplo, `F1-H0-T03: ruta de salud`). No uses `--no-verify` ni fuerces pushes.
8. Entrega el reporte (sección 6) y **detente a esperar aprobación**. No empieces la siguiente tarea.

## 5. Cuándo detenerse y preguntar

- Un criterio de aceptación o una fila de una tabla de casos parece incorrecta.
- Una prueba exigiría algo que el requisito no dice.
- Dos documentos se contradicen, o falta un dato, un valor o una regla.
- La tarea exige tocar una ruta que no tiene permitida.
- La tarea parece requerir una dependencia nueva, una tabla o un endpoint no previstos.
- Un valor marcado [Propuesta] o [Decisión por defecto] no está validado: úsalo como dato de fábrica con un comentario que lo indique y menciónalo en el reporte.

Pregunta con: qué encontraste, dónde (archivo y sección), qué opciones ves y cuál recomiendas. No continúes hasta recibir respuesta.

## 6. Reporte de cada tarea

- Archivos tocados.
- Pruebas agregadas (por identificador).
- Resultado de `make verificar` y, si el hito lo exige, de `make humo`.
- **Lo que no se hizo** y por qué.
- **Fuera de alcance:** lo que se notó y no se hizo.
- **Desviaciones:** cualquier diferencia con la especificación (idealmente ninguna).
- Dudas y decisiones que necesitan aprobación.

## 7. Comandos

Todo se ejecuta con `make` desde la raíz del repositorio, dentro de Docker. No ejecutes `php`, `composer` ni `artisan` fuera del contenedor.

- `make levantar` / `make bajar`: iniciar y detener el entorno.
- `make consola`: terminal dentro del contenedor de la aplicación.
- `make migrar` / `make fresh`: migrar / reconstruir la base con datos de fábrica.
- `make pruebas`: solo las pruebas.
- `make humo`: guiones de humo contra la aplicación levantada.
- `make verificar`: estilo, análisis estático, pruebas y auditoría de dependencias. Es el árbitro de qué está terminado.

## 8. Herramientas de Boost

Usa la búsqueda de documentación de Boost antes de escribir código de Laravel 13, Sanctum o Pest que no conozcas con certeza, y las herramientas de esquema, errores y registros para depurar. Nunca uses Tinker ni consultas de base de datos para modificar datos: los cambios se hacen por migraciones, seeders o casos de uso.

## 9. API

- Prefijo `/api/v1`, solo JSON. Errores, advertencias, códigos HTTP y paginación: `docs/convenciones-api.md`.
- Un recurso fuera del alcance del usuario responde 404.
- Toda ruta nueva se documenta en `docs/api/openapi.yaml` en la misma tarea; no existen rutas sin documentar.
- Ningún texto visible al usuario se escribe en el código: va en `lang/es`.

## 10. Pruebas

- Estrategia, umbrales y tablas de casos: `docs/estrategia-pruebas.md`.
- PostgreSQL real; nunca SQLite.
- Cada criterio de aceptación tiene al menos una prueba, y cada fila de una tabla de casos es una prueba.
- Los comandos de consola del proyecto se prueban con `$this->artisan(...)`.
- Cada hito termina con un guion de humo en `scripts/humo/`.

## 11. Datos y seguridad

- No inventes reglas de negocio ni valores.
- No guardes secretos en el repositorio. Las credenciales de los seeders de desarrollo son solo de prueba.
- No escribas datos reales de personas en pruebas ni seeders.
- No llames a servicios externos.

=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>
