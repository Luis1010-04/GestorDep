# Decisiones del agente en H5

Decisiones que tomó el agente donde la especificación de H5 callaba (`docs/hitos/f1-h5.md` §0). La numeración sigue a las DA-01 a DA-19 de §3.3. Se registran en `docs/decisiones/registro-decisiones.md` al cerrar el hito (T09).

## DA-20. Columnas de entidad de los historiales (T01)

- **Qué faltaba:** §5.1 nombra las cinco tablas de historial, pero no su columna de entidad.
- **Qué se eligió:** `medicion_id`, `prueba_id`, `lesion_id`, `aviso_id` y `estado_id`. Es el patrón de H4 (`rendimiento_id`, `plantilla_id`, `umbral_id`).
- **Estado:** aprobada por el responsable con el plan de T01 (3/11/2026).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** una migración nueva que renombre la columna.

## DA-21. Nombres de los catálogos (T01)

- **Qué faltaba:** §5.1 exige la columna `nombre` en `tipos_prueba` y `rangos_mediciones_corporales`, pero §5.2 no da sus valores.
- **Qué se eligió:** `Velocidad`, `Resistencia`, `Tiempo de prueba`, `Peso` y `Estatura`, en los seeders, como los nombres de `MetricasSeeder` y `PosicionesSeeder`.
- **Estado:** confirmada por el responsable (3/11/2026).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** cambiar el dato en la base. El seeder no deshace un ajuste.

## DA-22. Longitud de las columnas de texto sin tamaño en §5.1 (T01)

- **Qué faltaba:** §5.1 no da el tamaño de estas columnas.
- **Qué se eligió:** `codigo` 50 y `nombre` 100, como `metricas`; `unidad` 20; `mejor_resultado` 10; `causa` y `estado` 20.
- **Estado:** confirmada por el responsable (3/11/2026).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** una migración nueva que cambie el tipo.

## DA-23. Fecha futura en un aviso (T02)

- **Qué faltaba:** §6.2 hace obligatoria la `fecha` del aviso, pero no dice si puede ser futura.
- **Qué se eligió:** no se valida que no sea futura, como DD-55 y DA-03.
- **Estado:** confirmada por el responsable (4/11/2026).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** agregar la regla a la solicitud.

## DA-24. Orden entre 422 y 409 `jugador_en_baja` en un aviso (T02)

- **Qué faltaba:** §6.1 fija el 404 antes del 422, pero no el orden entre el 422 y el 409 `jugador_en_baja`.
- **Qué se eligió:** primero la validación del cuerpo (422) y después el 409, como en H2.
- **Estado:** aprobada por el responsable con el plan de T02 (3/11/2026). Implementada en T02 (`04cfc9c`).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** comprobar la baja en `authorize()`.

## DA-25. Listado de avisos para un usuario con los dos roles (T02)

- **Qué faltaba:** §5.3 define la unión de alcances para ver el estado oficial y crear avisos, pero no para listarlos.
- **Qué se eligió:** si el usuario entrena al jugador, ve todos sus avisos; si solo le da clase, ve los que él creó.
- **Estado:** confirmada por el responsable (4/11/2026).
- **Privacidad o permisos:** **sí** (lectura).
- **Cómo se revierte:** cambiar la condición del listado.

## DA-26. Comprobar el estado oficial en §7.1 filas 1 y 7 (T02)

- **Qué faltaba:** §7.1 filas 1 y 7 piden comprobar que el estado oficial no cambia, pero `GET /jugadores/{jugador}/estado-disponibilidad` llega hasta T03.
- **Qué se eligió:** T02 comprueba que `estados_disponibilidad` no cambia, y T03 agrega la comprobación por la API.
- **Estado:** aprobada por el responsable con el plan de T02 (3/11/2026). Implementada en T02 (`04cfc9c`); T03 agregó la comprobación por la API (`455720e`).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** no aplica: T03 completa la comprobación.

