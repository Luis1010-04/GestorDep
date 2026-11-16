# F1-H6-T05: plan de la vista para convocar y el orden por desempeño

**Fecha:** 15 de noviembre de 2026 · **Especificación:** `docs/hitos/f1-h6.md` v1.9 · **Estado:** plan para aprobación. No se escribió código de T05.
**Base:** DA-94, DA-95 y DA-98; formas de respuesta de DA-105 (§6.2).

## 1. Qué construye

| Ruta | Quién | Respuesta |
|---|---|---|
| `GET /jugadores/{jugador}/vista-para-convocar` | Entrenador con alcance | La vista de §6.2 |
| `GET /equipos/{equipo}/jugadores/para-convocar` | Entrenador del equipo | Lista paginada de esa misma vista, ordenada por desempeño (DA-95) |

- **`ConsultaDeVistaParaConvocar`** (`src/Consultas/Aplicacion/`, SQL en `Infraestructura/`): arma las cinco dimensiones de uno o varios jugadores, siempre a través de `AlcanceDeJugadores`.
- **`CalculadorDeDesempeno`** (`src/Consultas/Aplicacion/`, `docs/arquitectura.md` §4.1): ordena por el promedio de la última evaluación; sin evaluación o con `datos_insuficientes`, al final; los empates conservan el orden de entrada (RN-18).
- **`MarcaDeDesactualizacion`** (`src/Consultas/Aplicacion/`): `posiblemente_desactualizado` es verdadero si la última captura es anterior a hoy de negocio menos `desactualizacion.dias`. Se calcula al leer; no se guarda.

## 2. De dónde sale cada dimensión

Todas con los datos del entrenador consultante (RN-14), salvo la disponibilidad y la situación académica, que son compartidas.

| Dimensión | Fuente | `actualizado_en` |
|---|---|---|
| `disponibilidad` | Estado oficial vigente de Salud (el mismo dato de H5) y si hay un aviso pendiente | Del estado; `null` si es implícito |
| `rendimiento_reciente` | Última fila de `evaluaciones_rendimiento` de (jugador, equipo, entrenador consultante) | `calculado_en` |
| `situacion_academica` | `resumenes_academicos` del periodo vigente, sin materias (resumen simplificado de H2) | `calculado_en` |
| `asistencia_entrenamientos` | Porcentaje de H3 sobre los entrenamientos realizados de los equipos del consultante | Fecha del último entrenamiento con asistencia capturada |
| `estado_fisico` | Última medición y última prueba por tipo que registró el consultante | La más reciente de las dos |
| `progreso` | Primer y último punto de la serie de calificaciones generales del consultante | Fecha del último partido |

## 3. Orden y paginación

- El orden se calcula sobre **todos** los jugadores del equipo y después se pagina; si se paginara antes, cada página quedaría ordenada por separado.
- «Orden de entrada» (m6 de la auditoría): propongo apellidos, nombres e `id`, que es estable entre páginas. **Necesita tu confirmación**: DA-95 no lo define.
- El promedio se compara con el decimal de Compartido, nunca con `float`.

## 4. Pruebas previstas

- §7.5, las tres filas, sobre `CalculadorDeDesempeno` (unitarias) y por la ruta.
- §7.7: medición de hace 31 días, marcada; de hace 30, no; sin evaluación, `promedio` nulo y `resultado` `datos_insuficientes`.
- Jugador en dos equipos con dos entrenadores: cada uno ve solo sus evaluaciones, mediciones y asistencia.
- 404 para un jugador sin alcance, en baja o inexistente, igual byte a byte; 403 para el docente.
- Contrato de las dos rutas contra `docs/api/openapi.yaml`.
- El valor de `desactualizacion.dias` se lee de la tabla: cambiarlo en la prueba cambia la marca (RNF-09).

## 5. Lo que este plan no puede resolver solo

| # | Asunto | Recomendación |
|---|---|---|
| 1 | **Orden de entrada** para los empates y para la paginación (§3) | Apellidos, nombres e `id` |
| 2 | **Equipo del jugador en `vista-para-convocar`.** La ruta no lleva equipo y el rendimiento es por equipo. Con un jugador en dos equipos del mismo entrenador no queda dicho cuál se usa | `filtro[equipo_id]` opcional; sin él, el equipo con la evaluación más reciente del consultante |
| 3 | **`src/Rendimiento/Aplicacion/**` «solo lectura de evaluaciones».** `docs/arquitectura.md` §4.3 ya permite a Consultas leer con SQL de solo lectura | Leer `evaluaciones_rendimiento` desde `src/Consultas/Infraestructura/` y no tocar Rendimiento |
| 4 | **`lang/es/**`** no está en las rutas de T05 y el 403 y el 404 reutilizan mensajes existentes | No hace falta, salvo que aparezca un mensaje nuevo; entonces me detengo |
| 5 | **Aviso pendiente para el entrenador** | Los entrenadores del jugador ven los avisos pendientes (RN-16); sin cambio |
| 6 | **Zona de «hoy de negocio»** (m5) | America/Mexico_City, como en H5 (`HoyDeNegocioSegunReloj`) |

## 6. Archivos previstos

Dentro de las rutas de T05: `src/Consultas/**`, `app/Http/**` (un controlador y dos recursos), `routes/api.php` (las dos rutas), `docs/api/openapi.yaml` y `tests/**`.

## 7. Dependencias

- T06 (vista consolidada de la convocatoria) devuelve esta misma vista por cada convocado: no puede empezar antes de T05.
- No depende de T03: la vista no muestra alertas.
