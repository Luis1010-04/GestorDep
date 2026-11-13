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
- La única fecha por defecto de la especificación es la del alta manual, y no dice que valga para un cierre automático. Su criterio, citado:
  - `docs/hitos/f1-h5.md` §3.4, línea 133: «pone `fecha_alta` en la lesión (opcional en el cuerpo; por defecto, el hoy de negocio)». §6.2, línea 310, repite «por defecto, el hoy de negocio».
  - El «hoy de negocio» es un **día calendario, no hábil**. Lo define DD-36 (`docs/hitos/f1-h1.md`, línea 74): «el día en America/Mexico_City mediante `HoyDeNegocio` (Compartido); la zona sale de `config('app.zona_presentacion')`». La interfaz `backend/src/Compartido/Dominio/HoyDeNegocio.php` lo describe como «el día calendario vigente en su zona horaria, no en UTC (RNF-04)».
  - En el código, `backend/src/Salud/Aplicacion/DarDeAlta.php`, líneas 46 y 47: `$hoy = $this->hoyDeNegocio->hoy()->format('Y-m-d')` y `darDeAlta($fechaAlta ?? $hoy, $instante)`.
  - No hay calendario de días inhábiles en el proyecto, y no se inventa uno.
  - Corrección: la primera versión de este reporte decía «día hábil». Era un error de redacción; el criterio es el día calendario.

No se cambió `ConfirmarAviso`. Siguen las dos pruebas `DA-45` (`ConfirmacionYEstadoTest.php:366` y `:386`), que fallarían en cuanto se implemente el cierre y habrá que reescribir.

Lo que hace falta decidir:

1. **Fecha.** Opciones:
   - A, recomendada: el hoy de negocio de la confirmación (día calendario en America/Mexico_City, `HoyDeNegocio`, DD-36). Es el mismo criterio que el alta manual (§3.4), y la auditoría pidió usar el criterio de campos y de historial del alta.

   Luis indicó (13/11/2026) no implementar la fecha hasta confirmarla. `ConfirmarAviso` sigue sin cambios.

   **Decisión registrada (Luis, 13/11/2026):** DA-76 sigue pendiente de confirmación. Si Luis la confirma, la fecha del cierre será el hoy de negocio de `HoyDeNegocio` (DD-36; `docs/hitos/f1-h5.md` §3.4 y §6.2), no un calendario de días hábiles.
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
- **Docker: `make levantar` no reconstruye la imagen.** Docker Desktop se apagó antes del humo; lo más probable es que la computadora se suspendiera. Al volver, `make levantar` falló al construir: la versión nueva de compose pide `--allow=fs.read=…Dockerfile`. **`make humo` corrió con la imagen existente**, levantada con `docker compose up -d --no-build --wait`. C01 no toca el Dockerfile, así que la imagen corresponde al código. Por orden de Luis (13/11/2026), no se cambió ninguna configuración de Docker ni el `Makefile`.

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

## 7. Comprobaciones pedidas por Luis (13/11/2026)

1. **Códigos 409 de DA-79 en `docs/api/openapi.yaml`:**
   - `POST /avisos-lesion/{aviso}/confirmacion` (líneas 4893 a 4896): el 409 nombra `aviso_no_pendiente` y `conflicto`. Correcto.
   - `POST /avisos-lesion/{aviso}/descarte` (líneas 4821 y 4822): el 409 nombra **solo** `aviso_no_pendiente`. **Falta `conflicto`**, que el descarte puede responder desde C01 en el choque al guardar. El estado 409 y el esquema `Error` ya están documentados, así que las pruebas de contrato pasan. Lo que falta es el código en la descripción.
   - **Aplicado con la aprobación de Luis (13/11/2026):** a la descripción se le agregó «o hubo un choque con otra escritura (conflicto, DA-79)». Solo cambia la descripción; el estado y el esquema siguen iguales.
   - `GET /jugadores/{jugador}/avisos-lesion` no escribe, así que no responde 409.
