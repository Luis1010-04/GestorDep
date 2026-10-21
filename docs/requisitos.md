# Documento de Requisitos del Sistema de Seguimiento Académico y Deportivo (MVP)

**Proyecto:** Sistema web de gestión académica y deportiva de futbolistas, Universidad del Fútbol y Ciencias del Deporte
**Versión:** 1.2 (precisiones de RN-04 y RN-09)
**Fecha:** 21 de octubre de 2026
**Estado:** Pendiente de validación por la institución. Lo marcado **[Propuesta]** o **[Decisión por defecto]** debe validarse antes de construir la parte que lo usa (ver `docs/decisiones/registro-decisiones.md`)

## Control de versiones

| Versión | Fecha | Descripción |
|---|---|---|
| 1.0 | 12/10/2026 | Primera versión, derivada del proceso de descubrimiento y del documento "Proceso general del sistema web" |
| 1.1 | 19/10/2026 | Correcciones de la auditoría cruzada: RN-19 a RN-23, HU-27 a HU-30, referencias cruzadas, matriz de visibilidad, RNF-10 y RNF-11, y backlog completo según el PDF |
| 1.2 | 21/10/2026 | RN-04: tipo de redondeo. RN-09: ventana móvil de 30 días y vencimiento por tiempo. HU-17: cómo se confirma una corrección |

## 1. Introducción

### 1.1 Propósito
Este documento define los requisitos del Producto Mínimo Viable (MVP) del sistema. Está dirigido al equipo del proyecto, a los docentes y entrenadores que lo usarán y al personal institucional que validará las reglas de negocio. No describe tecnología: define el **qué** y el **por qué**.

### 1.2 Problema
La información académica y deportiva de los futbolistas está dispersa, desactualizada y sin trazabilidad. Las decisiones sobre a quién convocar o dar de baja se toman con datos poco confiables y los resultados no son los esperados.

### 1.3 Objetivo del MVP
Ofrecer un **expediente único y actualizado por jugador** que permita (a) decidir convocatorias con información confiable y (b) conocer el progreso individual y grupal mediante gráficos con historial.

### 1.4 Alcance
**Dentro del MVP:** expediente del jugador, calificaciones y asistencia a clases, programación de entrenamientos y partidos, asistencia a entrenamientos, rendimiento en partidos, estado físico, lesiones y disponibilidad, alertas visibles en el sistema, vista consolidada para convocar, gráficos de historial y trazabilidad de cambios.

**Fuera del MVP:** ver sección 9 (Backlog). Lo que se completa en la Fase 1 y lo que se espera en la Fase 2, punto por punto del PDF, está en `docs/alcance-fases.md`.

### 1.5 Definiciones

| Término | Significado |
|---|---|
| Expediente | Conjunto de datos académicos y deportivos de un jugador |
| Aviso | Reporte de una molestia o lesión creado por un docente o entrenador, aún sin confirmar |
| Estado oficial | Disponibilidad confirmada por un entrenador: apto, con molestia o lesionado |
| Umbral | Valor mínimo que determina si un resultado "pasa" o "no pasa" |
| Plantilla | Conjunto de umbrales predefinidos que el entrenador puede usar o reemplazar |
| Criterio general | Regla institucional predefinida (por ejemplo, calificación mínima aprobatoria) |
| Retícula | Plan de materias de la carrera |
| Borrado lógico | El dato se marca como eliminado pero se conserva en el historial |
| Temporada | Intervalo de fechas dado de alta como catálogo (HU-28). Un partido pertenece a la temporada que contiene su fecha. **[Decisión por defecto]** |
| Periodo | Unidad de seguimiento de un dato: el periodo escolar para calificaciones, el mes (AAAA-MM) para mediciones y pruebas físicas |
| Resumen simplificado | Promedio, materias reprobadas, porcentaje de asistencia y nivel de riesgo académico, sin calificaciones individuales |
| Ventana | Número N de calificaciones generales de partido que usa un umbral (RN-08) |
| Desempeño | Promedio de las últimas N calificaciones generales de partido del jugador, con N igual a la ventana del umbral del entrenador (RN-18) |

### 1.5.1 Convenciones
Los requisitos se identifican así: **HU-xx** (historia de usuario), **RN-xx** (regla de negocio), **RNF-xx** (requisito no funcional). Prioridad con método MoSCoW: **Must** (indispensable en el MVP), **Should** (importante), **Could** (deseable). Todas las historias de este documento son **Must**, salvo que se indique otro caso. Los requisitos funcionales son las HU y las RN; los no funcionales son los RNF. **[Decisión por defecto]** marca una decisión tomada por el equipo de análisis para poder avanzar, pendiente de validar por la institución.

## 2. Descripción general

### 2.1 Roles del MVP

| Rol | Cantidad estimada | Responsabilidad |
|---|---|---|
| Docente | ~15 | Captura calificaciones y asistencia a clases de su materia; puede crear avisos de lesión |
| Entrenador | ~10 | Gestiona equipos, programa entrenamientos y partidos, captura rendimiento, estado físico y lesiones; confirma el estado oficial |

La institución cuenta con unos 15 equipos. Futbolistas, coordinadores, directivos, administradores y preparadores físicos no son usuarios del MVP. Mientras no exista perfil de administración, los usuarios los crea el equipo del proyecto por comando de consola (RN-19).

