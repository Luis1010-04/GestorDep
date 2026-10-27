# Plan F1-H2-T07: materias

- **Dominio:** `Materia` (datos), `RepositorioDeMaterias` (existe/guardar con historial), `RepositorioDeCriterios::generalVigente()` (T11 lo extiende al criterio propio). Se agrega `existe()` a `RepositorioDeAsignaturas` y `RepositorioDePeriodos`.
- **Aplicación:** `CrearMateria` (docente = usuario actual), `ListarMaterias` (solo las del docente, `filtro[periodo_id]`), `VerMateria` (nulo si es ajena o no existe → 404), interfaz `ConsultaDeMaterias` con los datos de asignatura y periodo (se muestran aunque estén eliminados), `MateriaConDetalle`, `MateriaConCriterio`, excepciones `MateriaDuplicada`, `AsignaturaInexistente`, `PeriodoInexistente`.
- **Infraestructura:** `RepositorioDeMateriasEloquent` (inserción + fila `crear` en `materias_historial` en la misma transacción con `EscritorDeHistorial`), `ConsultaDeMateriasSql`, `RepositorioDeCriteriosEloquent`.
- **HTTP:** `MateriaController` (`GET /materias`, `POST /materias`, `GET /materias/{materia}`), `SolicitudDeNuevaMateria`, `MateriaRecurso`, rutas bajo `rol:docente`, 409/422 en `FormatoDeError`, `lang/es`, `openapi.yaml`.
- **Pruebas:** §7.3 filas 1 a 10; unitarias de `Materia` y `Asignatura` (cubre los 5 mutantes sin cobertura de T06); contrato.
- **Decisión propia:** el orden del listado no está en la especificación → DA-09.
