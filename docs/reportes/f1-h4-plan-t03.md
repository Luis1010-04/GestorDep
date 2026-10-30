# Plan de F1-H4-T03: plantillas y umbrales (HU-15)

**Fecha:** 30 de octubre de 2026 · **Rama:** `hito/f1-h4` · **Especificación:** `docs/hitos/f1-h4.md` v1.0.3, que pasa a v1.0.4 en esta tarea
**Estado:** aprobado por el responsable el 30/10/2026, con estas decisiones (especificación v1.0.4):

- Sin PATCH de plantillas: §7.2 fila 4 queda reservada y `plantilla_de_fabrica` también (opción b de §3.1).
- `plantilla_duplicada` 409 en `nombre`, también cuando el nombre coincide con una plantilla de fábrica de la misma posición y cuando choca el índice único en una carrera.
- `plantilla_inexistente` 422 en `plantilla_id`, con el mismo cuerpo para una inexistente y una ajena.
- Forma del umbral: la de §3.4.
- El filtro de posición no v7 sigue DA-26 (lista vacía); `{posicion_id}` sale de la regla de UUID de §6.1.
- `umbral_ambiguo` 422 en `plantilla_id` cuando llegan `plantilla_id` y valores propios a la vez (reemplaza el `validacion_fallida` de §3).
- Límite técnico de la ventana: 32 767, con `valor_fuera_de_rango`.
- El primer PUT no cierra nada.

El resto del documento es el plan tal como se propuso.

## 1. Qué entra en el commit de T03

| # | Qué | Origen |
|---|---|---|
| 1 | Plantillas propias: lista y creación | §6.1, §7.2 filas 4 y 5 |
| 2 | Umbral: consulta (umbral vigente o plantilla de la posición) y PUT, que cierra el vigente y abre otro, con historial | §6.1, §7.2 filas 1, 2, 3, 6 y 7 |
| 3 | Corrección de la migración `050500` de T01: dos UQ parciales en lugar de una | Punto 4 del responsable; DA nueva |
| 4 | RN-06: `categoria_calificacion` de solo lectura en las respuestas de rendimiento | Punto 2 del responsable; DA nueva |
| 5 | Prueba de la violación de la UQ en la captura (carrera de dos capturas): 409 `rendimiento_duplicado` | Punto 1 del responsable; DA nueva |
| 6 | Especificación v1.0.4: §3.1 (RN-06), §5.1 (dos UQ), §6.2 (`categoria_calificacion` y lo que se decida en §3 de este plan) | Puntos 2 y 4 |
| 7 | Tiempo de Pest de T03 en `f1-h4-progreso.md` | Punto 7 |

Los puntos 4 y 5 corresponden a T02, pero entran aquí porque T03 tiene permiso sobre `src/Rendimiento/**`, las rutas base y `tests/**`. El reporte los separa.

## 2. Diseño

### 2.1 Migración (punto 3)

En `2026_10_30_050500_crear_tabla_plantillas_evaluacion.php`, `uq_plantillas_evaluacion_nombre_posicion` se reemplaza por dos índices:

- `uq_plantillas_evaluacion_propia`: (`nombre`, `posicion_id`, `creada_por`) donde `creada_por is not null and eliminado_en is null`.
- `uq_plantillas_evaluacion_fabrica`: (`nombre`, `posicion_id`) donde `creada_por is null and eliminado_en is null`.

Consecuencia: un entrenador puede crear una plantilla propia llamada «Desarrollo», porque el índice de fábrica no la alcanza. Ninguna regla lo prohíbe, así que no lo bloqueo; lo dejo anotado.

`MigracionesDeRendimientoTest`: la prueba de la UQ antigua se reemplaza por cuatro, una que falla y otra que pasa por cada índice, incluido el caso de dos entrenadores con el mismo nombre y posición. Además, una prueba de `migrate:rollback` del grupo de Rendimiento y su nueva migración. No se debilita ninguna aserción: la regla cambió por decisión del responsable.

### 2.2 RN-06 (punto 2)