## DA-27. Mensaje de `jugador_en_baja` al crear un aviso (T02)

- **Qué faltaba:** el mensaje existente dice «no se puede asignar, inscribir ni capturar calificaciones o asistencias», que no describe un aviso, y T02 solo puede agregar claves nuevas en `lang/es`.
- **Qué se eligió:** una clave nueva, solo para avisos, con el texto «El jugador está en baja: no se pueden crear avisos.». La clave existente no cambia y el código de la respuesta sigue siendo `jugador_en_baja`. El texto se elige en un punto local del módulo de avisos. Si hay que tocar `FormatoDeError.php`, las pruebas de H2 a H4 deben demostrar que sus respuestas siguen idénticas.
- **Estado:** aprobada por el responsable con el plan de T02 (3/11/2026). Implementada en T02 (`04cfc9c`).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** quitar la clave nueva y usar la existente.

## DA-28. Orden entre 422 y 409 `aviso_no_pendiente` (T02)

- **Qué faltaba:** DA-24 fija el orden para `jugador_en_baja` al crear, pero nada lo fija al descartar.
- **Qué se eligió:** primero la validación del cuerpo (422) y después el 409 `aviso_no_pendiente`, por coherencia con DA-24.
- **Estado:** confirmada por el responsable (4/11/2026).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** comprobar el estado del aviso en `authorize()`.

## DA-29. Texto del mensaje de `aviso_no_pendiente` (T02)

- **Qué faltaba:** §6.3 define el código, pero no su mensaje.
- **Qué se eligió:** «El aviso ya no está pendiente de confirmación.», en `lang/es/errores.php`.
- **Estado:** confirmada por el responsable (4/11/2026).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** cambiar el texto en `lang/es/errores.php`.

## DA-30. Estado abierto y cerrado en el mismo segundo (T03)

- **Qué faltaba:** `estados_disponibilidad` exige `vigente_hasta > vigente_desde`, y las dos columnas guardan segundos. Un estado que se abre y se cierra en el mismo segundo viola el `CHECK`, y §5.1 no dice qué responder.
- **De dónde viene el límite:** de la columna, no de `Reloj`. Las dos son `timestamptz(0)` (`datetime_precision = 0`), que redondea la fracción. `RelojDelSistema` entrega microsegundos. `Instante::truncadoASegundos` trunca en el código, para que lo guardado y lo devuelto sean el mismo valor, pero no crea el límite: sin él, dos instantes del mismo segundo también podrían coincidir al redondear.
- **Qué se eligió:** responder 409 `conflicto`, que §6.3 ya prevé para escrituras que chocan. El esquema no cambia. Las pruebas con reloj fijo adelantan el reloj entre una operación y otra.
- **Estado:** decidida por el responsable (opción A, 5/11/2026). Implementada en T03 (`455720e`).
- **Privacidad o permisos:** no.
- **Límite para revisión posterior (T08 o H7):** quien confirma un aviso y cambia el estado o da de alta en el mismo segundo recibe 409 y debe repetir la operación. Falta comprobar que esto no llegue al usuario como 500 y decidir si el caso merece otra solución.
- **Cómo se revierte:** una migración nueva que cambie el `CHECK` a `>=` o la precisión de las columnas.

## DA-31. Orden del listado de lesiones (T03)

- **Qué faltaba:** §6.1 no dice en qué orden se devuelven las lesiones del jugador.
- **Qué se eligió:** por `fecha_inicio` y por `id`, descendentes, como los avisos (DD-56).
- **Estado:** confirmada por el responsable (5/11/2026).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** cambiar el orden en `RepositorioDeLesionesSql::delRegistrante`.

## DA-32. `nota` nula o vacía en la lesión (T03)