2. **UUID v7 frente a `ConfirmacionYEstadoTest.php`, líneas 484 a 524.** **No las cubre todas.**
   - Esas líneas prueban UUID v4 en siete rutas: estado (GET y PATCH), alta, confirmación, lesiones del jugador y lesión (GET y PATCH).
   - La prueba nueva (línea 526) cubre con UUID v7 solo la **confirmación**.
   - Las otras dos rutas que cubre, descarte y avisos del jugador, tienen su caso v4 en `AvisosTest.php`, línea 296, no en 484 a 524.
   - **Agregado con la aprobación de Luis (13/11/2026):** la prueba «§6.1: en estado, alta, lesiones del jugador y lesión, un UUID v7 inexistente responde el mismo 404 que un UUID v4» (`ConfirmacionYEstadoTest.php`, después de la línea 546). Cubre:
     - estado, GET y PATCH;
     - alta;
     - lesiones del jugador;
     - lesión, GET y PATCH.

     Usa los mismos cuerpos que las líneas 511 a 524, y cada caso responde 404 `recurso_no_encontrado` con un cuerpo idéntico al de UUID v4, sin escribir nada.
   - **Pasa sin código:** el comportamiento ya existía. Con esto, las siete rutas de las líneas 484 a 524 tienen caso v7.
3. **Copia temporal sin bloqueo.**
   - La copia vivió solo dentro del contenedor (`/tmp/mut`, con el enlace `/tmp/docs` y el respaldo `/tmp/orig.php`) y el guion la borra al final. Después de la corrida, `ls` dentro del contenedor confirma que no existe ninguna de las tres rutas.
   - En el árbol del repositorio no hay ningún directorio `mut`, y `git status` no muestra archivos de la copia.
   - Lo único que quedó es el guion: `backend/build/mut-c01.sh`, ignorado por `.gitignore` (`/backend/build/`), y una copia versionada como evidencia en `docs/reportes/logs/mutaciones/f1-h5-c01-guion.sh`. Ninguno contiene código mutado.

### Corridas de la segunda entrega (13/11/2026)

| Corrida | Resultado | Log |
|---|---|---|
| PHPStan | sin errores | — |
| `make verificar` | código 0, 1 967 pruebas (12 216 aserciones), Pest 675 s, total 755 s (por debajo de 13 minutos) | `docs/reportes/logs/verificar/f1-h5-c01b-20261113-0502.log` |
| `make fresh` + `make humo` | código 0, 119 pasos OK, con la imagen existente | `docs/reportes/logs/humo/f1-h5-c01b-20261113-0515.log` |

### Push de 7ba90ac y feb4734 (13/11/2026)

- **Rama de origen:** los dos commits estaban en la rama local `auditoria/f1-h5`, no en `hito/f1-h5`. El reflog muestra un cambio de rama el 12/11/2026 a las 13:24, entre el pull y el inicio de C01. `auditoria/f1-h5` sale de `9972755`, la punta de `origin/hito/f1-h5`.
- **Cómo se subió:** con la aprobación de Luis, `git push origin feb4734:hito/f1-h5`. Fue un avance rápido (`9972755..feb4734`), sin force ni rebase, y no se tocó ninguna rama local.
- **Actions:**
  - La ejecución #87 (id 43417460822, commit `feb4734`, flujo `verificar`) se creó y arrancó a las 11:29:24 UTC, sin espera en cola. Estaba en curso al consultarla.
  - #85 y #86 siguen en cola desde el 11/11/2026; no se canceló nada.

## 9. DA-76: cierre automático de la lesión al confirmar `apto` (13/11/2026)

- **Decisión de Luis:** el cierre usa `HoyDeNegocio` (DD-36; `f1-h5.md` §3.4 y §6.2) y solo afecta a la lesión propia de quien confirma. Registro completo en `f1-h5-decisiones-del-agente.md`, DA-76.
- **Código:** `backend/src/Salud/Aplicacion/ConfirmarAviso.php`, método `cerrarLesionPropia`.
  - Si el estado nuevo es `apto`, bloquea la lesión del estado vigente (`buscarParaActualizar`).
  - Si la registró quien confirma y no tenía alta, la guarda con `darDeAlta(hoy de negocio)` mediante `guardarCambio`, que escribe el historial.
  - Todo ocurre dentro de la transacción de la confirmación. El dominio no cambió.