- `src/Rendimiento/Dominio/CategoriaDeCalificacion.php`: enumeración con `deficiente`, `regular`, `bueno` y `sobresaliente`, y `de(int $calificacion)`, con los cortes de RN-06 (1 a 4, 5 a 6, 7 a 8, 9 a 10) como constante del dominio.
- `RendimientoRecurso` agrega `categoria_calificacion`. El campo sale en el POST, el PATCH y la lista por partido, que usan el mismo recurso.
- `openapi.yaml`: un `enum` de solo lectura en el esquema `Rendimiento`.
- Pruebas: una unitaria de los ocho cortes (1, 4, 5, 6, 7, 8, 9 y 10) y una de contrato que valida el campo en la respuesta.

### 2.3 Prueba de carrera de la captura (punto 1)

`CapturaDeRendimientoTest` reemplaza en el contenedor `RepositorioDeRendimientos` por un decorador de prueba cuyo `existeDe` siempre devuelve falso. Así la segunda captura llega al `insert`, choca con `uq_rendimientos_partido_*` y debe responder 409 `rendimiento_duplicado` con el campo `jugador_id`. La prueba cubre las líneas 64 y 65 de `RepositorioDeRendimientosEloquent`. Va en `tests/**` y no cambia código de producción.

### 2.4 Plantillas

- **Dominio:** `CriterioDeEvaluacion` (calificación mínima `NumeroDecimal` de 1 a 10 con dos decimales y ventana entera mayor que 0), que comparten la plantilla y el umbral. Fuera de rango lanza el `ValorFueraDeRango` de T02 con su campo. Además, `PlantillaDeEvaluacion` (id, nombre, posición, creada por, criterio, es de fábrica) y `RepositorioDePlantillas`.
- **Aplicación:**
  - `CrearPlantilla`: la posición del cuerpo; una posición inexistente o no v7 da 422 `posicion_inexistente`, con el código de Competencia.
  - `ListarPlantillas`: las de fábrica más las propias, nunca las de otro entrenador.
  - `BuscarPlantillaVisible`.
- **Infraestructura:** `RepositorioDePlantillasSql`, que escribe el historial `crear` en la misma transacción.
- **HTTP:** `PlantillaEvaluacionController`, `SolicitudDePlantilla` y `PlantillaRecurso` (id, nombre, posicion_id, calificacion_minima como cadena con dos decimales, ventana_partidos, es_de_fabrica). `creada_por` no sale, por DA-19; la fila 5 lo comprueba en la base.
- **Lista:** paginada como las demás. Orden: las de fábrica primero, después por nombre.
- **Cuerpo:** `calificacion_minima` viaja como cadena con hasta dos decimales, como en Académico (DD-51), y `ventana_partidos` como entero.

### 2.5 Umbral

- **Control de pertenencia, en este orden y antes del cuerpo (§6.1):**
  1. El entrenador entrena el equipo (`EquiposDelEntrenador`); si no, 404.
  2. El jugador está en su alcance, incluidas las bajas (`VisibilidadDeJugador`); si no, 404.
  3. El jugador tiene posición en el equipo (`PosicionEnEquipo`); si no, 404.
- **GET:** devuelve el umbral vigente de (jugador, equipo, entrenador). Si no hay, devuelve la plantilla de fábrica de la posición. Si una posición tuviera más de una plantilla de fábrica, se toma la más antigua (`creado_en`, `id`), y queda como DA.
- **PUT:**
  - Con `plantilla_id`: la plantilla debe ser visible y de la misma posición; si es de otra, 422 `plantilla_de_otra_posicion`. Se copian sus valores.
  - Con `calificacion_minima` y `ventana_partidos`: valores propios, sin plantilla.
  - Sin nada: la plantilla de fábrica de la posición.
  - En los tres casos, en una transacción: se cierra el vigente (`vigente_hasta` igual al instante, con historial `actualizar` y la instantánea completa, que incluye el valor anterior) y se inserta el nuevo (historial `crear`). Responde 200 con el umbral nuevo.
  - En el primer PUT no hay umbral anterior que cerrar; antes se aplicaba la plantilla de fábrica, que no cambia. Queda como DA.
  - Si hay una carrera de dos PUT y salta `uq_umbrales_rendimiento_vigente`, responde 409 `conflicto`, el código genérico que ya existe.
- **Ventana:** el límite superior es 32 767, el máximo de `smallint`. Es un límite técnico para que un valor grande no termine en 500, no una regla de negocio. Queda como DA.
- **Evaluación:** recalcularla al cambiar el umbral es T04 (§7.3 fila 7). En T03 no se toca `evaluaciones_rendimiento`.

