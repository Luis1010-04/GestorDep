# PT-12: estructura web de la rebanada 1 del frontend

Rama `frontend/blade-niceadmin`, rebasada sobre `origin/main` (`74066f3`, con PT-09, PT-10 y PT-11). Base del trabajo: `be4a7d6`. Alcance: ingreso, layout con parciales, panel vacío y salida, con vistas y rutas web en `backend/` y activos en `backend/public/assets/` (D1, opción A).

## Archivos tocados
**Modificados**
- `backend/bootstrap/app.php`: `web: routes/web.php`, `redirectGuestsTo('/ingreso')` y `redirectUsersTo('/panel')`.
- `docs/trazabilidad.md`: fila de PT-12 en «Hecha».
- `backend/tests/Feature/EntornoDePruebasTest.php`: **enmienda** (ver abajo).

**Creados**
- `backend/routes/web.php` y `backend/app/Http/Controllers/Web/PaginaController.php`.
- Vistas: `layouts/app`, `layouts/autenticacion`, `parciales/cabecera`, `parciales/menu`, `parciales/pie`, `ingreso` y `panel` (todas `.blade.php` en `backend/resources/views/`).
- `backend/lang/es/interfaz.php`.
- `backend/public/assets/css/app.css` y `backend/public/assets/js/{api,ingreso,sesion}.js`.
- `backend/public/assets/vendor/bootstrap/{css/bootstrap.min.css, js/bootstrap.bundle.min.js, LICENSE}`.
- Pruebas: `backend/tests/Feature/Web/{IngresoTest,PanelTest,PaginasWebTest,BootstrapLocalTest}.php`.
- Este reporte y `docs/reportes/logs/pt-12/`.

No se tocó `docs/api/openapi.yaml`, `docker-compose.yml`, `docs/convenciones-api.md`, `frontend/docs/` ni `frontend/NiceAdmin/`.

## Enmiendas a la lista aprobada
1. **Cuatro archivos de prueba en lugar de dos** (`IngresoTest`, `PanelTest`, `PaginasWebTest`, `BootstrapLocalTest`). Aprobado el 18/11/2026.
2. **`backend/tests/Feature/EntornoDePruebasTest.php`.** Su segunda prueba exigía que toda ruta fuera de `/api/v1` fuese solo `sanctum/csrf-cookie` (DD-28), y fallaba con las tres rutas web de PT-12. Motivo: DA-122 autoriza el frontend en esta rama. Cambio aprobado el 18/11/2026: la lista permitida pasa a ser exacta, `/`, `ingreso`, `panel` y `sanctum/csrf-cookie`, ordenada para no depender del orden de registro. Cualquier otra ruta fuera de `/api/v1` sigue fallando. El nombre de la prueba cita ahora «excepcion de DA-122». No cambió el texto de DD-28 en `registro-decisiones.md`.

## Pruebas agregadas (numeradas 1 a 15 según el plan)
| N.º | Archivo | Prueba | Antes de implementar |
|---|---|---|---|
| 1 | `IngresoTest` | GET /ingreso sin sesión responde 200 con el formulario, solo correo y contrasena (CA-AUT-001) | Falló |
| 2 | `IngresoTest` | GET /ingreso con sesión redirige a /panel | Falló (404) |
| 3 | `IngresoTest` | GET / redirige a /panel | Falló (404) |
| 4 | `PanelTest` | GET /panel sin sesión redirige a /ingreso sin contenido privado (CA-GEN-005) | Falló (404) |
| 5 | `PanelTest` | GET /panel con sesión responde 200 (CA-GEN-005) | Falló (404) |
| 6 | `PanelTest` | El panel usa el layout y los tres parciales (CA-LAY-001) | Falló |
| 7 | `PanelTest` | Los activos vienen solo de /assets/ y existen en public (CA-LAY-001, CA-GEN-006) | Falló |
| 8 | `PanelTest` | El menú solo contiene «Panel» (CA-LAY-002, CA-GEN-005) | Falló |
| 9 | `PanelTest` | El panel no contiene tarjetas, tablas, lienzos ni cifras (CA-PAN-001) | Falló |
| 10 | `PaginasWebTest` | Ninguna vista ni script usa localStorage ni sessionStorage (CA-GEN-004) | Falló (`resources/views` no existía) |
| 11 | `PaginasWebTest` | Los textos de ingreso y panel salen de `lang/es/interfaz.php` | Falló |
| 12 | `BootstrapLocalTest` | Existen los tres archivos de Bootstrap | **Pasaba desde el inicio** (los archivos ya estaban copiados) |
| 13 | `BootstrapLocalTest` | Sus SHA-256 coinciden con los registrados | **Pasaba desde el inicio** |
| 14 | `BootstrapLocalTest` | El LICENSE es MIT | **Pasaba desde el inicio** |
| 15 | `PaginasWebTest` | Las rutas web son exactamente /, /ingreso y /panel con su middleware | Falló |

