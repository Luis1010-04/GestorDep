# Reporte de cierre del hito F1-H1 (Identidad y Plantel)

**Fecha del reporte:** 26 de octubre de 2026
**Rama:** `hito/f1-h1` (desde `main` en la etiqueta `f1-h0`). Código reportado: commits T01 a T16, los commits `F1-H1-FIX` y `F1-H1-T17` (este último contiene este reporte, así que su hash no puede figurar aquí: va en la entrega de la tarea).
**Especificación:** `docs/hitos/f1-h1.md` v1.13. **Etiqueta `f1-h1`:** no creada; la crea el responsable (compuerta, punto 10).

## 0. Cómo leer este reporte (límites de la evidencia)

- De H1 solo existen los reportes originales de T01 (`f1-h1-t01.md`) y T02 (`f1-h1-t02.md`). **Todas las demás secciones por tarea (T03 a T16 y los commits `F1-H1-FIX`) están reconstruidas desde git** (mensajes, archivos y nombres de pruebas de cada commit) **sin el reporte original**. Donde ni git ni las notas del responsable dicen algo, se escribe "no consta".
- **Notas del responsable:** apuntes que el responsable entregó con la tarea T17 y que se usan como fuente. Se atribuyen así: *(nota del responsable)*.
- Los resultados de `make verificar` por tarea solo constan para T01 (89 pruebas), T02 (95 pruebas) y T16 (439 pruebas, último reporte según el responsable). El resultado del cierre del hito es el de la sección 3.
- Las demostraciones de que una prueba falla a propósito (T08, T09 y T05 de H0) no dejan rastro en el repositorio; solo constan donde el reporte original existe (T02).
- El conteo de pruebas por tarea es de **definiciones** `test(...)`/`it(...)` agregadas por el commit (un `->with([...])` genera varias ejecuciones de una definición). El total de la suite es el que imprime `make verificar`.

## 1. Reporte por tarea

### F1-H1-T01. Corregir la medición de cobertura (commit `8581059`)

- **Reporte original:** `docs/reportes/f1-h1-t01.md`.
- **Archivos tocados:** `backend/composer.json` (script `verificar`: `-d pcov.directory=.`), `docs/reportes/f1-h1-t01.md`.
- **Pruebas:** ninguna (la tarea no las pide).
- **`make verificar`:** pasa, 89 pruebas, 377 aserciones, 100 % en todas las capas.
- **No se hizo:** confirmar en CI que `app/` aparece medido (el reporte lo dejó pendiente tras el push).
- **Fuera de alcance:** el reporte anota que antes del cambio `app/` sí aparecía, con cifras parciales, y no ausente como decía el contexto de la tarea.
- **Desviaciones:** ninguna de rutas.

### F1-H1-T02. Umbral de cobertura por capa (commit `c9d7182`)

- **Reporte original:** `docs/reportes/f1-h1-t02.md`.
- **Archivos tocados:** `backend/bin/verificar-cobertura.php` (nuevo), `backend/composer.json`, `backend/tests/Unit/Herramientas/VerificarCoberturaTest.php`, `docs/estrategia-pruebas.md` (§4), `docs/reportes/f1-h1-t02.md`.
- **Pruebas (6, `HERRAMIENTAS cobertura: ...`):** falla en 94.9 % de Dominio; pasa en 95.0 %; falla bajo 85 % de Aplicación; falla bajo 70 % de Infraestructura y HTTP; una capa sin archivos no falla; falla si el reporte no existe. Se escribieron primero y fallaron porque el guion no existía.
- **`make verificar`:** pasa, 95 pruebas, 391 aserciones. El experimento descartable (clase sin pruebas en `Dominio/`) hizo fallar `make verificar` con Dominio en 85.7 %, y pasó al revertirlo (salidas en el reporte original).
- **No se hizo:** nada que conste.
- **Fuera de alcance:** `bin/` no está en las rutas de PHPStan ni de la cobertura; el guion se valida solo con sus pruebas. Aplicación aparecía "sin código".
- **Desviaciones:** ninguna.

### F1-H1-T03. Migraciones de Identidad y de sesión (commit `3442865`) — *reconstruido desde git, sin el reporte original*

- **Archivos tocados:** `backend/.env.example`; migraciones `compartido/2026_10_21_000400_crear_tabla_sessions`, `identidad/…010100_usuarios`, `…010200_roles`, `…010300_usuario_rol`, `…090100_usuarios_historial`; `database/seeders/RolesSeeder.php` (nuevo) y `DatabaseSeeder.php`; `tests/Integration/Compartido/EscritorDeHistorialTest.php`, `tests/Integration/Identidad/MigracionesDeIdentidadTest.php` (nuevo); **`docs/trazabilidad.md`**.
- **Pruebas (11 en `MigracionesDeIdentidadTest`):** RNF-03 ×7, RNF-02 ×2, RNF-10 ×2 (incluye `RNF-03: el índice parcial de correo permite reutilizarlo tras el borrado lógico`, `RNF-03: roles.codigo solo admite docente o entrenador`, `RNF-02: usuarios_historial es de solo inserción`); más el ajuste de `EscritorDeHistorialTest` para que siga pasando con `usuarios` real.
- **`make verificar`:** no consta.
- **No se hizo:** no consta.
- **Fuera de alcance:** no consta.
- **Desviaciones:**
  - *(nota del responsable)* el campo `usuario_id` del historial chocaba con la columna de autor; se resolvió con DD-34 (`usuario_afectado_id`).
  - Editó `docs/trazabilidad.md`, que la especificación decía no tocar hasta T17. *(nota del responsable)*: por una contradicción de la especificación, que ahora lo reserva a T17.

