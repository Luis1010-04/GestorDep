# Plan de F1-H3-T02: catálogos (HU-28)

**Rutas:** `src/Competencia/**`, `routes/api.php` (rutas de catálogos de §6.1), `lang/es`, `docs/api/openapi.yaml`, `tests/**`, y por R-1 `app/Http/**` y `app/Exceptions/FormatoDeError.php`.

## Enfoque

Mismo patrón que categorías (H1) y periodos escolares (H2), con dos diferencias que pide H3: cada alta y cada borrado escriben historial (§3.4) y las temporadas no se pueden cruzar (DD-07).

- **Dominio** (`src/Competencia/Dominio`): `Lugar`, `Rival`, `Temporada` (rechaza `fecha_fin` no posterior a `fecha_inicio`), `Torneo`, y las interfaces `RepositorioDeLugares`, `RepositorioDeRivales`, `RepositorioDeTemporadas`, `RepositorioDeTorneos`.
- **Aplicación**: `Crear*`, `Listar*`, `Eliminar*` por catálogo; excepciones `LugarDuplicado`, `RivalDuplicado`, `TemporadaDuplicada`, `TemporadaSolapada`, `TorneoDuplicado`, `TemporadaInexistente`.
- **Infraestructura**: repositorios con `EscritorDeHistorial` (operación `crear` con la instantánea nueva; `eliminar` con la anterior). El alta de una temporada toma un candado de tabla (`lock table temporadas in share row exclusive mode`) dentro de la transacción, comprueba el cruce y escribe, para que dos altas simultáneas no se crucen (DA-06). Proveedor `CompetenciaServiceProvider` con los enlaces.
- **HTTP**: `LugarController`, `RivalController`, `TemporadaController`, `TorneoController`; solicitudes de alta; recursos `LugarRecurso` (también para rivales), `TemporadaRecurso`, `TorneoRecurso`. Rutas en el grupo `rol:entrenador`, con `whereUuid` y `IdentificadorDeRuta` (UUID no v7 → 404).
- **Orden de los listados**: `nombre` (por defecto) o `-nombre` en lugares, rivales y torneos; `-fecha_inicio` (por defecto) o `fecha_inicio` en temporadas, como periodos escolares.
- **Errores**: duplicados con 409 y código propio (`lugar_duplicado`, `rival_duplicado`, `temporada_duplicada`, `torneo_duplicado`; DA-04); `temporada_solapada` 409 sin detalle por campo (DA-05); `fecha_fin` no posterior con la regla `after:fecha_inicio`, 422 con el campo `fecha_fin` (fila 4); temporada de un torneo inexistente o eliminada: 422 `temporada_inexistente` (patrón de `periodo_inexistente`).
- **Contrato**: rutas y esquemas `Lugar`, `Rival`, `Temporada`, `Torneo` en `openapi.yaml`.

## Pruebas (`tests/Feature/Competencia/CatalogosTest.php`, `ContratoDeCompetenciaTest.php`, `tests/Unit/Competencia/`)

- §7.1 filas 1 a 7, una prueba por fila, con `HU-28 (§7.1 fila N)`. La fila 7 inserta el partido directo en la base (los partidos llegan en T05) y comprueba que sigue apuntando a L1, que L1 conserva su fila con `eliminado_en` y que sale del listado.
- HU-28 criterios 1 a 3 cubiertos por esas filas, más el mismo comportamiento en rivales, torneos y temporadas (duplicado, borrado, historial).
- RNF-02: historial `crear` y `eliminar` con autor e instante.
- 401 sin sesión, 404 con id inexistente, ya eliminado o UUID v4.
- Contrato de cada respuesta.
- Unitarias de `Temporada` (rango) para Infection.

## Dudas

Ninguna que detenga la tarea; DA-04 a DA-06.