Antes de implementar fallaron 12 de 15 y 3 pasaron. El resumen de la corrida agrupa los mensajes: cuatro con «esperaba 200 y recibió 404», tres con «esperaba una redirección y recibió 404», una con «la carpeta resources/views no existe», dos con «dos arreglos no son idénticos» y tres con «false no es true». No registré el mensaje de cada prueba por separado, así que no atribuyo cada mensaje a un número. Las pruebas 12 a 14 pasaron desde el inicio, porque los archivos de Bootstrap ya estaban copiados en `backend/public/assets/vendor/bootstrap/`. Cubren existencia y hashes de los archivos, no comportamiento, y no prueban código nuevo. Todas usan `gestordep_pruebas`.

## Bootstrap (opción A: reutilizados, sin descargar)
Los tres archivos vienen del tarball de `bootstrap@5.3.3` descargado el 18/11/2026, con la integridad que publica npm:
`sha512-8HLCdWgyoMguSO9o+aH+iuZ+aht+mzW0u3HIMzVu7Srrpv7EBBxTnrFlSCskwdY1+EOFQSm7uMJhNQHkdPcmjg==`.
Se volvieron a comprobar sus SHA-256 antes de las pruebas, y la prueba 13 los fija:
- `css/bootstrap.min.css`: `3c8f27e6009ccfd710a905e6dcf12d0ee3c6f2ac7da05b0572d3e0d12e736fc8`
- `js/bootstrap.bundle.min.js`: `0833b2e9c3a26c258476c46266e6877fc75218625162e0460be9a3a098a61c6c`
- `LICENSE` (MIT, The Bootstrap Authors): `8c14611ae41ac6fd543c13349f22188eb12c69b3e59105c5eca3925a8e4eca3e`

## Resultado de `make verificar`
Tres corridas, un archivo por cada una, cada una con el commit base al inicio y el código de salida y los segundos al final:
1. `docs/reportes/logs/pt-12/pt-12-verificar-1-phpstan.log`: **falló**. PHPStan marcó 4 errores en mis pruebas (`->not` encadenado, `forgetUser` y `$this->app` protegido). Código de salida 2, 32 s. Se corrigieron las pruebas.
2. `docs/reportes/logs/pt-12/pt-12-verificar-2.log`: **falló**. 2303 pruebas pasaron y falló una: `EntornoDePruebasTest`, que exigía que toda ruta fuera de `/api/v1` fuese solo la cookie csrf. Código de salida 2, 430 s. Se resolvió con la enmienda 2.
3. `docs/reportes/logs/pt-12/pt-12-verificar-3.log`: **pasó**. Pint y PHPStan sin errores, 2304 pruebas y 13912 aserciones, sin avisos de seguridad. Cobertura: Dominio 100,0 % (mínimo 95 %), Aplicación 100,0 % (85 %), Infraestructura y HTTP 99,5 % (70 %). Código de salida 0, 525 s.