### F1-H1-T04. Usuario, autenticación con Sanctum y `usuarios:crear` (commit `509a123`) — *reconstruido desde git, sin el reporte original*

- **Archivos tocados:** `composer.json` y `composer.lock` (`laravel/sanctum`), `bootstrap/app.php`, `config/auth.php`, `config/sanctum.php` (nuevo), **`config/identidad.php`** (nuevo), `.env.example`, `lang/es/errores.php` y `lang/es/identidad.php`; `src/Compartido/Aplicacion/UsuarioActual.php`, **`src/Compartido/Infraestructura/Historial/EscritorDeHistorial.php`**; `src/Identidad/` (`Dominio/Usuario`, `PoliticaDeContrasena`, `RepositorioDeUsuarios`; `Aplicacion/CrearUsuario`; `Infraestructura/Consola/CrearUsuarioComando`, `Modelos/UsuarioModelo`, `RepositorioDeUsuariosEloquent`, `UsuarioActualDeSesion`, `IdentidadServiceProvider`); pruebas.
- **Pruebas:** `CrearUsuarioComandoTest` 9 (`CONSOLA usuarios:crear: ...`), `UsuarioTest` 7 (RN-19 ×1, RNF-10 ×6, entre ellas la tabla de bloqueo), `PoliticaDeContrasenaTest` 4 (RNF-10), `UsuarioActualDeSesionTest` 6 (HU-01 ×4, RNF-03, RNF-10); una de `EscritorDeHistorialTest` (`F1-H1-T04`, historial sin usuario actual).
- **`make verificar`:** no consta.
- **No se hizo:** no consta.
- **Fuera de alcance:** no consta.
- **Desviaciones:** *(nota del responsable)* instaló Sanctum sin las migraciones de tokens (consecuente con DD-28 y con §2.3). Rutas fuera de la lista: ver sección 5.

### F1-H1-T05. Iniciar sesión y bloqueo por intentos (commit `e682f7e`) — *reconstruido desde git, sin el reporte original*

- **Archivos tocados:** `app/Http/Controllers/SesionController.php`, `Requests/SolicitudDeInicioDeSesion.php`, `Resources/UsuarioRecurso.php`, `routes/api.php`, **`app/Exceptions/FormatoDeError.php`**, `lang/es/errores.php`; `src/Identidad/Aplicacion/` (`IniciarSesion`, `CredencialesInvalidas`, `CuentaBloqueada`), **`Dominio/RepositorioDeUsuarios.php`**, **`Infraestructura/IdentidadServiceProvider.php`**, **`Infraestructura/RepositorioDeUsuariosEloquent.php`**; `docs/api/openapi.yaml`; `tests/Feature/Api/ContratoTest.php`, `tests/Feature/EntornoDePruebasTest.php` (la prueba heredada de H0 sobre "la única ruta es la de salud"), `tests/Feature/Identidad/IniciarSesionTest.php`.
- **Pruebas (15 definiciones en `IniciarSesionTest`):** HU-01 ×9, RNF-10 ×6, que cubren las 14 filas de §7.1.
- **`make verificar`:** no consta.
- **No se hizo:** no consta (`throttle`, usuario actual y "recordarme" están prohibidos y no hay rastro de ellos).
- **Fuera de alcance / dudas:** *(nota del responsable)* sus pendientes de seguridad están en `docs/hitos/f1-h1.md` §11 (carrera en el bloqueo, tiempo de respuesta, petición sin `Origin`/`Referer`).
- **Desviaciones:** rutas fuera de la lista (sección 5).

### F1-H1-T06. Cerrar sesión y cambiar contraseña (commit `5feec23`) — *reconstruido desde git, sin el reporte original*

