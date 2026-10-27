# Plan F1-H2-T05: periodos escolares

Sigue el patrón de `categorias` (H1): consultas con el constructor de consultas, sin historial (la tabla no lo lleva), borrado lógico con `eliminado_en`/`eliminado_por`.

- **Dominio:** `PeriodoEscolar` (invariante `fecha_fin > fecha_inicio`, `rango_de_fechas_invalido`), `RepositorioDePeriodos`.
- **Aplicación:** `CrearPeriodo`, `ListarPeriodos`, `EliminarPeriodo`, `PeriodoVigente` (DD-45: periodo no eliminado cuyo rango contiene el día de `HoyDeNegocio`, extremos incluidos; con varios, el de `fecha_inicio` más reciente; T09 lo reutilizará), `PeriodoDuplicado`, `PeriodoConVigencia`.
- **Infraestructura:** `RepositorioDePeriodosEloquent` y su registro en `AcademicoServiceProvider`.
- **HTTP:** `PeriodoEscolarController`, `SolicitudDeNuevoPeriodo`, `PeriodoEscolarRecurso`, ayudante `IdentificadorDeRuta` (un UUID que no es v7 responde 404, no 500), rutas bajo `rol:docente`, `FormatoDeError` (409 `periodo_duplicado`), `lang/es`, `docs/api/openapi.yaml`.
- **Pruebas:** §7.1 filas 1 a 13 en `tests/Feature/Academico/PeriodosEscolaresTest.php`, `PeriodoEscolarTest` (unitaria) y una prueba de contrato.
- **Dudas:** ninguna. `PeriodoVigente` y `HoyDeNegocio` ya existen; no se necesita método auxiliar en Compartido.
