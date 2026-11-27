# FRONT-R3: jugadores, lectura

Rama `frontend/blade-niceadmin`, sobre `692e8f8` (FRONT-R2). Orden del responsable del 24/11/2026: implementar R3 según el plan del PR #38, con la estructura de la orden de las 14:15 (`modulos/` y `comun/`), DataTables desde esta rebanada y DAF-09 ajustada («Descargar esta página» y «Descargar todo», con tope de 1000 filas y confirmación). Entorno: proyecto Docker `gestordep-front`, web en `http://localhost:8001`, con `worker` y `scheduler` apagados.

## Resultado

- **`/jugadores`** (docente y entrenador):
  - Búsqueda por matrícula con `GET /jugadores/busqueda`. Muestra nombres, apellidos y matrícula sin enlace, porque la API no da identificador ni alcance (RN-20).
  - Tabla «Mis jugadores» en modo servidor sobre `GET /jugadores`: `pagina` y `por_pagina` (15, 25, 50 o 100), `orden` por apellidos o nombres, y `filtro[equipo_id]` solo para el entrenador, con sus equipos de `GET /equipos`. El total sale de `meta`.
  - La columna «Equipos» solo la ve el entrenador: el docente recibe `equipos` vacío (RN-13).
- **`/jugadores/{jugador}`**, la ficha, con tres pestañas que piden sus datos la primera vez que se abren (D9):
  - Datos y equipos.
  - Situación académica: el resumen para todos y la tabla «Mis materias del periodo vigente» solo si la API manda `materias`, es decir, al docente del jugador (DD-48).
  - Asistencia a entrenamientos: solo para el entrenador; un 404 (DA-20) se muestra como «sin datos que usted pueda ver».
  - Un jugador inexistente o fuera de alcance muestra un aviso neutro, sin datos.
- **Descarga (F4, DAF-09 ajustada):**
  - Cada tabla ofrece «Descargar esta página» y «Descargar todo», en Excel y en PDF.
  - En modo servidor, «todo» confirma con SweetAlert2 cuántas filas bajará y luego pide las páginas una tras otra (`por_pagina=100`) con los mismos filtros y orden, hasta 1000 filas.
  - En las tablas sin paginar (materias), «todo» exporta las filas ya cargadas, con la misma confirmación y sin pedir nada a la API.
  - La columna de acciones no se exporta.
- **Ítem «Jugadores» en el menú lateral** para docente y entrenador: oculto hasta conocer la sesión y marcado como activo en sus páginas. `sesion.js` expone `window.Sesion.usuario` y muestra los elementos con `data-roles` (CA-LAY-002).
- **Bibliotecas:** DataTables 3.1.6 con su integración de Bootstrap 5, Buttons 4.1.4 con la suya, JSZip 3.10.2 y pdfmake 0.3.15, sin jQuery. Las cinco tienen su integridad npm verificada antes de copiarlas.

## Archivos

**Creados**
- `backend/resources/views/modulos/jugadores/{index,ver}.blade.php`
- `backend/public/assets/js/comun/tabla.js`, `backend/public/assets/js/modulos/jugadores/{index,ver}.js`
- `backend/public/assets/css/comun/tablas.css`
- `backend/public/assets/vendor/datatables/{js/dataTables.min.js, js/dataTables.bootstrap5.min.js, js/dataTables.buttons.min.js, js/buttons.bootstrap5.min.js, css/dataTables.bootstrap5.min.css, css/buttons.bootstrap5.min.css, License.txt}`
- `backend/public/assets/vendor/jszip/{jszip.min.js, LICENSE.markdown}`, `backend/public/assets/vendor/pdfmake/{pdfmake.min.js, vfs_fonts.js, LICENSE}`
- `backend/tests/Feature/Web/{JugadoresTest, ContratoDeVistasTest}.php`
- Este reporte, `docs/reportes/capturas/front-r3/` (capturas, consola, red y las descargas generadas) y `docs/reportes/logs/front-r3/`

