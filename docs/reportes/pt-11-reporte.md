# PT-11: error controlado de origen en POST /api/v1/sesion

Rama `tecnico/pt-11`, creada desde `origin/main` (`6fb4095`, que incluye PT-09 y PT-10). Plan aprobado con la opción A: sin middleware nuevo.

## Archivos tocados
- `backend/app/Exceptions/OrigenNoPermitido.php` (nuevo): excepción sin texto.
- `backend/app/Exceptions/FormatoDeError.php`: una rama nueva, `OrigenNoPermitido` responde 403 `origen_no_permitido`.
- `backend/app/Http/Controllers/SesionController.php`: `iniciar` comprueba `$solicitud->hasSession()` antes de `IniciarSesion` y lanza la excepción si no hay sesión.
- `backend/lang/es/errores.php`: clave `origen_no_permitido`.
- `docs/api/openapi.yaml`: respuesta 403 en `POST /sesion`.
- `docs/trazabilidad.md`: fila de PT-11 en «Hecha», con sus pruebas.
- `backend/tests/Feature/Identidad/OrigenNoPermitidoTest.php` (nuevo), este reporte y `docs/reportes/logs/pt-11/pt-11-verificar-1.log`.

No se tocó `docs/convenciones-api.md`, `frontend/` ni ningún otro endpoint.

## Pruebas agregadas (`OrigenNoPermitidoTest`, siete)
Numeración del plan aprobado: 1 a 6 son las seis del plan y la 7 es la que el responsable añadió como ajuste 1. El orden dentro del archivo es otro (allí la del 422 va cuarta).

| N.º | Prueba | Antes de implementar | Error observado antes |
|---|---|---|---|
| 1 | `PT-11: POST /sesion sin Origin ni Referer responde 403 origen_no_permitido con el formato de error` | Falló | `Expected response status code [403] but received 500.` |
| 2 | `PT-11: POST /sesion con un Origin que no es de un dominio con estado responde 403 origen_no_permitido` | Falló | `Expected response status code [403] but received 500.` |
| 3 | `PT-11: el 403 de origen no cuenta un intento fallido ni consulta credenciales` | Falló | `Expected response status code [403] but received 401.` (consultaba las credenciales) |
| 4 | `PT-11: POST /sesion con Referer permitido y sin Origin sigue respondiendo 200` | Pasaba desde el inicio (regresión) | — |
| 5 | `PT-11: POST /sesion con un Origin permitido no cambia su respuesta 200` | Pasaba desde el inicio (regresión) | — |
| 6 | `PT-11: el 403 de POST /sesion cumple el contrato de OpenAPI` | Falló | `ValidationException`. No capturé el texto del mensaje; atribuyo la falla a que `openapi.yaml` no declaraba el 403 de `POST /sesion`, pero no lo verifiqué contra el mensaje. |
| 7 | `PT-11: sin Origin ni Referer y con cuerpo inválido responde 422 validacion_fallida` | Pasaba desde el inicio (regresión) | — |

Las pruebas 4, 5 y 7 fijan comportamiento que no cambia: el `Referer` y el `Origin` permitidos siguen abriendo sesión, y la validación va antes del controlador. Se dejaron como regresión, no como prueba de lo nuevo. Las siete usan `gestordep_pruebas`, no la base de desarrollo.

## Resultado de `make verificar`
Pasó a la primera. Pint y PHPStan sin errores, 2289 pruebas y 13825 aserciones, sin avisos de seguridad. Cobertura: Dominio 100,0 % (mínimo 95 %), Aplicación 100,0 % (85 %), Infraestructura y HTTP 99,5 % (70 %). Código de salida 0, 432 s. Log: `docs/reportes/logs/pt-11/pt-11-verificar-1.log` (abre con el commit base y cierra con el código y los segundos). No se tocó ningún umbral ni regla de análisis.

`make humo`: no se ejecutó, por indicación del responsable.

## Lo que no se hizo
- Sin middleware (opción A): un cuerpo inválido sin origen responde 422, no 403. Queda fijado por la prueba 4 y escrito en la descripción del 403 de OpenAPI.
- No se probó en el navegador ni contra el servicio `web` levantado.
- No se ejecutó `make fresh`, `migrate:rollback` ni nada que borre datos.

## Fuera de alcance
- El 403 solo se aplica a `POST /sesion`. `DELETE /sesion` y `PUT /contrasena` sin origen siguen respondiendo 401 por `auth:web`, sin cambios.
- `docs/convenciones-api.md` no menciona `origen_no_permitido`: su tabla de 403 solo describe `sin_permiso`. No se tocó, por indicación del responsable; queda una laguna de documentación.
- `frontend/docs/ESPECIFICACION_FUNCIONAL.md` (§9) describe el requisito de origen; al rebasar la rama del frontend conviene revisar que diga 403 `origen_no_permitido` y no 500.
- Un 419 por CSRF vencido sigue sin documentarse en OpenAPI.

## Desviaciones
Ninguna respecto al plan aprobado. El formato de commit `PT-11:` es la excepción aprobada para tareas técnicas.

## Dudas
Ninguna.

## Verificación de la etiqueta `f1-h6` (18/11/2026)
- `git ls-remote --tags origin f1-h6` devuelve el objeto de etiqueta `6c40ede8669da2385205238ba46e0fabcacc399a`, y su referencia pelada `f1-h6^{}` apunta a `944079152cc14137d57da5fb64796269c4bed355` (9440791).
- `git rev-parse "f1-h6^{commit}"` da `944079152cc14137d57da5fb64796269c4bed355` (9440791). Coinciden.
- La etiqueta es anotada. Su autor es Luis Angel Gonzalez Yudho y la fecha de creación es 17/11/2026 22:06:26 -0600. El commit al que apunta (9440791) es de 17/11/2026 21:41:09 -0600, del mismo autor.
- **Quién la publicó en origin y cuándo:** no lo puedo determinar. Git no guarda la fecha del push, y no hay `gh` en esta máquina para consultar los eventos de GitHub. Solo puedo afirmar lo que hice yo: en toda esta sesión hice dos pushes, `git push -u origin tecnico/pt-09` y `git push -u origin tecnico/pt-10`, sin `--tags` ni `--follow-tags`. Su salida solo listó `[new branch]` y no `[new tag]`. No hay `push.followTags` en la configuración. Yo no creé ni publiqué `f1-h6`, así que no declaro incidente.
- Detalle propio: para esta verificación ejecuté `git fetch origin --tags`. Eso solo descarga y puede crear etiquetas locales; no publica nada. No sé si `f1-h6` ya existía localmente antes de ese fetch.