- **Archivos tocados:** `app/Http/Controllers/ContrasenaController.php`, `SesionController.php`, `Requests/SolicitudDeCambioDeContrasena.php`, `routes/api.php`, `app/Exceptions/FormatoDeError.php`, `lang/es/errores.php`; `src/Identidad/Aplicacion/` (`CambiarContrasena`, `CierreDeSesiones`, `ContrasenaNuevaMuyCorta`), `Dominio/Usuario.php` y `RepositorioDeUsuarios.php`, `Infraestructura/CierreDeSesionesEnBaseDeDatos.php`, `RepositorioDeUsuariosEloquent.php`, `IdentidadServiceProvider.php`; `docs/api/openapi.yaml`; `tests/Feature/Identidad/CerrarSesionYContrasenaTest.php`.
- **Pruebas (10 definiciones):** HU-27 ×8, RNF-10 ×2 (cierre de sesión invalida la cookie; las 4 filas de §7.2).
- **`make verificar`:** no consta.
- **No se hizo:** no consta.
- **Fuera de alcance:** *(nota del responsable)* pendientes de seguridad en §11.
- **Desviaciones:** *(nota del responsable)* usó `src/.../Dominio` y `app/Exceptions`, que la lista de rutas original de T06 omitía (omisión de la especificación; la lista ya está corregida en `4891a1c`). Con la lista vigente no hay ruta fuera de lo permitido.

### F1-H1-T07. Migraciones de Plantel (commit `55f8b15`) — *reconstruido desde git, sin el reporte original*

- **Archivos tocados:** siete migraciones `plantel/…0201` a `…0207` (`categorias`, `posiciones`, `jugadores`, `equipos`, `jugador_equipo`, `entrenador_equipo`, `bajas_jugador`), cinco `plantel/…0902` a `…0906` (historiales), `database/seeders/PosicionesSeeder.php` y `DatabaseSeeder.php`, `tests/Integration/Plantel/MigracionesDePlantelTest.php`.
- **Pruebas (16 definiciones en el commit; 20 hoy tras T12):** RNF-03 ×11, RNF-02 ×2, RN-17, RN-22 y RN-23 (incluye `RNF-03: dos categorías con el mismo nombre en otras mayúsculas chocan` y `RNF-03: el índice de equipo permite reutilizar el nombre tras el borrado lógico`).
- **`make verificar`:** no consta.
- **No se hizo:** no consta.
- **Fuera de alcance:** no consta.
- **Desviaciones:** *(nota del responsable)* el agente resolvió una colisión de ayudantes de prueba. Ninguna ruta fuera de la lista.

### F1-H1-T08. Pruebas que protegen el alcance: borrado e historial (commit `b4f40fd`) — *reconstruido desde git, sin el reporte original*

- **Archivos tocados:** `tests/Arch/BorradoLogicoTest.php`, `tests/Integration/Compartido/HistorialDeTablasTest.php`.
- **Pruebas (6):** `F1-H1-T08: ...` ×6 (3 de borrado: lista de tablas del framework, detector contra violación de ejemplo, ningún código de `src`/`app` borra de forma física; 3 de historial: lectura de las tablas `(H)` de la arquitectura, comparación solo para las tablas existentes, cada tabla `(H)` existente tiene historial).
- **`make verificar`:** no consta.
- **No se hizo:** la demostración contra una violación de ejemplo no deja rastro en git (no se commitea por especificación); no consta su salida.
- **Fuera de alcance / Desviaciones:** no constan; ninguna ruta fuera de la lista.

### F1-H1-T09. `AlcanceDeJugadores` y control de rol (commit `305b639`) — *reconstruido desde git, sin el reporte original*

- **Archivos tocados:** `app/Http/Middleware/ExigirRol.php`, `bootstrap/app.php`; `src/Compartido/Aplicacion/AlcanceDeJugadores.php` (nuevo) y `UsuarioActual.php` (con `roles()`); `src/Consultas/Infraestructura/AlcanceDeJugadoresSql.php` y `ConsultasServiceProvider.php`; `tests/Arch/AlcanceDeJugadoresTest.php`, `tests/Feature/Identidad/ControlDeRolTest.php`, `tests/Integration/Consultas/AlcanceDeJugadoresTest.php`, `tests/Integration/Compartido/EscritorDeHistorialTest.php`; **`tests/Ayudantes/SesionesDePrueba.php`** y **`tests/Pest.php`**.
- **Pruebas (28):** `AlcanceDeJugadoresTest` de Integration 17 (RNF-01 ×11, HU-02 ×2, RN-17 ×2, RN-23, RNF-02), de Arch 5 (RNF-01), `ControlDeRolTest` 6 (HU-01: docente recibe 403, usuario con los dos roles accede).
- **`make verificar`:** no consta.
- **No se hizo:** la rama docente (prohibida por la tarea); no consta nada más.
- **Fuera de alcance:** no consta.
- **Desviaciones:** *(nota del responsable)* la prueba de arquitectura de `AlcanceDeJugadores` no existía en H0 (error de la especificación) y T09 la creó con una regla más estricta. Rutas de pruebas fuera de la lista: sección 5.

### F1-H1-T10. Categorías (commit `750fb3d`) — *reconstruido desde git, sin el reporte original*

