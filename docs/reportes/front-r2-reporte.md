# FRONT-R2: cambiar contraseña

Rama `frontend/blade-niceadmin`, sobre `e587b7c` (plan aprobado). Orden del responsable del 24/11/2026: implementar R2 según el plan del PR #38, con la prueba primero, después la vista y la comprobación en navegador con capturas. A mitad de la tarea llegó la orden de las 14:15 sobre la estructura de vistas y scripts (`modulos/` y `comun/`), que se aplicó a R2 antes del commit. Entorno: proyecto Docker `gestordep-front`, web en `http://localhost:8001`.

## Resultado

- Página `/contrasena` (solo con sesión) que cambia la contraseña con `PUT /api/v1/contrasena`. Pide confirmación con SweetAlert2, porque la API cierra las demás sesiones del usuario (HU-27), y avisa del éxito solo después del 204.
- Cada error 422 que trae campo se muestra debajo de su control. El 422 `contrasena_actual_incorrecta` llega sin campo (`errores: []`) y se muestra como aviso del formulario. Tras un envío fallido se vacían las contraseñas escritas. El 401 lleva a `/ingreso?expirada=1`; los demás códigos los trata `comun/avisos.js` (CA-GEN-003).
- Enlace «Cambiar contraseña» en el menú de usuario de la cabecera, para todo usuario con sesión.
- SweetAlert2 11.26.26 instalado en `backend/public/assets/vendor/sweetalert2/`, con su licencia MIT. Se carga solo en esta página.
- Estructura de la orden de las 14:15: vista en `modulos/contrasena/editar.blade.php`; scripts comunes en `assets/js/comun/` (`avisos.js` con `exito()`, `error()`, `aviso()` y `confirmar()`, y `formularios.js`); script del módulo en `assets/js/modulos/contrasena/editar.js`.

## Archivos

**Creados**
- `backend/resources/views/modulos/contrasena/editar.blade.php`
- `backend/public/assets/js/comun/avisos.js`, `backend/public/assets/js/comun/formularios.js`, `backend/public/assets/js/modulos/contrasena/editar.js`
- `backend/public/assets/vendor/sweetalert2/{sweetalert2.min.js, sweetalert2.min.css, LICENSE}`
- `backend/tests/Feature/Web/{ContrasenaTest, BibliotecasLocalesTest, EstructuraDelFrontTest}.php`
- Este reporte, `docs/reportes/capturas/front-r2/` y `docs/reportes/logs/front-r2/`

**Modificados**
- `backend/routes/web.php`: `GET /contrasena` con `auth:web`.
- `backend/app/Http/Controllers/Web/PaginaController.php`: método `contrasena()`.
- `backend/resources/views/layouts/app.blade.php`: `@stack('estilos')` después de `style.css` y `@stack('scripts')` después de `sesion.js`.
- `backend/resources/views/parciales/cabecera.blade.php`: enlace «Cambiar contraseña» en el menú de usuario.
- `backend/resources/views/parciales/menu.blade.php`: «Panel» solo se marca como sección activa en `/panel` (CA-GEN-005). Antes llevaba `aria-current="page"` fijo y aparecía activo en cualquier página.
- `backend/public/assets/js/sesion.js`: escribe el nombre y los roles solo si la página tiene esos elementos. La bienvenida existe solo en el panel; sin este cambio, `sesion.js` fallaba en cualquier otra página.
- `backend/lang/es/interfaz.php`: textos de la página, del menú y de los avisos.
- Pruebas existentes, sin debilitarlas: `PaginasWebTest` (PT-12 15 y 19), `PanelTest` (PT-12 7) y `tests/Feature/EntornoDePruebasTest.php` (F1-H0-T03, ampliación autorizada por el responsable).
- `docs/trazabilidad.md` (fila `/contrasena` en «Rutas web»), `frontend/docs/ACTIVOS_NICEADMIN.md` (§4 bis y §6.3) y `docs/reportes/front-plan-rebanadas.md` (§0.9, estructura).

## Integración

- `PUT /api/v1/contrasena` (204; 401; 422 `validacion_fallida`, `contrasena_actual_incorrecta`, `contrasena_muy_corta`), con la cookie de sesión y `X-XSRF-TOKEN` de `api.js` (rebanada 1).
- `GET /api/v1/sesion`, que ya cargaba `sesion.js`.

## Pruebas

