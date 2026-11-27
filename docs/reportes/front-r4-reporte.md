# FRONT-R4: estatus, alertas y avisos de lesión (docente y entrenador)

Rama `frontend/blade-niceadmin`, sobre `7ad298e` (FRONT-R6). Orden del responsable del 25/11/2026: la segunda rebanada autorizada según DAF-14, con las mismas reglas que R2, R3 y R6. Entorno: proyecto Docker `gestordep-front`, web en `http://localhost:8001`. `worker` estuvo encendido solo durante la comprobación en navegador, porque estas pantallas dependen del resumen académico y de las alertas. Con autorización del responsable se corrió una vez `php artisan alertas:recalcular`, porque las alertas deportivas y de sanciones solo las recalcula ese comando del planificador; `scheduler` siguió apagado.

## Resultado

- **`/estatus`**, sobre `GET /estatus-de-jugadores`:
  - Paginada y sin orden, porque la ruta no lo admite. Lleva las dos descargas.
  - Las columnas siguen la forma por rol (DA-105, D12):
    - Comunes: jugador, matrícula, estado oficial (con la fecha estimada de regreso y «requiere actualización»), aviso pendiente y alertas, con su causa.
    - Solo el entrenador: el resumen académico (nivel de riesgo y promedio).
    - Solo el docente: «Mis materias», con calificación y si está aprobada o reprobada, tal como llega.
  - Filtros:
    - Equipo, para el entrenador, con sus equipos de `GET /equipos`.
    - Materia, para el docente, con sus materias de `GET /materias`.
    - Tipo de alerta, con los valores del `enum` del contrato.
- **Ficha del jugador**, con tres pestañas nuevas para docente y entrenador, cada una cargada al abrirla:
  - **Alertas:** `GET /jugadores/{jugador}/alertas`, con tipo, causa en texto, equipo y fecha. Cada quien ve las que le corresponden (DA-89, DA-90). Un 404 (jugador en baja o fuera de alcance, RN-23) muestra un mensaje neutro.
  - **Disponibilidad:** `GET /jugadores/{jugador}/estado-disponibilidad`, con estado, regreso estimado, «vigente desde», origen (registrado o apto por omisión) y si requiere actualización.
  - **Avisos de lesión:** alta con `POST` (nota, fecha y «lo reportó el propio jugador») y listado con `GET`, en una tabla **sin descargas** (DAF-16). Una nota nula se muestra como «No visible para usted» (DD-58).
- **Menú:** «Estatus de jugadores» para docente y entrenador.
- **`comun/estatus.js`**, nuevo: texto de una alerta (tipo y causa) y del estado oficial, compartido por el estatus y la ficha. Solo presenta lo que manda la API.
- **`comun/tabla.js`**: opción `descargas: false`, que quita los botones de descarga (DAF-16).
- **`comun/formularios.js`**: `datos()` envía las casillas como `true` o `false`.

## Archivos

**Creados**
- `backend/resources/views/modulos/estatus/index.blade.php`
- `backend/public/assets/js/modulos/estatus/index.js` y `backend/public/assets/js/comun/estatus.js`
- `backend/tests/Feature/Web/EstatusTest.php`
- Este reporte, `docs/reportes/capturas/front-r4/` y `docs/reportes/logs/front-r4/`

**Modificados**
- `backend/routes/web.php` (`GET /estatus`) y `backend/app/Http/Controllers/Web/PaginaController.php` (`estatus()`).
- `backend/resources/views/parciales/menu.blade.php` y `backend/resources/views/modulos/jugadores/ver.blade.php` (tres pestañas y el formulario de aviso).
- `backend/public/assets/js/modulos/jugadores/ver.js`, `backend/public/assets/js/comun/tabla.js` y `backend/public/assets/js/comun/formularios.js`.
- `backend/lang/es/interfaz.php`: estatus, alertas, avisos, disponibilidad y tipos de alerta.
- `backend/tests/Feature/Web/ContratoDeVistasTest.php`: `/estatus` en las pruebas de contrato. FRONT-R6 10 revisa ahora también los filtros de los GET, contra los `enum` de los parámetros.
- Enmiendas sin debilitar: PT-12 7, 8 y 15 y F1-H0-T03.
- `docs/trazabilidad.md`: fila `/estatus` y la de la ficha, ampliada.

## Integración

`GET /estatus-de-jugadores` (`filtro[equipo_id]`, `filtro[materia_id]`, `filtro[tipo_alerta]`, `pagina`, `por_pagina`), `GET /jugadores/{jugador}/alertas`, `GET /jugadores/{jugador}/estado-disponibilidad`, `GET` y `POST /jugadores/{jugador}/avisos-lesion`, `GET /equipos` y `GET /materias`.

## Pruebas

