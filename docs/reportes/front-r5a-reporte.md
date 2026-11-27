# FRONT-R5a: periodos, asignaturas y materias (docente)

Rama `frontend/blade-niceadmin`, sobre `aacb652` (R4 con PB-07). Orden del responsable del 27/11/2026, 10:28: las dos rebanadas siguientes de DAF-14 (R5a y R5b), con las mismas reglas. Entorno: proyecto Docker `gestordep-front`, web en `http://localhost:8001`. `worker` y `scheduler` siguieron apagados: ninguna comprobación de R5a depende del resumen académico ni de las alertas.

## Resultado

- **Menú:** encabezado «Académico», solo para el docente, con «Periodos escolares», «Asignaturas» y «Mis materias».
- **`/academico/periodos`:**
  - Alta con `POST /periodos-escolares` (nombre, fecha de inicio y fecha de fin).
  - Tabla paginada con `GET /periodos-escolares`: nombre, inicio, fin y «vigente hoy». Ordena solo por fecha de inicio, el único campo que admite la ruta, y lleva las dos descargas.
  - Eliminar pide confirmación de peligro y usa `DELETE /periodos-escolares/{id}`: es un borrado lógico sin forma de restaurarlo.
- **`/academico/asignaturas`:**
  - Alta con `POST /asignaturas` (clave, nombre y grado).
  - Tabla con `GET /asignaturas`, ordenable por clave y con descargas.
  - Eliminar, con la misma confirmación de peligro que los periodos.
- **`/academico/materias`:**
  - Alta con `POST /materias`, eligiendo asignatura y periodo de las listas de la API.
  - Tabla con `GET /materias`: clave, asignatura, periodo y criterio aplicado. Se filtra por periodo, sin orden (la ruta no lo ofrece) y con descargas; el título de la descarga lleva el periodo filtrado.
- **`/academico/materias/{materia}`:**
  - Datos y criterio aplicado, con `GET /materias/{materia}`.
  - Criterio propio con `PUT /materias/{materia}/criterio`. Pide confirmación antes de enviar, porque sustituye al vigente y recalcula los resúmenes de los inscritos.
  - Una materia ajena o inexistente recibe 404 y muestra «La materia no existe o no es suya».
- **`comun/formularios.js`:** `datos()` envía como número un campo `type="number"` con valor, porque `grado` es un entero en el contrato. Vacío lo envía como `""`, para que la API responda que falta.
- **`comun/avisos.js` (arreglo de seguridad):** `confirmar()` pasa el título como `titleText` y no como `title`, que SweetAlert2 interpreta como HTML. Los títulos llevan nombres que llegan de la API: categoría, jugador y ahora periodo y asignatura. Ver «Hallazgos».

## Archivos

**Creados**
- `backend/resources/views/modulos/{periodos,asignaturas,materias}/index.blade.php` y `modulos/materias/ver.blade.php`.
- `backend/public/assets/js/modulos/{periodos,asignaturas,materias}/index.js` y `modulos/materias/ver.js`.
- `backend/tests/Feature/Web/AcademicoTest.php`.
- Este reporte, `docs/reportes/capturas/front-r5a/` y `docs/reportes/logs/front-r5a/`.

**Modificados**
- `backend/routes/web.php` (cuatro rutas GET con `auth:web`; la de la materia con `whereUuid`) y `PaginaController` (`periodos()`, `asignaturas()`, `materias()` y `materia()`).
- `backend/resources/views/parciales/menu.blade.php`.
- `backend/public/assets/js/comun/formularios.js` y `comun/avisos.js`.
- `backend/lang/es/interfaz.php`: menú, `periodos`, `asignaturas`, `materias` y `js.academico`.
- Enmiendas sin debilitar:
  - PT-12 8 y 15, `PAGINAS_PROPIAS` de `PanelTest` y F1-H0-T03.
  - `paginasConContrato()` de `ContratoDeVistasTest`, con las cuatro páginas nuevas.
- Aserciones corregidas, que ahora prueban lo que dicen: FRONT-R2 13, FRONT-R3 14 y FRONT-R6 1. Ver «Hallazgos».
- `docs/trazabilidad.md`: cuatro filas en «Rutas web».

## Integración

- `GET`, `POST /periodos-escolares` y `DELETE /periodos-escolares/{id}`.
- `GET`, `POST /asignaturas` y `DELETE /asignaturas/{id}`.
- `GET /materias` (`filtro[periodo_id]`) y `POST /materias`.
- `GET /materias/{materia}` y `PUT /materias/{materia}/criterio`.

