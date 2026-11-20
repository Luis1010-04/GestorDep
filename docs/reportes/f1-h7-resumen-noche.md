# Resumen de la noche del 19 al 20/11/2026 (H7: T02, T03 y T04)

Autorización del responsable: excepción a «una tarea por sesión» para ejecutar F1-H7-T02, T03 y T04 en orden. Para cada tarea:
- pruebas primero y `make verificar`;
- un commit, push normal y PR en borrador contra `main`;
- sin merge, sin etiquetas, sin `make fresh` ni `migrate:rollback`;
- detenerse si el plan contradice la especificación o necesita una decisión nueva.

## Resultado
| Tarea | Estado | PR | CI |
|---|---|---|---|
| F1-H7-T02 | **Hecha**, en espera de revisión | [#27](https://github.com/Luis1010-04/GestorDep/pull/27) (borrador), commit `faa3449` | push: [verde](https://github.com/Luis1010-04/GestorDep/actions/runs/44682829045) · pull_request: [verde](https://github.com/Luis1010-04/GestorDep/actions/runs/44682864764) |
| F1-H7-T03 | **Detenida antes de escribir pruebas**: contradicción con la especificación (ver abajo) | — | — |
| F1-H7-T04 | **Detenida antes de escribir pruebas**: la misma contradicción | — | — |

## F1-H7-T02
- Rutas `GET /api/v1/jugadores/{jugador}/historial/calificaciones` y `.../historial/asistencias-clase`, con la paridad de DA-124: 401, 403 por rol, y 404 si el docente no tiene ninguna materia con el jugador.
- 10 pruebas, que fallaron antes de implementar por la razón correcta (404 de ruta inexistente).
- `make verificar`: código 0, **342 s**, 2348 pruebas y 14223 aserciones, cobertura 100,0 / 100,0 / 99,5 %.
- Reporte: `docs/reportes/f1-h7-t02.md`. Log: `docs/reportes/logs/f1-h7/f1-h7-t02-verificar-1.log`.
- **Duda abierta:** §6.2 de la especificación pide instantes «con la zona de presentación», con un ejemplo en `-06:00`. `docs/convenciones-api.md` fija ISO 8601 en UTC, y T02 responde en UTC, como el resto de la API. Hay que corregir el texto de §6.2 o decidir lo contrario.

## Por qué se detuvieron T03 y T04
**La contradicción.** D-2 y D-3 (`docs/hitos/f1-h7.md` §3.1) chocan con **DA-18** de H5 (`docs/hitos/f1-h5.md` línea 121, aprobada el 01/11/2026).
- **DA-18:** «Las respuestas no revelan `creado_por`, `confirmado_por`, `registrado_por` ni `registrada_por` (precedente DA-19 de H4). El estado oficial devuelve `lesion_id` solo a quien registró la lesión; a los demás, nulo.»
- **D-2:** el historial se entrega con `datos_anteriores` y `datos_nuevos` completos, sin filtrar campos, a quien ve la fila completa.
- **D-3:** cada entrada muestra el nombre del autor.

**Las instantáneas de historial de H5 contienen justo esos campos.** Revisé las funciones `instantanea()` de los repositorios:

| Recurso | Repositorio | Campos de autor o de lesión que guarda la instantánea |
|---|---|---|
| Avisos de lesión (T03) | `src/Salud/Infraestructura/RepositorioDeAvisosSql.php` | `creado_por`, `confirmado_por`, `descartado_por` |
| Estados de disponibilidad (T03) | `src/Salud/Infraestructura/RepositorioDeEstadosSql.php` | `confirmado_por`, `lesion_id` |
| Lesiones (T03) | `src/Salud/Infraestructura/RepositorioDeLesionesSql.php` | `registrada_por` |
| Mediciones corporales (T04) | `src/CondicionFisica/Infraestructura/RepositorioDeMedicionesSql.php` | `registrado_por` |
| Pruebas físicas (T04) | `src/CondicionFisica/Infraestructura/RepositorioDePruebasFisicasSql.php` | `registrado_por` |

**Consecuencias concretas.**
- **Estados de disponibilidad:** §5.3 de H7 los hace visibles para el docente y para los dos entrenadores. Con D-2, todos ellos recibirían `confirmado_por` y `lesion_id`, que DA-18 reserva o prohíbe. Y si la regla de «fila completa» se aplica de forma estricta, nadie salvo el registrante ve la fila completa, lo que contradice que los tres ven el estado.
- **Avisos:** la lectura existente no revela nunca quién creó, confirmó o descartó un aviso (`AvisoVisible`: «no se revela quién lo creó, confirmó o descartó (DA-18)»). Ningún usuario ve la fila completa por la API, así que con D-2 el historial de avisos no se entregaría a nadie, y con D-3 revelaría al autor.
- **Lesiones, mediciones y pruebas:** solo las ve quien las registró, así que el autor suele ser él mismo. Aun así, entregar `registrada_por` o `registrado_por` contradice la letra de DA-18. Además, no sé si alguien distinto del registrante puede aparecer como autor de una entrada (por ejemplo, una lesión que se cierra al cambiar el estado, DD-63, AB-30). Eso habría que comprobarlo.

**Lo que hace falta decidir** (no propongo solución):
1. Si en H7 prevalece DA-18 o D-2 y D-3 para los historiales de H5.
2. Si la regla de «fila completa» (D-2) se aplica al estado de disponibilidad, que §5.3 hace visible para todos.

Ni T03 ni T04 tienen código, pruebas, ramas ni PR.

## Lo que queda pendiente
- Revisión y merge del PR #27 (T02), y la duda del formato de los instantes.
- La decisión sobre DA-18 frente a D-2 y D-3, para poder planificar T03 y T04.
- T05 a T09: sin tocar, como pide la autorización.

## Notas de proceso
- Ningún `make verificar` de la noche superó 900 s (T02: 342 s).
- No se ejecutaron `make fresh` ni `migrate:rollback`. No hubo merge ni etiquetas.
- Este resumen queda **sin commitear** en el árbol de trabajo. La autorización pide un commit por tarea, y meterlo en la rama de T02 lo mezclaría con su PR. El responsable decide dónde registrarlo.