- **Contradicción resuelta con Luis:** la orden pedía mantener en verde las dos pruebas `DA-45`, pero la segunda (`ConfirmacionYEstadoTest.php:386`) exigía que, al confirmar `apto`, la lesión propia quedara **sin** alta. Luis aprobó reescribirla como prueba `DA-76`. La primera prueba `DA-45` (confirmar con un estado que no es `apto`) sigue igual y en verde.
- **Efecto nuevo a vigilar (DA-17):** si la lesión propia empieza después del hoy de negocio, confirmar `apto` responde 422 `rango_de_fechas_invalido`, igual que el alta sin fecha.

| Prueba (`ConfirmacionYEstadoTest.php`) | Sin código | Con código |
|---|---|---|
| «DA-45: confirmar otro aviso deja sin alta la lesión propia anterior…» | pasa | pasa |
| «DA-76 (sustituye a DA-45 en apto): confirmar apto cierra la lesión propia con el hoy de negocio…». Reloj a las 03:00 UTC del 16: el hoy de negocio es 2026-11-15, no el día UTC. Una fila `actualizar` en el historial con `fecha_alta` de nula a 2026-11-15; el alta responde 409 `sin_lesion_vigente` | **falla** (`fecha_alta` nula) | pasa |
| «DA-76 (RN-15, RN-16): confirmar apto no cierra la lesión de otro entrenador (DD-63)» | pasa | pasa |
| «DA-76: confirmar apto no toca una lesión propia que ya tenía alta» | pasa | pasa |

Las dos pruebas negativas pasan sin código porque antes no se cerraba nada. Las mutaciones prueban que detectan una implementación equivocada. Log: `docs/reportes/logs/mutaciones/f1-h5-c01-da76-20261113-0533.log`; guion: `f1-h5-c01-da76-guion.sh`. La copia temporal se borró y el log lo confirma.

| Mutación | La detecta |
|---|---|
| M1: sin el filtro del registrante | no cierra la lesión de otro entrenador |
| M2: sin el filtro de lesión ya cerrada | no toca una lesión que ya tenía alta |
| M3: día UTC en lugar de `HoyDeNegocio` | cierra con el hoy de negocio |
| M4: cierra también sin `apto` | la primera `DA-45` |
| M5: no cierra nunca | cierra con el hoy de negocio |

Corridas de DA-76:

| Corrida | Resultado | Log |
|---|---|---|
| PHPStan | sin errores | — |
| `make verificar` | código 0, 1 969 pruebas (12 230 aserciones), Pest 807 s, **total 868 s** | `docs/reportes/logs/verificar/f1-h5-c01-da76-20261113-0534.log` |
| `make fresh` + `make humo` | código 0, 119 pasos OK, con la imagen existente | `docs/reportes/logs/humo/f1-h5-c01-da76-20261113-0549.log` |

- **Tiempo de `make verificar`:** 868 s pasa el aviso de 13 minutos (780 s) y queda 32 s por debajo del alto de 15 (900 s). Pest subió de 675 s a 807 s con solo 2 pruebas más que la corrida anterior, así que la diferencia parece de carga de la máquina, no de la suite. Se reporta según la regla.
- **CI del push anterior:** la ejecución #87 (`feb4734`) terminó con éxito a las 11:36:55 UTC.

## 10. Fuera de alcance y observaciones para H7

- **Para H7: `make humo` exige `make fresh` antes.** Después de `make verificar`, la base queda con datos de prueba, y el humo de H1 falla en el paso 3 con 409 `categoria_duplicada` (log en `backend/build/f1-h5-c01-humo-sin-fresh-*`). T08 y C01 corrieron `make fresh` justo antes del humo, en el mismo log.
- **Para H7: `make levantar` no reconstruye la imagen** con la versión actual de compose (§5). No se tocó la configuración.