- **Archivos tocados:** `app/Http/Controllers/CategoriaController.php`, `Requests/SolicitudDeNuevaCategoria.php`, `Resources/CategoriaRecurso.php`, `routes/api.php`, **`app/Exceptions/FormatoDeError.php`**, `lang/es/errores.php`; `src/Plantel/Aplicacion/` (`CrearCategoria`, `EliminarCategoria`, `ListarCategorias`, `CategoriaDuplicada`), `Dominio/Categoria.php`, `RepositorioDeCategorias.php`, `Infraestructura/RepositorioDeCategoriasEloquent.php`, `PlantelServiceProvider.php`; `docs/api/openapi.yaml`; `tests/Feature/Plantel/CategoriasTest.php`.
- **Pruebas (11):** HU-28 ×11 (las 7 filas de §7.5; la fila 5 se completó por la API en T11).
- **`make verificar`:** no consta.
- **No se hizo / Fuera de alcance:** no constan.
- **Desviaciones:** *(nota del responsable)* se aprobó `por_pagina` por defecto de 15 y se actualizó `docs/convenciones-api.md` (commit aparte `b69bdc6`). `FormatoDeError.php`: sección 5.

### F1-H1-T11. Equipos (commit `fdb4406`; commit relacionado `9f67b66`) — *reconstruido desde git, sin el reporte original*

- **Archivos tocados:** `app/Http/Controllers/EquipoController.php`, `Requests/SolicitudDeNuevoEquipo.php`, `Resources/EquipoRecurso.php`, `routes/api.php`, `FormatoDeError.php`, `lang/es/errores.php`; `src/Compartido/Dominio/HoyDeNegocio.php`, `src/Compartido/Infraestructura/HoyDeNegocioSegunReloj.php` y `CompartidoServiceProvider.php`; `src/Plantel/` (`CrearEquipo`, `ListarEquipos`, `EquipoConCategoria`, `EquipoDuplicado`, `CategoriaInexistente`, `Dominio/Equipo`, `RepositorioDeEquipos`, `RepositorioDeEquiposEloquent`, y cambios en los repositorios de categorías: *(nota del responsable)* `buscarVigente`); `docs/api/openapi.yaml`; pruebas.
- **Pruebas (21):** `EquiposTest` 16 (RN-22 ×12, RNF-02 ×3, HU-28 ×1, incluye la fila 5 de §7.5 por la API y la atomicidad de la creación), `HoyDeNegocioTest` 4 y `HoyDeNegocioEnContenedorTest` 1 (RNF-04).
- **`make verificar`:** no consta.
- **No se hizo / Fuera de alcance:** no constan.
- **Desviaciones:** *(nota del responsable)* aprobó explícitamente `HoyDeNegocio` en `src/Compartido`, el cambio `buscarVigente` en el código de T10 y el arreglo del plazo de composer en un commit aparte (`9f67b66`, `F1-H1-FIX: verificar sin limite de tiempo de composer`, solo `composer.json`).

### F1-H1-T12. Registrar un jugador (commit `a69111a`) — *reconstruido desde git, sin el reporte original*

- **Archivos tocados:** `app/Http/Controllers/JugadorController.php`, `Requests/SolicitudDeNuevoJugador.php`, `Resources/JugadorRecurso.php`, `routes/api.php`, `FormatoDeError.php`, `lang/es/errores.php`; **`database/migrations/plantel/2026_10_21_020800_indice_de_matricula_sin_mayusculas.php`**; `src/Plantel/` (`RegistrarJugador`, `JugadorRegistrado`, `MatriculaDuplicada`, `PosicionInvalida`, `EquipoNoEncontrado`, `Dominio/Jugador`, `RepositorioDeJugadores`, `RepositorioDePosiciones`, repositorios Eloquent, `PlantelServiceProvider`); `docs/api/openapi.yaml`; `tests/Feature/Plantel/JugadoresTest.php`, `tests/Integration/Plantel/MigracionesDePlantelTest.php`.
- **Pruebas (19 + 4):** `JugadoresTest` 19 (HU-03 ×11, RNF-03 ×5, HU-01 ×2, RNF-02), 4 de RNF-03 en `MigracionesDePlantelTest` (índice de matrícula, DD-37).
- **`make verificar`:** no consta.
- **No se hizo / Fuera de alcance:** no constan.
- **Desviaciones:** *(nota del responsable)* aprobó la migración `020800` (índice sobre `lower(matricula)`, DD-37) y la línea en `FormatoDeError`.

### F1-H1-T13. Consultar jugadores y buscar por matrícula (commit `a8947ea`) — *reconstruido desde git, sin el reporte original*