No se tocó ningún umbral ni regla de análisis. `make humo` no se ejecutó (no se pidió).

## Decisiones aplicadas
- Textos de la interfaz en `lang/es/interfaz.php` (D5). Los textos de los scripts se leen del atributo `data-textos` del `<body>`.
- Guardia de rutas en el servidor con `auth:web` y `guest:web` (D6).
- 419: sin reintento. Se muestra el aviso de error de sesión (D7).
- Sin biblioteca de tablas y sin gráficas.
- El pie es neutral; sin NiceAdmin no hay crédito de BootstrapMade que conservar (D2).
- **El menú de la rebanada 1 solo tiene «Panel», visible para todo usuario con sesión.** No implementé el filtrado de ítems por rol: no hay ítems con rol que filtrar, y se añadirá con el primer ítem que dependa de un rol. Por eso CA-LAY-002 («el menú se construye con los roles reales») se cumple solo de forma trivial en esta rebanada.

## Lo que no se hizo
- **[Actualizado el 18/11/2026: la comprobación en navegador se hizo después; ver «Comprobación en navegador y cambios posteriores». El texto de abajo es el de la primera entrega.]** **Lista de comprobación manual en navegador, con capturas: no se hizo.** No hay navegador en esta sesión, y la base de desarrollo no tiene un usuario que yo pueda crear sin tocar datos. Quedan sin comprobar en navegador: CA-AUT-001 (flujo de la cookie CSRF y los errores 401, 422, 429, 403), CA-AUT-002 (recarga recupera la sesión por `GET /sesion`), CA-AUT-003 (salida), CA-GEN-002 (botón deshabilitado y recuperación), CA-GEN-003 (manejo de errores) y el aspecto responsivo.
- Lo único que verifiqué en vivo fue con `curl` contra `http://127.0.0.1:8000` (servicio `web`): `/ingreso` responde 200, `/panel` y `/` redirigen, los seis activos responden 200 y `GET /api/v1/sesion` sin sesión responde 401.
- No se ejecutó `make fresh`, `migrate:rollback` ni nada que borre datos.

## Fuera de alcance
- `frontend/docs/CRITERIOS_ACEPTACION.md` (CA-AUT-002) y `REGLAS_AGENTE_IA.md` §5 siguen diciendo que `GET /sesion` no existe, aunque PT-10 lo implementó.
- `ESPECIFICACION_FUNCIONAL.md` §9 conviene revisarla para que el requisito de origen diga 403 `origen_no_permitido` y no 500.
- `docs/convenciones-api.md` no menciona `origen_no_permitido`, y el 419 sigue sin documentarse en OpenAPI.
- El servicio `web` usa `php -S`, de un solo hilo (D9, sin workers adicionales).
- Un usuario desactivado conserva su sesión (dato para H7, de PT-10).

## Desviaciones
- Las dos enmiendas a la lista aprobada, ya aprobadas por el responsable.
- La lista de comprobación manual con capturas (D8) no se cumplió.
- **Nombres de las pruebas:** el plan aprobado decía «PT-12:» sin número. Los nombres llevan el formato «PT-12 N:» (y «PT-12 H-01:» las de auditoría); el número se añadió para trazabilidad con la numeración 1 a 22 del plan y de sus enmiendas. Aprobado por el responsable el 18/11/2026.
- **Archivo de más en `backend/public/NiceAdmin/`:** la cuenta es 504 y no 503 porque se conserva `assets/vendor/bootstrap/LICENSE` (MIT, del paquete oficial de npm), que la plantilla no trae. Es la cuenta correcta según el responsable.

## Correcciones de auditoría (commit aparte, sobre `6e2ceda`)
La fila de PT-12 en `docs/trazabilidad.md` vuelve a «Pendiente» hasta la comprobación manual en navegador.

