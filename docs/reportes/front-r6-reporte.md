# FRONT-R6: plantel (entrenador)

Rama `frontend/blade-niceadmin`, sobre `bcfdb86`, que es la fusión de `main` con T06, T06b y T07 (sin conflictos). Orden del responsable del 25/11/2026: las dos rebanadas siguientes según DAF-14 (R6 y después R4), con las mismas reglas que R2 y R3. Entorno: proyecto Docker `gestordep-front`, web en `http://localhost:8001`, con `worker` y `scheduler` apagados, porque R6 no depende del resumen académico ni de las alertas.

## Resultado

- **`/plantel/categorias`:**
  - Alta con `POST /categorias`.
  - Tabla en modo servidor sobre `GET /categorias`, con orden por nombre y las dos descargas.
  - «Eliminar» con `DELETE /categorias/{id}`, tras una confirmación de peligro: es un borrado lógico sin restauración desde la aplicación.
- **`/plantel/equipos`:**
  - Alta con `POST /equipos`, eligiendo la categoría de `GET /categorias`.
  - Tabla de los equipos del usuario con `GET /equipos`.
- **`/plantel/equipos/{equipo}`:**
  - Cabecera tomada de `GET /equipos`, porque no existe `GET /equipos/{id}` (PB-04).
  - Jugadores del equipo con `GET /jugadores?filtro[equipo_id]`, incluida su posición en ese equipo.
  - «Cambiar posición» con `PATCH /equipos/{equipo}/jugadores/{jugador}`.
  - «Asignar un jugador ya registrado, por matrícula» con `POST /equipos/{equipo}/jugadores` (RN-20).
  - Enlace a «Registrar jugador nuevo».
- **`/jugadores/crear`:**
  - `POST /jugadores` con los seis campos del contrato.
  - El equipo se elige de `GET /equipos` y `?equipo=` lo preselecciona.
  - Ante el 409 `matricula_duplicada` sugiere asignar por matrícula.
  - Al terminar lleva a la ficha.
- **`/jugadores/{jugador}/editar`:** `GET` para llenar el formulario y `PATCH /jugadores/{jugador}` con nombres, apellidos y fecha de nacimiento (HU-05).
- **Ficha (`/jugadores/{jugador}`):**
  - Acciones del entrenador («Editar datos» y «Registrar baja»), ocultas para el docente.
  - La baja usa `POST /jugadores/{jugador}/baja` con motivo y fecha, tras una confirmación de peligro (HU-29). Es definitiva y la API no ofrece cómo revertirla.
  - Con el jugador en baja, el botón de baja ya no aparece.
- **Menú:** encabezado «Plantel» con «Categorías» y «Equipos», solo para el entrenador.
- **`comun/formularios.js`:** suma `datos()`, `ocultarAviso()` y `mostrarError()`. Los 422 y 409 se quedan en el formulario: junto a su campo si la API lo indica y, si no, en el aviso del formulario con el mensaje de la API (las reglas de negocio llegan sin campo). El resto va a `Avisos.error`.

## Archivos

**Creados**
- Vistas: `backend/resources/views/modulos/categorias/index.blade.php`, `modulos/equipos/{index,ver}.blade.php` y `modulos/jugadores/{crear,editar}.blade.php`.
- Scripts: `backend/public/assets/js/modulos/categorias/index.js`, `modulos/equipos/{index,ver}.js` y `modulos/jugadores/{crear,editar}.js`.
- Pruebas: `backend/tests/Feature/Web/PlantelTest.php`.
- Este reporte, `docs/reportes/capturas/front-r6/` y `docs/reportes/logs/front-r6/`.

**Modificados**
- `backend/routes/web.php`: cinco rutas GET con `auth:web`, y `whereUuid` donde llevan identificador.
- `backend/app/Http/Controllers/Web/PaginaController.php`: `crearJugador()`, `editarJugador()`, `categorias()`, `equipos()` y `equipo()`. Solo entregan la vista.
- `backend/resources/views/parciales/menu.blade.php` (grupo «Plantel») y `backend/resources/views/modulos/jugadores/ver.blade.php` (acciones del entrenador y formulario de baja).
- `backend/public/assets/js/comun/formularios.js` y `backend/public/assets/js/modulos/jugadores/ver.js` (baja).
- `backend/lang/es/interfaz.php`: textos de categorías, equipos, alta, edición, baja y posiciones.
- `backend/tests/Feature/Web/ContratoDeVistasTest.php`: las páginas de R6 entran en FRONT-R3 9 a 11, y hay una prueba nueva, FRONT-R6 10.
- Enmiendas sin debilitar: PT-12 7, 8 y 15 y F1-H0-T03.
- `docs/trazabilidad.md`: cinco filas nuevas en «Rutas web» y la de la ficha, ampliada.