- **Qué faltaba:** §6.2 dice que la `nota` es opcional, pero no qué pasa si llega nula o vacía.
- **Qué se eligió:** responde 422, al confirmar y en `PATCH /lesiones/{lesion}`. Una nota ya guardada no se puede borrar, solo reemplazar.
- **Estado:** confirmada por el responsable (5/11/2026).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** agregar `nullable` a la regla y distinguir en `Lesion::corregir` entre campo ausente y nulo.

## DA-33. Orden de los 409 al confirmar (T03)

- **Qué faltaba:** el punto 2 del plan fija el orden en `PATCH` y alta, pero no al confirmar.
- **Qué se eligió:** 403 por rol, 404 por alcance, 422 por cuerpo, 409 `aviso_no_pendiente`, 409 `jugador_en_baja`, 422 `rango_de_fechas_invalido`, 409 `conflicto`.
- **Estado:** confirmada por el responsable (5/11/2026).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** reordenar las comprobaciones en `ConfirmarAviso`.

## DA-34. Textos nuevos de T03 (T03)

- **Qué faltaba:** §6.3 define los códigos, pero no sus mensajes.
- **Qué se eligió:** `sin_lesion_vigente`: «El jugador no tiene una lesión vigente.»; `no_es_registrante`: «Solo quien registró el estado oficial puede modificarlo.»; y dos `cuerpo_sin_campos` con la forma de los existentes.
- **Estado:** confirmada por el responsable (5/11/2026).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** cambiar el texto en `lang/es/errores.php`.

## DA-35. Tipo que devuelve `EstadoDeDisponibilidadOficial` (T04)

- **Qué faltaba:** §8 pide el estado por identificador, sin fijar su tipo.
- **Qué se eligió:** cadenas (`apto`, `con_molestia`, `lesionado`), porque Compartido no debe importar `TipoDeEstado` de Salud.
- **Estado:** aprobada por el responsable con el plan de T04 (5/11/2026).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** mover el enum a Compartido.

## DA-36. Identificador sin registro en el puerto (T04)

- **Qué faltaba:** `InicioDePartidos` omite lo que no existe, pero DA-01 dice que sin registro el jugador está apto.
- **Qué se eligió:** devolver cada identificador pedido, con `apto` si no tiene estado, incluso sin jugador, porque Salud no puede leer Plantel.
- **Estado:** aprobada por el responsable con el plan de T04 (5/11/2026).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** omitir los identificadores sin registro y tratar la ausencia en Competencia.

## DA-37. `eliminado_en` en el puerto (T04)

- **Qué faltaba:** `InicioDePartidos` filtra por `eliminado_en`, pero `RepositorioDeEstadosSql` no.
- **Qué se eligió:** no filtrar, igual que el repositorio: no hay ruta de borrado y el índice de estado vigente tampoco lo considera.
- **Estado:** aprobada por el responsable con el plan de T04 (5/11/2026).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** filtrar en el adaptador y en el repositorio a la vez.

## DA-38. Orden de los errores en mediciones (T05)

- **Qué faltaba:** §6.1 fija el 404 antes del 422, pero no el orden entre los demás errores de T05.
- **Qué se eligió:** 403 por rol, 404 por alcance, 422 por cuerpo, 409 `jugador_en_baja`, 422 `equipo_inexistente`, 422 `valor_fuera_de_rango`, 422 `nada_que_corregir`, 409 `medicion_ya_registrada`, 409 `conflicto`.
- **Estado:** aprobada por Luis (6/11/2026). Se implementa en T05.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** reordenar las comprobaciones del caso de uso de T05.

## DA-39. `motivo` sin `es_correccion` (T05)

- **Qué faltaba:** §6.2 hace obligatorio `motivo` con `es_correccion`, pero no dice qué pasa si llega sin él.
- **Qué se eligió:** responde 422 `prohibited`, para no perder un motivo en silencio.
- **Estado:** aprobada por Luis (6/11/2026). Se implementa en T05.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** quitar la regla de la solicitud e ignorar el campo.

## DA-40. Corrección con valores idénticos (T05)