- **Archivos tocados:** `app/Http/Controllers/JugadorController.php`, `Resources/JugadorBuscadoRecurso.php`, `JugadorConsultadoRecurso.php`, `routes/api.php`, `FormatoDeError.php`; `src/Plantel/Aplicacion/` (`ListarJugadores`, `VerJugador`, `BuscarJugadorPorMatricula`, `BuscadorDeJugadorPorMatricula`, `ConsultaDeJugadores`, `JugadorConEquipos`, `EquipoDelJugador`, `JugadorBuscado`, `JugadorNoEncontrado`), `Infraestructura/BusquedaDeJugadorPorMatricula.php`, `ConsultaDeJugadoresSql.php`, `PlantelServiceProvider.php`; `docs/api/openapi.yaml`; `tests/Ayudantes/MundoDeJugadores.php`, `tests/Pest.php`, `tests/Feature/Plantel/ConsultaDeJugadoresTest.php`, `tests/Feature/Seguridad/JugadoresSeguridadTest.php`.
- **Pruebas (47):** `ConsultaDeJugadoresTest` 32 (HU-02 ×11, RN-20 ×8, RN-14 ×7, RNF-01 ×5) y `JugadoresSeguridadTest` 15 acumuladas (en T13: RN-14, RN-20 y RNF-01 ×5; el recorrido de seguridad que pide la tarea).
- **`make verificar`:** no consta.
- **No se hizo / Fuera de alcance:** no constan.
- **Desviaciones:** *(nota del responsable)* usó el esquema `Jugador` por referencia en OpenAPI y la búsqueda incluye bajas (DD-38, registrado en `563c865`).

### F1-H1-T14. Asignar a otro equipo y cambiar la posición (commit `22e91de`) — *reconstruido desde git, sin el reporte original*

- **Archivos tocados:** `app/Http/Controllers/EquipoJugadorController.php`, `Requests/SolicitudDeAsignacionDeJugador.php`, `SolicitudDeCambioDePosicion.php`, `SolicitudDeJugadorDeEquipo.php`, `routes/api.php`, `FormatoDeError.php`, `lang/es/errores.php`; `src/Plantel/Aplicacion/` (`AsignarJugadorAEquipo`, `CambiarPosicionDeJugador`, `ComprobarEquipoDelEntrenador`, `JugadorEnBaja`, `JugadorParaAsignar`, `JugadorYaEnEquipo`), `Dominio/RepositorioDeMembresias.php`, `RepositorioDeJugadores.php`, `Infraestructura/RepositorioDeMembresiasEloquent.php`, `RepositorioDeJugadoresEloquent.php`, `PlantelServiceProvider.php`; `docs/api/openapi.yaml`; **`tests/TestCase.php`**; `tests/Feature/Plantel/AsignacionDeJugadoresTest.php`, `tests/Feature/Seguridad/JugadoresSeguridadTest.php`.
- **Pruebas (25 + 3):** `AsignacionDeJugadoresTest` 25 (HU-04 ×10, HU-05 ×9, RN-20 ×2, RNF-02 ×2, HU-01), y RN-20/RNF-01 en `JugadoresSeguridadTest`.
- **`make verificar`:** no consta.
- **No se hizo:** quitar jugadores de un equipo y mover estadísticas pasadas (prohibido).
- **Fuera de alcance:** *(nota del responsable)* la carrera asignación-baja quedó como pendiente de seguridad (`f1-h1.md` §11, añadida en `34f08ed`).
- **Desviaciones:** *(nota del responsable)* aprobó el cambio en `tests/TestCase.php` (conexiones agotadas) y `FormatoDeError`. Adoptó la instantánea completa en el historial de `actualizar` (DD-39, registrada en `34f08ed`).

### F1-H1-T15. Actualizar el expediente (commit `cc4f6ed`) — *reconstruido desde git, sin el reporte original*

- **Archivos tocados:** `app/Http/Controllers/JugadorController.php`, `Requests/SolicitudDeActualizacionDeJugador.php`, `routes/api.php`, `lang/es/errores.php`; `src/Plantel/Aplicacion/ActualizarExpedienteDeJugador.php`, `Dominio/RepositorioDeJugadores.php`, `Infraestructura/RepositorioDeJugadoresEloquent.php`; `docs/api/openapi.yaml`; `tests/Feature/Plantel/ConsultaDeJugadoresTest.php`, `ExpedienteDeJugadorTest.php`, `tests/Feature/Seguridad/JugadoresSeguridadTest.php`.
- **Pruebas (15 + 3):** `ExpedienteDeJugadorTest` 15 (HU-05 ×14, RNF-02), una de HU-02 y dos de RNF-01 en las pruebas de seguridad.
- **`make verificar`:** no consta.
- **No se hizo / Fuera de alcance:** no constan.
- **Desviaciones:** *(nota del responsable)* usó `campo: "cuerpo"` y `prohibited` para los campos no permitidos, y se actualizó `docs/convenciones-api.md` (DD-40, commit `fbb20cf`).

### F1-H1-T16. Baja de un jugador (commit `5aa51ad`) — *reconstruido desde git, sin el reporte original*