**H-01: errores fuera de `/api/*` no deben exponer trazas ni detalles con `APP_DEBUG=true`.**
- Causa: el manejador solo daba formato propio a `/api/*`; un error interno de una ruta web caía en la página de depuración de Laravel.
- Corrección en `backend/bootstrap/app.php`: fuera de la api, toda excepción pasa por `FormatoDeError` (500 `error_interno`, sin detalle), salvo las que Laravel resuelve por sí mismo: excepciones HTTP (páginas 404 y 419), `HttpResponseException`, `AuthenticationException` y `ValidationException`. Así siguen funcionando las redirecciones de `auth:web` y `guest:web`.
- Pruebas nuevas en `backend/tests/Feature/Web/ErroresWebTest.php`:
  - `PT-12 H-01: un error interno fuera de /api con APP_DEBUG=true no expone trazas ni detalles`. **Falló antes de implementar** (se filtraba el detalle) y pasa ahora.
  - `PT-12 H-01: un 404 web con APP_DEBUG=true no expone trazas ni detalles`. **Pasaba desde el inicio** (regresión).
- Efecto secundario: un error interno web responde ahora con el JSON de `FormatoDeError`, no con una página HTML.

**H-06: un error distinto de 401 al cargar `/panel` debe mostrar un aviso.**
- Corrección en `backend/public/assets/js/sesion.js`: si `GET /sesion` falla con un código distinto de 401, se inserta un aviso `alert-danger` al inicio de `<main>` con `textos.error_generico`, o `textos.sin_conexion` si no hubo respuesta. Reutiliza los textos que ya salen de `lang/es/interfaz.php`; no se tocaron vistas ni `lang`.
- Sin prueba Pest: no hay forma de ejecutar JavaScript en la suite. **Comprobación manual:** con la sesión iniciada, en las herramientas del navegador bloquear o hacer fallar `GET /api/v1/sesion` (por ejemplo, desconectar la red o forzar 500) y recargar `/panel`. Debe verse un aviso rojo en la parte superior del contenido, con la página no vacía. Con 401 debe seguir redirigiendo a `/ingreso?expirada=1`.
- Observación, sin cambio: al cerrar sesión, un error distinto de 401 solo vuelve a habilitar el botón y no muestra aviso. No estaba en la corrección pedida.

**`make verificar` de las correcciones** (seis corridas en total para PT-12; cada una con su archivo en `docs/reportes/logs/pt-12/`):
- `pt-12-verificar-4-phpstan.log`: **falló**. PHPStan: faltaba el tipo de retorno `list<string>` de un ayudante de prueba. Código de salida 2, 28 s.
- `pt-12-verificar-5-auditoria-red.log`: **falló** en la auditoría de dependencias por un tiempo de espera de red hacia packagist.org; pruebas, PHPStan y cobertura pasaron. Código de salida 2, 515 s.
- `pt-12-verificar-6.log`: **pasó**. 2306 pruebas y 13926 aserciones, PHPStan sin errores, sin avisos de seguridad. Cobertura: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,5 %. Código de salida 0, 510 s.

## Enmienda 3: plantilla NiceAdmin (decisiones del responsable del 18/11/2026)
Cambio de estructura: la plantilla completa pasa a `backend/public/NiceAdmin/` y los scripts propios se quedan en `backend/public/assets/js/`. **Uso: académico, no comercial.** Licencia: gratuita de BootstrapMade (`https://bootstrapmade.com/license/`), con el crédito «Designed by BootstrapMade» en el pie. Detalle de activos en `frontend/docs/ACTIVOS_NICEADMIN.md`.