- **Qué faltaba:** `docs/convenciones-api.md` define la corrección, pero no qué responder si los valores no cambian.
- **Qué se eligió:** responde 200 sin historial, como en T03 (DA-48).
- **Estado:** aprobada por Luis (6/11/2026). Se implementa en T05.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** escribir historial también sin cambios reales.

## DA-41. Mensaje de `jugador_en_baja` en mediciones (T05)

- **Qué faltaba:** los mensajes existentes de `jugador_en_baja` no describen las mediciones.
- **Qué se eligió:** clave propia, con «El jugador está en baja: no se pueden registrar ni corregir mediciones.». El código sigue siendo `jugador_en_baja`.
- **Estado:** aprobada por Luis (6/11/2026). Se implementa en T05.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** quitar la clave y usar una existente.

## DA-42. Docente que creó un aviso y perdió el alcance (T02)

- **Qué faltaba:** RN-16 dice que los avisos pendientes los ven los entrenadores del jugador y quien los creó. §6.1 dice que un jugador fuera del alcance responde 404. Los dos chocan cuando un docente crea un aviso y después pierde el alcance sobre el jugador, por ejemplo si se elimina la inscripción.
- **Qué se eligió:** gana §6.1 (opción A). Ese docente recibe el mismo 404 que con un jugador inexistente, no ve sus avisos y no recibe datos extra. RN-16 queda sin cumplirse en ese caso y `docs/requisitos.md` no cambió.
- **Estado:** decidida por el responsable (opción A, 3/11/2026). Implementada en T02 (`04cfc9c`). La fija la prueba `RN-16 frente a §6.1 (opción A)`. Detalle en `f1-h5-t02.md` §1.
- **Privacidad o permisos:** **sí** (lectura).
- **Cómo se revierte:** dar al creador acceso a sus avisos en `ComprobarJugadorVisible` y `ListarAvisos`, y resolver antes el choque con §6.1.

## DA-43. Registrante que perdió el alcance sobre el jugador (T03)

- **Qué faltaba:** RN-15 reserva el detalle de la lesión a quien la registró, y §6.1 responde 404 fuera del alcance, pero nada dice qué pasa cuando quien la registró deja de entrenar al jugador.
- **Qué se eligió:** responde 404, igual que una lesión inexistente, en `GET` y `PATCH /lesiones/{lesion}` y en `GET` de las lesiones del jugador. Mientras no recupere el alcance, esa persona no ve ni corrige la lesión que registró.
- **Estado:** aprobada por el responsable (punto 1 del plan de T03, 5/11/2026). Implementada en T03 (`455720e`), en `BuscarLesionDelRegistrante`. La fija la prueba `Punto 1 del plan`.
- **Privacidad o permisos:** **sí** (lectura y escritura).
- **Cómo se revierte:** quitar la comprobación de `esVisibleComoEntrenador` en `BuscarLesionDelRegistrante` y permitir el listado sin alcance de entrenador.

## DA-44. Límite de `motivo` en mediciones y pruebas físicas (T05 y T06)

- **Qué faltaba:** §6.2 decía «hasta 500», pero la macro `historial()` guarda `motivo` en un `varchar(255)` compartido por todos los historiales. Un motivo de 256 a 500 caracteres pasaría la validación y fallaría en la base.
- **Qué se eligió:** opción A, límite de 255. §6.2 se corrigió en la v1.0.5 y el esquema no cambia. T05 y T06 prueban que 255 se acepta y que 256 responde 422 `validacion_fallida`, sin llegar a la base y nunca con un 500. `docs/api/openapi.yaml` lleva el límite cuando T05 y T06 documenten sus rutas, que aún no existen.
- **Nota de la auditoría:** esta decisión cambia un contrato aprobado. La auditoría de los Lotes A y B (hallazgo A-1) detectó la diferencia; T01 había declarado «ninguna desviación» y el conflicto solo apareció en el plan de T05, sin registro en el repositorio.
- **Estado:** decidida por el responsable (opción A, 6/11/2026). Especificación corregida; la validación y sus pruebas llegan con T05 y T06.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** una migración nueva que amplíe `motivo` en los historiales de Condición física, y volver §6.2 a 500.