| N.º | Archivo | Prueba | Antes de implementar |
|---|---|---|---|
| FRONT-R2 1 | `ContrasenaTest` | `GET /contrasena` sin sesión redirige a `/ingreso` sin contenido privado (CA-GEN-005) | Falló: 404 |
| FRONT-R2 2 | `ContrasenaTest` | Con sesión, 200 con el layout y sus tres parciales (CA-LAY-001) | Falló: 404 |
| FRONT-R2 3 | `ContrasenaTest` | El formulario declara `PUT /contrasena` y tiene solo `contrasena_actual` y `contrasena_nueva`, de tipo `password` (CA-FOR-001) | Falló |
| FRONT-R2 4 | `ContrasenaTest` | Los textos salen de `lang/es/interfaz.php` | Falló: 404 |
| FRONT-R2 5 | `ContrasenaTest` | La cabecera con sesión enlaza a `/contrasena`; el ingreso no | Falló |
| FRONT-R2 6 | `ContrasenaTest` | La página carga SweetAlert2 y sus scripts después de `sesion.js`; el panel no carga SweetAlert2 (DAF-07) | Falló |
| FRONT-R2 7 | `BibliotecasLocalesTest` | Los archivos de SweetAlert2 existen y su SHA-256 coincide con el del paquete npm | Falló: los archivos se retiraron a propósito antes de correrla |
| FRONT-R2 8 | `BibliotecasLocalesTest` | SweetAlert2 es la 11.26.26 y su licencia es MIT | Falló: archivo inexistente |
| FRONT-R2 9 | `BibliotecasLocalesTest` | SweetAlert2 no contiene `location` | Falló: archivo inexistente |
| FRONT-R2 10 | `PaginasWebTest` | Ninguna vista usa las clases que `main.js` inicializa sin comprobar su biblioteca (DAF-12) | **Pasó desde el inicio**: es una guarda de regresión |
| FRONT-R2 11 | `EstructuraDelFrontTest` | Las vistas nuevas viven en `modulos/{modulo}/` con `index`, `crear`, `editar` o `ver` | **Sin fase roja**: se escribió después de mover los archivos (orden de las 14:15) |
| FRONT-R2 12 | `EstructuraDelFrontTest` | Cada vista de módulo extiende el layout, define `titulo` y `contenido`, no lleva `<script>` en línea y carga sus scripts desde `comun/`, `modulos/{modulo}/` o `vendor/` | **Sin fase roja**, por el mismo motivo |
| FRONT-R2 13 | `EstructuraDelFrontTest` | Solo `comun/avisos.js` usa `Swal` | **Sin fase roja**, por el mismo motivo |
| FRONT-R2 14 | `EstructuraDelFrontTest` | `avisos.js` ofrece `exito`, `error`, `aviso` y `confirmar`; el aviso va arriba a la derecha, sin botón y dura 1,5 s; `error()` solo usa `icon: 'error'` | Falló una vez por mi aserción, que contaba también la mención del comentario de cabecera. Se corrigió para revisar solo el cuerpo de `error()`; el requisito no cambió |

**Enmiendas a pruebas existentes** (ninguna se debilita):
- PT-12 15 agrega `contrasena => auth:web` a la lista exacta.
- PT-12 19 exige además `@stack('scripts')` después de `sesion.js` y `@stack('estilos')` después de `style.css`.
- PT-12 7 excluye `/contrasena` de los activos, porque es una página propia y no un recurso.
- F1-H0-T03 agrega `contrasena` a la lista exacta de rutas fuera de `/api/v1`.

Antes de implementar fallaron PT-12 15, PT-12 19 y F1-H0-T03, por la ruta y las pilas que faltaban.

**Logs** (en `docs/reportes/logs/front-r2/`):
- `front-r2-pruebas-antes.log`: 12 fallaron y 34 pasaron.
- `front-r2-pruebas-despues.log`: 50 pasaron.
- `front-r2-verificar-1-phpstan.log`: `make verificar` **falló** en PHPStan por un error mío de tipo: `vistasDeModulo()` devolvía `array<int, string>` y no `list<string>`. Se corrigió con `array_values`. Código de salida 1, 164 s.
- `front-r2-verificar-2.log`: `make verificar` **pasó**. Pint sin cambios en 1027 archivos, PHPStan sin errores, 2410 pruebas y 15 635 aserciones, sin avisos de seguridad. Cobertura: Dominio 100,0 % (mínimo 95 %), Aplicación 100,0 % (85 %), Infraestructura y HTTP 99,5 % (70 %). Código de salida 0, 447 s.

Las dos corridas se hicieron con el comando de la receta (`composer verificar` con `bin/con-candado.sh`), en el proyecto `gestordep-front`, sobre el árbol de R2 sin commitear (base `e587b7c`).