### 2.2 Restricciones
- Un jugador puede pertenecer a más de un equipo a la vez.
- Cada entrenador y docente ve únicamente a los jugadores que tiene asignados.
- No se elimina información de forma definitiva.
- La información deportiva se actualiza después de cada partido.

### 2.3 Supuestos
- Los docentes y entrenadores capturan los datos de forma oportuna; sin esa captura el sistema pierde su valor.
- Como no existe perfil de administración, la carga inicial la hacen entrenadores y docentes (RN-12) y los usuarios los crea el equipo del proyecto (RN-19).
- Como el futbolista no es usuario del MVP, un jugador que quiera reportar una molestia lo informa a un docente o entrenador, quien registra el aviso (ver HU-19). *Supuesto a validar.*

## 3. Reglas de negocio

Los valores marcados como **[Propuesta]** son valores de fábrica sugeridos por el equipo de análisis y **deben ser validados por la institución** antes de la construcción. Más adelante un perfil de administración podrá ajustarlos.

### 3.1 Reglas académicas
- **RN-01.** La escala de calificación es de 0 a 10. **[Propuesta]** Calificación mínima aprobatoria general: 7.0.
- **RN-02.** El sistema calcula automáticamente el promedio general y el número de materias reprobadas a partir de las calificaciones capturadas. **[Propuesta]** La calificación de una materia es la final si existe; si no, el promedio de sus parciales. Una materia sin ninguna calificación no cuenta como reprobada y marca el dato como incompleto (HU-09).
- **RN-03.** Un docente puede definir un criterio propio para su materia, que prevalece sobre el general. El sistema debe mostrar siempre cuál criterio se aplicó.
- **RN-04.** El riesgo académico es único para todo el jugador, se calcula sobre el periodo escolar vigente y tiene tres niveles. **[Propuesta]** El promedio se redondea a dos decimales antes de comparar y prevalece el nivel más grave que se cumpla. **[Decisión por defecto]** El redondeo es al medio hacia arriba (7.495 se vuelve 7.50).
  - *Sin riesgo:* promedio general igual o mayor a 7.5 y ninguna materia reprobada.
  - *En observación:* promedio igual o mayor a 7.0 y menor a 7.5, o una materia reprobada.
  - *En riesgo:* promedio menor a 7.0, o dos o más materias reprobadas.
- **RN-05.** El porcentaje de asistencia a clases se calcula automáticamente y se muestra junto al riesgo, pero no forma parte de su cálculo en el MVP.

### 3.2 Reglas deportivas
- **RN-06.** La calificación general de partido usa una escala de números enteros de 1 a 10. **[Propuesta]** 1 a 4 deficiente, 5 a 6 regular, 7 a 8 bueno, 9 a 10 sobresaliente. Esto permite que un 8 signifique lo mismo en todos los equipos.
- **RN-07.** Cada posición tiene una plantilla de captura y de umbrales. El entrenador puede usarla, ajustarla o crear la suya por jugador. Todo cambio de umbral queda registrado.
- **RN-08.** "Bajo rendimiento" se determina así: el sistema promedia las últimas N calificaciones generales del jugador (N es la ventana de su umbral) y compara el promedio con el umbral; el resultado es "pasa" o "no pasa". Si el jugador tiene menos de N calificaciones, el resultado es "datos insuficientes" y no activa ninguna alerta. **[Propuesta]** Plantilla base: N = 5, umbral 6.0.
- **RN-09.** Acumulación de sanciones deportivas. **[Propuesta]** Se activa con una tarjeta roja, o con tres amarillas en la temporada, o con tres inasistencias injustificadas ("falta", no "falta justificada") a entrenamientos en 30 días. Se calcula por equipo, con los datos que registró su entrenador. La tarjeta roja mantiene la acumulación activa hasta el final de la temporada en que ocurrió. **[Decisión por defecto]** Los 30 días son una ventana móvil que termina en la fecha de cálculo, inclusive; cuando las faltas salen de la ventana o termina la temporada, la acumulación deja de estar activa aunque no se haya capturado nada nuevo. Las sanciones académicas son una categoría separada y quedan en el backlog.
- **RN-10.** Riesgo de baja deportiva: se activa cuando el jugador "no pasa" su umbral de rendimiento o tiene acumulación de sanciones deportivas. "Datos insuficientes" no lo activa. Se calcula por equipo, con el umbral del entrenador de ese equipo.
- **RN-11.** Asistencia a entrenamientos: cada registro es presente, falta o falta justificada.

