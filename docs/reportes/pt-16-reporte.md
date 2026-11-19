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
- Este reporte y `docs/reportes/logs/pt-16/pt-16-verificar-1.log`.

**Modificados**
- `backend/bootstrap/app.php`: la línea `web(replace: ...)` y sus dos `use`.
- `docs/trazabilidad.md`: fila de PT-16.

No se tocaron `routes/web.php`, `frontend/`, NiceAdmin, las rutas de `/api`, `config/`, migraciones ni `lang/`. **OpenAPI no cambia**: las tres rutas son web.

## Pruebas agregadas (9)
| Prueba | Antes de implementar |
|---|---|
| PT-16: GET / anónimo sin cookie redirige a /panel sin crear fila en sessions ni enviar cookie de sesión | Falló (se enviaba la cookie y se creaba la fila) |
| PT-16: GET /ingreso anónimo sin cookie responde 200 sin crear fila en sessions ni enviar cookie de sesión | Falló (ídem) |
| PT-16: GET /panel anónimo sin cookie redirige a /ingreso sin crear fila en sessions ni enviar cookie de sesión | Falló (ídem) |
| PT-16: una cookie de sesión que no se puede descifrar se trata como ausente y no crea fila | Falló (1 fila) |
| PT-16: con la cookie de una sesión autenticada, GET /panel responde 200 y sigue guardando su fila | Pasaba (regresión) |
| PT-16: con la cookie de una sesión anónima existente, GET /ingreso responde 200 y la conserva | Pasaba (regresión) |
| PT-16: un usuario autenticado en GET /ingreso sigue redirigido a /panel | Pasaba (regresión) |
| PT-16: GET /sanctum/csrf-cookie sin cookie sigue creando la sesión y entregando sus cookies | Pasaba (regresión) |
| PT-16: una cookie de sesión válida con fila inexistente crea la fila con ese id, como hoy | Pasaba (regresión pedida por el responsable) |

En la primera corrida, las regresiones 5 y 6 fallaron por un error de la propia prueba. Comparaban `_previous.url` con `http://localhost/...`, pero el entorno de pruebas usa `APP_URL` con el puerto 8000. Se corrigieron con `url(...)` antes de implementar. Las pruebas de PT-12 (`IngresoTest`, `PanelTest`, `PaginasWebTest`, `ErroresWebTest`, `BootstrapLocalTest`) pasan sin cambios.

## Resultado de `make verificar`
`docs/reportes/logs/pt-16/pt-16-verificar-1.log`: **pasó**, con los cambios sin commitear sobre la base `66edef2`.
- 2335 pruebas y 14064 aserciones.
- Pint y PHPStan sin errores.
- Sin avisos de seguridad.
- Cobertura: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,5 %.
- Código de salida 0, 395 s.

La fila de trazabilidad, este reporte y el log se agregaron después de la corrida; ninguna prueba lee esos archivos. El log se escribió fuera del repositorio y se copió sin cambios. `make humo` no se ejecutó (no se pidió). No se tocó ningún umbral ni regla de análisis.

## Riesgos
1. **Cookie de sesión en el navegador.**
   - Un visitante anónimo sin cookie ya no recibe `laravel_session` al abrir `/`, `/ingreso` o `/panel`. La recibe al pulsar «Entrar», cuando `ingreso.js` llama a `/sanctum/csrf-cookie`.
   - Si el navegador ya trae una cookie válida, aunque sea sin usuario, la sesión se lee, se guarda y se renueva como antes.
   - Una cookie válida cuya fila ya no existe crea la fila con ese id, como hoy (prueba 9).
2. **`XSRF-TOKEN` de la visita anónima.** `PreventRequestForgery` sigue enviando esta cookie, pero pertenece a una sesión que no se guarda y no sirve. No rompe el ingreso, que pide una nueva a `/sanctum/csrf-cookie` antes del `POST`.
3. **`url.intended`.** Deja de guardarse para `/panel` anónimo. Si algún día se usa `redirect()->intended()`, hay que revisarlo.
4. **Escrituras que siguen.** `/sanctum/csrf-cookie` y `POST /sesion` siguen escribiendo una fila por llamada, porque lo necesitan. H-02 queda cerrado para las tres páginas, no para esos endpoints. El límite por IP (M-3) sigue siendo un riesgo aceptado aparte.
5. **Recolección de basura.** El sorteo no corre en las visitas anónimas; sigue corriendo en las demás peticiones.
6. **Lista de rutas fija.** Una página web nueva no queda cubierta hasta que se agregue a `PAGINAS`.

## Verificación en Chrome (pendiente, a cargo del responsable)
1. En una ventana privada, abrir `/ingreso`. En DevTools → Application → Cookies no debe aparecer `laravel_session`.
2. Abrir `/panel`: debe redirigir a `/ingreso`.
3. Pulsar «Entrar» con un usuario de demostración: aparece `laravel_session` y se abre `/panel`.
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