## DA-45. Lesión propia abierta al confirmar otro aviso (T03)

- **Qué faltaba:** DD-63 dice que la lesión de otro registrante sigue sin alta cuando un estado nuevo sustituye al suyo, pero nada dice qué pasa con la lesión de quien confirma.
- **Qué se eligió:** se deja sin alta, con el mismo criterio que DD-63. Confirmar no toca la lesión anterior y el alta solo cierra la lesión del estado vigente. Si el estado nuevo es `apto`, el alta responde 409 `sin_lesion_vigente`, así que ninguna ruta cierra esa lesión anterior.
- **Estado:** aprobada por Luis (6/11/2026), con el criterio de DD-63: la lesión propia no admite alta por la ruta del jugador. Es el comportamiento de `ConfirmarAviso` desde T03 (`455720e`); lo fijan las dos pruebas `DA-45`. **Pendiente para la revisión de T08.**
- **Privacidad o permisos:** no.
- **Cómo se revierte:** cerrar la lesión propia en `ConfirmarAviso`, con su historial, y cambiar las pruebas `DA-45`.

## DA-46. Orden de los errores en `PATCH` del estado y en el alta (T03, punto 2 del plan)

- **Qué faltaba:** §6.1 no fija el orden entre estos errores.
- **Qué se eligió:** 403 por rol, 404 por alcance, 422 por cuerpo, 409 `sin_lesion_vigente`, 403 `no_es_registrante`, 409 `jugador_en_baja`. Fuera del alcance nunca se revela `no_es_registrante`.
- **Estado:** aprobada por el responsable con el plan de T03 (5/11/2026). Implementada en T03 (`455720e`). La fijan las pruebas `Punto 2 del plan`.
- **Privacidad o permisos:** **sí** (el orden decide qué sabe quien no tiene permiso).
- **Cómo se revierte:** reordenar las comprobaciones en `CambiarEstado` y `DarDeAlta`.

## DA-47. `PATCH` con el mismo estado (T03, punto 3 del plan)

- **Qué faltaba:** DA-14 define el cambio de estado y el de fecha, pero no un `PATCH` que repite el estado vigente.
- **Qué se eligió:** no abre fila nueva y solo aplica la fecha, si viene.
- **Estado:** aprobada por el responsable con el plan de T03 (5/11/2026). Implementada en T03 (`455720e`).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** tratarlo como un cambio de estado en `CambiarEstado`.

## DA-48. `PATCH` idéntico o sin campos (T03, punto 4 del plan)

- **Qué faltaba:** §6.2 pide al menos un campo, pero no qué responder si los valores no cambian.
- **Qué se eligió:** valores idénticos responden 200 sin historial; sin campos responde 422.
- **Estado:** aprobada por el responsable (5/11/2026). Implementada en T03 (`455720e`).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** escribir historial también sin cambios reales.

## DA-49. Fechas futuras en el alta y en el regreso estimado (T03, punto 5 del plan)

- **Qué faltaba:** §6.2 no dice si `fecha_alta` y `fecha_estimada_regreso` pueden ser futuras.
- **Qué se eligió:** pueden serlo.
- **Estado:** aprobada por el responsable con el plan de T03 (5/11/2026). Implementada en T03 (`455720e`). La fija la prueba `Punto 5 del plan`.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** agregar la regla a las solicitudes.

## DA-50. Alta sin fecha con un aviso de fecha futura (T03, punto 6 del plan)

