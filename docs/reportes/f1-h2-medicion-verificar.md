# Medición de `make verificar` (sin carga ajena)

## Medición antes de T12 (pedida por el responsable tras la auditoría de C2)

Una sola corrida el 28 de octubre de 2026, sobre `e30e54f` más las pruebas del FIX de privacidad (`F1-H2-FIX: pruebas de privacidad de la auditoria de C2`, 785 pruebas). Antes de empezar, `docker stats` daba app 0,73 %, db 0,03 %, worker 0,00 % y scheduler 0,12 % de CPU. Durante la corrida no se ejecutó nada más: ni lecturas de archivos ni otros comandos. Cada etapa se ejecutó con el mismo comando que `composer verificar`, dentro del contenedor `app` y con cronómetro propio (guion `bash -s`, una etapa tras otra; se detiene en la primera que falle).

| Etapa | Comando | Segundos | Resultado |
|---|---|---:|---|
| Pint | `pint --test` | 4 | `PASS ... 356 files` |
| PHPStan nivel 7 | `phpstan analyse --memory-limit=1G --no-progress` | 15 | `[OK] No errors` |
| Infection | `infection --no-progress --with-uncovered` | 64 | 335 generados, 335 eliminados; MSI 100 %; MSI cubierto 100 % (Infection: `Time: 1m 3s`) |
| Verificación de mutación | `bin/verificar-mutacion.php build/infection/resumen.log` | 0 | `Mutación completa: 335 mutantes evaluados, ninguno omitido ni con tiempo agotado.` |
| Pest con cobertura | `pest --coverage …` | 332 | `Tests: 785 passed (4010 assertions)`, `Duration: 318.71s` |
| Verificación de cobertura por capa | `bin/verificar-cobertura.php` | 0 | Dominio 100,0 % (95), Aplicación 100,0 % (85), Infraestructura y HTTP 99,8 % (70) |
| Auditoría | `composer audit` | 5 | `No security vulnerability advisories found.` |
| **Total** | | **420 (7 min 0 s)** | **pasa** |

**Conclusiones.**

- 7 min 0 s, con 8 min de holgura frente al límite de 15 min de §0.4. Pest es el 79 % del tiempo (~0,41 s por prueba).
- Comparación: 344 s al cierre de C2 (783 pruebas) y 604 s en la corrida del auditor (con lecturas ligeras en paralelo, sin arranque en frío). La variabilidad del entorno sigue siendo de hasta 1,8 veces para el mismo código.
- Proyección para el Lote D: T12 a T14 agregan del orden de 100 a 150 pruebas de ruta (~60 s más en condiciones normales). Una corrida con la variabilidad observada podría acercarse al límite. Regla del responsable: si una corrida de `make verificar` pasa de 15 minutos, el lote se detiene.

## Medición de T04

Una sola corrida, con el commit `5db7870` (T04), sin otros procesos ni lecturas por terminal durante la medición. Cada etapa se ejecutó con el mismo comando que `composer verificar`, dentro del contenedor `app`, con cronómetro por etapa. No se cambió código.

| Etapa | Comando | Segundos | Tiempo | Resultado |
|---|---|---:|---|---|
| Pint | `pint --test` | 6 | 6 s | pasa |
| PHPStan nivel 7 | `phpstan analyse` | 6 | 6 s | sin errores |
| Infection | `infection --with-uncovered` | 56 | 56 s | 305 mutantes eliminados; MSI 100 %; MSI cubierto 100 % |
| Pest con cobertura | `pest --coverage …` | 485 | 8 min 05 s | 596 pruebas, 2920 aserciones |
| Verificación de cobertura por capa | `bin/verificar-cobertura.php` | 1 | 1 s | pasa |
| Auditoría | `composer audit` | 3 | 3 s | sin avisos |
| **Suma de `composer verificar`** | | **557** | **9 min 17 s** | **pasa** |
| *Pest sin cobertura (solo medición, no forma parte de `verificar`)* | `pest` | 487 | 8 min 07 s | 596 pruebas, 2920 aserciones |

## Conclusiones

- La suma de `composer verificar` es **9 min 17 s**, por debajo de los 15 min de §0.4. No se detiene el lote por este motivo.
- La corrida de T04 que tardó 31 min 04 s estaba contaminada por mis lecturas de terminal en paralelo (la terminal y Docker comparten el montaje del repositorio). Esa cifra no es representativa.
- Casi todo el tiempo es Pest (485 s); la cobertura (pcov) casi no cuesta nada sobre Pest sin cobertura (487 s frente a 485 s). Pint, PHPStan, Infection y la auditoría suman 71 s.
- Margen: 5 min 43 s hasta el límite. Las 596 pruebas crecerán con T05 a T15; a este ritmo (~0,81 s por prueba) el límite se alcanzaría cerca de las 1100 pruebas. Conviene vigilarlo y medir de nuevo al terminar el lote C2.
- Regla que sigo desde ahora: mientras corre `make verificar` no ejecuto nada más por terminal.

## Sobre el plan de T04: ¿había una sexta pregunta?

Sí: `f1-h2-plan-t04.md` §5 tiene **seis** preguntas, no cinco:

1. Enumeraciones (`AmbitoDeCriterio`, `NivelDeRiesgo`; sin enum de asistencia).
2. Entrada de la asistencia como conteos.
3. Invariantes extra de `ParametrosDeRiesgo`.
4. Escala 2 como constante del código.
5. Redondeo de cada calificación antes del promedio.
6. `aprobada()` nulo cuando no hay calificación (`?bool`).

No recibí en mi contexto las respuestas que dices haber dado (el mensaje que aprobó el plan no las traía); por eso adopté las recomendaciones del plan en DA-08, y ya las aceptaste. Si tus respuestas a 1 a 5 eran distintas, dímelo y se corrige en un `F1-H2-FIX`.