## Integración

`GET` y `POST /categorias`, `DELETE /categorias/{id}`; `GET` y `POST /equipos`; `GET /jugadores?filtro[equipo_id]`; `POST /equipos/{equipo}/jugadores`; `PATCH /equipos/{equipo}/jugadores/{jugador}`; `POST /jugadores`; `GET` y `PATCH /jugadores/{jugador}`; `POST /jugadores/{jugador}/baja`. Toda escritura va por `POST`, `PATCH` o `DELETE` de la API; las rutas web son solo GET de páginas.

## Pruebas

| N.º | Archivo | Prueba | Antes de implementar |
|---|---|---|---|
| FRONT-R6 1 | `PlantelTest` | Las cinco páginas sin sesión redirigen a `/ingreso` sin contenido privado | Falló |
| FRONT-R6 2 | `PlantelTest` | Con sesión, 200 con el layout; pasan el identificador a la página; uno que no es UUID da 404 | Falló |
| FRONT-R6 3 | `PlantelTest` | Menú «Plantel» con Categorías y Equipos, solo entrenador, ocultos hasta la sesión, con la sección activa | Falló |
| FRONT-R6 4 | `PlantelTest` | Cada formulario tiene exactamente los campos de su endpoint, con su tipo de control (siete formularios) | Falló |
| FRONT-R6 5 | `PlantelTest` | Las tablas solo ordenan por los campos admitidos y su columna de acciones no se exporta | Falló |
| FRONT-R6 6 | `PlantelTest` | Textos de `lang/es/interfaz.php` | Falló |
| FRONT-R6 7 | `PlantelTest` | Orden de carga: SweetAlert2, `avisos.js`, `formularios.js` y el script del módulo; DataTables y `tablas.css` solo donde hay tabla | Falló |
| FRONT-R6 8 | `PlantelTest` | Eliminar categoría y registrar baja confirman con `peligro: true` antes de llamar a la API | Falló |
| FRONT-R6 9 | `PlantelTest` | Editar y dar de baja solo se muestran al entrenador | Falló |
| FRONT-R6 10 | `ContratoDeVistasTest` | Cada lista fija de opciones de un formulario usa exactamente el `enum` de su campo en `openapi.yaml` (las posiciones) | Falló |

También falló FRONT-R3 9, porque ya recorría las páginas nuevas, que aún daban 404. Fallaron igualmente las enmiendas PT-12 8 y 15 y F1-H0-T03: 14 fallaron y 60 pasaron (`front-r6-pruebas-antes.log`). Después de implementar pasan las 74 (`front-r6-pruebas-despues.log`).

Antes de `make verificar` corrí PHPStan sobre las pruebas. Marcó un `array_values` innecesario en `PlantelTest`, que corregí.

**`make verificar`** (`docs/reportes/logs/front-r6/front-r6-verificar-1.log`): **pasó a la primera.** Pint sin cambios en 1042 archivos, PHPStan sin errores, 2476 pruebas y 16 783 aserciones, sin avisos de seguridad. Cobertura: Dominio 100,0 % (mínimo 95 %), Aplicación 99,7 % (85 %), Infraestructura y HTTP 99,5 % (70 %). Código de salida 0, 425 s. La Aplicación pasa de 100,0 a 99,7 % por el código del backend que llegó con la fusión de `main`, no por R6, que no toca `src/`. Se corrió con el proyecto del backend levantado, con autorización del responsable.

## Comprobación en navegador (verificado)

Playwright contra `http://localhost:8001`, con `entrenador1@example.test` y `docente@example.test` sobre los datos inventados de R3. En R6 se crearon la categoría «Sub-17 de prueba», el «Equipo de prueba C» y el jugador «Nuevo Prueba Alta» (`PRB-2000`). Capturas en `docs/reportes/capturas/front-r6/`.