- **Qué faltaba:** el alta sin fecha usa el hoy de negocio, que puede ser anterior al inicio de una lesión creada con un aviso de fecha futura.
- **Qué se eligió:** responde 422 `rango_de_fechas_invalido`.
- **Estado:** aprobada por el responsable con el plan de T03 (5/11/2026). Implementada en T03 (`455720e`). La fija la prueba `Punto 6 del plan`.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** cambiar la fecha por defecto en `DarDeAlta`.

## DA-51. Mensaje de `rango_de_fechas_invalido` en la lesión (T03, punto 7 del plan)

- **Qué faltaba:** el mensaje existente no describe la fecha de una lesión.
- **Qué se eligió:** clave propia, con «La fecha no puede ser anterior al inicio de la lesión.». El mensaje existente no cambia.
- **Estado:** aprobada por el responsable (5/11/2026). Implementada en T03 (`455720e`). La fija la prueba `Punto 7 del plan`.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** quitar la clave y usar la existente.

## DA-52. Mensaje de `jugador_en_baja` al confirmar o cambiar el estado (T03, punto 8 del plan)

- **Qué faltaba:** el mensaje de DA-27 solo describe la creación de avisos.
- **Qué se eligió:** clave propia, con «El jugador está en baja: no se puede confirmar el aviso ni cambiar su estado.». El código sigue siendo `jugador_en_baja`.
- **Estado:** aprobada por el responsable (5/11/2026). Implementada en T03 (`455720e`).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** quitar la clave y usar la existente.

## DA-53. Mensaje de `valor_fuera_de_rango` en mediciones (T05)

- **Qué faltaba:** el texto de `errores.reglas.valor_fuera_de_rango` habla de goles.
- **Qué se eligió:** clave propia en `errores.condicion_fisica`, con «El valor está fuera del rango permitido.».
- **Estado:** aprobada por Luis (6/11/2026). Implementada en T05.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** quitar la clave y usar otra.

## DA-54. Textos nuevos de T05 (T05)

- **Qué faltaba:** §6.3 define los códigos, pero no sus mensajes.
- **Qué se eligió:** `medicion_ya_registrada`: «Ya hay una medición registrada en ese periodo. Si quieres reemplazarla, confírmalo como corrección.»; `nada_que_corregir`: «No hay nada que corregir en ese periodo.»; `equipo_inexistente`: «El equipo indicado no existe.».
- **Estado:** aprobada por Luis (6/11/2026). Implementada en T05 (`94b0772`).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** cambiar el texto en `lang/es/errores.php`.

## DA-55. Forma de `equipo_id` en mediciones (T05)

- **Qué faltaba:** DA-13 fija la respuesta para un equipo inexistente, pero no para un valor que no identifica a ninguno.
- **Qué se eligió:** un texto que no es UUID responde 422 `validacion_fallida`. Un UUID válido que no es v7 responde 422 `equipo_inexistente`, con el mismo cuerpo que un equipo inexistente, como la captura de rendimiento con `metrica_id`.
- **Estado:** aprobada por Luis (6/11/2026). Implementada en T05 (`94b0772`).
- **Privacidad o permisos:** no.
- **Cómo se revierte:** usar la regla `uuid:7` en `SolicitudDeMedicion`.

## DA-56. Fecha de última actualización del estado oficial (RNF-04, hallazgo B-5 de la auditoría)

- **Qué faltaba:** RNF-04 pide que todo dato muestre su fecha de última actualización, pero §6.2 no da ese campo al estado oficial. `vigente_desde` no cambia cuando solo cambia la fecha estimada, así que ese cambio no deja nada visible.
- **Qué se eligió:** el estado oficial muestra la fecha y hora de su última actualización. Solo el momento, nunca el contenido del cambio.
- **Estado:** aprobada por Luis (6/11/2026). **Sin implementar.** Asignada por Luis a T03b, antes de T06, con §6.2 y `docs/api/openapi.yaml` actualizados. Falta fijar el nombre del campo.
- **Privacidad o permisos:** **sí** (lectura: quien ve el estado oficial ve el momento, nunca qué cambió).
- **Cómo se revierte:** quitar el campo del recurso y del contrato.

