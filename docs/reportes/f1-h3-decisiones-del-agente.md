# Decisiones del agente en F1-H3

Decisiones que la especificación no cubre (`docs/hitos/f1-h2.md` §0.3, aplicable a H3 por `docs/hitos/f1-h3.md` §0). Cada una dice qué faltaba, qué se eligió, por qué y cómo se revierte.

## DA-01 (T01): la columna del autor en los historiales es `usuario_id`

- **Qué faltaba:** §5.1 dice que cada historial guarda «`autor_id` y `ocurrido_en` (`docs/arquitectura.md` §9)», pero §9 y la macro `historial()` de Compartido llaman `usuario_id` al autor.
- **Qué se eligió:** la macro `Schema::historial()` sin cambios (`usuario_id`), como todos los historiales de H1 y H2.
- **Por qué:** la especificación remite a §9 y la macro es la única forma de crear historiales idénticos (arquitectura §8.1, regla 5). «`autor_id`» se lee como descripción, no como nombre.
- **Cómo se revierte:** una migración nueva que renombre la columna en las ocho tablas; ningún código de H3 depende del nombre salvo `EscritorDeHistorial`.

## DA-02 (T01): el resultado de un partido existe si y solo si el partido está `jugado`

- **Qué faltaba:** §5.1 pide un `CHECK` «que goles existan solo si estado es `jugado`»; no dice si un partido `jugado` puede quedar sin goles, ni si puede haber un gol sin el otro.
- **Qué se eligió:** `ck_partidos_resultado`: `goles_favor` y `goles_contra` son ambos nulos o ambos no nulos, y no son nulos exactamente cuando `estado = 'jugado'`. Aparte, `ck_partidos_goles`: cada uno entre 0 y 99.
- **Por qué:** es la lectura más estricta (§0.3, regla 3) y coincide con §3.4: un partido pasa a `jugado` solo al capturar el resultado.
- **Cómo se revierte:** una migración nueva que reemplace el `CHECK` por la implicación simple.

## DA-03 (T01): `convocatorias.posicion_id` admite nulo

- **Qué faltaba:** §5.1 dice que `rol_alineacion` es nulo mientras no haya alineación, pero no lo dice de `posicion_id`. La convocatoria se crea solo con `jugador_id` (§6.2) y la posición llega con la alineación (T07).
- **Qué se eligió:** `posicion_id` nulo, con clave foránea a `posiciones`. No se agrega un `CHECK` que ate `posicion_id` a `rol_alineacion`: §5.1 no lo nombra.
- **Por qué:** sin nulo no se podría convocar antes de definir la alineación.
- **Cómo se revierte:** una migración nueva con `not null` (exigiría la posición al convocar).