### 3.3 Reglas de datos y acceso
- **RN-12.** La carga inicial de equipos y jugadores la realiza el entrenador. Los docentes dan de alta sus materias e inscriben a jugadores ya existentes según la retícula. Periodos escolares, asignaturas, categorías, lugares, torneos, rivales y temporadas son catálogos que dan de alta quienes los usan (HU-28).
- **RN-13.** Cada perfil ve el detalle de su propia área y solo un resumen esencial del resto. El entrenador ve el resumen académico simplificado; el docente ve del lado deportivo únicamente el estado oficial de disponibilidad, no el riesgo deportivo, porque este se calcula por equipo (RN-14). **[Decisión por defecto]**
- **RN-14.** Cuando un jugador pertenece a dos equipos, cada entrenador ve únicamente los datos que él registró, por protección de datos. Excepciones: el estado oficial de disponibilidad con su fecha estimada de regreso, y el resumen académico simplificado, visibles para todos sus entrenadores.
- **RN-15.** El detalle de una lesión (tipo, zona, causa) lo ve solo quien la registró.
- **RN-16.** Un aviso permanece "pendiente de confirmación" hasta que un entrenador del jugador lo confirme. Quien lo confirma queda como registrante de la lesión y es el único que después puede modificar ese estado y su detalle. Los avisos pendientes los ven los entrenadores del jugador y quien los creó. **[Decisión por defecto]**
- **RN-17.** El historial de cambios se conserva mientras el jugador permanezca en la institución, y solo existe borrado lógico. Al darse de baja un jugador el historial no se elimina; el plazo posterior se define con la institución.
- **RN-18.** Los criterios de convocatoria son del entrenador; el sistema muestra información y no bloquea ni valida convocatorias. Se prioriza visualmente a quienes tienen mejor desempeño (ver definición en 1.5); los jugadores con datos insuficientes se muestran al final. **[Propuesta]**

### 3.4 Reglas añadidas tras la auditoría
Todas son **[Decisión por defecto]** y deben validarse con la institución.
- **RN-19.** Mientras no exista perfil de administración, los usuarios (docentes y entrenadores) los crea el equipo del proyecto con un comando de consola o un seeder. Un usuario puede tener uno o los dos roles.
- **RN-20.** Un usuario puede buscar a un jugador por matrícula aunque no esté asignado a él; la búsqueda devuelve solo nombres, apellidos y matrícula. Asignar al jugador a un equipo (HU-04) o inscribirlo en una materia (HU-06) le otorga acceso al usuario que lo hace.
- **RN-21.** La alerta de riesgo de baja académica se activa cuando el nivel de riesgo es "en riesgo" (RN-04); "en observación" no genera alerta.
- **RN-22.** Quien crea un equipo queda asignado a él como entrenador y puede asignar a otros entrenadores existentes. Un equipo puede tener más de un entrenador; entre entrenadores de un mismo equipo se aplica la regla estricta de RN-14 hasta que la institución decida otra cosa.
- **RN-23.** Dar de baja a un jugador exige un motivo y una fecha, y queda en el historial. Un jugador dado de baja deja de aparecer en convocatorias, listados y alertas, y su información se conserva (RN-17).

## 4. Requisitos funcionales

### Épica E1. Acceso y alcance por asignación

**HU-01. Iniciar sesión.**
Como docente o entrenador, quiero ingresar con mi usuario y contraseña, para acceder a las funciones que corresponden a mi perfil.
- Dado un usuario registrado, cuando ingresa credenciales válidas, entonces accede a las funciones de su perfil.
- Dado un usuario, cuando ingresa credenciales incorrectas, entonces no accede y se le informa sin revelar cuál dato falló.
- Dado un docente, cuando navega, entonces no ve funciones exclusivas de entrenador, y viceversa.
- Dado un usuario con los dos roles, cuando navega, entonces accede a las funciones de ambos y sigue viendo solo a sus jugadores asignados.

**HU-02. Ver solo mis jugadores asignados.**
Como docente o entrenador, quiero ver solo a los jugadores asignados a mí, para proteger la confidencialidad.
- Dado un entrenador con un equipo asignado, cuando consulta jugadores, entonces solo ve los de sus equipos.
- Dado un docente con una materia asignada, cuando consulta alumnos, entonces solo ve los inscritos en sus materias.
- Dado un jugador no asignado al usuario, cuando intenta consultarlo por cualquier vía, entonces el sistema no muestra su información (la API responde 404).

**HU-27. Cerrar sesión y cambiar contraseña.**
Como docente o entrenador, quiero cerrar mi sesión y cambiar mi contraseña, para proteger mi cuenta.
- Dado un usuario con sesión, cuando cierra sesión, entonces la sesión deja de ser válida.
- Dado un usuario con sesión, cuando cambia su contraseña indicando la actual y una nueva válida (RNF-10), entonces se guarda y las demás sesiones se cierran.
- Dado un usuario, cuando indica una contraseña actual incorrecta, entonces el cambio se rechaza sin revelar más detalle.

### Épica E2. Equipos y expediente del jugador

**HU-03. Registrar equipos y jugadores.**
Como entrenador, quiero dar de alta equipos y jugadores, para iniciar el seguimiento.
- Dado un entrenador, cuando registra un equipo con nombre y categoría, entonces el equipo queda disponible para asignar jugadores.
- Dado un jugador nuevo, cuando se registra con nombres, apellidos, fecha de nacimiento, matrícula, equipo y posición, entonces se crea su expediente; la categoría se toma del equipo.
- Dado un jugador con una matrícula ya existente, cuando se intenta registrar de nuevo, entonces el sistema lo impide con un conflicto (409) sin revelar datos del expediente existente; el usuario puede buscarlo por matrícula (RN-20).

