# F1-H6: decisiones del agente

Decisiones que el agente tomó al implementar y que no estaban escritas en la especificación ni en el registro. Se anotan aquí para que el responsable las confirme o las cambie. Ninguna cambia un requisito, una regla ni un valor de fábrica.

## T03: recálculo de alertas

| # | Decisión | Por qué | Dónde está |
|---|---|---|---|
| 1 | Las claves de `parametros_institucionales` que lee Alertas son `sanciones_deportivas` y `alerta_academica` (con `nivel`), las que siembra `ParametrosInstitucionalesSeeder`. El plan de T03 (§4) decía `alerta_academica_nivel`, que no existe | La clave del plan era un error de nombre; el seeder es la fuente (DA-100) | `RepositorioDeParametrosDeAlertasSql` |
| 2 | `MembresiasVigentes` tiene dos métodos, `todas()` y `delJugador()`, y el equipo de una membresía puede ser nulo. **Confirmada por el responsable el 16/11/2026.** | El escucha de la alerta académica necesita saber si un solo jugador está en baja sin cargar a todos, y un jugador sin membresía vigente igual debe tener su alerta académica (no depende del equipo). El plan hablaba solo de parejas | `src/Compartido/Aplicacion/MembresiasVigentes.php` |
| 3 | Abrir una alerta es `INSERT … ON CONFLICT DO NOTHING` (`insertOrIgnore`), y no un `INSERT` que se captura al fallar con 23505 | En PostgreSQL, un error dentro de una transacción la deja abortada; un conflicto ignorado en el propio `INSERT` tiene el mismo efecto que tratar el 23505 como «ya abierta», sin romper la transacción por jugador | `RepositorioDeAlertasSql::abrir` |
| 4 | El recálculo de un jugador va en una transacción; si falla, se informa con `report()`, la corrida sigue con los demás y el comando sale con 1 | Es lo que describe el plan (§4); un jugador con datos corruptos no debe dejar sin recálculo a todo el club | `RecalcularTodasLasAlertas`, `RecalcularAlertasComando` |
| 5 | La temporada de cada partido se obtiene con la fecha de inicio del partido (`InicioDePartidos`) en la zona de negocio y `TemporadaDeFecha`; un partido que ya no existe, o cuya fecha no cae en ninguna temporada, no suma tarjetas | DA-117; no se lee `partidos.temporada_id` porque es de Competencia | `RecalcularAlertasDeportivasDeMembresia` |
| 6 | `promedio` de la `causa` es un decimal en texto (`"6.00"`), igual que los demás decimales de la API | Decimales con el objeto decimal de Compartido, nunca `float` (reglas del proyecto) | causas de las tres alertas |
| 7 | `AcumulacionDeSanciones::conteos()` devuelve un arreglo con las tres claves de la causa, en español y `snake_case` | `src/Alertas/Dominio/` no permite crear otro archivo en T03 (solo `AcumulacionDeSanciones.php`), así que no hay objeto de valor propio | `src/Alertas/Dominio/AcumulacionDeSanciones.php` |
| 8 | «En baja» es `jugadores.estatus_deportivo = 'baja'`, la misma condición que `AlcanceDeJugadores` | Una sola definición de baja en todo el sistema (P-08c, DA-99) | `MembresiasVigentesPlantel` |
| 9 | El escucha de la alerta académica no toca las alertas de un jugador en baja: ni las abre ni las cierra | P-08c: se conservan abiertas y no se muestran | `RecalcularAlertaAcademica` |
| 10 | Las pruebas comparan la `causa` con `toEqual`, no con `toBe` | PostgreSQL `jsonb` reordena las claves de un objeto | `tests/Feature/Alertas/` |
| 11 | En `routes/console.php`, `resumenes:conciliar` se define antes que `alertas:recalcular` | Son independientes (DA-113); el orden solo fija cuál corre primero en la misma pasada | `routes/console.php` |
| 12 | El detector de RNF-01 se satisface con DA-121 (el archivo se añadió a la lista de usos autorizados), no cambiando la consulta | Evitar la tabla `jugadores` consultando otras habría esquivado la regla sin cumplir su intención | `tests/Ayudantes/DeteccionDeConsultasDeJugadores.php` |
| 13 | Los archivos de `backend/build/` de T03 se mueven a `docs/reportes/logs/verificar/` con el nombre `f1-h6-t03-<fecha>-<hora>.log` (la corrida roja a las 13:36; las dos finales con el sufijo `-pruebas` y `-final`) | Respaldan el reporte | `docs/reportes/logs/verificar/` |

