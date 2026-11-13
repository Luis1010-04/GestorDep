# F1-H5-C01: correcciones de la auditoría de T08

Rama `hito/f1-h5`, base `9972755`. Orden de Luis del 12/11/2026. Decisiones DA-76 a DA-79 en `f1-h5-decisiones-del-agente.md`.

Regla de la orden: los hallazgos del auditor son sospechas hasta reproducirlos, y una prueba nueva que pasa sin código cuenta como hallazgo. Todas las referencias de la auditoría coincidieron con el código antes de editar.

## 1. Hallazgos, decisión, prueba y resultado

| # | Hallazgo | DA | Prueba | Sin código | Resultado |
|---|---|---|---|---|---|
| 1 | Las pruebas de bloqueo de avisos, estados y lesiones solo revisaban que el SQL dijera `for update` | DA-79 | `RepositorioDeAvisosTest.php:130` y `RepositorioDeEstadosYLesionesTest.php:183` (dos casos: estado y lesión). Dos conexiones: la segunda, con `lock_timeout` de 200 ms, recibe 55P03 mientras la primera tiene la fila | Pasa: el bloqueo ya existía. Las mutaciones de §3 prueban que las tres miden el bloqueo | Reemplazan a las dos pruebas que revisaban el SQL. Producción sin cambios |
| 2 | Descarte y confirmación en paralelo: la segunda petición | DA-79 | `RepositorioDeAvisosTest.php:156`. La conexión A descarta y confirma; la B lee el aviso ya descartado y su confirmación se responde 409 `aviso_no_pendiente`. El historial queda en versiones 1 y 2 | Pasa: `AvisoNoPendiente` ya se respondía 409 | Comprueba también `EscritorDeHistorial.php`, líneas 48 a 50 (max + 1): con el bloqueo tomado, la versión no se repite. No cambia |
| 3 | Un choque de escritura en `guardarDescarte` o `guardarConfirmacion` llegaba como 500 (`FormatoDeError.php`, línea 121) | DA-79 | `RepositorioDeAvisosTest.php:187` (dos casos: descarte y confirmación). Un disparador de la prueba levanta `unique_violation` al insertar en el historial y se borra al final | **Falla con 500** | Corregido: `RepositorioDeAvisosSql` convierte `UniqueConstraintViolationException` en `EstadoConcurrente`, que ya es 409 `conflicto`, como `RepositorioDeEstadosSql`. El aviso queda `pendiente` |
| 4 | `RepositorioDeAvisosSql.php`, líneas 138 y 139, no normalizaba a UTC | — | `RepositorioDeAvisosTest.php:214`: zona `UTC` en `creado_en` y `actualizado_en` | **Falla** (`+00:00` en vez de `UTC`) | Corregido con `setTimezone(UTC)`, igual que estados, lesiones y mediciones |
| 5 | Listado de historial y vista del periodo con un tipo de prueba borrado | DA-77 | `PruebasFisicasTest.php:370`: el listado conserva la prueba del tipo borrado (total 2) y la vista del periodo ya no muestra el tipo | Pasa: el código ya hacía lo que dice DA-77 | Producción sin cambios, como pide la orden. La aserción queda explícita |
| 6 | El seeder de rangos no repone una fila borrada lógicamente | DA-78 | `RangoFaltanteTest.php:32`: el seeder corre dos veces, la fila de estatura sigue borrada (una sola fila, mismo `eliminado_en`) y la medición responde 422 `rango_no_configurado` | Pasa: es la lógica actual | Lógica sin cambios. Comentario en el seeder. Restauración manual en §4. `RangoFaltanteTest.php`, líneas 21 a 29, se mantiene |
| 7 | Aserción vacía `expect(true)->toBeTrue()` | — | `MedicionCorporalTest.php:92`: `exigir` no lanza `ValorFueraDeRango` con 20.00 y 200.00 (los límites de §5.2, DA-16) ni con 100.50 | Pasa: el código hace lo que dice el nombre | Aserción con valores concretos. El caso contrario ya lo cubre la prueba siguiente, «fuera del rango se señala el campo» |
| 8 | Faltaban pruebas con UUID v7 en las rutas que solo tenían UUID v4 | — | `ConfirmacionYEstadoTest.php:526`: confirmación, descarte y avisos del jugador con un UUID v7 inexistente responden el mismo 404 `recurso_no_encontrado` que con un v4, sin escribir nada | Pasa | Interpretación de «las tres rutas afectadas» en §5 |
| 9 | `Respuesta.php` cambió en T07 fuera de las rutas permitidas, sin anotarse | — | No aplica | No aplica | Anotado en `f1-h5-t07.md`, §5 |
| 10 | `f1-h5-progreso.md`, §17, decía que DA-65 estaba pendiente | — | No aplica | No aplica | Corregido: aprobada el 11/11/2026 y registrada |
| 11 | La lesión abierta al confirmar `apto` | DA-76 | Ninguna nueva | — | **Detenido**: ver §2 |