- **Archivos tocados:** `app/Http/Controllers/JugadorController.php`, `Requests/SolicitudDeBajaDeJugador.php`, `Resources/BajaRecurso.php`, `routes/api.php`, `FormatoDeError.php`, `lang/es/errores.php`; `src/Plantel/Aplicacion/` (`DarDeBajaAJugador`, `Baja`, `JugadorYaEnBaja`, `MotivoObligatorio`), `Dominio/RepositorioDeJugadores.php`, `Infraestructura/RepositorioDeJugadoresEloquent.php`; `docs/api/openapi.yaml`; `tests/Feature/Plantel/BajaDeJugadorTest.php`, `tests/Feature/Seguridad/JugadoresSeguridadTest.php`.
- **Pruebas (18 + 2):** `BajaDeJugadorTest` 18 (HU-29 ×8, RN-23 ×5, RN-17, RNF-02 y 3 de DD-30 y DD-40), RNF-01 ×2 en seguridad.
- **`make verificar`:** pasa con 439 pruebas *(nota del responsable: es el del último reporte)*.
- **No se hizo:** cerrar membresías, el evento `JugadorDadoDeBaja` (DD-32) y reactivar (prohibidos).
- **Fuera de alcance:** no consta.
- **Desviaciones:** *(nota del responsable)* **no se vio fallar las pruebas antes de implementar** (desviación del flujo de `proyecto.md` §4, paso 3); aprobó `FormatoDeError` y los mensajes de `lang/es`. DD-41 fija los valores por defecto de la baja.

### Commits `F1-H1-FIX` (fuera de las tareas) — *reconstruido desde git, sin el reporte original*

| Commit | Qué hizo | Archivos | Pruebas |
|---|---|---|---|
| `9f67b66` verificar sin límite de tiempo de composer | Desactiva el plazo del script `verificar` | `backend/composer.json` | ninguna |
| `a83c1ca` `actualizado_en` coherente con `Reloj` | `actualizado_en` de Plantel y del historial sale de `Reloj` y se trunca a segundos en el origen (`Instante`), para que lo guardado y lo devuelto coincidan | `src/Compartido/Dominio/Instante.php` (nuevo), `src/Compartido/Infraestructura/Historial/EscritorDeHistorial.php`, `src/Plantel/Aplicacion/CrearCategoria`, `CrearEquipo`, `RegistrarJugador`, repositorios Eloquent de Plantel | `ActualizadoEnCoherenteTest` 7 (RNF-02), `InstanteTest` 2 |
| `9b051a6` suite de pruebas más rápida | *(nota del responsable)* volúmenes de Docker, `memory_limit` y `esquemaMarcarSucio`. El reinicio rápido del esquema vacía tablas en lugar de `migrate:fresh` en cada prueba | `Makefile`, `README.md`, `docker-compose.yml`, `backend/composer.json` (`-d memory_limit=1G` en `verificar`), `tests/Pest.php`, `tests/Ayudantes/EsquemaDePrueba.php` (nuevo), `tests/Ayudantes/SesionesDePrueba.php` y 15 archivos de prueba | `ReinicioDeEsquemaTest` 4 |

Su alcance no figura en ninguna tarea; las rutas están en la sección 5. Ningún umbral ni prueba se debilitó (el commit solo cambia el modo de reiniciar el esquema).

### F1-H1-T17. Guion de humo y cierre documental (este commit)

- **Archivos tocados:** `backend/database/seeders/UsuariosDemoSeeder.php` (nuevo) y `DatabaseSeeder.php` (lo llama si el entorno no es `production`); `backend/lang/es/identidad.php` (clave `demo.produccion`; ruta aprobada por el responsable); `backend/tests/Integration/Identidad/UsuariosDemoSeederTest.php` (nuevo); `scripts/humo/lib.sh` y `scripts/humo/humo-h1.sh` (nuevos); `Makefile` (solo `humo`: una línea por guion, sin sintaxis de shell), `docker-compose.yml` (monta `./scripts` en `/scripts`, solo lectura) y `README.md` (nota del montaje); `docs/trazabilidad.md`; `docs/decisiones/registro-decisiones.md`; este reporte.
- **Pruebas (2):** `CONSOLA: UsuariosDemoSeeder se niega a correr en producción` y `CONSOLA: UsuariosDemoSeeder crea un docente y dos entrenadores y es idempotente` (la segunda, aprobada por el responsable). Se vieron fallar antes de implementar con un seeder vacío: la primera por `Exception "RuntimeException" not thrown.` y la segunda por `Failed asserting that 0 is identical to 3.`
- **`make verificar`:** pasa (sección 3). **`make humo`:** pasa (sección 3).
- **No se hizo:** nada de lo pedido queda sin hacer. El guion exige una base recién sembrada: en una segunda corrida sin `make fresh` el paso 3 falla con 409 (se comprobó; sale con código 2).
- **Fuera de alcance:** (1) el `.env` del contenedor usa los dominios de Sanctum por defecto y no incluye `localhost:8099`, por lo que el guion envía `Origin: http://localhost`; (2) RN-12 no tiene prueba con su identificador (ver `docs/trazabilidad.md`); (3) `humo-h1.sh` no comprueba `GET /sanctum/csrf-cookie` por separado: lo ejerce el inicio de sesión.
- **Desviaciones:** la lista de rutas de T17 no incluía `backend/lang/es/identidad.php`; el responsable la aprobó en el plan (omisión de la especificación: ningún texto visible va en el código).
- **Desviaciones (humo en Windows):** el humo se probó solo en un shell POSIX; fallaba en PowerShell/cmd (el objetivo `humo` usaba `$$(ls ... || true)` y un `for`). Corregido tras la revisión del responsable: `scripts/` se monta de solo lectura en `/scripts`, el objetivo `humo` son dos líneas simples y `humo-h1.sh` carga `lib.sh` con `source` desde su propio directorio. Se validó con `make humo` en Git Bash, `powershell.exe -NoProfile -Command "make humo"` y `cmd /c make humo`. Un contenedor creado antes del montaje se recrea con `make levantar`.