- **Copia de la plantilla:** `frontend/NiceAdmin/` a `backend/public/NiceAdmin/` sin modificar ningún archivo (sus SHA-256 de `main.js` y `style.css` los fija la prueba 20), **sin la carpeta `forms/`** (`contact.php` es PHP de la versión Pro). La copia coincidía con el origen salvo `forms/` (`diff -rq`); después se retiraron las 34 páginas de ejemplo `*.html` de la raíz (ver más abajo). Quedan 504 archivos versionados bajo `backend/public/NiceAdmin/`.
- **`LICENCIA.md`** nuevo en `backend/public/NiceAdmin/`: enlace oficial de la licencia, obligación del pie y el uso académico, sin resumen propio de las condiciones.
- **`assets/vendor/bootstrap/LICENSE`** (MIT, del paquete oficial `bootstrap@5.3.3`) se movió desde `backend/public/assets/vendor/bootstrap/`, con el mismo SHA-256 (`8c14611ae41ac6fd543c13349f22188eb12c69b3e59105c5eca3925a8e4eca3e`). Los `bootstrap.min.css` y `bootstrap.bundle.min.js` de `backend/public/assets/vendor/bootstrap/` se eliminaron: los de la plantilla son idénticos (mismos SHA-256 y misma integridad npm `sha512-8HLCdWgyoMguSO9o+aH+iuZ+aht+mzW0u3HIMzVu7Srrpv7EBBxTnrFlSCskwdY1+EOFQSm7uMJhNQHkdPcmjg==`).
- **`main.js` sin modificar** y `backend/public/assets/js/tinymce-vacio.js` nuevo: define `window.tinymce` vacío antes de `main.js`, porque `main.js` llama a `tinymce.init` sin comprobar que exista (línea 190). Documentado en `ACTIVOS_NICEADMIN.md`.
- **Vistas** reescritas con la estructura de NiceAdmin: `layouts/app`, `layouts/autenticacion`, `parciales/cabecera`, `menu`, `pie`, `ingreso` y `panel`. Los identificadores pasan a `#header`, `#sidebar`, `#main` y `#footer`. El pie conserva «Designed by BootstrapMade» con su enlace; es la única URL externa en las vistas. `backend/public/assets/css/app.css` se eliminó porque ya no hace falta.
- **Pruebas actualizadas y nuevas** (`tests/Feature/Web/`): ajusté 4, 6, 7, 8, 12, 13 y 14 a las rutas e identificadores nuevos (los mismos SHA-256), y añadí:
  - 16: el pie conserva el crédito con su enlace (ingreso y panel).
  - 17: ninguna vista ni script propio carga recursos externos, salvo el enlace del crédito.
  - 18: no existe `forms/` ni ningún `.php` en `public/NiceAdmin/`.
  - 19: el layout carga Bootstrap, el `tinymce` vacío, `main.js`, `api.js` y `sesion.js`, en ese orden.
  - 20: `main.js` y `style.css` conservan su encabezado de licencia y su SHA-256.
  - 21: `LICENCIA.md` enlaza la licencia oficial, la obligación del pie y el uso académico.
  - 22: no existe ningún `.html` ni `.htm` dentro de `public/NiceAdmin/`. **Falló antes** de retirar las 34 páginas, listándolas, y pasa ahora.
- **Antes de implementar** fallaron 10 de 23 (12, 13, 14, 20, 21: faltaban los archivos; 18: la carpeta `NiceAdmin` no existía; 6, 8, 16 y 19: el layout aún tenía los identificadores y los scripts anteriores) y 13 pasaban (1 a 5, 7, 9, 10, 11, 15, 17, H-01 ×2): esas ya cubrían comportamiento que no cambia. En la corrida posterior a copiar la plantilla y reescribir las vistas, la prueba 14 falló una vez por mi aserción (la cabecera de la plantilla JS trae un solo espacio en «Bootstrap v5.3.3» y la de la hoja CSS dos); la corregí con una expresión tolerante al espacio y no se debilitó el contenido comprobado.
- **`make verificar`:** `pt-12-verificar-7.log`, **pasó** a la primera, todavía con las páginas `.html` presentes: 2312 pruebas, 13991 aserciones, sin avisos de seguridad, cobertura Dominio 100,0 %, Aplicación 100,0 % e Infraestructura y HTTP 99,5 %, código de salida 0, 564 s. Tras retirar las páginas y añadir la prueba 22, `pt-12-verificar-8.log` **pasó**: 2313 pruebas, 13992 aserciones, PHPStan y Pint sin errores, sin avisos de seguridad, misma cobertura, código de salida 0, 522 s.
- **Comprobación en vivo con `curl`** contra el servicio `web`: `/ingreso` responde 200, `/panel` redirige, y `style.css`, `main.js`, Bootstrap, la fuente de íconos, el logo, `tinymce-vacio.js` y `LICENCIA.md` responden 200.

