# Plan de F1-H4-T04: pasa, no pasa y datos insuficientes (HU-16, RN-08, RN-07)

**Fecha:** 30 de octubre de 2026 · **Rama:** `hito/f1-h4` (después de `c0d36cf`, T03) · **Especificación:** `docs/hitos/f1-h4.md` v1.0.4
**Estado:** aprobado por el responsable el 30/10/2026, con estas decisiones (especificación v1.0.5):

- Solo cuentan las calificaciones que el entrenador registró de ese jugador en ese equipo (punto 1 de §5).
- Las últimas N se ordenan por fecha del partido, con el puerto `InicioDePartidos` en `Compartido/Aplicacion` y su adaptador en `Competencia/Infraestructura` (opción a del punto 2). Desempate por fecha de inicio y después por `id`. Rutas autorizadas.
- Corrección de un jugador que ya no está en el equipo y no tiene umbral propio: el `posicion_id` guardado en el rendimiento se usa solo para elegir la plantilla de fábrica (punto 3).
- `calculado_en` es la hora actual cuando no hay evaluaciones; la columna es NOT NULL (punto 4).
- Se compara el promedio ya redondeado a dos decimales. Prueba: 5.875 → 5.88 con umbral 5.88 da pasa. La regla queda en §3.3.

El resto del documento es el plan tal como se propuso.

## 1. El cálculo, en el Dominio y sin base de datos

`src/Rendimiento/Dominio/`:

- **`ResultadoDeRendimiento`:** enumeración con `pasa`, `no_pasa` y `datos_insuficientes`, los valores del `CHECK` de T01.
- **`EvaluacionCalculada`:** resultado, promedio (`?NumeroDecimal`) y partidos considerados.
- **`CalculoDeDesempeno::evaluar(CriterioDeEvaluacion $criterio, list<int> $calificaciones): EvaluacionCalculada`.** Las calificaciones llegan de la más reciente a la más antigua.
  - **Datos insuficientes:** si hay menos de N (N es la ventana), el resultado es `datos_insuficientes`, el promedio es nulo y `partidos_considerados` es el número que hay (§3.3, RN-08).
  - **Promedio:** con N o más, se toman las N primeras y se calcula `NumeroDecimal::media(..., 2)`, que redondea HALF_UP a dos decimales. `partidos_considerados` es N.
  - **Comparación:** pasa si el promedio redondeado es mayor o igual que la calificación mínima; si es menor, no pasa (§3.3, `[Decisión por defecto]`). Se compara el promedio ya redondeado, el mismo que se guarda y se muestra, para que el resultado y el número que ve el entrenador nunca se contradigan.
- **Pruebas unitarias** (`tests/Unit/Rendimiento/CalculoDeDesempenoTest.php`):
  - §7.3 filas 1 a 4: cuatro de cinco; 6.60 pasa; 5.40 no pasa; 6.00 contra 6.00 pasa.
  - Más de N calificaciones: solo cuentan las N más recientes.
  - Redondeo en el borde: con N = 8 y calificaciones que suman 47, la media es 5.875, que redondea a 5.88. Con un mínimo de 5.88 pasa; sin redondear no pasaría.
  - Ventana 1.
  - Lista vacía.
  - Mínimo decimal (6.50).

  `make mutacion` debe matar todos los mutantes de las comparaciones.

## 2. Qué rendimientos cuentan

Los del entrenador (`registrado_por`), del jugador **en ese equipo** (`equipo_id`, punto 1 de §5), no eliminados, ordenados del partido más reciente al más antiguo (punto 2 de §5). El alcance se comprueba antes (`ComprobarJugadorDelEquipo` de T03, §3.3).

## 3. Persistencia en la misma transacción (§3.3, §7.3 filas 6 y 7)

- **`RecalcularEvaluacion`** (Aplicación): lee el criterio que se aplica, lee las calificaciones, calcula e inserta una fila en `evaluaciones_rendimiento`.
  - **Criterio:** el umbral vigente del entrenador (`umbral_id`, y `plantilla_id` si el umbral vino de una plantilla) o, si no hay, la plantilla de fábrica de la posición actual del jugador en el equipo (`plantilla_id`).
  - **Columnas:** se copian `calificacion_minima` y `ventana_partidos` (T01, v1.0.2), y `calculado_en` es el instante del reloj.