## 2. Pruebas del hito por requisito

Archivos de prueba por historia, regla y requisito: `docs/trazabilidad.md` (columna Pruebas). Los criterios diferidos de §2.2 están anotados allí.

## 3. Resultado de `make verificar` y `make humo` al cierre (26 de octubre de 2026)

- **`make verificar`:** código de salida 0. Pint (211 archivos), PHPStan nivel 6 sin errores, **441 pruebas pasan (2 405 aserciones)** en 322,7 s, umbral de cobertura por capa y `composer audit` sin avisos.
- **`make humo`:** código de salida 0; los 14 pasos de `docs/hitos/f1-h1.md` §9 con `OK` y el código HTTP.

### Cobertura por capa (reporte Clover, `bin/verificar-cobertura.php`)

| Capa | Rutas | Cobertura | Umbral | Estado |
|---|---|---|---|---|
| Dominio | `src/*/Dominio/**` | 100,0 % | 95 % | ok |
| Aplicación | `src/*/Aplicacion/**` | 100,0 % | 85 % | ok |
| Infraestructura y HTTP | `src/*/Infraestructura/**` y `app/**` | 100,0 % | 70 % | ok |
| Total | | 100,0 % | | |

## 4. Compuerta de H1 (`docs/hitos/f1-h1.md` §10)

| # | Condición | Estado | Evidencia |
|---|---|---|---|
| 1 | `make verificar` pasa en local y en CI, con el umbral por capa | **Local: cumplido. CI: pendiente de verificar por el responsable** | Local: sección 3. CI: no hay `gh`; la API pública de GitHub muestra la corrida `verificar` nº 28 del commit `0c3bbe0` (último subido) en `success`. El commit de T17 aún no está subido, así que su corrida no existe |
| 2 | Cada historia tiene pruebas con su identificador; criterios diferidos anotados | Cumplido, con una observación | `docs/trazabilidad.md`; HU-01 a HU-05, HU-27, HU-28 y HU-29 tienen pruebas. RN-12 no tiene ninguna con su identificador (no es una historia) |
| 3 | `openapi.yaml` incluye las rutas de §6.1 y las respuestas coinciden | Cumplido | `ContratoTest` y las pruebas de contrato de cada tarea pasan en `make verificar`; el humo recorre las rutas principales |
| 4 | `make fresh` y `migrate:rollback` sin errores | Cumplido | `make fresh` migra 21 migraciones y siembra roles, posiciones y usuarios de demostración; `migrate:rollback --database=propietario` revierte las 21 (quedan 0) y `make migrar` las vuelve a aplicar, sin errores |
| 5 | Las tablas de §7 pasan, una prueba por fila | Cumplido | `make verificar`: 441 pruebas |
| 6 | `make humo` sin fallas | Cumplido | Sección 3 y salida completa en la entrega |
| 7 | Criterios propios de H1 | Cumplido | `usuarios:crear`: `CrearUsuarioComandoTest`; sesión: `IniciarSesionTest`, `CerrarSesionYContrasenaTest` y humo 2 y 14; equipo creado y asignado: `EquiposTest`, humo 4; 409 sin datos: `JugadoresTest`, humo 6; 404 ajeno: humo 8; búsqueda: humo 9; bloqueo tras 5 intentos: `IniciarSesionTest` (RNF-10) |
| 8 | Este reporte con la lista de rutas fuera de la especificación | Cumplido | Secciones 1 y 5 |
| 9 | DD-28 a DD-41 registradas | Cumplido | `docs/decisiones/registro-decisiones.md` |
| 10 | Aprobación, etiqueta `f1-h1` y fusión | **Del responsable** | No se creó la etiqueta ni se hizo push ni PR |

## 5. Rutas tocadas que la especificación no permitía

Clasificación: **(a)** aprobada explícitamente por el responsable en el plan de la tarea; **(b)** omisión o contradicción de la especificación (el responsable la corrige en `docs/hitos/f1-h1.md`); **(c)** sin aprobación registrada (ninguna).

