# Plan de F1-H5-T08: seguridad, RN-15 y RN-16 (y pendientes de H5)

**Fecha:** 11 de noviembre de 2026
**Rama:** `hito/f1-h5` · **Especificación:** `docs/hitos/f1-h5.md` v1.0.8, §7.3, §7.4, §7.8 y §8 (T08) · **Pendientes:** `docs/reportes/f1-h5-progreso.md` §7 y §10, y los reportes de T02, T03, T05 y T06
**Estado:** **a la espera de la aprobación de Luis.** T08 no empieza hasta que la apruebe. Las decisiones de la sección 3 están abiertas.

## 1. Qué prueba T08

T08 no agrega rutas ni funciones nuevas. Su trabajo es comprobar que lo construido en T01 a T07 se comporta como dice la especificación frente a un usuario que no debe ver o cambiar algo. Se divide en tres bloques:

- **Tablas de seguridad:** §7.3 (RN-16, 5 filas), §7.4 (RN-15, 7 filas) y §7.8 (alcance y privacidad, 6 filas).
- **Mutación de los filtros de alcance:** cada filtro de H5 se rompe a propósito en una copia temporal, para comprobar que alguna prueba lo detecta. Es lo que falta desde T02 (`f1-h5-t02.md` §3).
- **Pendientes acumulados:** los que los reportes anteriores dejaron para T08 (ver sección 3).

## 2. Qué se reutiliza

- Las pruebas que ya existen en T02, T03, T05 y T06. T08 no las duplica: las enlaza con la fila de la tabla que cubren, y solo agrega las que faltan.
- El puerto `EstadoDeDisponibilidadOficial` (T04) y la lectura de `avisos_lesion` (T02).
- Los mensajes de error de `lang/es/errores.php`. No se agregan claves nuevas salvo que una decisión lo pida.

## 3. Decisiones que necesito

| Propuesta | Vacío | Recomendación |
|---|---|---|
| **DA-70.** Rango faltante en `rangos_mediciones_corporales` (hoy 500) | T05 lo dejó para T08 porque el plan lo aprobó así | Responder 422 con código `rango_no_configurado` y un mensaje claro. Así un fallo de datos de fábrica no aparece como error del servidor. Son 2 pruebas y una clave nueva en `lang/es` |
| **DA-71.** IMC que no cabe en `numeric(5,2)` (hoy 500 con 200 kg y estatura menor de 44.7 cm) | Hoy el mínimo sembrado es 100 cm, así que no pasa con los rangos actuales | Validar el IMC en el caso de uso antes de guardar y responder 422 `valor_fuera_de_rango`. Protege aunque alguien cambie los rangos. Una prueba de límite |
| **DA-72.** Desempate por `id` cuando dos registros tienen el mismo milisegundo (observación de RN-15 y DD-56) | El generador `Identificador::generar()` no tiene contador monotónico | No cambiar el generador en H5. Las pruebas fijan los ids (ya lo hacen RN-15 y DD-56). Anotarlo como decisión para H7 si el orden en producción importa |
| **DA-73.** `ConvocatoriaTest`, líneas 169 a 170 | Ordena por `creado_en` y luego por `id` generado por el código | Fijar los ids en la prueba, igual que RN-15 y DD-56. Solo cambia la prueba, no el código |
| **DA-74.** Concurrencia real (dos peticiones simultáneas) | Pest no paraleliza peticiones HTTP. Hay que escoger cómo probarla | Dos transacciones con conexiones distintas a PostgreSQL, en una prueba de integración, que confirme que la segunda recibe 409 `conflicto` o `prueba_ya_registrada` y no 500. No es una prueba HTTP completa |
| **DA-30.** Límite del 409 por confirmar y cambiar el estado en el mismo segundo (T03 y DA-30) | Ya está anotado para revisar | Comprobar que llega como 409 y no como 500 en los tres flujos, y dejar la solución como está. Sin cambio de contrato |

Lo que ya está decidido y no cambia en T08: DA-45 (la lesión propia no admite alta por la ruta del jugador), DA-58 a DA-67 y DD-63.