| N.º | Archivo | Prueba | Antes de implementar |
|---|---|---|---|
| FRONT-R4 1 | `EstatusTest` | `/estatus` sin sesión redirige sin contenido privado | Falló |
| FRONT-R4 2 | `EstatusTest` | Con sesión, 200 con layout, filtros y tabla | Falló |
| FRONT-R4 3 | `EstatusTest` | Ítem «Estatus de jugadores» para docente y entrenador, oculto hasta la sesión y activo en su página | Falló |
| FRONT-R4 4 | `EstatusTest` | Filtros exactos del contrato; el de equipo es del entrenador y el de materia del docente | Falló |
| FRONT-R4 5 | `EstatusTest` | La tabla no ofrece orden, lleva columnas por rol y su columna de acciones no se exporta | Falló |
| FRONT-R4 6 | `EstatusTest` | La ficha suma las tres pestañas para ambos roles; el formulario de aviso tiene los campos del contrato | Falló |
| FRONT-R4 7 | `EstatusTest` | La tabla de avisos no ofrece descargas (DAF-16) y `tabla.js` respeta `descargas: false` | Falló |
| FRONT-R4 8 | `EstatusTest` | Textos de `lang/es/interfaz.php` | Falló |
| FRONT-R4 9 | `EstatusTest` | Orden de carga de bibliotecas y scripts | Falló |

**Enmiendas:**
- FRONT-R6 10 (opciones fijas contra el `enum`) cubre ahora los filtros GET. **Pasó antes de implementar** porque `/estatus` daba 404 y no tenía filtros; después revisa el de tipo de alerta.
- FRONT-R3 9 falló antes de implementar, porque ya recorría `/estatus`.
- PT-12 8 y 15 y F1-H0-T03 fallaron por la ruta y el ítem nuevos.

En total fallaron 13 y pasaron 70 (`front-r4-pruebas-antes.log`). Después pasan las 83 (`front-r4-pruebas-despues.log`). PHPStan, sin errores sobre las pruebas antes de `make verificar`.

**`make verificar`** (`docs/reportes/logs/front-r4/front-r4-verificar-1-auditoria.log`): **falló solo en la auditoría de dependencias.**
- **Lo que pasó:** Pint sin cambios en 1043 archivos, PHPStan sin errores, 2485 pruebas y 17 050 aserciones. Cobertura: Dominio 100,0 %, Aplicación 99,7 % e Infraestructura y HTTP 99,5 %.
- **Lo que falló:** `composer audit` encontró 5 avisos de seguridad publicados el 25/11/2026 en tres paquetes:
  - `symfony/http-kernel`: CVE-2026-133743.
  - `symfony/mailer`: CVE-2026-133751 y CVE-2026-133755.
  - `symfony/routing`: CVE-2026-133746 y CVE-2026-133754 (instalada la 8.1.11; el arreglo está en la 8.1.12).
