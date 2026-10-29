# Plan F1-H2-T04: dominio del riesgo académico (REVISIÓN DE PLAN)

**Estado:** plan para aprobar. No se escribió código de T04. El agente se detiene aquí (`docs/hitos/f1-h2.md` §0.1 y T04).

**Reglas y decisiones que cubre:** RN-01 a RN-05, DD-20, DD-43, DD-44, DD-47, DD-54. **Rutas:** `src/Academico/Dominio/**`, `tests/Unit/Academico/**`, `tests/Arch/**`, reportes. No toca base de datos, `config` ni framework.

## 1. Principios

1. Dominio puro: sin Eloquent, facades, `Carbon`, `config` ni `float`. Solo `NumeroDecimal` (T02) para promedios, umbrales y porcentajes.
2. **Los parámetros llegan por argumento** (`ParametrosDeRiesgo`). Ninguna constante de umbral, de calificación mínima ni de escala de negocio vive en el código. El único literal numérico de negocio permitido es la escala de redondeo `2` de RN-04 (ver pregunta 4).
3. Redondeo único: `NumeroDecimal::redondear(2)` (HALF_UP). La fila 7.495 se cubre porque el promedio entra como `NumeroDecimal::desdeTexto('7.495')`; con `float` (7.495 ≈ 7.49499999…) daría 7.49 y `en_observacion`.
4. Cada clase es `final`, inmutable y con constructor que valida sus invariantes.

## 2. Firmas públicas propuestas (namespace `Src\Academico\Dominio`)

```php
enum AmbitoDeCriterio: string { case General = 'general'; case Materia = 'materia'; }
enum NivelDeRiesgo: string { case SinRiesgo = 'sin_riesgo'; case EnObservacion = 'en_observacion'; case EnRiesgo = 'en_riesgo'; }

final class CriterioDeAprobacion                       // RN-01, RN-03
{
    public function __construct(AmbitoDeCriterio $ambito, NumeroDecimal $calificacionMinima);
    public static function resolver(?self $propio, self $general): self;   // el propio prevalece e indica cuál se aplicó
    public function aplicaA(): AmbitoDeCriterio;                            // ambito()
    public function calificacionMinima(): NumeroDecimal;
    public function aprueba(NumeroDecimal $calificacion): bool;             // igual al mínimo aprueba
}

final class CalificacionDeMateria                      // RN-02, DD-43
{
    /** @param list<NumeroDecimal> $parciales */
    public static function calcular(?NumeroDecimal $final, array $parciales): ?NumeroDecimal;
    // final si existe; si no, media de parciales (HALF_UP, 2 decimales); sin ninguna: null. El 0.00 es calificación.
}

final class MateriaEvaluada                            // RN-03, DD-47
{
    public function __construct(?NumeroDecimal $calificacion, CriterioDeAprobacion $criterio);
    public function calificacion(): ?NumeroDecimal;
    public function criterio(): CriterioDeAprobacion;
    public function aprobada(): ?bool;                 // null si no hay calificación (ni aprobada ni reprobada)
}

final class ConteoDeAsistencia                         // RN-05, DD-44
{
    public function __construct(int $presentes, int $faltasJustificadas, int $faltas);   // >= 0
    public function registros(): int;
    public function porcentaje(): ?NumeroDecimal;      // (presentes + justificadas) / registros * 100, 2 decimales HALF_UP; null sin registros
}

final class ParametrosDeRiesgo                         // valor; lo arma Aplicacion desde parametros_institucionales
{
    public function __construct(
        NumeroDecimal $promedioSinRiesgoMinimo,        // 7.50
        NumeroDecimal $promedioObservacionMinimo,      // 7.00
        int $reprobadasObservacion,                    // 1
        int $reprobadasRiesgo,                         // 2
    );
}

final class RiesgoAcademico                            // RN-04
{
    public static function nivel(?NumeroDecimal $promedio, int $materiasReprobadas, ParametrosDeRiesgo $parametros): ?NivelDeRiesgo;
}

final class ResumenAcademico                           // RN-02, RN-04, RN-05 (resultado del cálculo)
{
    /** @param list<MateriaEvaluada> $materias */
    public static function calcular(array $materias, ConteoDeAsistencia $asistencia, ParametrosDeRiesgo $parametros): self;
    public function promedio(): ?NumeroDecimal;
    public function materiasReprobadas(): int;
    public function materiasSinCalificar(): int;
    public function datoIncompleto(): bool;            // hay materias sin calificar, o ninguna materia tiene calificación
    public function porcentajeAsistencia(): ?NumeroDecimal;
    public function nivelRiesgo(): ?NivelDeRiesgo;
}
```

## 3. Reglas de cálculo