## Pruebas

| N.º | Prueba (`AcademicoTest`) | Antes de implementar |
|---|---|---|
| FRONT-R5a 1 | Las cuatro páginas sin sesión redirigen a `/ingreso` sin `data-endpoint` | Falló (404) |
| FRONT-R5a 2 | Con sesión, 200 con layout; la materia lleva `data-materia` y un id que no es UUID da 404 | Falló |
| FRONT-R5a 3 | Menú «Académico» solo para el docente, oculto hasta la sesión, con la sección activa (también en la ficha) | Falló |
| FRONT-R5a 4 | Cada formulario tiene exactamente los campos de su endpoint, con el tipo de control adecuado | Falló |
| FRONT-R5a 5 | Las tablas ordenan solo por `fecha_inicio` y `clave`; la de materias no ordena; la columna de acciones no se exporta | Falló |
| FRONT-R5a 6 | Textos de `lang/es/interfaz.php` | Falló |
| FRONT-R5a 7 | Orden de carga de SweetAlert2, avisos, formularios y el script del módulo; DataTables solo en las páginas con tabla | Falló |
| FRONT-R5a 8 | Eliminar periodo y asignatura piden confirmación de peligro, y el criterio pide confirmación antes del `PUT` | Falló (sin archivos) |
| FRONT-R5a 9 | `formularios.js` envía el número como número y lo vacío como `""` | Falló |
| FRONT-R5a 10 | `Avisos.confirmar` usa `titleText` y `avisos.js` no tiene `title:` ni `html:` | Falló (`title:`) |

**Enmiendas:** fallaron antes de implementar, por las rutas y los ítems nuevos, PT-12 8 y 15, F1-H0-T03 y FRONT-R3 9, que ya recorría las páginas nuevas. FRONT-R3 10 y 11 y FRONT-R6 10 pasaban antes, porque una página 404 no tiene controles; después revisan los formularios, filtros y columnas ordenables nuevos contra `openapi.yaml`.

**Después:** pasan las 93 pruebas web.

**`make verificar`** (`docs/reportes/logs/front-r5a/front-r5a-verificar.log`): **pasó.**
- Antes de correrlo, `docker compose -p gestordep top app` mostraba solo `boost:mcp` y `tail`, sin `composer`, `pest` ni `phpstan`.
- Pint y PHPStan, sin errores.
- 2497 pruebas y 17 449 aserciones.
- Cobertura: Dominio 100,0 %, Aplicación 99,7 % e Infraestructura y HTTP 99,5 %.
- Sin avisos de seguridad.
- Código de salida 0, en 437 s.

## Comprobación en navegador (verificado)

Playwright contra `http://localhost:8001`, como `docente@example.test`. Datos inventados por la propia interfaz:
- el periodo «Periodo R5a de prueba», del 2027-02-01 al 2027-07-15;
- la asignatura HIS-R5A «Historia de prueba R5a», grado 10;
- su materia en ese periodo, con criterio propio de 6.50;
- un periodo con HTML en el nombre, que se eliminó.

Capturas en `docs/reportes/capturas/front-r5a/`.

| N.º | Paso | Resultado observado | Captura |
|---|---|---|---|
| 1 | Menú del docente | «Académico» con sus tres ítems; «Plantel» oculto | `r5a-01-periodo-creado.png` |
| 2 | Periodo vacío | 422 con los tres errores, cada uno debajo de su campo | — |
| 3 | Fin anterior al inicio | 422 `rango_de_fechas_invalido`, sin campo: «La fecha de fin debe ser posterior a la fecha de inicio.» en el aviso del formulario | — |
| 4 | Alta del periodo | 201, aviso breve y la tabla recargada; «Vigente hoy: Sí» solo en el que contiene hoy | `r5a-01-periodo-creado.png` |
| 5 | Mismo nombre en otras mayúsculas | 409 «Ya existe un periodo escolar con ese nombre.» | — |
| 6 | Periodo con `<img onerror>` en el nombre | La celda y el título de la confirmación lo muestran como texto; no se crea ningún `<img>` ni se ejecuta nada. Confirmado: 204 y sale de la lista | `r5a-02-eliminar-periodo-titulo-como-texto.png` |
| 7 | Orden por fecha de inicio | `orden=fecha_inicio` en la solicitud | — |
| 8 | Asignatura sin grado; con grado 13; con grado 10 | Se envía `"grado": ""` y llega «obligatorio»; luego «debe estar entre 1 y 12»; al final se envía `"grado": 10` y llega 201 | — |
| 9 | Clave en minúsculas, duplicada | 409 «Ya existe una asignatura con esa clave.» | `r5a-03-asignatura-duplicada-409.png` |
| 10 | Materia: listas, alta, vacía y duplicada | Listas de la API; 201; 422 debajo de cada lista; 409 «Ya tiene una materia con esa asignatura en ese periodo.» | — |
| 11 | Filtro por periodo y descarga PDF | 1 de 1; el PDF se titula «Mis materias · Periodo R5a de prueba (…)», sin la columna de acciones | `r5a-04-materias-filtro-periodo.png`, `descargas/materias-2026-11-27.pdf` |
| 12 | Criterio 11 | Confirmación; luego 422 «La calificación debe estar entre 0 y 10.» debajo del campo | `r5a-05-confirmar-criterio.png` |
| 13 | Criterio 6.5 | Confirmación; 200; «6.50 (criterio propio de la materia)» | `r5a-06-criterio-propio.png` |
| 14 | Materia inexistente | 404: «La materia no existe o no es suya.», sin el contenido | — |
| 15 | Materias a 375 px | El documento mide 360 px y no se desborda; la tabla se desplaza dentro de su tarjeta | `r5a-07-materias-375px.png` |
| 16 | Entrenador, por la API | `GET /periodos-escolares` responde 403: la autorización es de la API | — |