**HU-04. Asignar un jugador a varios equipos.**
Como entrenador, quiero que un jugador pueda pertenecer a más de un equipo, para reflejar su situación real.
- Dado un jugador en un equipo, cuando el entrenador lo localiza por matrícula (RN-20) y lo asigna a otro equipo, entonces pertenece a ambos y el entrenador obtiene acceso a él.
- Dado un jugador en dos equipos, cuando cada entrenador consulta, entonces ve solo lo que él registró (RN-14).
- Dado un jugador en dos equipos, cuando se consulta su estado de disponibilidad, entonces ambos entrenadores ven el mismo estado oficial y fecha estimada de regreso.

**HU-05. Mantener el estatus del jugador.**
Como entrenador, quiero actualizar el estatus deportivo y académico del jugador y su posición, para que el expediente refleje su situación actual.
- Dado un expediente, cuando se modifica un dato, entonces se guarda con fecha y autor (HU-26).
- Dado un jugador que cambia de posición en un equipo, cuando se registra el cambio, entonces el historial conserva la posición anterior y las estadísticas pasadas no se alteran.

**HU-29. Registrar la baja de un jugador.**
Como entrenador, quiero dar de baja a un jugador indicando el motivo, para que deje de aparecer en convocatorias y alertas sin perder su historia.
- Dado un jugador asignado, cuando el entrenador registra la baja con motivo y fecha, entonces el jugador queda en baja y la baja queda en el historial (RN-23).
- Dado una baja sin motivo, cuando se intenta guardar, entonces el sistema la rechaza con un mensaje claro.
- Dado un jugador en baja, cuando se consultan listados, convocatorias o alertas, entonces no aparece; su historial sigue consultable.

### Épica E3. Gestión académica

**HU-06. Dar de alta materias y alumnos.**
Como docente, quiero registrar mis materias e inscribir a mis alumnos según la retícula, para capturar su desempeño.
- Dado un docente, cuando da de alta una materia con periodo escolar, entonces queda asociada a él.
- Dado una materia, cuando se inscribe a un jugador existente, localizado por matrícula (RN-20), entonces el docente puede capturar sus calificaciones.
- Dado un jugador no registrado en el sistema, cuando el docente intenta inscribirlo, entonces el sistema le indica que debe existir su expediente.

**HU-07. Capturar calificaciones y asistencia a clases.**
Como docente, quiero registrar calificaciones parciales y finales y las asistencias, para mantener actualizada la situación académica.
- Dado un alumno inscrito, cuando el docente captura una calificación entre 0 y 10, entonces se guarda y se actualizan los cálculos.
- Dado una calificación fuera del rango, cuando se intenta guardar, entonces el sistema la rechaza con un mensaje claro.
- Dado una asistencia capturada, cuando se guarda, entonces el porcentaje de asistencia se recalcula (RN-05).

**HU-08. Definir criterio de aprobación por materia.**
Como docente, quiero usar el criterio general o definir el mío para mi materia, para adaptarme a mi asignatura.
- Dado una materia sin criterio propio, cuando se evalúa a un alumno, entonces se aplica el criterio general.
- Dado una materia con criterio propio, cuando se evalúa, entonces se aplica el del docente y el sistema indica cuál se usó.
- Dado un cambio de criterio, cuando se guarda, entonces queda registrado con fecha y autor.

**HU-09. Ver riesgo académico calculado.**
Como docente o entrenador, quiero ver el promedio general, las materias reprobadas, la asistencia y el nivel de riesgo de un jugador, para decidir con información actual.
- Dado un jugador con calificaciones capturadas, cuando se consulta su situación, entonces se muestran promedio, reprobadas, asistencia y nivel (RN-04).
- Dado un jugador con materias sin calificación, cuando se calcula el promedio, entonces el sistema indica que el dato está incompleto.
- Dado un entrenador, cuando consulta lo académico, entonces ve únicamente el resumen simplificado.

### Épica E4. Entrenamientos y partidos

**HU-10. Programar entrenamientos.**
Como entrenador, quiero programar sesiones de entrenamiento, para organizar y registrar la asistencia.
- Dado un entrenador, cuando crea una sesión con fecha, hora de inicio, hora de fin, lugar, equipo y responsable, entonces los jugadores del equipo quedan listados para registrar asistencia.
- Dado una sesión programada, cuando se modifica o cancela, entonces el cambio queda registrado con fecha y autor.
- Dado una sesión en el mismo lugar y horario que otra, cuando se intenta guardar, entonces el sistema advierte del empalme.

**HU-11. Registrar asistencia a entrenamientos.**
Como entrenador, quiero marcar presente, falta o falta justificada por jugador, para conocer su participación.
- Dado una sesión realizada, cuando el entrenador registra la asistencia de cada jugador, entonces se guarda con el estado elegido.
- Dado un jugador lesionado con estado oficial vigente, cuando se registra su asistencia, entonces el sistema lo indica para facilitar la captura.
- Dado un historial de asistencias, cuando se consulta, entonces se muestra el porcentaje de asistencia por jugador y por equipo.

**HU-12. Programar partidos y armar convocatoria.**
Como entrenador, quiero programar un partido y convocar jugadores, para organizar la participación del equipo.
- Dado un entrenador, cuando crea un partido con fecha, hora, lugar, rival, torneo (opcional en amistosos), tipo (amistoso, oficial, interno o torneo) y condición (local o visitante), entonces queda programado; la categoría se toma del equipo.
- Dado un partido programado, cuando el entrenador arma la convocatoria, entonces ve la vista consolidada de cada jugador (HU-24).
- Dado un jugador con estado oficial "lesionado", cuando se agrega a la convocatoria, entonces el sistema muestra una advertencia visible, sin impedirlo (RN-18).