| N.º | Paso | Resultado observado | Captura |
|---|---|---|---|
| 1 | Categoría vacía | 422 debajo del campo | `01-categoria-422.png` |
| 2 | Alta «Sub-17 de prueba» | 201; aviso «Categoría creada.»; la tabla se recarga | `02-categoria-creada.png` |
| 3 | Alta «sub-17 de PRUEBA» | 409 en el aviso: «Ya existe una categoría con ese nombre.» | `03-categoria-409.png` |
| 4 | Eliminar una categoría | Confirmación de peligro con botón rojo; Cancelar no envía nada; confirmar da `DELETE` 204 y la fila desaparece | `04-confirmar-eliminar-categoria.png` |
| 5 | Equipos: vacío, alta y duplicado | 422 en los dos campos; 201; 409 «Ya existe un equipo con ese nombre en la categoría.» | `05-equipo-409.png` |
| 6 | Asignar PRB-0001 al Equipo C | 201; volver a asignarlo da 409 «El jugador ya pertenece a ese equipo.» | `06-asignar-409.png` |
| 7 | Asignar una matrícula inexistente | 404; «No hay ningún jugador con esa matrícula.» | `07-asignar-matricula-inexistente.png` |
| 8 | Cambiar posición | Formulario con la posición actual (Portero); `PATCH` 200; la tabla pasa a «Delantero» | `08-cambiar-posicion.png` |
| 9 | Registrar jugador desde el Equipo C | Equipo preseleccionado; vacío da 422 en cinco campos | `09-crear-jugador-422.png` |
| 10 | Matrícula repetida | 409 con la sugerencia de asignar por matrícula | `10-crear-jugador-matricula-duplicada.png` |
| 11 | Alta válida (`PRB-2000`) | 201, aviso de éxito y la ficha del jugador nuevo en el Equipo C | `11-ficha-del-jugador-registrado.png` |
| 12 | Editar datos | Formulario prellenado; nombres en blanco da 422; cambio válido da 200 y vuelve a la ficha | `12-editar-422.png` |
| 13 | Baja | Vacía: confirmación y luego 422 en la fecha. Con motivo y fecha: confirmación de peligro; Cancelar no envía nada; confirmar da 201 | `13-confirmar-baja.png` |
| 14 | Ficha tras la baja | Estatus «Baja» y «Baja»; sin botón de baja; el jugador ya no aparece en la lista del Equipo C | `14-ficha-en-baja.png` |
| 15 | Alta a 375 px | 0 px de desbordamiento | `15-crear-jugador-375px.png` |
| 16 | Docente | El menú solo muestra «Panel» y «Jugadores»; en `/plantel/equipos` la API responde 403 y aparece «No tiene autorización para esta acción.» | `16-docente-403-en-equipos.png` |

**Consola:** ningún error de JavaScript. Los 12 registros de error son los 403, 404, 409 y 422 provocados a propósito (`consola.log`). **Red:** cada escritura corresponde a una acción del usuario (`red.log`).

## Criterios

- **Cumplidos:**
  - CA-FOR-001: campos exactos y `enum` del contrato.
  - CA-FOR-002: 422 junto al campo; reglas de negocio en el aviso.
  - CA-FOR-003: confirmación de peligro en eliminar y en la baja, y éxito solo tras la respuesta.
  - CA-TAB-001 a 004, CA-GEN-002 a 006 y CA-LAY-002.

## Lo que no se hizo

- **Sin cambios en R1 a R3** salvo los necesarios: la ficha (acciones y baja), `formularios.js` y la prueba de contrato.
- **No se ocultan las páginas del plantel al docente:** si entra por URL, la API responde 403 y la pantalla lo informa. La autorización es de la API (DAF-10).

## Peticiones para el backend

- **PB-04** (ya en cola) afecta a la cabecera de la página del equipo.
- **PB-06** se amplía: los 422 de `POST /categorias`, `POST /equipos`, `POST /jugadores`, `PATCH /jugadores/{id}` y la baja también usan el nombre técnico del campo («El campo categoria id es obligatorio.», «fecha nacimiento»).

## Desviaciones

- **Alta y edición del jugador en páginas propias** (`/jugadores/crear` y `/jugadores/{jugador}/editar`), no en ventanas modales como decía el plan. Siguen la estructura `index`, `crear`, `editar` y `ver` de tu orden de las 14:15.
- **Las rutas del plantel usan el prefijo `/plantel`**, como el plan.
