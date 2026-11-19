# PT-15: descartada (M-5 de la auditoría de PT-12)

Rama `tecnico/pt-15-descarte`, desde `origin/main` (`d398927`, con el PR #20). Origen: auditoría dirigida de PT-12, hallazgo M-5 (H-05). Descartada por el responsable el 19/11/2026 tras una investigación previa al push. No hay código en esta rama.

## Qué se descartó
La implementación vivió en la rama local `tecnico/pt-15-aviso-419`, commit `2eb9ff5`, que no se publicó. Contenía:
- El aviso de 419 en `backend/public/assets/js/sesion.js`.
- La clave `sesion_no_se_pudo_cerrar` en `backend/lang/es/interfaz.php`.
- La respuesta `'419'` de `DELETE /sesion` en `docs/api/openapi.yaml`.
- La prueba `backend/tests/Feature/Identidad/CerrarSesionSinTokenTest.php` (tres pruebas).
- La fila de PT-15 en «En revisión», el reporte de esa rama y sus dos logs de `make verificar`.

Nada de eso está en `main` ni en esta rama. El commit `2eb9ff5` queda solo en el reflog local.

## Por qué
`PreventRequestForgery` (en Laravel 13, la clase que ejecuta `ValidateCsrfToken` en las rutas stateful de Sanctum) deja pasar una petición sin validar el token cuando la cabecera `Sec-Fetch-Site` vale `same-origin`. Un navegador moderno envía esa cabecera en las peticiones a la misma página, incluso en `localhost` por HTTP. Por eso el 419 no se produce desde la propia aplicación: el responsable observó en el navegador un `DELETE /api/v1/sesion` con `X-XSRF-TOKEN` inválido y la respuesta fue 204. El caso de un token vencido termina en 401, que `sesion.js` ya maneja.

El aviso de 419 era código correcto pero casi inalcanzable desde el navegador. Por eso se descartó en lugar de dejar código sin camino real.

## Diagnóstico con curl (`localhost:8000`)
Cada caso con una sesión nueva del usuario de demostración `entrenador1@example.test`; la petición es `DELETE /api/v1/sesion` con `Origin: http://localhost:8000`.

| Caso | Cabeceras | Código |
|---|---|---|
| A | `X-XSRF-TOKEN` inválido, sin `Sec-Fetch-Site` | 419 |
| B | sin `X-XSRF-TOKEN`, sin `Sec-Fetch-Site` | 419 |
| C | `X-XSRF-TOKEN` inválido y `Sec-Fetch-Site: same-origin` | 204 |
| D | sin `X-XSRF-TOKEN` y `Sec-Fetch-Site: same-origin` | 204 |
| E | `X-XSRF-TOKEN` inválido y `Sec-Fetch-Site: cross-site` | 419 |

Los casos A, B y E devuelven `{"codigo":"solicitud_no_valida","mensaje":"La solicitud no es válida.","errores":[]}`. La prueba descartada cubría A y B, no el camino C que sigue un navegador.

## Qué queda abierto del M-5
El 419 no está documentado en `docs/api/openapi.yaml` para `DELETE /sesion`. Se considera no aplicable mientras la aplicación no tenga clientes que no envíen `Sec-Fetch-Site`. Si en el futuro aparece un cliente así (otro navegador, un script o una integración), habrá que documentarlo y reabrir el aviso.

## Lo que no se hizo
Nada de código: ni `sesion.js`, ni `interfaz.php`, ni `openapi.yaml`, ni pruebas. No se ejecutó `make verificar`, porque la rama solo cambia documentación.

## Desviaciones
Ninguna.
