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
1. `PT-11: POST /sesion sin Origin ni Referer responde 403 origen_no_permitido con el formato de error`
2. `PT-11: POST /sesion con un Origin que no es de un dominio con estado responde 403 origen_no_permitido`
3. `PT-11: el 403 de origen no cuenta un intento fallido ni consulta credenciales`
4. `PT-11: sin Origin ni Referer y con cuerpo inválido responde 422 validacion_fallida`
5. `PT-11: POST /sesion con Referer permitido y sin Origin sigue respondiendo 200`
6. `PT-11: POST /sesion con un Origin permitido no cambia su respuesta 200`
7. `PT-11: el 403 de POST /sesion cumple el contrato de OpenAPI`

Antes de implementar, las pruebas 1 y 2 fallaron con 500 (el error de hoy), la 3 con 401 (consultaba las credenciales) y la 7 con una excepción de validación (OpenAPI no declaraba el 403). **Las pruebas 4, 5 y 6 ya pasaban antes de implementar**: fijan comportamiento que no cambia (la validación va antes del controlador, y el `Referer` y el `Origin` permitidos siguen abriendo sesión). Se dejaron como regresión, no como prueba de lo nuevo. Usan `gestordep_pruebas`, no la base de desarrollo.

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