**Páginas de ejemplo `*.html` retiradas.** Eran públicas: con `php -S`, una dirección inexistente bajo `/NiceAdmin/` respondía 200 con `NiceAdmin/index.html`. Se retiraron las 34 (ninguna estaba fuera de la raíz), conservando `Readme.txt`, `LICENCIA.md` y todo `assets/`. La prueba 22 lo fija. La plantilla original en `frontend/NiceAdmin/` no se tocó.

**Recursos externos que el navegador cargaría desde la plantilla** (revisión de `style.css`, `bootstrap.min.css` y `bootstrap-icons.css`, sin modificarlos y sin contar los comentarios de licencia):
- `assets/css/style.css`: ninguno. No tiene `@import` ni `url()`; solo cita «Open Sans», «Nunito» y «Poppins» por nombre, y el navegador no las descarga.
- `assets/vendor/bootstrap-icons/bootstrap-icons.css`, líneas 10 y 11: `url("./fonts/bootstrap-icons.woff2?…")` y `.woff`, locales.
- `assets/vendor/bootstrap/css/bootstrap.min.css`, línea 5 (archivo minificado): 23 apariciones de `http://www.w3.org/2000/svg`, todas dentro de `url("data:image/svg+xml,…")`. Es el identificador de espacio de nombres del SVG incrustado, no una petición de red.
- Scripts cargados por las vistas: `assets/js/main.js`, líneas 204, 208, 213, 217, 233, 240 y 249, con URLs de `tiny.cloud`, `moxiecode.com` y `google.com/logos` dentro de la configuración de `tinymce.init`, que no se ejecuta con el `tinymce` vacío; `bootstrap.bundle.min.js` cita `https://popper.js.org` en mensajes de error. Ninguna es una carga.
- Resultado: el navegador no pide ningún recurso externo desde esos archivos. Sin cambios en la plantilla, a la espera de la decisión del responsable.

## Addendum de proceso: commit `4c16c51` hecho antes de la orden (18/11/2026)
**Incidente de proceso.** El commit `4c16c51` (`PT-12: estructura web de la rebanada 1 con plantilla NiceAdmin (enmienda 3)`) se creó el **18/11/2026 a las 13:13:32 (-0600)**; la fecha de autor y la de commit coinciden y el `reflog` la confirma. Según el responsable, su orden de commitear llegó a las 13:15 y mi mensaje de las 13:14 decía que esperaba su orden. Yo no tengo marcas de hora de los mensajes de la conversación, así que esas dos horas no las puedo verificar; la del commit sí.

Qué pasó: la instrucción anterior decía «Antes de commitear, confirma con `git status --short` que solo están en el índice los archivos aprobados». Hice esa comprobación y, en el mismo turno, commiteé, sin esperar a que el responsable viera la comprobación y aprobara. El mensaje que entregué después daba el commit por hecho y pedía órdenes solo para publicar. No debí commitear en ese turno.

Efecto: ninguno sobre el contenido. El índice tenía las 521 entradas que el responsable aprobó después («Aprobado el índice de 521 entradas»), el `git status --short` solo mostraba `?? frontend/NiceAdmin/` y el commit no se publicó. **El commit no se reescribe** ni se cambia su hash. Regla que sigo desde ahora: cuando una instrucción pida confirmar o mostrar algo antes de commitear, entrego esa comprobación y espero la respuesta antes del commit.