### 2.6 Pruebas (primero, con la falla pegada en el reporte)

- `tests/Feature/Rendimiento/PlantillasYUmbralesTest.php`: §7.2 filas 1 a 7, más:
  - plantilla ajena invisible en la lista;
  - `posicion_inexistente`;
  - PUT sin cuerpo, que toma la plantilla de fábrica;
  - primer PUT sin umbral anterior;
  - 404 por un jugador fuera del alcance o sin membresía.
- `tests/Feature/Rendimiento/ContratoDePlantillasYUmbralesTest.php`: las rutas nuevas contra `openapi.yaml`.
- `tests/Unit/Rendimiento/CriterioDeEvaluacionTest.php` y `CategoriaDeCalificacionTest.php`, sin base.
- `tests/Integration/Rendimiento/MigracionesDeRendimientoTest.php`: los índices y el rollback (§2.1).

### 2.7 Archivos

`src/Rendimiento/{Dominio,Aplicacion,Infraestructura}/**` (los nuevos de §2.4 y §2.5, y los enlaces en `RendimientoServiceProvider`), la migración `050500`, `app/Http/Controllers/PlantillaEvaluacionController.php`, `UmbralController.php`, las solicitudes y los recursos nuevos, `RendimientoRecurso`, `FormatoDeError` (los códigos nuevos), `lang/es/errores.php`, `routes/api.php`, `docs/api/openapi.yaml`, `docs/hitos/f1-h4.md`, `tests/**` y los reportes.

## 3. Decisiones que necesito antes de codificar

1. **§7.2 fila 4 sin ruta.** La fila espera 409 `plantilla_de_fabrica` al editar «Desarrollo», pero §6.1 no tiene ruta para editar una plantilla.
   - **(a) Recomendada:** agregar `PATCH /plantillas-evaluacion/{plantilla}` con `nombre`, `calificacion_minima` y `ventana_partidos`. Una plantilla de fábrica da 409, una ajena o inexistente da 404, y la propia da 200 con historial. Los umbrales que ya la usan no cambian, porque guardan sus valores copiados. Así la fila 4 se prueba por HTTP, como dice §6.3, y el «ajustarla» de RN-07 tiene ruta.
   - **(b)** Sin ruta nueva: la fila 4 pasa a ser una prueba de dominio (`PlantillaDeEvaluacion::ajustar()` lanza `PlantillaDeFabrica`) y `plantilla_de_fabrica` queda reservado, como `rendimiento_no_editable`.
2. **Nombre repetido en las plantillas propias.** §6.3 no tiene código para esto. Recomiendo 409 `plantilla_duplicada`, con el campo `nombre` y el mismo patrón que `categoria_duplicada` y `torneo_duplicado`.
3. **`plantilla_id` inexistente o de otro entrenador en el PUT del umbral.** Recomiendo 422 `plantilla_inexistente`, con el campo `plantilla_id` y el mismo cuerpo en los dos casos, como `torneo_inexistente` en H3. La alternativa es 404, pero el recurso de la ruta (el jugador en el equipo) sí existe.
4. **Recurso del umbral.** §6.2 no lo define. Propongo `origen` (`umbral` o `plantilla`), `plantilla_id` (nulo con valores propios), `calificacion_minima` (cadena con dos decimales), `ventana_partidos` y `vigente_desde` (nulo cuando es la plantilla).
5. **`filtro[posicion_id]` no v7.** §6.1 pide 404 para `{posicion_id}`, pero es un filtro de la lista, no un parámetro de ruta, y en H3 (DA-26) un filtro no v7 da una lista vacía. Recomiendo seguir DA-26: un UUID no v7 da una lista vacía y algo que no es UUID da 422. Si prefieres el 404 literal, lo hago así.

Decisiones menores que tomaré como DA salvo objeción:

- «Su posición» en §3.3 y §6.1 se lee como la posición de la plantilla, porque un entrenador no tiene posición.
- Un PUT con `plantilla_id` y valores a la vez da 422 `validacion_fallida`.
- Para los valores propios, `calificacion_minima` y `ventana_partidos` van juntos; si falta uno, 422.

## 4. Después

Al cerrar T03 entrego su reporte y presento el plan de T04 antes de codificarlo. Sin push, PR ni etiquetas.