## Comprobación en navegador (verificado)

Playwright contra `http://localhost:8001`, con `docente2@example.test` (usuario de `UsuariosDemoSeeder`). Capturas en `docs/reportes/capturas/front-r2/`.

| N.º | Paso | Resultado observado | Captura |
|---|---|---|---|
| 1 | `/contrasena` sin sesión | Redirige a `/ingreso` | — |
| 2 | Menú de usuario | Muestra «Cambiar contraseña» y «Cerrar sesión» | `01-menu-de-usuario.png` |
| 3 | `/contrasena` con sesión | Formulario con los dos campos y la nota de las demás sesiones; «Panel» no aparece activo | `02-pagina-cambiar-contrasena.png` |
| 4 | Enviar | Confirmación «¿Cambiar la contraseña?»; con Cancelar no sale ninguna solicitud | `03-confirmacion.png` |
| 5 | Envío vacío confirmado con doble clic | Un solo `PUT`, 422; un error debajo de cada campo | `04-422-campos-obligatorios.png` |
| 6 | Contraseña actual incorrecta | 422 sin campo; aviso «La contraseña actual no es correcta.» y contraseñas vaciadas | `05-422-contrasena-actual-incorrecta.png` |
| 7 | Contraseña nueva de 9 caracteres | 422 `contrasena_muy_corta` debajo de «Contraseña nueva» | `06-422-contrasena-muy-corta.png` |
| 8 | Cambio correcto | 204 y aviso de éxito; formulario vacío | `07-204-contrasena-cambiada.png` |
| 9 | Otra sesión abierta con curl antes del cambio | `GET /api/v1/sesion` pasa de 200 a 401 `no_autenticado`; la del navegador sigue con 200 al recargar | — |
| 10 | Pantalla de 375 px | 0 px de desbordamiento horizontal | `08-pantalla-375px.png` |
| 11 | La contraseña vuelve a su valor de fábrica desde la misma pantalla | 204 | — |
| 12 | Sin cookies (sesión perdida) y envío | `PUT` 401 y redirección a `/ingreso?expirada=1` con «Su sesión terminó» | `09-401-sesion-terminada.png` |

- **Consola:** sin errores de JavaScript. Solo aparecen los registros del navegador para los 422 y el 401 esperados, más el registrador de navegador de Laravel Boost, que existe solo en desarrollo y ya estaba en R1 (`consola.log`).
- **Red:** exactamente un `PUT` por envío confirmado (`red.log`).
- **No verificado en navegador:** `aviso()` (no tiene consumidor en R2; lo usa R3) y los mensajes de 403, 404, 409, 419, 429 y 5xx de `avisos.js`, porque `PUT /contrasena` no los produce.

## Criterios

- **Cumplidos:** CA-FOR-001, 002 y 003; CA-GEN-002, 003 (en los códigos que esta ruta produce), 004 y 005; CA-LAY-001.
- **CA-GEN-006:** sin errores de consola ni desbordamiento a 375 px.

## Lo que no se hizo

- No se agregó un campo de usuario oculto al formulario, aunque Chrome lo sugiere (mensaje de nivel *verbose*), porque CA-FOR-001 limita el formulario a los campos del contrato.
- No se modificaron `frontend/docs/` CA-AUT-002, REGLAS §5 ni ESPECIFICACION §6 y §9 (punto 9 de §10 del plan): no formaban parte de R2.

## Fuera de alcance (peticiones para el backend)

- **PB-06 (menor):** los mensajes de validación de `PUT /contrasena` dicen «El campo contrasena actual es obligatorio.» y «El campo contrasena nueva es obligatorio.», sin tilde y con el nombre técnico del campo. PT-12 lo resolvió para el ingreso con `custom` en `backend/lang/es/validation.php`, que es archivo del backend.

## Desviaciones

- **Estructura:** la vista y los scripts no quedaron donde decía §4 del plan, sino donde indica la orden de las 14:15 (`modulos/` y `comun/`). El plan ya lo refleja en §0.9.
- **`@stack('estilos')`:** la orden menciona `@yield('titulo')`, `@yield('contenido')` y `@stack('scripts')`. Mantengo además `@stack('estilos')` para cargar la hoja de SweetAlert2 solo donde se usa (DAF-07).
- **`avisos.js` expone sus funciones dentro de `window.Avisos`** (`Avisos.exito()`, etc.), no como funciones globales sueltas, para no chocar con otros nombres globales.
- **Cambios en `parciales/menu` y `sesion.js`:** no estaban listados en el plan; los exige la página nueva (sección activa y bienvenida inexistente).