**Modificados**
- `backend/routes/web.php`: `GET /jugadores` y `GET /jugadores/{jugador}` (`whereUuid`), con `auth:web`.
- `backend/app/Http/Controllers/Web/PaginaController.php`: métodos `jugadores()` y `jugador()`. Solo entregan la vista; los datos y el alcance los decide la API.
- `backend/resources/views/parciales/menu.blade.php`: ítem «Jugadores».
- `backend/public/assets/js/sesion.js`: `window.Sesion.usuario` y elementos por rol.
- `backend/lang/es/interfaz.php`: textos de jugadores, de las tablas y de las descargas.
- `backend/tests/Feature/Web/BibliotecasLocalesTest.php` (FRONT-R3 12 a 14).
- Enmiendas sin debilitar: `PanelTest` (PT-12 7 y 8), `PaginasWebTest` (PT-12 15) y `tests/Feature/EntornoDePruebasTest.php` (F1-H0-T03, ampliación autorizada).
- `docs/trazabilidad.md` (dos filas en «Rutas web»), `frontend/docs/ACTIVOS_NICEADMIN.md` (§4 y §4 bis) y `docs/reportes/front-plan-rebanadas.md` (DAF-18 a DAF-20).

## Integración

- `GET /api/v1/jugadores`, `/jugadores/busqueda`, `/jugadores/{jugador}`, `/jugadores/{jugador}/situacion-academica`, `/jugadores/{jugador}/asistencia-entrenamientos` y `/equipos`. Todas son lecturas: R3 no cambia ningún dato y ninguna escritura va por GET.

## Pruebas

| N.º | Archivo | Prueba | Antes de implementar |
|---|---|---|---|
| FRONT-R3 1 | `JugadoresTest` | `/jugadores` y la ficha sin sesión redirigen a `/ingreso` sin contenido privado | Falló: 404 |
| FRONT-R3 2 | `JugadoresTest` | `/jugadores` con sesión, 200 con layout, búsqueda y tabla | Falló: 404 |
| FRONT-R3 3 | `JugadoresTest` | La ficha lleva el identificador y la zona de presentación; un id que no es UUID da 404 | Falló |
| FRONT-R3 4 | `JugadoresTest` | El menú muestra «Jugadores» con `data-roles="docente entrenador"`, oculto, y activo en sus páginas | Falló |
| FRONT-R3 5 | `JugadoresTest` | La tabla solo ordena por apellidos y nombres, y la columna de acciones no se exporta | Falló |
| FRONT-R3 6 | `JugadoresTest` | Los textos salen de `lang/es/interfaz.php` | Falló |
| FRONT-R3 7 | `JugadoresTest` | Orden de carga de bibliotecas y scripts; hoja `tablas.css`; el panel y la contraseña no cargan DataTables | Falló |
| FRONT-R3 8 | `JugadoresTest` | `tabla.js` traduce la paginación al contrato y ofrece las dos descargas con tope de 1000 | Falló: archivo inexistente |
| FRONT-R3 9 | `ContratoDeVistasTest` | Cada `data-campo` existe en la respuesta 200 de su `data-endpoint` en `openapi.yaml` | Falló: 404 |
| FRONT-R3 10 | `ContratoDeVistasTest` | Cada control de formulario es un parámetro (GET) o un campo del cuerpo (escrituras) del contrato | **Pasó desde el inicio**: con las páginas de jugadores en 404 solo revisaba el formulario de `/contrasena`; ahora cubre también la búsqueda y el filtro |
| FRONT-R3 11 | `ContratoDeVistasTest` | Cada `data-orden` es un valor del parámetro `orden` de su endpoint | Falló |
| FRONT-R3 12 | `BibliotecasLocalesTest` | SHA-256 de los archivos de DataTables, Buttons, JSZip y pdfmake | Falló: archivos inexistentes |
| FRONT-R3 13 | `BibliotecasLocalesTest` | Versiones y licencias | Falló: archivos inexistentes |
| FRONT-R3 14 | `BibliotecasLocalesTest` | No hay jQuery en `public/` ni en las vistas | **Pasó desde el inicio**: es una guarda |

**Enmiendas a pruebas existentes** (ninguna se debilita):
- PT-12 8: el menú contiene exactamente «Panel» y «Jugadores».
- PT-12 7: excluye `/jugadores` de los activos.
- PT-12 15 y F1-H0-T03: agregan `jugadores` y `jugadores/{jugador}` a sus listas exactas.

Antes de implementar fallaron 15 y pasaron 49.