**HU-13. Registrar alineación y resultado.**
Como entrenador, quiero registrar titulares, suplentes, posiciones y resultado, para completar el registro del partido.
- Dado un partido convocado, cuando se definen titulares, suplentes y posiciones, entonces quedan guardados.
- Dado un partido finalizado, cuando se captura el resultado, entonces el partido pasa a estado "jugado" y habilita la captura de rendimiento.
- Dado un jugador no convocado, cuando se intenta incluirlo en la alineación, entonces el sistema lo impide.

### Épica E5. Rendimiento en partidos

**HU-14. Capturar rendimiento después del partido.**
Como entrenador, quiero registrar el desempeño de cada jugador al terminar el partido, para mantener datos actualizados.
- Dado un partido jugado, cuando el entrenador abre la captura, entonces ve los datos comunes (minutos, titular o suplente, tarjetas, calificación general, observación breve) y los específicos de la posición del jugador.
- Dado un jugador de cualquier posición, cuando se captura, entonces se piden también goles y asistencias; además, para portero, atajadas y goles recibidos; para defensa, recuperaciones y faltas cometidas; para medio, pases completados y balones perdidos; para delantero, tiros a portería.
- Dado un partido jugado sin rendimiento capturado, cuando el entrenador consulta sus pendientes, entonces el partido aparece como pendiente de captura.

**HU-15. Gestionar plantillas y umbrales.**
Como entrenador, quiero usar plantillas predefinidas o crear las mías, para evaluar a cada jugador con parámetros adecuados.
- Dado un jugador sin umbral propio, cuando se evalúa, entonces se usa la plantilla de su posición.
- Dado un entrenador, cuando crea o ajusta un umbral para un jugador, entonces se aplica solo a ese jugador y en su equipo.
- Dado un cambio de umbral, cuando se guarda, entonces queda registrado quién lo hizo, cuándo y el valor anterior.

**HU-16. Evaluar si el jugador pasa o no pasa.**
Como entrenador, quiero que el sistema compare el promedio del jugador con su umbral, para detectar bajo rendimiento.
- Dado un jugador con evaluaciones suficientes, cuando se calcula su desempeño, entonces el resultado es "pasa" o "no pasa" (RN-08).
- Dado un jugador con menos evaluaciones de las requeridas, cuando se calcula, entonces el sistema indica "datos insuficientes" en lugar de un resultado.
- Dado un jugador en dos equipos, cuando cada entrenador consulta, entonces el resultado se calcula con sus propios datos y umbral.

### Épica E6. Estado físico

**HU-17. Registrar medidas periódicas.**
Como entrenador, quiero registrar peso y estatura por periodo, para seguir la evolución física.
- Dado un jugador, cuando se captura peso y estatura en un periodo, entonces se guardan junto con el índice de masa corporal calculado automáticamente.
- Dado una medida ya registrada para el periodo, cuando se captura otra, entonces el sistema pide confirmar si es una corrección; confirmada con un motivo, reemplaza la anterior y la anterior queda en el historial (`docs/convenciones-api.md` §4.1).
- Dado un historial de medidas, cuando se consulta, entonces se muestra su evolución.

**HU-18. Registrar pruebas físicas.**
Como entrenador, quiero registrar velocidad, resistencia y tiempo en pruebas cuando se realicen, para comparar resultados entre periodos.
- Dado una prueba física realizada, cuando el entrenador captura los resultados, entonces se guardan con fecha y periodo.
- Dado un jugador con pruebas en varios periodos, cuando se consulta, entonces se muestra su evolución.
- Dado una prueba no realizada, cuando se consulta el periodo, entonces aparece sin dato y no se asume valor alguno.

### Épica E7. Lesiones y disponibilidad

**HU-19. Crear un aviso de molestia o lesión.**
Como docente o entrenador, quiero reportar que un jugador tiene una molestia o lesión, para que el entrenador lo confirme.
- Dado un docente o entrenador, cuando crea un aviso con nota y fecha, entonces queda como "pendiente de confirmación".
- Dado un jugador que informa a un docente o entrenador de una molestia, cuando este lo registra, entonces el aviso indica que fue reportado por el jugador.
- Dado un aviso pendiente, cuando se muestra el jugador, entonces el estado oficial no cambia hasta que un entrenador lo confirme.

**HU-20. Confirmar el estado oficial de disponibilidad.**
Como entrenador, quiero confirmar el estado del jugador y el detalle de la lesión, para que la información de convocatoria sea confiable.
- Dado un aviso pendiente, cuando un entrenador del jugador lo confirma, entonces define estado (apto, con molestia o lesionado), tipo, zona, causa y fecha estimada de regreso, y queda como registrante (RN-16).
- Dado un docente, cuando intenta confirmar un estado oficial, entonces el sistema no se lo permite.
- Dado un jugador en dos equipos, cuando un entrenador que no registró la lesión intenta modificarla, entonces el sistema no se lo permite (403 sobre el estado oficial; 404 sobre el detalle, que no puede ver, RN-15).