- **Promedio general:** media (`NumeroDecimal::media`, 2 decimales HALF_UP) de las calificaciones de las materias que tienen una; `null` si ninguna.
- **Reprobadas:** materias con calificación y `aprobada() === false`. Las sin calificación no cuentan como reprobadas y suben `materiasSinCalificar`.
- **Nivel** (`RiesgoAcademico::nivel`; el promedio se redondea a 2 decimales antes de comparar; prevalece el más grave que se cumpla):
  1. `null` si el promedio es `null`.
  2. `EnRiesgo` si `promedio < promedioObservacionMinimo` **o** `reprobadas >= reprobadasRiesgo`.
  3. `EnObservacion` si `promedio < promedioSinRiesgoMinimo` **o** `reprobadas >= reprobadasObservacion`.
  4. `SinRiesgo` en otro caso.
- **La asistencia no entra en el nivel** (RN-05): `ResumenAcademico::calcular` la copia al resultado y no la pasa a `RiesgoAcademico`; una prueba lo comprueba con asistencia 0 % y riesgo `SinRiesgo`.

## 4. Pruebas (todas las filas de §7.9, una prueba por fila; `tests/Unit/Academico/`)

| Archivo | Cubre |
|---|---|
| `CalificacionDeMateriaTest` | 9 filas de "Calificación de una materia" (incluye 7.005 → 7.01, 22 ÷ 3 → 7.33, el cero como calificación y la ausencia como `null`) |
| `CriterioDeAprobacionTest` y `MateriaEvaluadaTest` | 6 filas de "Materia aprobada o reprobada" (6.995 → 7.00 aprueba) y la resolución del criterio (el propio prevalece aunque pase el general; indica el ámbito) |
| `ResumenAcademicoTest` | 7 filas de "Promedio general" con `dato_incompleto` y las combinaciones de reprobadas |
| `ConteoDeAsistenciaTest` | 6 filas de "Porcentaje de asistencia" (80.00, 33.33, 66.67, sin registros → `null`) |
| `RiesgoAcademicoTest` | las 13 filas de "Riesgo académico" (la 3 con `'7.495'`; las 12 y 13 con parámetros distintos), más los límites 7.50/7.49, 7.00/6.99 y 1/2 reprobadas con ambos lados |
| `tests/Arch/` | el dominio de Académico no usa `Illuminate`, `config` ni `float` (la regla de T02 ya cubre `src/*/Dominio/`) |

Infection (mínimo 80 % y 85 %) debe pasar sobre estas clases; se escriben pruebas de frontera para matar los mutantes de `<`/`<=`/`>=` y de los incrementos. Los mensajes de `ReglaDeNegocioViolada` que lance el dominio no se muestran al usuario en T04.

## 5. Preguntas abiertas (necesitan respuesta antes de implementar)

1. **Enumeraciones.** La regla del proyecto prohíbe "enumeraciones que la tarea y los documentos no nombren". Los tres conjuntos de valores (`general`/`materia`, `sin_riesgo`/`en_observacion`/`en_riesgo`, y los estados de asistencia) sí están nombrados en §5.1 y §6.2, pero T04 no dice "enum". *Recomiendo* `AmbitoDeCriterio` y `NivelDeRiesgo` como `enum` (se usan en la salida del dominio). El estado de asistencia **no** entra al dominio: `ConteoDeAsistencia` recibe cantidades, de modo que `EstadoDeAsistencia` se crea en T13 si hace falta.
2. **Entrada de la asistencia:** conteos (la capa de aplicación los obtiene con un `COUNT` agrupado, sin traer filas) en lugar de una lista de registros. *Recomiendo* conteos.
3. **Invariantes extra de `ParametrosDeRiesgo`:** exigir `promedioObservacionMinimo <= promedioSinRiesgoMinimo`, `0 < reprobadasObservacion <= reprobadasRiesgo` y valores entre 0 y 10. Lanzarían `ReglaDeNegocioViolada('parametros_de_riesgo_invalidos')`. La especificación no las pide; *recomiendo* incluirlas (la más estricta, §0.3) porque un parámetro mal cargado cambiaría el riesgo de todos los jugadores. Cada una se cubre con una prueba; las filas 12 y 13 las cumplen.
4. **Escala 2.** RN-04 y DD-43 fijan dos decimales. La escala vive como constante privada de las reglas (`ESCALA = 2`), no en la tabla de parámetros: no es un umbral y la especificación no la parametriza. Si prefieres leerla de `parametros_institucionales`, hay que ampliar §5.3 (cambio de contrato).
5. **Redondeo de cada calificación individual.** DD-43 redondea la calificación de la materia antes de compararla con el criterio; el promedio general es la media de esas calificaciones **ya redondeadas** (por eso 7.49 y 7.50 dan 7.50). Es lo que dicen las filas; lo dejo explícito por si se esperaba la media de las calificaciones sin redondear.
6. **`aprobada` nulo** cuando no hay calificación: la fila lo exige ("ni aprobada ni reprobada"); se expone como `?bool`.

## 6. Fuera de alcance de T04 (para tareas siguientes)

Leer parámetros y criterios de la base y armar `ParametrosDeRiesgo` y `CriterioDeAprobacion` desde filas (T09), persistir el resumen (T09), exponerlo por HTTP (T14), la marca "posiblemente desactualizado" (H6) y las alertas (H6).
