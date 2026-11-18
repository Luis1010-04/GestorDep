# PT-10: GET /api/v1/sesion

Rama `tecnico/pt-10`, creada desde `origin/main` (`cf88542`). Tarea técnica aprobada por el responsable con tres decisiones: caso de uso `VerSesion`, 401 `no_autenticado` para el usuario con borrado lógico y sin extender el humo de H6.

## Archivos tocados
- `backend/src/Identidad/Aplicacion/VerSesion.php` (nuevo): caso de uso. Toma `UsuarioActual` y `RepositorioDeUsuarios::buscarPorId`; devuelve el `Usuario` o `null`.
- `backend/app/Http/Controllers/SesionController.php`: método `ver`. Traduce `null` a `AuthenticationException` (401 `no_autenticado`) y devuelve `UsuarioRecurso` con `Respuesta::conDatos`, la misma forma que `POST /sesion`.
- `backend/routes/api.php`: `GET /sesion` dentro del grupo `auth:web`.
- `docs/api/openapi.yaml`: operación `verSesion` (200 con `Usuario`, 401 con `Error`).
- `docs/trazabilidad.md`: sección nueva "Tareas técnicas" con la fila de PT-10.
- `backend/tests/Feature/Identidad/VerSesionTest.php` (nuevo).
- `docs/reportes/logs/pt-10/` (dos logs, ver abajo) y este reporte.

## Pruebas agregadas (`VerSesionTest`)
1. `PT-10: GET /sesion con sesión devuelve la misma forma de datos que POST /sesion`
2. `PT-10: GET /sesion sin sesión responde 401 no_autenticado`
3. `PT-10: GET /sesion tras cerrar la sesión responde 401 no_autenticado`
4. `PT-10: un usuario con borrado lógico y sesión viva recibe 401 no_autenticado`
5. `PT-10: VerSesion no devuelve usuario sin identificador ni con borrado lógico`
6. `PT-10: las respuestas 200 y 401 de GET /sesion cumplen el contrato`

Antes de implementar, las seis fallaron por la razón esperada: 405 (la ruta no existía para GET), clase `VerSesion` inexistente y «OpenAPI spec contains no such operation [/sesion,get]». Las pruebas usan la base de pruebas (`gestordep_pruebas`, forzada en `phpunit.xml`), no la de desarrollo.

## Resultado de `make verificar`
- Corrida 1: **falló** en PHPStan (`return.unusedType`) por mi prueba 5, que tenía una clase anónima cuyo `identificador()` nunca devolvía `null`. Código de salida 2 (el de `make`; composer devolvió 1), 47 s. Log: `docs/reportes/logs/pt-10/pt-10-verificar-1-phpstan.log`. Se conserva.
- Corrida 2: **pasó**. Pint sin cambios pendientes, PHPStan sin errores, 2282 pruebas y 13811 aserciones, sin avisos de seguridad. Cobertura: Dominio 100,0 % (mínimo 95 %), Aplicación 100,0 % (85 %), Infraestructura y HTTP 99,5 % (70 %). Código de salida 0, 433 s. Log: `docs/reportes/logs/pt-10/pt-10-verificar-2.log`.
- La corrección fue de la prueba (una sola clase anónima con identificador opcional). No se tocó ninguna regla de análisis ni umbral.
- Los logs abren con el commit base (`cf88542`, con cambios sin commitear) y cierran con el código de salida y los segundos. Cada corrida tiene su archivo.

`make humo`: no se ejecutó. Por decisión del responsable, el humo de H6 no se extiende, y el hito no lo exige a una tarea técnica.

## Lo que no se hizo
- No se extendió el humo (decisión 3).
- No se probó en navegador ni contra el servicio `web` levantado: la cobertura es de pruebas Pest.

## Fuera de alcance
- **Dato para H7 (no se arregla aquí):** un usuario desactivado (`activo = false`) conserva su sesión. `auth:web` no revisa `activo`, y el único filtro del modelo es el borrado lógico. Por lectura de código; no hay una prueba que lo fije. Con PT-10, `GET /sesion` le devolvería 200.
- El 401 por borrado lógico ya lo produce `auth:web` antes de llegar al caso de uso, porque `UsuarioModelo` oculta los usuarios con `eliminado_en`. La rama `null` de `VerSesion` es defensiva y se prueba directamente en la prueba 5.
- `frontend/docs/CRITERIOS_ACEPTACION.md` (CA-AUT-002) y `REGLAS_AGENTE_IA.md` §5 siguen diciendo que `GET /sesion` no existe; se actualizan en la rama del frontend, no aquí.

## Desviaciones
- **Formato del commit:** `CLAUDE.md` §4 pide `F1-HN-TNN: descripción`. Uso `PT-10: GET /api/v1/sesion`, igual que `PT-09:`, porque PT-10 es tarea técnica y no pertenece a un hito. Es una diferencia con la regla escrita.
- Los archivos sin seguimiento `backend/public/assets/` (Bootstrap para PT-12) y `frontend/NiceAdmin/` no entran en este commit.

## Dudas y decisiones que necesitan aprobación
- Aprobar el formato `PT-10:` del commit o pedir otro.
- Orden de fusión: PT-09 todavía no está en `main`. PT-10 sale de `origin/main` y no depende de PT-09, así que ambas pueden fusionarse en cualquier orden.
