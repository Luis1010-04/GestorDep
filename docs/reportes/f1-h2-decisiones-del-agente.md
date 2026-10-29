# Decisiones del agente en H2 (DA-nn)

Cada decisión indica qué faltaba, qué se eligió, por qué y cómo se revierte (`docs/hitos/f1-h2.md` §0.3).

## DA-01: rutas adicionales en T01 para satisfacer PHPStan nivel 7 (autorizada por el responsable)

- **Qué faltaba:** subir a nivel 7 produce 66 errores, todos fuera de las rutas de T01 (`src/*/Infraestructura`, `app/Exceptions/FormatoDeError.php`, `tests/Integration`, `tests/Feature`, `tests/Ayudantes`).
- **Qué se eligió:** corregirlos solo con tipos y anotaciones (`stdClass` en lugar de `object`, `@var list<string>`, `array_values()` sobre listas ya indexadas, un ayudante `consolaArtisan()` que acota `PendingCommand|int`, `statement()` en lugar de `unprepared()` con SQL interpolado), sin baseline, `ignoreErrors` ni exclusiones, y sin cambiar comportamiento. Lo preguntó el agente y lo aprobó el responsable.
- **Cómo se revierte:** `git revert` de F1-H2-T01 y volver a `level: 6`.
- **Seguridad/privacidad:** ninguna.

## DA-02: adaptador de Pest para Infection y `allow-plugins` en `composer.json`