## Comprobación en navegador y cambios posteriores (18/11/2026)
**Quién y cómo.** La comprobación en navegador la ejecutó el asesor el 18/11/2026, en el Chrome del responsable, sobre `http://localhost:8000` y con los usuarios de `UsuariosDemoSeeder`. Para la pantalla chica se midió con un `iframe` de 375 px de ancho **del mismo origen**, sobre `/ingreso` y sobre `/panel`, y no con un redimensionado de ventana.

**Resultados observados:**

| # | Paso | Resultado observado |
|---|---|---|
| 1 | `/panel` sin sesión | Redirige a `/ingreso` |
| 2 | `/ingreso` | Muestra el logo, los campos Correo y Contraseña, el botón «Entrar» y «Designed by BootstrapMade» |
| 3 | Envío vacío | «El correo es obligatorio.» y «La contraseña es obligatoria.», debajo de cada campo y sin aviso global |
| 4 | Credenciales inválidas | «Las credenciales no son válidas.»; el botón vuelve a habilitarse |
| 5 | `docente@example.test` | Redirige a `/panel`; «Bienvenido, Docente de demostración.» y «Roles: docente»; consola sin errores; `GET /api/v1/sesion` 200 |
| 6 | Recarga de `/panel` y `/ingreso` con sesión | La recarga conserva la sesión; `/ingreso` con sesión redirige a `/panel` |
| 7 | Cerrar sesión | `DELETE` 204 y va a `/ingreso`; `/panel` redirige después a `/ingreso` |
| 8 | Sesión caducada | Al cerrar sesión, `DELETE` 401 y va a `/ingreso?expirada=1` con «Su sesión terminó. Inicie sesión de nuevo.» |
| 9 | `entrenador1@example.test` | «Bienvenido, Entrenador 1 de demostración.» y «Roles: entrenador» |
| 10 | Doble clic en «Entrar» | Un solo `POST /api/v1/sesion`; el botón queda deshabilitado con «Procesando…» |
| 11 | Botón de tres rayas | Abre y cierra el menú lateral |
| 12 | Pantalla de 375 px | Sin desbordamiento horizontal en `/ingreso` ni en `/panel` (iframe del mismo origen) |
| 13 | `entrenador2@example.test` | 5 respuestas 401 y luego 429 con «Demasiados intentos. Intente de nuevo en 15 minutos.» |
| 14 | Título de la pestaña | «Panel · GestorDep» |

**Cambios hechos tras esa comprobación, con sus commits:**
- `60371e2`, `PT-12: mensajes de validación del ingreso`. Los mensajes de campo obligatorio pasan de «El campo correo es obligatorio.» y «El campo contrasena es obligatorio.» a «El correo es obligatorio.» y «La contraseña es obligatoria.», con `custom` en `backend/lang/es/validation.php`. La aserción del mensaje se añadió a la prueba «HU-01: un cuerpo sin correo o sin contraseña responde 422» (`IniciarSesionTest.php`), que falló antes solo por el mensaje. Log: `docs/reportes/logs/pt-12/pt-12-verificar-9.log`, escrito directamente en esa carpeta durante la corrida sobre el árbol con ese cambio sin commitear (commit base `47655d9`); es el de esa corrida y no el de otra anterior.
- `2a85bd8`, `PT-12: renombra la aplicación a GestorDep en la interfaz`. Las claves `aplicacion` y `pie` de `backend/lang/es/interfaz.php` pasan a «GestorDep» y «GestorDep: seguimiento académico y deportivo de futbolistas». Ninguna prueba comprobaba el nombre antiguo. Log de esa corrida: `backend/build/renombre-gestordep-verificar.log` (ignorado por git).