**HU-21. Compartir la disponibilidad entre entrenadores.**
Como entrenador de un jugador en dos equipos, quiero ver su estado oficial y fecha estimada de regreso, para no convocar a alguien no disponible.
- Dado un estado oficial confirmado, cuando el otro entrenador consulta al jugador, entonces ve el estado y la fecha estimada de regreso.
- Dado el mismo caso, cuando consulta el detalle, entonces no ve tipo, zona ni causa de la lesión (RN-15).
- Dado una fecha estimada de regreso vencida, cuando se consulta, entonces el sistema señala que el estado requiere actualización.

**HU-30. Actualizar o descartar la disponibilidad.**
Como entrenador, quiero actualizar el estado de un jugador, darlo de alta o descartar un aviso, para que la disponibilidad esté vigente.
- Dado un estado oficial del que soy registrante, cuando registro la recuperación, entonces el estado pasa a "apto" con fecha de alta y el anterior queda en el historial.
- Dado un estado oficial del que soy registrante, cuando cambio la fecha estimada de regreso, entonces se actualiza y queda el valor anterior en el historial.
- Dado un aviso pendiente, cuando un entrenador del jugador lo descarta con una nota, entonces el aviso queda "descartado" y el estado oficial no cambia.

### Épica E8. Alertas y listado de estatus

**HU-22. Ver alertas del jugador.**
Como docente o entrenador, quiero ver las alertas activas de un jugador, para detectar situaciones que requieren atención.
- Dado un jugador en riesgo de baja académica, cuando se consulta su vista, entonces aparece la alerta con el dato que la causa.
- Dado un jugador con riesgo de baja deportiva o con acumulación de sanciones deportivas, cuando el entrenador lo consulta, entonces aparece la alerta correspondiente.
- Dado una alerta que deja de cumplirse, cuando se recalcula, entonces desaparece y queda en el historial.

**HU-23. Ver listado de estatus.**
Como docente o entrenador, quiero un listado con el estatus de mis jugadores, para priorizar mi atención.
- Dado un usuario, cuando abre el listado, entonces ve solo a sus jugadores asignados con el resumen de su situación.
- Dado el listado, cuando se filtra por equipo, materia o tipo de alerta, entonces muestra solo los que cumplen el filtro.
- Dado un docente, cuando abre el listado, entonces ve el detalle de su materia y solo el estado oficial de disponibilidad del lado deportivo; para un entrenador, a la inversa.
- Dado un jugador con un aviso de lesión pendiente, cuando se muestra en el listado, entonces aparece un indicador de aviso pendiente junto a su estado oficial.

### Épica E9. Vista para convocar y gráficos

**HU-24. Consultar la vista consolidada del jugador para convocar.**
Como entrenador, quiero ver en un solo lugar el rendimiento reciente, la situación académica, la asistencia a entrenamientos, el estado físico y el progreso histórico, para decidir la convocatoria.
- Dado un jugador asignado, cuando el entrenador abre su vista, entonces ve las cinco dimensiones y su disponibilidad (incluido un aviso pendiente) con su fecha de última actualización.
- Dado un dato con más de un periodo sin actualizar, cuando se muestra, entonces se marca como posiblemente desactualizado.
- Dado una lista de jugadores a convocar, cuando se ordena por desempeño, entonces los de mejor desempeño aparecen primero.

**HU-25. Ver gráficos de historial individual y grupal.**
Como entrenador o docente, quiero gráficos con el historial, para entender cómo evoluciona cada jugador y el grupo.
- Dado un jugador, cuando un entrenador consulta su evolución, entonces se muestran rendimiento en partidos, asistencia a entrenamientos, mediciones físicas y el resumen académico simplificado por periodo; cuando lo consulta un docente, calificaciones y asistencia a clases de su materia.
- Dado un equipo, cuando se consulta, entonces se muestra la evolución del grupo y el jugador contra el promedio del equipo.
- Dado un jugador en dos equipos, cuando un entrenador ve los gráficos, entonces solo incluyen los datos que ese entrenador registró.

### Épica E10. Trazabilidad

**HU-26. Consultar el historial de cambios.**
Como docente o entrenador, quiero saber quién capturó o modificó un dato y cuándo, para confiar en la información.
- Dado cualquier dato capturado, cuando se consulta su historial, entonces se muestra autor, fecha, valor anterior y valor nuevo.
- Dado un dato "eliminado", cuando se consulta, entonces no aparece en la vista normal pero sí en el historial (borrado lógico).
- Dado un historial, cuando se consulta, entonces solo muestra los cambios a los que el usuario tiene derecho a ver (RN-14, RN-15).

### Épica E11. Catálogos

**HU-28. Gestionar catálogos.**
Como docente o entrenador, quiero dar de alta los datos de apoyo que necesito, para poder programar partidos, crear materias y registrar equipos.
- Dado un entrenador, cuando da de alta una categoría, un lugar, un rival, un torneo o una temporada, entonces queda disponible para todos los entrenadores.
- Dado un docente, cuando da de alta un periodo escolar o una asignatura de la retícula, entonces queda disponible para todos los docentes.
- Dado un valor de catálogo ya existente (mismo nombre), cuando se intenta registrar de nuevo, entonces el sistema lo impide con un conflicto (409).
- Dado un valor de catálogo en uso, cuando se intenta eliminar, entonces solo se permite el borrado lógico y el histórico lo sigue mostrando.

## 5. Requisitos no funcionales