| Tarea | Ruta | Clase | Observación |
|---|---|---|---|
| T03 | `docs/trazabilidad.md` | (b) | *(nota del responsable)* contradicción de la especificación; ahora reservada a T17 |
| T04 | `backend/config/identidad.php` | (b) | DD-35 la nombra como lugar de los parámetros de acceso, pero la lista de T04 no la incluía |
| T04 | `src/Compartido/Infraestructura/Historial/EscritorDeHistorial.php` | (b) | T04 permitía de `src/Compartido` solo la implementación de `UsuarioActual`. Se tocó para escribir historial sin usuario (DD-35, registrada después en `bb4bc24`). Aceptadas por el responsable al cierre de H1 |
| T05 | `app/Exceptions/FormatoDeError.php` | (b) | *(nota del responsable)* debió figurar en las rutas desde T05 |
| T05 | `src/Identidad/Dominio/RepositorioDeUsuarios.php`, `src/Identidad/Infraestructura/RepositorioDeUsuariosEloquent.php`, `IdentidadServiceProvider.php` | (b) | La lista de T05 solo nombraba `src/Identidad/Aplicacion/**`; el caso de uso las necesita. Omisión de la especificación. Aceptadas por el responsable al cierre de H1 |
| T06 | `src/Identidad/Dominio/**`, `src/Identidad/Infraestructura/**`, `app/Exceptions/**` | (b) | *(nota del responsable)* la lista original las omitía; ya corregida en la especificación (`4891a1c`). Con la lista vigente no hay desvío |
| T09 | `backend/tests/Pest.php`, `backend/tests/Ayudantes/SesionesDePrueba.php` (carpeta nueva) | (b) | La lista permitía `tests/Arch`, `tests/Feature` y `tests/Integration`. Aceptadas por el responsable al cierre de H1 |
| T10 | `app/Exceptions/FormatoDeError.php` | (b) | Igual que en T05 |
| T11 | `src/Compartido/Dominio/HoyDeNegocio.php`, `src/Compartido/Infraestructura/HoyDeNegocioSegunReloj.php`, `CompartidoServiceProvider.php` | (a) | *(nota del responsable)* aprobó `HoyDeNegocio` en `src/Compartido` |
| T11 | `app/Exceptions/FormatoDeError.php` | (b) | Igual que en T05 |
| `9f67b66` | `backend/composer.json` | (a) | *(nota del responsable)* arreglo del plazo de composer en un commit aparte |
| T12 | `database/migrations/plantel/2026_10_21_020800_indice_de_matricula_sin_mayusculas.php` | (a) | *(nota del responsable)* DD-37 |
| T12 | `app/Exceptions/FormatoDeError.php` | (a) | *(nota del responsable)* aprobó la línea |
| T13 | `app/Exceptions/FormatoDeError.php` | (b) | Igual que en T05 |
| T14 | `tests/TestCase.php` | (a) | Dentro de `tests/**`, pero es un cambio de la base de pruebas (conexiones agotadas) que el responsable aprobó explícitamente |
| T14 | `app/Exceptions/FormatoDeError.php` | (a) | *(nota del responsable)* |
| T16 | `app/Exceptions/FormatoDeError.php` | (a) | *(nota del responsable)* |
| `a83c1ca` | `src/Compartido/Dominio/Instante.php`, `EscritorDeHistorial.php`, `src/Plantel/**` (7 archivos de Aplicación e Infraestructura) | (a) | Commit `F1-H1-FIX` fuera de una tarea; aprobada explícitamente por el responsable en el plan |
| `9b051a6` | `Makefile`, `README.md`, `docker-compose.yml`, `backend/composer.json` (`-d memory_limit=1G`), `tests/Pest.php`, `tests/Ayudantes/EsquemaDePrueba.php`, `SesionesDePrueba.php`, 15 archivos de prueba | (a) | Commit `F1-H1-FIX` fuera de una tarea (añadió volúmenes de Docker y cambió el reinicio del esquema de pruebas); aprobada explícitamente por el responsable en el plan |
| T17 | `backend/lang/es/identidad.php` | (a) | Aprobado en el plan de T17 |
| T17 | `docker-compose.yml`, `README.md` | (a) | Montaje `./scripts:/scripts:ro` y su nota; aprobada por el responsable tras el hallazgo del humo en PowerShell/cmd |

Sin clasificación necesaria: T01, T02, T07, T08, T15 y los commits `F1-DOC` (solo tocan `docs/`, por decisión del responsable). No hay rutas de `frontend/` tocadas en ningún commit.

**Resumen:** la lista no está vacía. Hay 11 filas (a), 10 (b) y **0 (c)**: ninguna ruta quedó sin aprobación ni clasificación. Las filas (b) de T04 (`EscritorDeHistorial.php`), T05 (repositorio y proveedor de Identidad) y T09 (`tests/Pest.php` y `tests/Ayudantes/`) las aceptó el responsable al cierre de H1; la especificación debe corregirse.

## 6. Dudas y decisiones que necesitan aprobación

Todas están en `docs/decisiones/registro-decisiones.md`: AB-10 a AB-21 y PT-01 a PT-04, además de DD-28 a DD-41. En particular, DD-30 y DD-40 se validan con la institución.