**Consola:** ningún error de JavaScript. Los 10 registros de error son los 422, 409 y 404 provocados a propósito (`docs/reportes/logs/front-r5a/consola.log`). **Red:** cada escritura corresponde a una acción, y ninguna va por GET (`red.log`).

## Criterios

- **Cumplidos:**
  - CA-TAB-001 a 004.
  - CA-FOR-001 a 003: 409 de duplicados; 422 `rango_de_fechas_invalido` y `calificacion_fuera_de_rango`; confirmaciones antes de eliminar y de definir el criterio.
  - CA-GEN-001, 003, 005 y 006.
  - CA-LAY-002.

## Hallazgos (corregidos en esta rebanada)

1. **Pruebas que no probaban nada.** En Pest, `toContain(mixed ...$needles)` toma cada argumento como otra aguja. Por eso `->not->toContain('X', $mensaje)` pasaba siempre que el texto no contuviera el mensaje, aunque sí contuviera `X`. Afectaba a:
   - FRONT-R2 13: ninguna vista llama a `Swal`.
   - FRONT-R3 14: ninguna vista usa jQuery.
   - FRONT-R6 1: sin sesión no hay `data-endpoint`.
   - Dos aserciones del borrador de R5a.

   Se reescribieron como `expect(str_contains(...))->toBeFalse($mensaje)`. Siguen pasando con el código actual, así que no ocultaban ninguna infracción. Ninguna se debilitó.
2. **HTML de la API en las confirmaciones (R2 y R6).** `Avisos.confirmar` pasaba el título como `title`, que SweetAlert2 muestra como HTML. Un nombre de categoría o de jugador con etiquetas se habría interpretado al pedir la confirmación. Ahora usa `titleText`. FRONT-R5a 10 lo cubre, y el paso 6 del navegador lo comprueba.

## Lo que no se hizo

- **Recálculo del resumen académico tras definir el criterio:** no se comprobó. La materia de prueba no tiene inscritos y `worker` siguió apagado. El recálculo es de la API (`CriterioDeMateriaModificado`).
- **Inscripciones, calificaciones, asistencia de clase e historial:** son de R5b.

## Fuera de alcance

DataTables alinea a la derecha las columnas que detecta como fechas, y en ellas dibuja la flecha de orden al lado izquierdo de la celda. En «Periodos escolares» la flecha de «Fecha de inicio» queda pegada a «Nombre». Es el comportamiento de la biblioteca; no se tocó.

## Desviaciones

- **Ubicación de los archivos:** el plan nombraba `AcademicoController`, `views/academico/` y `paginas/academico-*.js`. Se usaron `PaginaController`, `modulos/{periodos,asignaturas,materias}/` y `assets/js/modulos/…`, por la estructura que ordenó el responsable el 24/11 a las 14:15, igual que en R6 y R4.
- **`datos()` no omite los campos vacíos:** omitirlos en general habría roto el `PATCH` de edición del jugador. Un nombre borrado dejaría de dar 422 «obligatorio» y se ignoraría en silencio. R5a solo convierte los números. Omitir los campos opcionales vacíos, marcados con `data-opcional`, llega con R5b, donde el contrato los tiene.
- **La ficha de la materia no carga DataTables en R5a:** no tiene tablas. Las suma R5b.