- **RNF-01 Confidencialidad.** Cada usuario accede únicamente a la información de sus jugadores asignados y al nivel de detalle que su perfil permite.
- **RNF-02 Trazabilidad.** Todo alta, cambio o baja lógica registra autor y fecha, y se conserva mientras el jugador esté en la institución.
- **RNF-03 Integridad.** No se permiten matrículas duplicadas entre registros no eliminados ni valores fuera de rango (por ejemplo, calificaciones fuera de 0 a 10).
- **RNF-04 Vigencia de datos.** Todo dato muestra su fecha de última actualización. Los instantes se guardan en UTC y se presentan en la zona America/Mexico_City. Un dato se marca como posiblemente desactualizado cuando su última captura supera 30 días (parámetro configurable). **[Propuesta]**
- **RNF-05 Usabilidad.** La captura posterior a un partido debe ser breve; se muestran solo los campos que aplican a la posición. Meta: completar el rendimiento de un jugador en menos de 2 minutos. *Meta a validar con entrenadores.* Es una meta de interfaz: se verifica en la fase del frontend. La API de la Fase 1 solo garantiza que la captura pida únicamente los campos de la posición.
- **RNF-06 Capacidad.** El sistema debe operar con al menos 15 equipos, 25 usuarios y los jugadores de esos equipos, con espacio para crecer. *Por confirmar: cifra estimada de futbolistas (supuesto de trabajo: unos 25 por equipo, es decir, alrededor de 375).*
- **RNF-07 Disponibilidad.** Debe estar disponible para captura el mismo día de los partidos, y no exigir conexión simultánea de todos los usuarios. Se resuelve en el despliegue, con respaldos y recuperación definidos antes de producción; no es una funcionalidad de la API.
- **RNF-08 Idioma.** Toda la interfaz está en español.
- **RNF-09 Extensibilidad.** Los umbrales, criterios y plantillas son configurables, para que un futuro perfil de administración los gestione sin rehacer el sistema.
- **RNF-10 Seguridad de acceso.** **[Propuesta]** Contraseñas de al menos 10 caracteres; bloqueo temporal de 15 minutos tras 5 intentos fallidos; sesión que caduca tras 120 minutos de inactividad; las contraseñas nunca se guardan ni se registran en claro.
- **RNF-11 Mantenibilidad y pruebas.** Cada historia y cada regla de negocio tiene pruebas automatizadas que se ejecutan con un solo comando; la cobertura mínima por capa se define en `docs/estrategia-pruebas.md`.

## 6. Información que maneja el sistema

| Entidad | Datos principales |
|---|---|
| Jugador | Nombre, fecha de nacimiento, matrícula, categoría, posición, estatus académico y deportivo |
| Equipo | Nombre, categoría, entrenador(es), jugadores |
| Materia | Nombre, periodo, docente, criterio de aprobación, alumnos |
| Calificación y asistencia a clase | Alumno, materia, parcial o final, valor, fecha |
| Entrenamiento | Fecha, hora, lugar, equipo, asistencias |
| Partido | Fecha, hora, lugar, rival, categoría, torneo, tipo, convocatoria, alineación, resultado |
| Rendimiento de partido | Jugador, partido, datos comunes y específicos de la posición |
| Plantilla y umbral | Posición, valores mínimos, jugador y entrenador al que aplica |
| Medida y prueba física | Peso, estatura, velocidad, resistencia, tiempos, periodo |
| Aviso de lesión | Jugador, autor, fecha, nota, origen (docente, entrenador o jugador), estado de confirmación |
| Estado de disponibilidad | Estado oficial, tipo, zona, causa, fecha estimada de regreso, entrenador que confirma |
| Alerta | Tipo, jugador, equipo, causa, fecha de activación y de cierre |
| Registro de cambio | Dato, autor, fecha, valor anterior y nuevo |
| Usuario | Nombre, correo, rol o roles, activo |
| Catálogos | Categoría, periodo escolar, asignatura, lugar, torneo, rival, temporada |
| Baja de jugador | Jugador, motivo, fecha, entrenador que la registra |

## 7. Matriz de visibilidad

| Información | Docente | Entrenador del equipo | Segundo entrenador del mismo jugador |
|---|---|---|---|
| Calificaciones y asistencia a clases (detalle) | Solo sus materias | Resumen simplificado | Resumen simplificado |
| Riesgo académico | Sí | Sí | Sí |
| Rendimiento en partidos (detalle) | No | Solo lo que registró | No |
| Riesgo deportivo | No | De su equipo | No |
| Estado oficial y fecha estimada de regreso | Sí | Sí | Sí |
| Aviso de lesión pendiente | Solo el que creó | Sí | Sí |
| Detalle de la lesión (tipo, zona, causa) | No | Solo si la registró | No |
| Estado físico (detalle) | No | Solo lo que registró | No |
| Asistencia a entrenamientos y convocatorias | No | Solo de sus equipos | Solo de sus equipos |
| Gráficos e historial | Calificaciones y asistencia de su materia | Solo lo que registró, más el resumen académico simplificado | Solo lo que registró, más el resumen académico simplificado |

Una celda con "No" significa que esa información no se muestra a ese perfil. El resumen simplificado se define en 1.5.

## 8. Criterios de éxito del MVP

