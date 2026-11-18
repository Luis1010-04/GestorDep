# PT-13: un usuario desactivado pierde la sesión viva

Rama `tecnico/h7-seguridad`, desde `origin/main` (`6d996c3`, con el PR #16). Origen: auditoría dirigida de PT-12, hallazgo M-1 (H-03). Autorizado por el responsable el 18/11/2026.

## Archivos tocados
**Modificados**
- `backend/src/Identidad/Infraestructura/Modelos/UsuarioModelo.php`: nuevo scope global `activos` (`activo = true`) junto al de `eliminado_en`; el docblock lo menciona.
- `backend/src/Identidad/Aplicacion/VerSesion.php`: devuelve `null` si el usuario no está activo.
- `backend/tests/Feature/Identidad/VerSesionTest.php` y `backend/tests/Integration/Identidad/UsuarioActualDeSesionTest.php`: pruebas de PT-13.
- `docs/trazabilidad.md`: fila de PT-13.

**Creados**
- Este reporte y `docs/reportes/logs/pt-13/` (dos logs de `make verificar`).

No se tocaron rutas, `docs/api/openapi.yaml` (el 401 de `GET /sesion` ya está documentado), migraciones ni tablas: `usuarios.activo` ya existía.

## Pruebas agregadas (8)
| Archivo | Prueba | Antes de implementar |
|---|---|---|
| `VerSesionTest` | PT-13: un usuario desactivado con sesión viva recibe 401 `no_autenticado` en `GET /sesion` | Falló (200) |
| `VerSesionTest` | PT-13: ... recibe 401 en una ruta protegida por `auth:web` (`GET /jugadores`) | Falló (200) |
| `VerSesionTest` | PT-13: ... recibe 401 y no 403 en una ruta con rol (`GET /categorias`, usuario solo docente) | Falló (403) |
| `VerSesionTest` | PT-13: un usuario activo conserva su sesión en `GET /sesion` y en una ruta con rol | **Pasaba desde el inicio** (regresión) |
| `VerSesionTest` | PT-13: `GET /panel` con la sesión de un usuario desactivado redirige a `/ingreso` y `/ingreso` no redirige (sin bucle) | Falló (200) |
| `VerSesionTest` | PT-13: `VerSesion` no devuelve al usuario desactivado | Falló (devolvía al usuario con `activo = false`) |
| `UsuarioActualDeSesionTest` | PT-13: un usuario desactivado no se recupera para autenticar | Falló |
| `UsuarioActualDeSesionTest` | PT-13: con la sesión de un usuario desactivado no hay usuario actual ni roles | Falló |

Las desactivaciones se hacen por la conexión del propietario, como un cambio manual de `activo`.

## Resultado de `make verificar`
1. `docs/reportes/logs/pt-13/pt-13-verificar-1-phpstan.log`: **falló**. PHPStan: el tipo de retorno `?Identificador` de una clase anónima de mi prueba nunca es nulo. Código de salida 2, 29 s. Se corrigió la prueba.
2. `docs/reportes/logs/pt-13/pt-13-verificar-2.log`: **pasó**. 2321 pruebas y 14014 aserciones, Pint y PHPStan sin errores, sin avisos de seguridad. Cobertura: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,5 %. Código de salida 0. Los 1974 s incluyen una pausa pedida por el responsable durante la corrida; no son el tiempo real de la suite (unos 430 a 520 s en corridas anteriores).

Ambas corridas fueron con los cambios sin commitear, sobre la base `6d996c3`. `make humo` no se ejecutó (no se pidió). No se tocó ningún umbral ni regla de análisis.

## Lo que no se hizo
- No se purgan las filas de `sessions` del usuario desactivado: la sesión sigue guardada y responde 401 hasta caducar.
- No existe un caso de uso para desactivar o reactivar usuarios (hoy solo se crean con `usuarios:crear`); no se creó.
- PT-14, PT-15 y PT-16 quedan para sus sesiones.

## Fuera de alcance
- Si algún día se añade «reactivar usuario», el scope `activos` ocultaría al usuario desactivado en `UsuarioModelo`; esa operación tendría que usar el repositorio y no el modelo de autenticación.

## Desviaciones
- Ninguna con la especificación. Detalle de proceso: la configuración de `--output-dir` de Playwright se aplicó en `~/.claude.json` (fuera del repositorio) con la ruta `C:/Users/LOQ/AppData/Local/ms-playwright-mcp/salida`, elegida por mí porque la orden no daba una; y se borró `.playwright-mcp/`, que solo tenía un log de consola y un snapshot.

## Dudas
- Ninguna.