**Logs** (en `docs/reportes/logs/front-r3/`):
- `front-r3-pruebas-antes.log`: 15 fallaron y 49 pasaron.
- `front-r3-pruebas-despues.log`: 64 pasaron, tras las correcciones de la comprobación en navegador.
- `front-r3-verificar-1-phpstan.log`: `make verificar` **falló** en PHPStan por cuatro errores de tipo en mis pruebas nuevas: `valoresDeOrden()` no devolvía una `list`, `query()` puede devolver `false` y había un `@var $this` antes de un `foreach`. Se corrigieron sin cambiar lo que comprueban. Código de salida 1, 49 s.
- `front-r3-verificar-2.log`: `make verificar` **pasó**. Pint sin cambios en 1029 archivos, PHPStan sin errores, 2424 pruebas y 16 070 aserciones, sin avisos de seguridad. Cobertura: Dominio 100,0 % (mínimo 95 %), Aplicación 100,0 % (85 %), Infraestructura y HTTP 99,5 % (70 %). Código de salida 0, 485 s.

Las dos corridas usaron el comando de la receta (`composer verificar` con `bin/con-candado.sh`) en el proyecto `gestordep-front`, sobre el árbol de R3 sin commitear (base `692e8f8`).

## Comprobación en navegador (verificado)

Playwright contra `http://localhost:8001`. Datos creados por la API en el entorno del front, todos inventados:

- `entrenador1@example.test`: categoría «Sub-15 de prueba», equipos A (1003 jugadores) y B (7 jugadores), jugadores «Jugador Prueba 0001» a «1010» con matrículas `PRB-0001` a `PRB-1010`.
- `docente@example.test`: periodo vigente, asignatura `MAT-PRB`, una materia, inscripciones de PRB-0001 a 0003 y una calificación parcial de 8.50.

Capturas en `docs/reportes/capturas/front-r3/`; los archivos descargados, en su subcarpeta `descargas/`.

| N.º | Paso | Resultado observado | Captura |
|---|---|---|---|
| 1 | Listado del entrenador | 15 de 1010 registros, 68 páginas, «Equipos» visible, filtro con sus 2 equipos; `GET /jugadores?pagina=1&por_pagina=15` | `01-listado-entrenador.png` |
| 2 | Orden, 100 por página, página 2 y filtro B | `orden=apellidos`, `orden=-apellidos`, `por_pagina=100`, `pagina=2` y `filtro[equipo_id]=…` (vuelve a la página 1); 7 de 7 con el filtro | `02-filtro-equipo-orden-descendente.png` |
| 3 | «Descargar esta página», Excel y PDF (equipo B) | Excel: título con el filtro, mensaje «Página 1 de 1 · Filas: 7 de 7 …», encabezados sin «Acciones», 7 filas. PDF: el mismo contenido, en horizontal, de 1 página | `descargas/pagina-equipo-b-*` |
| 4 | «Descargar todo» con el filtro B | Confirmación «Se descargarán 7 filas.»; una solicitud con el filtro y `por_pagina=100`; Excel de 7 filas; aviso «Descarga generada.» arriba a la derecha | `03-confirmar-descargar-todo-equipo-b.png` |
| 5 | «Descargar todo» sin filtro (1010 jugadores) | Confirmación «Se descargarán las primeras 1000 filas de 1010 (tope de 1000).»; exactamente 10 solicitudes de 100, ninguna para la página 11; Excel de 1000 filas en 2,5 s; Cancelar no pide nada | `04-confirmar-descargar-todo-tope-1000.png`, `descargas/todo-tope-*.xlsx` |
| 6 | «Descargar todo» en PDF con orden descendente | 10 solicitudes con `orden=-apellidos`; PDF de 1000 filas (de «Prueba 1010» a «Prueba 0011») y 46 páginas, en 3,0 s | `descargas/todo-tope-desc-*.pdf` |
| 7 | Búsqueda «  prb-0002 » | 200; muestra apellidos, nombres y matrícula | `05-busqueda-encontrada.png` |
| 8 | Búsqueda de una matrícula inexistente | 404; aviso «No hay ningún jugador con esa matrícula.» | `06-busqueda-sin-resultado.png` |
| 9 | Búsqueda vacía | 422 debajo del campo | `07-busqueda-422.png` |
| 10 | Ficha del entrenador | Datos, equipo A y posición; académica sin tabla de materias; asistencia «Sin dato», 0, 0, 0; cada pestaña pide sus datos una sola vez | `08`, `09`, `10-ficha-entrenador-*.png` |
| 11 | Ficha de un id fuera de alcance | 404; aviso «El jugador no existe o no está disponible para usted.», sin datos | `11-ficha-no-disponible.png` |
| 12 | Listado a 375 px | 0 px de desbordamiento de la página; la tabla se desplaza dentro de su celda, una línea por fila | `12-listado-375px.png` |
| 13 | Listado del docente | 3 de 3; sin columna «Equipos» ni filtro; no pide `/equipos` | `13-listado-docente.png` |
| 14 | Ficha del docente | «No comparte ningún equipo con este jugador.»; sin pestaña de asistencia; tabla de materias: MAT-PRB, 8.50, mínimo 7.00, aprobada | `14-ficha-docente-academica.png` |
| 15 | «Descargar todo» en la tabla de materias (modo cliente) | «Se descargará 1 fila.»; ninguna solicitud a la API; Excel con la fila | `15-materias-descargar-todo.png` |
| 16 | 401 durante la tabla (cookies borradas) | Lleva a `/ingreso?expirada=1` con «Su sesión terminó» | `16-401-en-la-tabla.png` |
| 17 | Red cortada para `/api/v1/jugadores` | La tabla dice «No se pudieron cargar los registros.» y aparece el aviso de conexión con `icon: 'error'` | `17-error-de-carga-sin-red.png` |

