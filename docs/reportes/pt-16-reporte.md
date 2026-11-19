# PT-16: una visita anónima no escribe en `sessions`

Rama `tecnico/pt-16-sesiones-anonimas`, desde `origin/main` (`66edef2`, con el PR #21). Origen: auditoría dirigida de PT-12, hallazgo M-4 (H-02). Autorizado por el responsable el 19/11/2026.

## Cambio
`App\Http\Middleware\IniciarSesionSinGuardarAnonimas` extiende `StartSession` y lo sustituye en el grupo web con `$middleware->web(replace: [...])`, el mecanismo que documenta Laravel 13. Ocupa la misma posición: después de `EncryptCookies` y `AddQueuedCookiesToResponse`, antes de `ShareErrorsFromSession` y `PreventRequestForgery`.

Se aplica cuando se cumplen las tres condiciones:
1. El método es GET o HEAD.
2. La petición no trae cookie de sesión. Se evalúa después de `EncryptCookies`: una cookie que no se puede descifrar cuenta como ausente.
3. La ruta es `/`, `ingreso` o `panel`.

En ese caso la sesión se arranca en memoria, porque `ShareErrorsFromSession`, el CSRF y el guard la necesitan. Pero no se guarda, no se envía su cookie y no corre la recolección de basura. En cualquier otro caso se ejecuta `StartSession` sin cambios. `sanctum/csrf-cookie` queda fuera de la lista porque el ingreso necesita que cree la sesión.

`/panel` anónimo quedó cubierto sin cambiar `auth:web`: sigue respondiendo 302 a `/ingreso`. Solo se pierde el `url.intended` que guardaba `redirect()->guest()`, y la aplicación no lo usa: `ingreso.js` va siempre a `/panel`.

## Archivos tocados
**Creados**
- `backend/app/Http/Middleware/IniciarSesionSinGuardarAnonimas.php`.
- `backend/tests/Feature/Web/SesionesAnonimasTest.php`.
- Este reporte.
- En `docs/reportes/logs/pt-16/`: `pt-16-verificar-1.log`, `pt-16-verificar-2.log`, `pt-16-verificar-3.log`, `pt-16-verificar-4.log`, `pt-16-sin-middleware.log` y `pt-16-mutacion-filas.log`.

**Modificados**
- `backend/bootstrap/app.php`: la línea `web(replace: ...)` y sus dos `use`.
- `docs/trazabilidad.md`: fila de PT-16.

No se tocaron `routes/web.php`, `frontend/`, NiceAdmin, las rutas de `/api`, `config/`, migraciones ni `lang/`. **OpenAPI no cambia**: las tres rutas son web.

## Pruebas agregadas (10)
| Prueba | Antes de implementar |
|---|---|
| PT-16: GET / anónimo sin cookie redirige a /panel sin crear fila en sessions ni enviar cookie de sesión | Falló en `assertCookieMissing`: se enviaba la cookie de sesión |
| PT-16: GET /ingreso anónimo sin cookie responde 200 sin crear fila en sessions ni enviar cookie de sesión | Falló en `assertCookieMissing` (ídem) |
| PT-16: GET /panel anónimo sin cookie redirige a /ingreso sin crear fila en sessions ni enviar cookie de sesión | Falló en `assertCookieMissing` (ídem) |
| PT-16: una cookie de sesión que no se puede descifrar se trata como ausente y no crea fila | Falló: se creó 1 fila. Esa ejecución fue con la versión original de la prueba, sin aserción de cookie, y su salida no se archivó. La comprobación de filas tiene evidencia archivada en la mutación (ver abajo). |
| PT-16: HEAD /ingreso anónimo sin cookie responde 200 sin crear fila en sessions ni enviar cookie de sesión | Agregada en la corrección de la auditoría; falla sin el middleware (ver abajo) |
| PT-16: con la cookie de una sesión autenticada, GET /panel responde 200 y sigue guardando su fila | Pasaba (regresión) |
| PT-16: con la cookie de una sesión anónima existente, GET /ingreso responde 200 y la conserva | Pasaba (regresión) |
| PT-16: un usuario autenticado en GET /ingreso sigue redirigido a /panel | Pasaba (regresión) |
| PT-16: GET /sanctum/csrf-cookie sin cookie sigue creando la sesión y entregando sus cookies | Pasaba (regresión) |
| PT-16: una cookie de sesión válida con fila inexistente crea la fila con ese id, como hoy | Pasaba (regresión pedida por el responsable) |

En las pruebas 1 a 3 la primera aserción que falla es la de la cookie, así que el conteo de filas no llegó a evaluarse. La creación de la fila solo se observó directamente en la prueba 4.

En la primera corrida, las regresiones 6 y 7 fallaron por un error de la propia prueba. Comparaban `_previous.url` con `http://localhost/...`, pero el entorno de pruebas usa `APP_URL` con el puerto 8000. Se corrigieron con `url(...)` antes de implementar. Las pruebas de PT-12 (`IngresoTest`, `PanelTest`, `PaginasWebTest`, `ErroresWebTest`, `BootstrapLocalTest`) pasan sin cambios.

## Corrección de la auditoría
La auditoría de `c0f2a1d` dejó cuatro observaciones, corregidas en un commit aparte:
1. Se agregó la prueba de HEAD en `/ingreso` (prueba 5).
2. La prueba de la cookie que no se puede descifrar ahora también comprueba que la respuesta no envía la cookie de sesión.
3. El ayudante `abrirSesionAutenticada` quita la cabecera `Origin` con `withoutHeader('Origin')`, en lugar de dejarla vacía.
4. Se corrigió en este reporte la frase sobre por qué fallaban las pruebas 1 a 3.

**Pruebas sin el middleware.** Para comprobarlo, se borró temporalmente la línea `web(replace: ...)` de `bootstrap/app.php`, se ejecutó `SesionesAnonimasTest` y se restauró el archivo con `git checkout`. Salida: `docs/reportes/logs/pt-16/pt-16-sin-middleware.log`.
- Fallaron las pruebas 1 a 5, todas con «Cookie [gestordep-session] is present on response.».
- Pasaron las 5 de regresión.

Con la aserción de cookie agregada a la prueba 4, ahora también falla primero por la cookie. Por eso, sin el middleware, ninguna prueba llega a evaluar el conteo de filas.

**Mutación de la comprobación de filas.** Para comprobar que el conteo de filas detecta una escritura por sí solo, se modificó temporalmente el middleware: en la rama anónima guardaba la sesión (`saveSession`) pero no enviaba la cookie. Después se ejecutó solo la prueba 4 y se restauró el árbol con `git checkout`. Quedó sin diff en `app/`, `bootstrap/` ni `tests/` respecto a `22ea045`. Salida: `docs/reportes/logs/pt-16/pt-16-mutacion-filas.log`.
- La prueba 4 falló en `expect(filasDeSesion())->toBe(0)` con «Failed asserting that 1 is identical to 0.».
- Las aserciones de estado (200) y de cookie ausente pasaron antes: 3 aserciones en total.

## Resultado de `make verificar`
`docs/reportes/logs/pt-16/pt-16-verificar-1.log`: **pasó**, con los cambios sin commitear sobre la base `66edef2`.
- 2335 pruebas y 14064 aserciones.
- Pint y PHPStan sin errores.
- Sin avisos de seguridad.
- Cobertura: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,5 %.
- Código de salida 0, 395 s.

`docs/reportes/logs/pt-16/pt-16-verificar-2.log`, sobre las correcciones de la auditoría sin commitear (base `c0f2a1d`): **pasó**.
- 2336 pruebas y 14068 aserciones.
- Pint y PHPStan sin errores.
- Sin avisos de seguridad.
- Cobertura: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,5 %.
- Código de salida 0, 392 s.

`docs/reportes/logs/pt-16/pt-16-verificar-3.log`, sobre la base `d7f7eb7` más el cambio sin commitear del conteo de pruebas en `docs/trazabilidad.md`: **pasó**.
- 2336 pruebas y 14068 aserciones.
- Pint y PHPStan sin errores.
- Sin avisos de seguridad.
- Cobertura: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,5 %.
- Código de salida 0, 391 s.

`docs/reportes/logs/pt-16/pt-16-verificar-4.log`, sobre la base `22ea045` más las correcciones de este reporte y el log de mutación sin commitear (sin cambios en `app/`, `bootstrap/` ni `tests/`): **pasó**.
- 2336 pruebas y 14068 aserciones.
- Pint y PHPStan sin errores.
- Sin avisos de seguridad.
- Cobertura: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,5 %.
- Código de salida 0, 399 s.

El resultado de la corrida 4 se anotó en este reporte después de la corrida.

En la corrida 1, la fila de trazabilidad, este reporte y el log se agregaron después de la corrida; ninguna prueba lee esos archivos. El log se escribió fuera del repositorio y se copió sin cambios. `make humo` no se ejecutó (no se pidió). No se tocó ningún umbral ni regla de análisis.

## Riesgos
1. **Cookie de sesión en el navegador.**
   - Un visitante anónimo sin cookie ya no recibe `gestordep-session` al abrir `/`, `/ingreso` o `/panel`. La recibe al pulsar «Entrar», cuando `ingreso.js` llama a `/sanctum/csrf-cookie`.
   - Si el navegador ya trae una cookie válida, aunque sea sin usuario, la sesión se lee, se guarda y se renueva como antes.
   - Una cookie válida cuya fila ya no existe crea la fila con ese id, como hoy (prueba 10).
2. **`XSRF-TOKEN` de la visita anónima.** `PreventRequestForgery` sigue enviando esta cookie, pero pertenece a una sesión que no se guarda y no sirve. No rompe el ingreso, que pide una nueva a `/sanctum/csrf-cookie` antes del `POST`.
3. **`url.intended`.** Deja de guardarse para `/panel` anónimo. Si algún día se usa `redirect()->intended()`, hay que revisarlo.
4. **Escrituras que siguen.** `/sanctum/csrf-cookie` y `POST /sesion` siguen escribiendo una fila por llamada, porque lo necesitan. H-02 queda cerrado para las tres páginas, no para esos endpoints. El límite por IP (M-3) sigue siendo un riesgo aceptado aparte.
5. **Recolección de basura.** El sorteo no corre en las visitas anónimas; sigue corriendo en las demás peticiones.
6. **Lista de rutas fija.** Una página web nueva no queda cubierta hasta que se agregue a `PAGINAS`.

## Verificación en Chrome (pendiente, a cargo del responsable)
1. En una ventana privada, abrir `/ingreso`. En DevTools → Application → Cookies no debe aparecer `gestordep-session`.
2. Abrir `/panel`: debe redirigir a `/ingreso`.
3. Pulsar «Entrar» con un usuario de demostración: aparece `gestordep-session` y se abre `/panel`.
4. Recargar `/panel`: sigue en el panel.
5. Cerrar la sesión: vuelve a `/ingreso`.

## Lo que no se hizo
- No se limitaron las escrituras de `/sanctum/csrf-cookie` ni de `POST /sesion`.
- No se dejó de enviar `XSRF-TOKEN` en la visita anónima.
- No se hizo la verificación en Chrome, que queda a cargo del responsable.

## Fuera de alcance
- Nada nuevo detectado.

## Desviaciones
- `bootstrap/app.php`: la aprobación decía «una línea»; además de esa línea se agregaron dos `use`, para importar `IniciarSesionSinGuardarAnonimas` y `StartSession`, como hace el archivo con sus demás clases.

## Dudas
- Ninguna.