## T05: vista para convocar y orden por desempeño

| # | Decisión | Por qué | Dónde está |
|---|---|---|---|
| 14 | Toda la vista se lee con SQL de Consultas (§4.3), incluidos el estado oficial (solo `estado`, `fecha_estimada_regreso` y `actualizado_en`, sin detalle de lesión, RN-15) y el resumen académico simplificado del periodo vigente | Las marcas de desactualizada y el orden se calculan en una sola capa de Aplicación; T04 compone esos dos datos en el controlador con casos de uso de Salud y Académico, y T05 tiene permitido leer con SQL en `src/Consultas/Infraestructura/` | `ConsultaDeVistaParaConvocarSql` |
| 15 | `filtro[equipo_id]` de un equipo que el usuario no entrena o donde el jugador no está responde 404; un valor que no es UUID, 422 `filtro_invalido` | Es un recurso único: el mismo 404 de un jugador ajeno no revela si el equipo existe (RNF-01). La lista de estatus devuelve lista vacía porque ahí es un filtro (P-05) | `ConsultarVistaParaConvocar`, `VistaParaConvocarController` |
| 16 | El porcentaje de `asistencia.equipo` y `asistencia.de_mis_capturas` es el total de los registros (no el de un mes) con la fórmula de DD-51, repetida en SQL | Los puertos de T07 devuelven el porcentaje por mes y T05 no puede ampliarlos; la duplicación queda anotada como deuda | `ConsultaDeVistaParaConvocarSql::porcentaje` |
| 17 | La paginación de `GET /equipos/{equipo}/jugadores/para-convocar` es de 15 por página por defecto y máximo 100, como el listado de estatus | `docs/convenciones-api.md` solo fija el máximo de 100; **[Decisión por defecto]** | `VistaParaConvocarController` |
| 18 | `make pruebas` no se cambió aunque se queda sin memoria (128 MB) en `ArquitecturaTest`; la suite se corrió con `php -d memory_limit=1G vendor/bin/pest` | El Makefile y Docker son de T00; `make verificar` ya usa 1 GB y pasa. Se reporta como duda | `docs/reportes/f1-h6-t05.md` §8 |

## T06: vista consolidada de la convocatoria

| # | Decisión | Por qué | Dónde está |
|---|---|---|---|
| 19 | Los convocados se leen con SQL de solo lectura en Consultas, con `AlcanceDeJugadores` aplicado a `convocatorias.jugador_id` en la misma consulta que cuenta y pagina | Decisión 5 (a) del responsable, apoyada en arquitectura §4.3 («Consultas puede leer las tablas de otros contextos con SQL de solo lectura»); así `meta.total` cuenta igual que H3 | `ConsultaDeVistaParaConvocarSql::convocatoriaDelPartido` |
| 20 | Un convocado que dejó el equipo del partido pero sigue en otro equipo del mismo usuario aparece, con el rendimiento del equipo del partido | Es el comportamiento que ya tiene la ruta de H3 (la regla de alcance exige membresía en algún equipo del usuario, no en el del partido); el responsable pidió anotarlo en el reporte | `docs/reportes/f1-h6-t06.md` §2 |
| 21 | Las pruebas de paridad comparan con el orden de H3 y no con uno fijo | Las convocatorias insertadas juntas comparten `creado_en` y el orden lo decide el `id` | `VistaDeLaConvocatoriaTest` |