**Defectos encontrados en el navegador y corregidos antes del commit:**
1. `exportOptions.columns: ':visible:not(.no-exportar)'` lanzaba `SyntaxError`: sin jQuery, `:visible` no es un selector del navegador. Se cambió por una función.
2. El menú de descarga quedaba abierto después de descargar: se activó `autoClose`.
3. El mensaje decía «Página 1 de 1s»: el reemplazo de `:pagina` se comía el inicio de `:paginas`. Ahora se reemplazan primero las claves largas.
4. A 375 px la página se desbordaba 136 px: se agregaron `tablas.css` y `text-nowrap`.
5. «Se descargarán 1 filas.»: se agregó el singular, y el mensaje de la descarga pasó a «Filas: N de T».

**Consola:** el único error de JavaScript de toda la sesión es el del punto 1, anterior a su corrección. Lo demás son los registros del navegador para los 401, 404, 422 y el corte de red provocados a propósito (`consola.log`, `red.log`).

## Criterios

- **Cumplidos:**
  - CA-TAB-001: estados de carga, vacío y error.
  - CA-TAB-002: `pagina` y `por_pagina` hasta 100, con el total de `meta`.
  - CA-TAB-003: solo los filtros y el orden que admite el contrato.
  - CA-TAB-004: DataTables 3 adaptado al contrato, sin confundirlo con simple-datatables y sin jQuery.
  - CA-GEN-001: los campos de las vistas se comprueban contra OpenAPI.
  - CA-GEN-002 a 006 y CA-LAY-002.
- CA-FOR-001 aplica a la búsqueda (FRONT-R3 10). No hay escrituras en R3, así que CA-FOR-003 no aplica.

## Lo que no se hizo

- **No se pidieron más de 1000 filas ni se ofreció descargar fuera del tope**, según la orden.
- **El resumen académico se ve «Sin dato» en el entorno del front.** Lo recalcula la cola (`CalificacionRegistrada`), y `worker` está apagado en ese entorno por orden del responsable. No es un defecto del front: con el worker encendido, la ficha mostraría lo que la API devuelva.
- **No se agregó la pestaña de historial**: es de R5b y R8.

## Fuera de alcance (peticiones para el backend)

- **PB-06 (menor, se amplía):** los mensajes de validación de `GET /jugadores/busqueda` («El campo matricula es obligatorio.») y de `PUT /contrasena` usan el nombre técnico del campo, sin tilde. Se corrigen en `backend/lang/es/validation.php`, que es del backend.

## Desviaciones

- **DAF-18 (decisión mía, a confirmar):** las fechas con hora se muestran en la zona de presentación de la aplicación, que la vista pasa en `data-zona`, con formato `es-MX`. DAF-13 solo fijaba las fechas `AAAA-MM-DD` y los decimales, que siguen tal como llegan.
- **DAF-19 (a confirmar):** hoja propia `assets/css/comun/tablas.css` y `text-nowrap`, por CA-GEN-006. La orden de las 14:15 no ubicaba los CSS propios; los puse en `assets/css/comun/`, en paralelo a `assets/js/comun/`.
- **`aviso()` se usa en R3** para «Descarga generada.» y «No hay filas para descargar.»: es su primer consumidor.
- **Las vistas repiten la lista de bibliotecas:** FRONT-R2 11 y 12 no permiten vistas de módulo con otros nombres ni `@include` de scripts.