## 4. Pruebas primero

| Archivo | Cubre | Falla esperada |
|---|---|---|
| `tests/Feature/Seguridad/RecorridoDeAlcanceH5Test.php` | §7.8 filas 1 a 6, con rutas de H5 y sus recursos ajenos, ids inexistentes y UUID v4 | Solo las filas que aún no tienen prueba equivalente |
| `tests/Feature/Salud/RegistroRN16Test.php` | §7.3 filas 1 a 5, donde no existan ya en `ConfirmacionYEstadoTest` | Clase no encontrada, si es nueva |
| `tests/Feature/Salud/RegistroRN15Test.php` | §7.4 filas 1 a 7, donde no existan ya en `ConfirmacionYEstadoTest` y `DetalleDeLesionTest` | Clase no encontrada, si es nueva |
| `tests/Feature/CondicionFisica/RangoFaltanteTest.php` | DA-70: fila borrada da 422 `rango_no_configurado` | 500 hoy |
| `tests/Unit/CondicionFisica/ImcTest.php` | DA-71: IMC en el límite y por encima | Caso que hoy da 500 |
| `tests/Integration/CondicionFisica/ConcurrenciaTest.php` | DA-74: dos transacciones, una gana y la otra recibe 409 | Según el caso |
| `tests/Feature/Competencia/ConvocatoriaTest.php` | DA-73: líneas 169 a 170 con ids fijos | Prueba sin cambio de comportamiento |

**Mutación de filtros:** cada filtro de alcance de H5 se rompe en una copia temporal, con control antes y después (como en T06). Cada mutación debe hacer fallar al menos una prueba. Se registra en `docs/reportes/logs/mutaciones/`.

**Regla:** si una fila de §7.3, §7.4 o §7.8 ya tiene una prueba equivalente, se enlaza en el reporte y no se duplica.

## 5. Archivos de producción

- **Solo si DA-70 o DA-71 se aprueban:**
  - `src/CondicionFisica/Aplicacion/` para validar el IMC en el caso de uso (DA-71).
  - El manejo de rango faltante en el caso de uso o en el catálogo, con la clave nueva en `lang/es/errores.php` (DA-70).
- **Sin cambios de producción si no se aprueban:** el resto de T08 es solo pruebas y mutación.
- **Contrato:** `docs/api/openapi.yaml` solo cambia si DA-70 agrega un código nuevo a una ruta existente.
- **Sin migraciones.**

## 6. Cierre

- **Corridas, en serie:** PHPStan tras editar pruebas, `make verificar`, `make mutacion`, `make fresh` y `make humo`, más la mutación de filtros en copia temporal.
- **Logs:** los que respaldan el reporte, en `docs/reportes/logs/`; los demás, en `backend/build/`. Ninguno se borra.
- **Tiempo:** aviso a los 13 minutos y alto a los 15, como en T06 y T07. T08 agrega pruebas; si la corrida pasa de 13 minutos, se reporta.
- **Reporte:** `docs/reportes/f1-h5-t08.md`, con cada fila de §7.3, §7.4 y §7.8 enlazada a su prueba, y cada pendiente cerrado o anotado para H7.
- **Commit:** `F1-H5-T08: seguridad, RN-15 y RN-16`, local, sin push. Atribución como en T06 y T07.

## 7. Riesgos

- **Crecimiento de la corrida:** T08 agrega pruebas a una suite que ya tarda unos 13 minutos. Se mitiga reutilizando pruebas existentes y anotando las duplicadas en el reporte.
- **Mutación de filtros en copia temporal:** puede dejar archivos sueltos. Se trabaja en una copia y se borra al terminar, sin tocar el árbol del repositorio.
- **Concurrencia real con PostgreSQL:** una prueba con dos conexiones puede ser lenta o inestable. Si resulta inestable, se reporta y no se da por cerrada.
- **Cambiar el contrato por DA-70:** un código nuevo en una ruta existente es aditivo. Si Luis prefiere mantener el 500 para ese caso, se anota como límite conocido.