Pruebas que pasan sin código, por nombre:
- «DA-79: buscarParaActualizar bloquea el aviso…»
- «DA-79: las lecturas para actualizar bloquean la fila…» (estado y lesión)
- «DA-79: descarte y confirmación en paralelo…»
- «DA-77: con el tipo borrado…»
- «DA-78: correr el seeder de rangos dos veces…»
- «DA-16: el rango admite sus dos límites» (tres casos)
- «§6.1: en confirmación, descarte y avisos del jugador, un UUID v7 inexistente…»

En todas, el código ya hacía lo que la prueba fija; ninguna se ajustó para pasar.

## 2. DA-76: detenido en la fecha del cierre

La orden pide implementar el cierre y tomar la fecha de DD-63 y DA-45, o parar y reportar si el código necesita una fecha que no está en la especificación. El código la necesita y no está:

- Cerrar una lesión es escribir `fecha_alta`, columna obligatoria del alta (§3.4), que DA-17 exige que no sea anterior a `fecha_inicio`.
- DD-63 (§3.5) dice que la lesión del otro registrante «se conserva sin fecha de alta». DA-45 deja también sin alta la lesión propia. Ninguna de las dos define una fecha de cierre automático.
- La única fecha por defecto de la especificación es la del alta manual: el día hábil de la petición (§3.4). No dice que valga para un cierre automático.

No se cambió `ConfirmarAviso`. Siguen las dos pruebas `DA-45` (`ConfirmacionYEstadoTest.php:366` y `:386`), que fallarían en cuanto se implemente el cierre y habrá que reescribir.

Lo que hace falta decidir:

1. **Fecha.** Opciones:
   - A, recomendada: el día hábil de la confirmación. Es el mismo criterio que el alta manual (§3.4), y la auditoría pidió usar el criterio de campos y de historial del alta.
   - B: la `fecha_aviso` del aviso confirmado.
   - C: el día anterior al nuevo estado.

   B y C pueden quedar antes de `fecha_inicio` y chocar con DA-17.
2. **Alcance.** ¿Solo la lesión propia de quien confirma (lo que DA-45 dejaba abierto), o también la de otro registrante, que DD-63 protege? Recomiendo solo la propia, para no contradecir DD-63.

## 3. Mutaciones de las pruebas de DA-79

Se hicieron en una copia temporal dentro del contenedor, con controles antes y después. La copia se borró al terminar. Log: `docs/reportes/logs/mutaciones/f1-h5-c01-bloqueo-y-captura-20261112-1328.log`; guion: `f1-h5-c01-guion.sh` en la misma carpeta.

| Mutación | Falla | Pasan |
|---|---|---|
| Sin `lockForUpdate` en `RepositorioDeAvisosSql` | bloqueo del aviso | las otras dos |
| Sin `lockForUpdate` en `RepositorioDeEstadosSql` | caso «estado» | las otras dos |
| Sin `lockForUpdate` en `RepositorioDeLesionesSql` | caso «lesión» | las otras dos |
| Captura cambiada a `LogicException` en `RepositorioDeAvisosSql` | los dos casos de choque | — |
| Control final | — | las cinco |