- **Dónde se llama:** desde `CapturarRendimiento`, `CorregirRendimiento` y `FijarUmbral`, dentro de la misma transacción que la escritura.
- **Puerto `Transaccion`:** para que la escritura y la evaluación vayan juntas, un puerto en `src/Rendimiento/Aplicacion` con un adaptador que usa `ConnectionInterface::transaction`. Las transacciones que ya abren los repositorios quedan anidadas como puntos de guardado. Si falla el insert de la evaluación, se deshace la captura, la corrección o el umbral.
- **`RepositorioDeEvaluaciones`:** `insertar` y `ultimaDe(jugador, equipo, entrenador)`, ordenada por `calculado_en` descendente y después `id` descendente. El `id` es UUID v7 y desempata dos cálculos en el mismo segundo.
- **Pruebas:**
  - §7.3 fila 6: una captura que cambia el resultado inserta una evaluación nueva y la lectura devuelve la última.
  - §7.3 fila 7: un cambio de umbral inserta una evaluación con el umbral nuevo.
  - Una corrección inserta una evaluación.
  - Una captura que falla (422) no deja evaluación.

## 4. Lectura del desempeño

- **Ruta:** `GET /equipos/{equipo}/jugadores/{jugador}/desempeno` en el grupo `rol:entrenador`, con el control de pertenencia de T03, que incluye bajas en la lectura (DA-28).
- **Respuesta:** la última evaluación guardada; si no hay ninguna, el cálculo sin guardarlo, con `calculado_en` igual al instante actual.
- **Campos (§6.2):** `resultado`, `promedio` (cadena con dos decimales o nulo), `calificacion_minima`, `ventana_partidos`, `partidos_considerados` y `calculado_en`.
- **Pruebas de §7.3:**
  - filas 1 a 5 de punta a punta: la fila 5 con E1 en A y E2 en B, cada uno con lo suyo (RN-14);
  - §7.4 fila 5: un jugador ajeno al alcance de E2 da el 404 de uno inexistente;
  - docente 403 y sin sesión 401;
  - UUID no v7, 404;
  - contrato en `openapi.yaml`.

## 5. Decisiones que necesito antes de codificar

1. **¿Las calificaciones se filtran por equipo?** §3.3 dice «los partidos en los que el entrenador registró al jugador», sin nombrar el equipo. Pero el umbral, la evaluación y la ruta son por jugador y equipo.
   - **Recomiendo filtrar por `equipo_id`:** E3 entrena A y B, y el desempeño de F1 en A no debería mezclar lo que E3 capturó de F1 en B, con otro umbral.
   - La alternativa es contar todos los partidos del entrenador con el jugador, en cualquier equipo.
2. **Orden de «las últimas N».** Para ordenar por la fecha del partido hace falta `partidos.inicia_en`, que es de Competencia. Las rutas de T04 solo permiten `src/Rendimiento/**`.
   - **(a) Recomendada:** por fecha del partido (`inicia_en` descendente, con `id` del rendimiento como desempate), con un puerto nuevo `InicioDePartidos` en `src/Compartido/Aplicacion` (`de(list<Identificador>): array<string, DateTimeImmutable>`), su adaptador en `src/Competencia/Infraestructura` y una línea en `CompetenciaServiceProvider`, como `ConvocatoriaDePartido` en T02. Requiere autorizar esas tres rutas y agregarlas a §8 en v1.0.5.
   - **(b)** Por el orden de captura (`rendimientos_partido.creado_en`), sin rutas nuevas. Problema: un partido viejo capturado tarde contaría como el más reciente.
3. **Corrección de un jugador que ya dejó el equipo, sin umbral propio.** No hay posición actual (DD-05) y por tanto no hay plantilla de fábrica que aplicar. Recomiendo usar la posición con la que se capturó el rendimiento corregido (`rendimientos_partido.posicion_id`) solo para elegir la plantilla. La alternativa es no insertar evaluación en ese caso, que sería una excepción a §3.3.
4. **`calculado_en` de una lectura sin evaluación guardada.** Recomiendo el instante actual, porque el cálculo es de ese momento, y no nulo. §6.2 no dice que pueda ser nulo.

Decisiones menores que tomaré como DA salvo objeción:

- Una corrección que no cambia la calificación también inserta evaluación, porque §3.3 dice «cada captura o corrección».
- El cambio de umbral recalcula solo para el entrenador que lo hizo.
- `ResultadoDeRendimiento` es una enumeración nueva con los tres valores que ya fija el `CHECK` de T01.

## 6. Archivos

`src/Rendimiento/**` (los nuevos de §1, §3 y §4, `CapturarRendimiento`, `CorregirRendimiento`, `FijarUmbral` y los enlaces), `app/Http/Controllers/DesempenoController.php`, `app/Http/Resources/DesempenoRecurso.php`, `routes/api.php`, `docs/api/openapi.yaml`, `tests/**` y los reportes. Si se aprueba la opción (a) del punto 2, también el puerto, el adaptador y el enlace.

Pruebas primero con la falla pegada, `make verificar` y `make mutacion`, el commit `F1-H4-T04`, el reporte, el tiempo de Pest en `f1-h4-progreso.md` y el resumen del Lote B. Sin push, PR ni etiquetas.