Medidos entre 3 y 6 meses después de iniciar el uso:
- Todo partido jugado tiene su rendimiento capturado en un plazo acordado (por ejemplo, 48 horas). *Medido por el sistema.*
- Los entrenadores consultan la vista del jugador antes de armar la convocatoria. *Medido por encuesta: el MVP no registra consultas.*
- Cada jugador activo tiene un historial de al menos un periodo con gráficos de evolución. *Medido por el sistema.*
- Los dos tipos de usuarios confirman que ya no necesitan fuentes paralelas para decidir una convocatoria o una baja. *Medición por encuesta breve.*

## 9. Backlog (fases futuras)

El detalle de lo que se espera en la Fase 2, punto por punto del PDF, está en `docs/alcance-fases.md`.

- Perfiles de futbolista (consulta), coordinador, directivo, administrador y preparador físico.
- Administración de umbrales, criterios generales y plantillas.
- Notificaciones activas de alertas.
- Sanciones académicas y su regla.
- Árbitros e incidencias de partido.
- Rendimiento físico y observaciones individuales y grupales por entrenamiento.
- Datos del PDF no incluidos en el MVP: datos personales y de contacto, fotografía, documentación, grado o periodo académico; actividades académicas, observaciones docentes y retardos; pases realizados, faltas recibidas, distancia recorrida, velocidad promedio y máxima, evaluaciones técnicas, tácticas y psicológicas, disciplina deportiva; fuerza, agilidad, flexibilidad, potencia y coordinación; tipo, objetivos, actividades y duración del entrenamiento.
- Alertas del PDF que no son alertas propias en el MVP: bajo promedio académico y materias reprobadas (se muestran en el nivel de riesgo, RN-04); bajo rendimiento deportivo (parte del riesgo de baja deportiva, RN-10); inasistencias a entrenamientos (parte de la acumulación de sanciones, RN-09); faltas académicas frecuentes, retardos constantes, disminución del rendimiento físico, falta de participación en partidos, falta de evaluaciones y documentación incompleta (backlog).
- Análisis avanzado: causa y efecto de los cambios, comparaciones por posición, torneo y temporada, y relación entre rendimiento académico y deportivo.
- Reportes y exportación (PDF, Excel, CSV).

## 10. Riesgos y decisiones pendientes

### 10.1 Riesgos

| Riesgo | Impacto | Mitigación |
|---|---|---|
| Los docentes o entrenadores no capturan a tiempo | Los datos vuelven a quedar desactualizados | Mostrar pendientes y fecha de última actualización; revisar la carga de captura con los usuarios |
| El entrenador carga la información inicial sin validación | Datos de partida incorrectos | Validar la carga inicial con una persona de la institución |
| Umbrales distintos por entrenador | Resultados poco comparables | Plantillas por defecto y registro de cada cambio |
| Jugador en dos equipos con visibilidad limitada | Visión parcial del progreso | Documentarlo como consecuencia de la protección de datos |
| Alcance amplio para un MVP | Retrasos | Mantener el backlog y revisar prioridades por etapas |

### 10.2 Decisiones pendientes de validar con la institución
1. Todos los valores marcados como **[Propuesta]** (calificación mínima, niveles de riesgo, sanciones deportivas, umbrales, escala de calificación de partido). **Responsable de validar: por definir** (sugerido: coordinación académica y coordinación deportiva).
2. Si existen reglamentos de elegibilidad para convocar, académicos o disciplinarios.
3. Origen de la retícula y quién verifica los datos iniciales.
4. Si el aviso reportado por el jugador a través de un docente o entrenador (HU-19) cubre la necesidad planteada, mientras el futbolista no sea usuario.
5. Meta de tiempo de captura (RNF-05) y plazo de captura posterior a un partido (sección 8).
6. Decisiones por defecto aplicadas tras la auditoría (RN-19 a RN-23, HU-27 a HU-30, posición por equipo, alerta académica en "en riesgo", docente sin riesgo deportivo): ver `docs/decisiones/registro-decisiones.md`.
7. Cifra estimada de futbolistas (RNF-06), plazo de conservación tras la baja (RN-17) y si los entrenadores de un mismo equipo comparten datos (RN-22).

## 11. Trazabilidad con el documento original

| Punto del PDF | Cobertura en el MVP |
|---|---|
| 1. Registro de usuarios y futbolistas | Parcial (HU-01, HU-03 a HU-05, HU-27, HU-29); administrador, datos de contacto y fotografía en backlog |
| 2. Expediente académico | Parcial (HU-06 a HU-09) |
| 3. Rendimiento deportivo | Parcial (HU-14 a HU-16), con datos por posición |
| 4. Programación de entrenamientos | Parcial (HU-10, HU-11): solo asistencia |
| 5. Programación de partidos | Parcial (HU-12, HU-13): sin árbitros ni incidencias |
| 6. Evaluaciones físicas | Parcial (HU-17, HU-18) |
| 7. Alertas | Parcial (HU-22): 3 de 13 alertas, solo visibles en el sistema |
| 8. Análisis estadístico | Parcial (HU-25) |
| 9. Reportes | Backlog |
| 10. Consulta por el futbolista | Backlog |
| Lesiones (no estaba en el PDF) | Agregado (HU-19 a HU-21, HU-30) |
| Catálogos de apoyo (no estaban en el PDF) | Agregado (HU-28) |