## 4. DA-78: restauración manual de un rango borrado

`rangos_mediciones_corporales` no tiene historial ni ruta en la API (comentario de su migración, `2026_11_03_060400`). El seeder no repone una fila con `eliminado_en`, así que:

1. **Fila borrada lógicamente** (`eliminado_en` con valor):
   - Se restaura con una migración nueva en `database/migrations/condicion_fisica/` que ponga `eliminado_en` en nulo para ese `codigo`.
   - Esa migración la aprueba Luis antes de integrarse.
   - Ni Tinker ni SQL a mano: las reglas del proyecto solo admiten cambios por migraciones, seeders o casos de uso.
2. **Fila borrada físicamente** (no debería pasar; no hay `delete` sobre tablas de negocio): `make migrar` y el seeder la vuelven a crear con los valores de fábrica de §5.2, porque el seeder busca por `codigo`.
3. **Para comprobarlo:** registrar una medición. Mientras falte el rango responde 422 `rango_no_configurado` (DA-70); después responde 201.

## 5. Desviaciones y dudas

- **«Las tres rutas afectadas» (punto 9).** La auditoría no las nombra. Todas las rutas de H5 ya tenían caso con UUID v4. Elegí las tres rutas que pasan por `RepositorioDeAvisosSql`, el archivo que cambia en C01: confirmación, descarte y avisos del jugador. Si eran otras, se agregan.
- **«409 conflicto» (DA-79).**
  - La segunda petición que encuentra el aviso ya cambiado responde 409 con el código `aviso_no_pendiente`, el que define §6.3, no `conflicto`.
  - El código `conflicto` queda para el respaldo, es decir, el choque de escritura.
  - Leí «409 conflicto» como el estado HTTP. Cambiar el código de §6.3 rompería el contrato, así que no lo hice.
- **Rutas.** Solo se tocaron archivos citados, más el reporte y los logs. `FormatoDeError.php` no cambió: la captura está en el repositorio. OpenAPI sin cambios.
- **`make verificar` tardó 794 s**, por encima del aviso de 13 minutos (780 s) y por debajo del alto de 15. Pest tardó 740 s, con 8 pruebas más que en T08 (713 s).
- **Docker Desktop se apagó** antes del humo; lo más probable es que la computadora se suspendiera. Al volver, `make levantar` no pudo reconstruir la imagen: la versión nueva de compose pide `--allow=fs.read=…Dockerfile`. Se levantaron los contenedores con la imagen existente (`docker compose up -d --no-build --wait`), porque C01 no toca el Dockerfile. El `Makefile` está fuera de las rutas de C01, así que no lo cambié; queda anotado para Luis.

## 6. Corridas

| Corrida | Resultado | Log |
|---|---|---|
| PHPStan | sin errores | — |
| `make fresh` | correcto | — |
| `make verificar` | código 0, 1 966 pruebas (12 189 aserciones), Pest 740 s, total 794 s | `docs/reportes/logs/verificar/f1-h5-c01-20261112-1330.log` |
| `make fresh` + `make humo` | código 0, 119 pasos OK | `docs/reportes/logs/humo/f1-h5-c01-20261113-0346.log` |

Corridas que no cuentan, guardadas en `backend/build/`:
- Dos intentos de humo con Docker apagado: `f1-h5-c01-humo-sin-docker-1-*` y `f1-h5-c01-humo-sin-docker-2-*`.
- Un humo sin `make fresh` previo, que falló en H1 paso 3 con 409 `categoria_duplicada` porque `make verificar` deja la base con datos de prueba: `f1-h5-c01-humo-sin-fresh-*`.
- La repetición que pidió Luis, `fresh` + humo, código 0 y 119 OK: `f1-h5-c01-humo-repeticion-20261113-035702.log`.

## 7. Fuera de alcance

- El `Makefile` con la versión nueva de compose (§5).
- La orden pone `make humo` justo después de `make verificar`, pero el humo de H1 necesita la base recién sembrada. Convendría que el guion o la orden incluyeran `make fresh` antes del humo, como hizo T08.
