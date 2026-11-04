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
- **Estado:** aprobada por el responsable con el plan de T02 (3/11/2026). T02 aún no se implementa.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** agregar la regla a la solicitud.

## DA-24. Orden entre 422 y 409 `jugador_en_baja` en un aviso (T02)

- **Qué faltaba:** §6.1 fija el 404 antes del 422, pero no el orden entre el 422 y el 409 `jugador_en_baja`.
- **Qué se eligió:** primero la validación del cuerpo (422) y después el 409, como en H2.
- **Estado:** aprobada por el responsable con el plan de T02 (3/11/2026). T02 aún no se implementa.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** comprobar la baja en `authorize()`.

## DA-25. Listado de avisos para un usuario con los dos roles (T02)

- **Qué faltaba:** §5.3 define la unión de alcances para ver el estado oficial y crear avisos, pero no para listarlos.
- **Qué se eligió:** si el usuario entrena al jugador, ve todos sus avisos; si solo le da clase, ve los que él creó.
- **Estado:** aprobada por el responsable con el plan de T02 (3/11/2026). T02 aún no se implementa.
- **Privacidad o permisos:** **sí** (lectura).
- **Cómo se revierte:** cambiar la condición del listado.

## DA-26. Comprobar el estado oficial en §7.1 filas 1 y 7 (T02)

- **Qué faltaba:** §7.1 filas 1 y 7 piden comprobar que el estado oficial no cambia, pero `GET /jugadores/{jugador}/estado-disponibilidad` llega hasta T03.
- **Qué se eligió:** T02 comprueba que `estados_disponibilidad` no cambia, y T03 agrega la comprobación por la API.
- **Estado:** aprobada por el responsable con el plan de T02 (3/11/2026). T02 aún no se implementa.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** no aplica: T03 completa la comprobación.

## DA-27. Mensaje de `jugador_en_baja` al crear un aviso (T02)

- **Qué faltaba:** el mensaje existente dice «no se puede asignar, inscribir ni capturar calificaciones o asistencias», que no describe un aviso, y T02 solo puede agregar claves nuevas en `lang/es`.
- **Qué se eligió:** una clave nueva, solo para avisos, con el texto «El jugador está en baja: no se pueden crear avisos.». La clave existente no cambia y el código de la respuesta sigue siendo `jugador_en_baja`. El texto se elige en un punto local del módulo de avisos. Si hay que tocar `FormatoDeError.php`, las pruebas de H2 a H4 deben demostrar que sus respuestas siguen idénticas.
- **Estado:** aprobada por el responsable con el plan de T02 (3/11/2026). T02 aún no se implementa.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** quitar la clave nueva y usar la existente.

## DA-28. Orden entre 422 y 409 `aviso_no_pendiente` (T02)

- **Qué faltaba:** DA-24 fija el orden para `jugador_en_baja` al crear, pero nada lo fija al descartar.
- **Qué se eligió:** primero la validación del cuerpo (422) y después el 409 `aviso_no_pendiente`, por coherencia con DA-24.
- **Estado:** por confirmar por el responsable.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** comprobar el estado del aviso en `authorize()`.

## DA-29. Texto del mensaje de `aviso_no_pendiente` (T02)

- **Qué faltaba:** §6.3 define el código, pero no su mensaje.
- **Qué se eligió:** «El aviso ya no está pendiente de confirmación.», en `lang/es/errores.php`.
- **Estado:** por confirmar por el responsable.
- **Privacidad o permisos:** no.
- **Cómo se revierte:** cambiar el texto en `lang/es/errores.php`.