## DA-57. Tiempo de `make verificar` en H5

- **Qué faltaba:** `make verificar` varió entre 487 y 871 s en H5, en la laptop actual, y T06 agrega más pruebas.
- **Qué se eligió:** primero medir y resolver las cachés. Pest en paralelo queda fuera de H5 y requiere un plan aparte.
- **Estado:** aprobada por Luis (6/11/2026). La medición se hace después de la auditoría de T05.
- Aprobada por Luis el 7/11/2026: opción (i), persistir la caché de PHPStan y de Pint en un volumen.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** no aplica: fija un orden de trabajo.

## DA-58. Orden de los errores en pruebas físicas (T06)

- **Qué faltaba:** DA-38 solo cubre las mediciones.
- **Qué se eligió:** 403 por rol, 404 por alcance, 422 por cuerpo, 409 `jugador_en_baja`, 422 `equipo_inexistente`, 422 `tipo_prueba_inexistente`, 422 `valor_fuera_de_rango`, 422 `nada_que_corregir`, 409 `prueba_ya_registrada`, 409 `conflicto`.
- **Estado:** aprobada por Luis (6/11/2026). Se implementa en T06.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** reordenar las comprobaciones del caso de uso de T06.

## DA-59. Mensaje de baja en pruebas físicas (T06)

- **Qué faltaba:** el texto de DA-41 habla de mediciones.
- **Qué se eligió:** clave propia: «El jugador está en baja: no se pueden registrar ni corregir pruebas físicas.»
- **Estado:** aprobada por Luis (6/11/2026). Se implementa en T06.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** quitar la clave y usar una existente.

## DA-60. Textos nuevos de T06 (T06)

- **Qué faltaba:** §6.3 no da mensajes.
- **Qué se eligió:** `prueba_ya_registrada`: «Ya hay una prueba de ese tipo registrada en ese periodo. Si quieres reemplazarla, confírmalo como corrección.»; `tipo_prueba_inexistente`: «El tipo de prueba indicado no existe.»
- **Estado:** aprobada por Luis (6/11/2026). Se implementa en T06.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** cambiar el texto en `lang/es/errores.php`.

## DA-61. Forma de `tipo_prueba_id` en el cuerpo (T06)

- **Qué faltaba:** §6.3 solo dice «inexistente».
- **Qué se eligió:** como DA-55: un texto que no es UUID da 422 `validacion_fallida`; un UUID no v7 o un tipo eliminado da 422 `tipo_prueba_inexistente`.
- **Estado:** aprobada por Luis (6/11/2026). Se implementa en T06.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** usar la regla `uuid:7` en la solicitud de T06.

## DA-62. Los dos filtros juntos en pruebas físicas (T06)

- **Qué faltaba:** §6.1 no dice qué pasa con `filtro[periodo]` y `filtro[tipo_prueba_id]` a la vez.
- **Qué se eligió:** la vista del periodo, solo con ese tipo. Con un UUID no v7, lista vacía, como DA-26 de H3.
- **Estado:** aprobada por Luis (6/11/2026). Se implementa en T06.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** rechazar la combinación con 422.

## DA-63. Formato y orden de la vista de un periodo (T06)

- **Qué faltaba:** §6.1 la excluye de la paginación, pero no fija el orden.
- **Qué se propone:** se mantiene §6.1. Mismo formato que los listados sin paginar, como `GET /metricas`: `{"datos": [...]}`, sin `meta`, sin leer `pagina` ni `por_pagina`. Por `codigo` ascendente, también en `GET /tipos-prueba`.
- **Estado:** **propuesta, pendiente de aprobación de Luis.**
- **Privacidad o permisos:** no.
- **Cómo se revierte:** cambiar el orden o el formato en T06, antes de implementarla.