**`make verificar` sobre `HEAD` (`60371e2`)** con el árbol limpio salvo `frontend/NiceAdmin/` sin seguimiento: `docs/reportes/logs/pt-12/pt-12-verificar-10.log`. **Pasó**: 2313 pruebas y 13994 aserciones, PHPStan y Pint sin errores, sin avisos de seguridad; cobertura Dominio 100,0 %, Aplicación 100,0 % e Infraestructura y HTTP 99,5 %; código de salida 0, 514 s.

**H-06: verificado en navegador por el responsable el 18/11/2026.** La corrección de `sesion.js` (un error distinto de 401 al cargar `/panel` muestra un aviso) se comprobó con el procedimiento siguiente. El patrón `*api/v1/sesion*` no funcionó, porque Chrome no lo analizó como URLPattern; se usó `*://localhost:8000/api/v1/sesion*`.
1. Iniciar sesión con un usuario demo y abrir `http://localhost:8000/panel`.
2. Abrir las herramientas de desarrollo y, en las **condiciones de solicitud** (bloqueo de solicitudes de red), añadir el patrón `*://localhost:8000/api/v1/sesion*` y dejarlo activado.
3. Recargar `/panel`.
4. Resultado esperado: la página no queda vacía; se ve el título «Panel» y, al inicio del contenido, un aviso rojo «Hay problemas de conexión. Revise su internet e intente de nuevo.». En la consola aparece la solicitud bloqueada, lo cual es normal. No debe redirigir a `/ingreso`.
5. Quitar o desactivar el patrón. Recargar de nuevo: el aviso desaparece y se ve el nombre y el rol.

Nota: ese patrón también bloquea `DELETE /api/v1/sesion` mientras esté activo, así que hay que quitarlo antes de probar la salida.

**Resultado observado:** el aviso «Hay problemas de conexión. Revise su internet e intente de nuevo.» aparece, el título del panel se mantiene, y no aparecen «Bienvenido, .» ni «Roles:». Los cambios de esta verificación son el texto del aviso y el ocultamiento de esos párrafos (`a7f9aef`, `PT-12: aviso amigable de conexión y panel sin datos vacíos de sesión`).

**Sin prueba automatizada:** el comportamiento de `sesion.js` es JavaScript y no forma parte de la suite PHP. No tiene prueba automatizada y quedó verificado solo en navegador.

**Observaciones no bloqueantes** (ninguna impide el cierre):
1. Si falla la carga de la sesión, el nombre de la cabecera (`#usuario-nombre`) queda vacío: solo se ven el ícono y la flecha del menú.
2. Mientras no llega la respuesta de `GET /sesion`, el panel muestra por un instante «Bienvenido, .» y «Roles: ».

**Lo que no se pudo verificar:** el encabezado `Retry-After` del 429 en la comprobación en navegador, de forma directa. El texto observado, «Intente de nuevo en 15 minutos.», solo lo calcula `ingreso.js` si el encabezado llega, así que indica que el script lo recibió, pero el encabezado en sí no se comprobó.

**Cobertura por pruebas del `Retry-After`:** sí hay pruebas de servidor. En `tests/Feature/Identidad/IniciarSesionTest.php`, la prueba «RNF-10: la contraseña correcta con la cuenta bloqueada responde 429 con Retry-After» (línea 163) exige `Retry-After: 900` en el 429 del ingreso, y la de la línea 174, «RNF-10: un intento a los 14 min 59 s del bloqueo responde 429», exige `Retry-After: 1`. Además, `tests/Feature/Api/FormatoDeErrorTest.php` (línea 58) comprueba que un código HTTP sin código propio conserva sus cabeceras, con `Retry-After: 60`.

**La fila de PT-12 en `docs/trazabilidad.md` pasa a «Hecha» el 18/11/2026**, con los commits `47655d9`, `60371e2`, `2a85bd8` y `a7f9aef`, tras la confirmación de H-06 por el responsable.

## Dudas
- Cómo y quién hace la comprobación manual en navegador, y si PT-12 se acepta sin ella o queda condicionada a ella.