- **Qué faltaba:** Infection 0.35 no soporta Pest: lee su versión como PHPUnit 4.7, no entiende su salida (`OK (`) y los nombres de clase del JUnit difieren de los de la cobertura (`Tests\` frente a `P\Tests\`). Además, Composer bloquea el plugin `infection/extension-installer`.
- **Qué se eligió:** (a) `tests/Ayudantes/AdaptadorPestParaInfection.php` (ruta no listada en T01 pero dentro de `tests/Ayudantes/**`, autorizada en DA-01 solo para tipos; se anota como ruta fuera de la especificación): informa la versión de PHPUnit, ejecuta Pest sin cambiar qué se ejecuta, usa su código de salida y agrega `OK (...)` o `FAILURES!`, y alinea los nombres del JUnit. (b) `infection/extension-installer: false` en `allow-plugins` de `composer.json` (el plugin solo sirve para extensiones de terceros, que no se usan; se deja **bloqueado**, la opción más conservadora). `composer.json` solo permitía el script y `require-dev`: se anota como ruta fuera de la especificación.
- **Por qué:** la alternativa (no usar Infection) incumple DD-53. Mutar solo con la suite `Unit` mantiene el análisis rápido y exige pruebas unitarias del dominio.
- **Cómo se revierte:** quitar el adaptador, `infection.json5`, la dependencia y el paso del script.
- **Seguridad/privacidad:** ninguna. El adaptador no se ejecuta fuera de `composer verificar`.

## DA-03: `--with-uncovered` y suite `Unit` para Infection

- **Qué faltaba:** el umbral de §3 DD-53 no dice si el código sin pruebas cuenta. Sin `--with-uncovered`, borrar todas las pruebas de una clase de dominio dejaba MSI 100 % (los mutantes sin cobertura ni se generan); con la opción, MSI cae a 63 % y falla.
- **Qué se eligió:** contar los mutantes sin cobertura (opción más estricta) y medir solo con las pruebas de `tests/Unit` (dominio sin base de datos, `docs/estrategia-pruebas.md` §3).
- **Cómo se revierte:** quitar `--with-uncovered` del script `verificar` (debilita el umbral: requeriría aprobación).
- **Seguridad/privacidad:** ninguna.

## DA-04: fila 7 de §7.12 se prueba en dos niveles

- **Qué faltaba:** la fila dice "la capa HTTP lo rechaza (422); el dominio acepta más precisión interna", pero T02 no permite crear reglas de validación HTTP (solo un ayudante de serialización).
- **Qué se eligió:** en T02 se prueba la mitad de dominio (acepta `7.495`, `decimales()` informa 3) y el rechazo 422 queda en las pruebas de las rutas que reciben decimales: §7.6 fila 6 (T11) y §7.7 fila 5 (T12). Se anota en la trazabilidad de T15.
- **Cómo se revierte:** n/a (no hay código que deshacer).
- **Seguridad/privacidad:** ninguna.

## DA-05: carpeta de `parametros_institucionales` y nombre de las columnas de historial

- **Qué faltaba:** la especificación fija el grupo (035) pero no la subcarpeta de la migración ni el nombre de la columna de entidad de cada historial.
- **Qué se eligió:** `database/migrations/alertas/` (arquitectura §7.9 agrupa la tabla con las alertas y H6 agrega `alertas` ahí); columnas `materia_id`, `inscripcion_id`, `criterio_aprobacion_id`, `calificacion_id`, `asistencia_clase_id` y `parametro_institucional_id` (singular de la tabla, como `jugador_equipo_id` y `baja_jugador_id` en H1).
- **Cómo se revierte:** mover el archivo de carpeta no altera el esquema; renombrar una columna de historial exige una migración nueva si ya se integró.
- **Seguridad/privacidad:** ninguna.

## DA-06: `Identificador::generar()` sin `floor`

- **Qué faltaba:** la regla de arquitectura de T02 (prohibir `round`, `floor`, `ceil` y `number_format` en `src/*/Dominio/`) encontró `(int) floor(microtime(true) * 1000)` en `src/Compartido/Dominio/Identificador.php` (H0).
- **Qué se eligió:** `(int) (microtime(true) * 1000)`: para un instante positivo, truncar y `floor` dan el mismo entero, así que el comportamiento no cambia (las pruebas de `IdentificadorTest` lo confirman). Es la opción que no debilita la regla: la alternativa era exceptuar el archivo. La ruta (`src/Compartido/Dominio/**`) está permitida en T02.
- **Cómo se revierte:** restaurar `floor` y exceptuar `Identificador.php` en la regla (debilita la regla: requeriría aprobación).
- **Seguridad/privacidad:** ninguna.

## DA-07: `bcmath` en la integración continua (aprobada por el responsable)

- **Qué faltaba:** `composer.json` exige `ext-bcmath` desde T02 y el flujo de CI instalaba PHP sin esa extensión.
- **Qué se eligió:** agregar `bcmath` a `extensions` de `shivammathur/setup-php` en `.github/workflows/verificar.yml`.
- **Cómo se revierte:** quitar la palabra (haría fallar `composer install` en CI).
- **Seguridad/privacidad:** ninguna.

## DA-05 (corregida): `parametros_institucionales` va en `database/migrations/academico/`

- **Corrección:** la carpeta `alertas/` elegida originalmente se reemplaza por `academico/` por decisión del responsable. Los nombres de las columnas de historial de DA-05 no cambian. Los nombres de archivo (y por tanto el orden y el registro en la tabla `migrations`) tampoco: solo cambia la carpeta.
- **Cómo se revierte:** mover los dos archivos de vuelta a `alertas/`.

## DA-08: respuestas a las preguntas abiertas del plan de T04

- **Qué faltaba:** el responsable aprobó el plan de T04 "con las respuestas del mensaje anterior", pero ese mensaje no traía respuestas a las 6 preguntas de `f1-h2-plan-t04.md` §5.
- **Qué se eligió:** la recomendación de cada pregunta, tal como está escrita en el plan: (1) `AmbitoDeCriterio` y `NivelDeRiesgo` como `enum`, sin enum de asistencia; (2) la asistencia entra como conteos; (3) `ParametrosDeRiesgo` valida `observacion <= sin_riesgo`, `0 < reprobadas_observacion <= reprobadas_riesgo` y umbrales entre 0 y 10 (`parametros_de_riesgo_invalidos`); (4) la escala 2 es constante del código, no parámetro; (5) el promedio es la media de calificaciones ya redondeadas; (6) `aprobada()` es `?bool`. Además, `CriterioDeAprobacion::resolver` exige ámbito `materia` en el propio y `general` en el general (`criterio_de_ambito_invalido`), para que "indica cuál se aplicó" sea confiable.
- **Cómo se revierte:** cada regla vive en una sola clase; si el responsable respondió distinto, se corrige en un `F1-H2-FIX`.
- **Seguridad/privacidad:** ninguna.

## DA-09: orden del listado de materias

- **Qué faltaba:** `GET /materias` (§6.1) no dice cómo se ordena.
- **Qué se eligió:** por `clave` de la asignatura y, a igualdad, por `id` (estable y legible para el docente). Sin parámetro `orden`.
- **Cómo se revierte:** cambiar el `orderBy` de `ConsultaDeMateriasSql::listarDelDocente`; agregar un parámetro `orden` exigiría actualizar el contrato.
- **Seguridad/privacidad:** ninguna; solo se listan las materias del propio docente.

## DA-10: las escrituras de plantel usan solo el alcance de entrenador; las académicas, el del docente (cambio de permisos, aprobado por el responsable)

- **Qué faltaba:** al agregar la rama del docente a `AlcanceDeJugadores`, las escrituras de H1 que reutilizaban `ConsultaDeJugadores::buscar()` (expediente, baja, asignar, cambiar posición) la habrían heredado, y un usuario docente-entrenador podría haber modificado a un jugador que solo ve porque lo enseña.
- **Qué se eligió (decisión del responsable sobre el plan de T08):** `AlcanceDeJugadores::restringir()` gana el parámetro opcional `soloEntrenador`; `ConsultaDeJugadores::buscarParaEscribir()` lo usa y lo emplean `ComprobarJugadorEscribible` (en las solicitudes de expediente y baja), `ActualizarExpedienteDeJugador`, `CambiarPosicionDeJugador` y `AsignarJugadorAEquipo`. Las lecturas (`GET /jugadores`, `GET /jugadores/{jugador}`) usan la unión de ramas. Las escrituras académicas (inscribir, calificar, asistencia, criterio; T10 a T13) se autorizan por ser el docente dueño de la materia, no por `AlcanceDeJugadores`.
- **Efecto sobre permisos:** ningún usuario gana permisos de escritura sobre plantel. Un docente puro sigue recibiendo 403; un docente-entrenador recibe 404 al intentar modificar a un jugador que solo enseña (probado en `EscrituraDeUsuarioConDosRolesTest`).
- **Cómo se revierte:** quitar el parámetro y volver a `buscar()` en esos cuatro casos de uso (ampliaría permisos de escritura: requeriría aprobación).
- **Anotado para la auditoría:** `estatus_deportivo` se mantiene en el recurso `Jugador` que ve el docente (fila 5 de §7.4, DD-30); el arreglo `equipos` sale vacío para un docente puro porque se limita a las asignaciones del propio usuario.

## DA-11: cuerpo del 404 de una matrícula sin expediente al inscribir

- **Qué faltaba / contradicción:** la instrucción del responsable para T10 pedía que la matrícula inexistente dé 404 «con los mismos cuerpos que en H1»; la especificación (§6.3 y §7.5 fila 3) pide `404 jugador_no_registrado` y H1 responde `recurso_no_encontrado`.
- **Qué se eligió:** la especificación: código `jugador_no_registrado`, mensaje «No existe un expediente con esa matrícula: el jugador debe tener un expediente.», formato estándar `{codigo, mensaje, errores: []}`, sin datos de nadie. `jugador_en_baja` conserva código y cuerpo de H1 (solo cambia el texto, por decisión del responsable).
- **Cómo se revierte:** en `FormatoDeError`, cambiar la rama de `JugadorNoRegistrado` a `self::respuesta(404, 'recurso_no_encontrado')` y ajustar el texto esperado de la prueba de la fila 3.
- **Seguridad/privacidad:** ninguna: ambos cuerpos son iguales sea cual sea la matrícula inexistente y no revelan datos.
- **Estado:** confirmada por el responsable tras la auditoría de C2 (`jugador_no_registrado` con 404, según §6.3).

## DA-12: texto de `jugador_en_baja` generalizado para cubrir la captura (T12)

- **Qué faltaba:** DD-52 pide 409 `jugador_en_baja` también al capturar calificaciones o asistencias, pero el texto vigente («…no se puede asignar ni inscribir.») no describe la captura, y cada código tiene un solo mensaje en `lang/es`.
- **Qué se eligió:** «El jugador está en baja: no se puede asignar, inscribir ni capturar calificaciones o asistencias.» El código no cambia. Se ajustó el texto esperado (con `assertExactJson`, sin debilitarlas) en `AsignacionDeJugadoresTest.php:160` (H1) e `InscripcionesTest.php:84`.
- **Cómo se revierte:** restaurar el texto en `lang/es/errores.php` y en esas dos pruebas.
- **Seguridad/privacidad:** ninguna.

## DA-13: corregir la calificación de un jugador en baja también da 409 (T12)

- **Qué faltaba:** DD-52 dice «capturar calificaciones o asistencias de un jugador en baja también (409)» y «la baja detiene la captura», pero no dice si una corrección (`PATCH`) es captura.
- **Qué se eligió:** la opción más estricta: `PATCH /calificaciones/{id}` de un jugador en baja responde 409 `jugador_en_baja` y no escribe nada. T13 aplica lo mismo a `PATCH /asistencias-clase/{id}`.
- **Cómo se revierte:** quitar la comprobación de baja de `CorregirCalificacion` (y de su equivalente en asistencias) y su prueba.
- **Seguridad/privacidad:** ninguna; restringe escrituras, no lecturas.

## DA-14: el listado de calificaciones de un jugador en baja sigue visible al docente dueño (T12)

- **Qué faltaba:** DD-52 dice que un jugador inscrito que pasa a baja «sigue visible (detalle) para quien ya lo tenía», sin nombrar el listado de calificaciones.
- **Qué se eligió:** `GET /inscripciones/{id}/calificaciones` responde 200 al docente dueño aunque el jugador esté en baja (como el detalle de H1, DD-30). Lo mismo para las asistencias en T13.
- **Cómo se revierte:** en el controlador, responder 404 si `InscripcionDelDocente::jugadorEnBaja`.
- **Seguridad/privacidad:** **sí, de lectura**: el docente dueño sigue viendo las calificaciones que él mismo capturó de un alumno ya en baja. No amplía a nadie más. Para la revisión del responsable.

## DA-15: la situación académica del docente lista solo sus materias del periodo vigente (T14)

- **Qué faltaba:** §6.2 dice que quien es docente del jugador recibe `materias`: «arreglo de sus propias materias», sin decir de qué periodo. El resumen (promedio, reprobadas, nivel) es del periodo vigente (DD-45).
- **Qué se eligió:** solo las materias del docente **del periodo vigente** en las que el jugador está inscrito (inscripción y materia sin eliminar); sin periodo vigente, arreglo vacío. La clave `materias` aparece si el usuario tiene el rol docente y una inscripción vigente del jugador en alguna materia suya de cualquier periodo («docente del jugador», §5.4); si no, no aparece.
- **Por qué:** es coherente con el resumen y revela menos (§0.3, regla 1).
- **Cómo se revierte:** quitar el filtro `materias.periodo_id` de `ConsultaDeSituacionAcademicaSql::materiasDelDocente` y la rama `periodoId === null` de `VerSituacionAcademica`, y la prueba `DA-15`.
- **Seguridad/privacidad:** **sí, de lectura**, y restrictiva: el docente ve menos de sus propias materias (no las de periodos pasados). Ningún otro usuario ve más. Para la revisión del responsable.

## DA-16: desempate del periodo vigente por `id` (T05; registrada tras la auditoría del Lote E, M-4)

- **Qué faltaba:** DD-45 elige, entre varios periodos que contienen el día de negocio, «el de `fecha_inicio` más reciente», pero no dice qué hacer si dos comparten `fecha_inicio`.
- **Qué se eligió:** el de `id` mayor. Los identificadores son UUID v7, así que es el creado más recientemente (`RepositorioDePeriodosEloquent::identificadorVigenteEn`, `orderByDesc('fecha_inicio')->orderByDesc('id')`). Así el resultado es determinista: sin desempate, PostgreSQL puede devolver cualquiera de los dos.
- **Por qué:** es el criterio más cercano a «más reciente» que ya usa DD-45 y no agrega ninguna validación (§2.3 excluye validar traslapes).
- **Cómo se revierte:** cambiar o quitar el segundo `orderByDesc` de esa consulta.
- **Seguridad/privacidad:** ninguna. Decide qué periodo es el vigente, no quién ve qué.
- **Nota:** en T05 se anotó en `f1-h2-t05.md:57` como «ninguna decisión DA nueva», y T15 la incluyó en DD-45 del registro como si viniera de §3 de la especificación. Ninguna prueba fija hoy el desempate: §7.1 fila 7 usa periodos con `fecha_inicio` distintas.
