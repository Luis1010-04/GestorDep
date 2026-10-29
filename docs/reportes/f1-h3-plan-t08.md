# Plan de F1-H3-T08: seguridad y alcance de Competencia

**Rutas:** `tests/Feature/Seguridad/**`, `tests/Feature/Competencia/**` y reportes (§8 de la especificación v1.0.2). Sin cambios de código de producción: si una prueba revela un hueco real, me detengo y pregunto.

## Pruebas

1. **`tests/Feature/Seguridad/CompetenciaSeguridadTest.php`.** Recorre cada ruta de §6.1 que lleva un recurso en la ruta:
   - **Equipo:** entrenamientos, asistencia del equipo y partidos (`GET` y `POST`).
   - **Entrenamiento:** detalle, `PATCH` y asistencias (`GET` y `POST`).
   - **Asistencia:** `PATCH` de un registro.
   - **Jugador:** porcentaje de asistencia.
   - **Partido:** detalle, `PATCH`, resultado, convocatoria (`GET` y `POST`) y alineación.
   - **Convocatoria:** `DELETE` de un convocado.

   El atacante es E2 (entrena B) y el recurso es de A. Cada ruta se prueba con cuerpos válidos, inválidos y vacíos, y con tres identificadores: el recurso ajeno, un UUID v7 inexistente y UUID válidos que no son v7 (v4 y v1). Todas deben dar el mismo 404, byte a byte, que la respuesta de referencia `{"codigo":"recurso_no_encontrado",…}`, sin filas nuevas ni cambios en las tablas de Competencia.

   Los catálogos son globales (no hay «ajeno»): `DELETE /lugares|rivales|temporadas|torneos/{id}` con un UUID v7 inexistente, v4 o v1 da el mismo 404 y nunca 500.
2. **§7.6 filas 1 a 8, con el identificador `RNF-01 (§7.6 fila N)`**:
   1. E2 consulta un entrenamiento de A → 404.
   2. E2 registra asistencia → 404 igual al de un inexistente.
   3. E2 convoca a J1 → 404.
   4. Un docente, en cualquier ruta de entrenador → 403 `sin_permiso`.
   5. Sin sesión, en cualquier ruta de H3 → 401.
   6. Un entrenamiento consultado con un UUID v4 → 404, nunca 500.
   7. E3 consulta un partido de A → 200.
   8. La asistencia de un equipo ajeno, con cuerpo válido e inválido y por cualquier método → 404 byte a byte, sin filas nuevas.
3. **Roles:** un docente recibe 403 `sin_permiso` en las 28 rutas de entrenador, y sin sesión todas las rutas de H3 dan 401. Las rutas de entrenador son todas las de §6.1 salvo `GET /jugadores/{jugador}/asistencia-entrenamientos`, que solo pide sesión y le da 404 al docente (DA-20, prueba de T04); aquí se repite.
4. **`tests/Feature/Competencia/AlineacionYResultadoTest.php`:** un campo extra en la raíz del cuerpo de la alineación da 422 `prohibited`. Cubre la línea 42 de `SolicitudDeAlineacion`, pendiente desde T07.
5. **Mutaciones**, una por filtro de comparación del alcance, sobre una copia de respaldo de cada archivo. Se corren `CompetenciaSeguridadTest` y la prueba de la tarea correspondiente; al final se restaura el archivo y se comprueba que `git diff` está vacío.
   - **Entrenamientos:** `ComprobarEquipoEntrenado`, `BuscarEntrenamientoDelEntrenador` y, en el adaptador `EquiposDelEntrenadorPlantel`, el usuario y la asignación vigente.
   - **Asistencias:** `BuscarAsistenciaDelEntrenador`, el filtro por equipos de `ConsultaDeAsistenciaSql`, el alcance de `VisibilidadDeJugadorSql` y la asignación exigida en `JugadoresDelEquipoPlantel`.
   - **Partidos:** `BuscarPartidoDelEntrenador`.
   - **Convocatorias:** el alcance de `ConsultaDeConvocatoriaSql`, la pertenencia en `ConvocarJugador` y el partido en `BuscarConvocatoriaDelEntrenador`.

## Decisiones

Ninguna prevista. Si hace falta una, desde DA-35.
