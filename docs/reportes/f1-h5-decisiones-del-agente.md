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