- Código de salida 1, 501 s.
- **No lo causa R4:** el CI de `main` en `b4ad2b3` (#41) falla por los mismos 5 avisos.
- **Arreglarlo exige actualizar `backend/composer.lock`, que es del backend.** No se tocó ni se debilitó la auditoría. Por decisión del responsable del 26/11/2026, R4 se commitea y se sube con este resultado documentado; el CI de la rama quedará en rojo solo por la auditoría, como `main`. Cuando `main` traiga la actualización, se fusiona en la rama del front y se vuelve a correr `make verificar` (PB-07).

## Comprobación en navegador (verificado)

Playwright contra `http://localhost:8001`. A los datos inventados de R3 y R6 se sumaron, por la API:

- un 3.00 de PRB-0002 en la materia del docente;
- un lugar «Cancha de prueba» y tres entrenamientos realizados del Equipo A (18, 20 y 22/11) con falta de PRB-0003;
- un aviso de PRB-0003, confirmado como «lesionado» con regreso estimado el 05/12/2026.

Con `worker` encendido se abrió la alerta académica de PRB-0002. Con la corrida única de `alertas:recalcular` se abrieron las de PRB-0003: acumulación de sanciones (3 faltas) y riesgo de baja deportiva. Capturas en `docs/reportes/capturas/front-r4/`.

| N.º | Paso | Resultado observado | Captura |
|---|---|---|---|
| 1 | Estatus del entrenador | 1010 registros; columnas comunes más «Resumen académico», sin «Mis materias»; filtro de equipo con sus 3 equipos | `01-estatus-entrenador.png` |
| 2 | Filtro «Acumulación de sanciones» | 1 de 1: PRB-0003 «Lesionado · regreso estimado 2026-12-05», con sus dos alertas y su causa en texto; la descarga Excel trae esa fila y las columnas visibles | `02-estatus-filtro-sanciones.png`, `descargas/` |
| 3 | Filtro «Riesgo de baja académica» | 1 de 1: PRB-0002, «Promedio: 3.00 · Nivel de riesgo: En riesgo · Materias reprobadas: 1» | — |
| 4 | Ficha de PRB-0003, pestaña Alertas | Las dos alertas con tipo, causa, «Equipo de prueba A» y fecha en la zona de presentación | `03-ficha-alertas-entrenador.png` |
| 5 | Pestaña Disponibilidad | «Lesionado», 2026-12-05, «vigente desde», «Registrado», no requiere actualización | `04-ficha-disponibilidad.png` |
| 6 | Pestaña Avisos y alta | Tabla sin botones de descarga; el alta envía `reportado_por_jugador: false` como booleano; 201 y el aviso aparece «Pendiente» | `05-ficha-avisos-entrenador.png` |
| 7 | Jugador en baja (PRB-2000) | Alertas: 404 y «No hay alertas que mostrar para este jugador.»; aviso: 409 «El jugador está en baja: no se pueden crear avisos.» | `06-aviso-jugador-en-baja-409.png` |
| 8 | Estatus del docente | Columna «Mis materias» (por ejemplo, «MAT-PRB: 3.00, reprobada»), sin resumen académico ni filtro de equipo; el filtro de materia envía `filtro[materia_id]` | `07-estatus-docente.png` |
| 9 | Ficha de PRB-0003 como docente | No ve las alertas deportivas (DA-90): «Sin alertas abiertas que usted pueda ver.»; ve la misma disponibilidad; no ve los avisos del entrenador (DA-25) | `08-ficha-avisos-docente.png` |
| 10 | El docente registra un aviso | 201; lo ve con su nota y «Registrado por usted: Sí» | `09-docente-crea-aviso.png` |
| 11 | El entrenador ve ese aviso | Nota «No visible para usted» (DD-58), «Registrado por usted: No»; el estatus pasa a «Aviso pendiente: Sí» | `10-entrenador-nota-no-visible.png` |
| 12 | Estatus a 375 px | 0 px de desbordamiento | `11-estatus-375px.png` |

**Consola:** ningún error de JavaScript. Los registros de error son los 403, 404, 409 y 422 provocados a propósito en R6 y R4: los archivos `consola.log` y `red.log` cubren la sesión entera del navegador. **Red:** cada escritura corresponde a una acción, y ninguna va por GET.

**Observación:** el registro de Playwright muestra una descarga de `/jugadores` como docente a las 10:59:19, mientras corría `make verificar` de R6 y yo no usaba el navegador. No la provoqué yo.

## Criterios

- **Cumplidos:**
  - CA-TAB-001 a 004.
  - CA-FOR-001 a 003: el alta de aviso no es destructiva, así que no lleva confirmación; el éxito solo aparece tras el 201.
  - CA-GEN-001 a 006.
  - CA-LAY-002.
- **Reglas de visibilidad comprobadas:** DA-105, DA-89, DA-90, DD-58 y DA-25.

## Lo que no se hizo

- **No se dejaron encendidos `worker` ni `scheduler`:** `worker` se apagó y se retiró al terminar la comprobación, y `scheduler` no se encendió.
- **Las acciones del entrenador sobre avisos y estado** (descartar, confirmar, cambiar estado y dar de alta) no se construyeron: son de R8b. Para la prueba, el aviso de PRB-0003 se confirmó por la API.

**Resolución (27/11/2026):** la actualización de Symfony llegó a `main` en `c419503` (#42). Esperé además a que el backend fusionara T08 (#43) y T09 (#44) y fusioné `main` en la rama del front (`9b4f84b`), sin conflictos. Instalé en el entorno del front las dependencias del `composer.lock` nuevo (`symfony/routing`, `http-kernel`, `http-foundation` y `mailer` a la 8.1.12). Antes de verificar, `docker compose -p gestordep top app` no mostraba `composer`, `pest` ni `phpstan` en el backend. `front-r4-verificar-2.log`: **`make verificar` pasó completo.** Pint sin cambios en 1045 archivos, PHPStan sin errores, 2487 pruebas y 17 082 aserciones, sin avisos de seguridad, cobertura 100,0 / 99,7 / 99,5 %, código de salida 0, 495 s.

## Peticiones para el backend

- **PB-07 (bloqueante para que `make verificar` pase en todas las ramas):** actualizar `symfony/http-kernel`, `symfony/mailer` y `symfony/routing` a versiones sin los 5 avisos del 25/11/2026 (`symfony/routing` 8.1.12 o superior) en `backend/composer.lock`. Hoy fallan por eso el CI de `main` y `make verificar` del front.

## Entorno

Al retomar la tarea el 26/11/2026, los contenedores del proyecto `gestordep-front` ya no existían, pero sus volúmenes sí (la base con los datos de prueba incluida). No fue una acción mía. Los recreé con `docker compose -p gestordep-front up -d --wait`, sin borrar nada, y comprobé que seguían los 1010 jugadores del entrenador y que pasaban las 83 pruebas web. El proyecto del backend se levantó en ese mismo momento y no lo toqué. `make verificar` de R4 se corrió con el backend levantado, con autorización del responsable.

## Desviaciones

- **`comun/estatus.js`** no estaba en el plan. Evita duplicar el formato de alertas y estado entre el estatus y la ficha.
- **La tabla del estatus no lleva `text-nowrap`:** el texto de las alertas es largo y se deja partir en varias líneas. Con `tablas.css`, la página no se desborda a 375 px.
