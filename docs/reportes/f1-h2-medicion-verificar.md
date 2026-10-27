# Medición de `make verificar` (sin carga ajena)

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
