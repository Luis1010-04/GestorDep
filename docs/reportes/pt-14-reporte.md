# PT-14: igualar el tiempo de respuesta de `POST /sesion`

Rama `tecnico/pt-14-tiempo-ingreso`, desde `origin/main` (`0cbf68d`, con el PR #18). Origen: auditoría dirigida de PT-12, hallazgo M-2 (H-08). Autorizado por el responsable.

## Cambio
En `IniciarSesion`, cuando el usuario no existe o está desactivado se llama a `$hasher->make($contrasena)` y después se lanza el mismo `CredencialesInvalidas`. Así esa respuesta cuesta un bcrypt, como la de una contraseña incorrecta (que cuesta un `check`). El texto y el 401 no cambian. Se eligió `make` y no una comparación con un hash ficticio fijo (decisión del responsable): sigue el costo configurado en `BCRYPT_ROUNDS` sin una constante que haya que regenerar.

## Archivos tocados
**Modificados**
- `backend/src/Identidad/Aplicacion/IniciarSesion.php`.
- `docs/trazabilidad.md`: fila de PT-14.

**Creados**
- `backend/tests/Feature/Identidad/IniciarSesionTiempoTest.php`.
- Este reporte y `docs/reportes/logs/pt-14/pt-14-verificar-1.log`.

No se tocaron `frontend/`, `lang/`, rutas, `docs/api/openapi.yaml` (el 401 ya está documentado y no cambia), migraciones, tablas ni configuración.

## Pruebas agregadas (5)
Usan un espía del `Hasher` que delega en el real y registra `make` y `check`; ninguna mide segundos.

| Prueba | Antes de implementar |
|---|---|
| PT-14: un correo inexistente genera un hash de la contraseña recibida y no compara | Falló (0 llamadas a `make`) |
| PT-14: un usuario desactivado genera un hash de la contraseña recibida y no compara | Falló (0 llamadas a `make`) |
| PT-14: una contraseña incorrecta compara una vez con el hash del usuario y no genera otro | Pasaba desde el inicio (regresión) |
| PT-14: la respuesta de un correo inexistente es idéntica a la de una contraseña incorrecta | Pasaba desde el inicio (regresión) |
| PT-14: una cuenta bloqueada responde 429 sin generar hash ni comparar (riesgo aceptado, DD-29) | Pasaba desde el inicio (fija el riesgo) |

## Resultado de `make verificar`
`docs/reportes/logs/pt-14/pt-14-verificar-1.log`: **pasó**, sobre la base `0cbf68d` con los cambios de PT-14 sin commitear. 2326 pruebas y 14030 aserciones, Pint y PHPStan sin errores, sin avisos de seguridad. Cobertura: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,5 %. Código de salida 0, 425 s. El log se escribió fuera del repositorio durante la corrida y se copió después sin cambios. `make humo` no se ejecutó (no se pidió). No se tocó ningún umbral ni regla de análisis.

## Riesgos aceptados (no resueltos)
- **Enumeración por 429 (DD-29).** Una cuenta bloqueada responde 429 sin generar hash ni comparar. Ese 429 solo ocurre si el correo existe, así que **revela que el correo existe**. Un atacante puede enumerar cuentas existentes provocando bloqueos con intentos fallidos. Es un riesgo aceptado bajo DD-29, **no resuelto** por PT-14. La prueba "cuenta bloqueada" documenta el comportamiento actual.
- El `UPDATE` de `guardarAcceso` (intento fallido o acceso correcto) sigue cambiando el tiempo de esas respuestas frente a las de correo inexistente.
- No se añade límite por IP: M-3 sigue aceptado y requiere aprobación aparte.
- Con `make`, el tiempo es igual solo mientras los hashes guardados tengan el mismo costo que `BCRYPT_ROUNDS` actual. Si ese valor cambia, los usuarios con hashes antiguos tardarán distinto hasta que se rehasheen.
- El tiempo no se mide: la igualdad se comprueba por las llamadas al `Hasher`, no por segundos.

## Lo que no se hizo
- PT-15 y PT-16 quedan para sus sesiones.
- No se agregó límite por IP ni se igualó el camino del 429.

## Fuera de alcance
- Nada nuevo detectado.

## Desviaciones
- Ninguna con la especificación aprobada.

## Dudas
- Ninguna.
