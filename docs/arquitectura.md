# Arquitectura de Software y Modelo de Datos del Sistema de Seguimiento Académico y Deportivo (MVP)

**Proyecto:** Sistema web de gestión académica y deportiva de futbolistas
**Versión:** 1.2.1 (ver sección 14)
**Fecha:** 21 de octubre de 2026
**Entrada:** Documento de Requisitos v1.1 (HU-01 a HU-30, RN-01 a RN-23, RNF-01 a RNF-11)

## 1. Propósito y alcance

Este documento define la arquitectura lógica (DDD), el modelo de datos en PostgreSQL y la estrategia de migraciones en Laravel para el MVP. Cada decisión se vincula con los requisitos v1.1; los valores marcados [Propuesta] o [Decisión por defecto] siguen pendientes de validar por la institución. El documento es la fuente para construir las migraciones y la capa de dominio; cualquier cambio posterior debe reflejarse aquí antes de programarse. Durante una fase, una decisión nueva se registra como ADR en `docs/decisiones/` y se consolida en este documento al cerrar la fase.

## 2. Stack base

| Elemento | Decisión | Nota |
|---|---|---|
| Framework | Laravel 13 | Es la última versión mayor (publicada el 17/03/2026). Laravel 12 sigue soportado hasta 2027 en seguridad, pero para un proyecto nuevo conviene la versión vigente. |
| PHP | 8.4 | Laravel 13 exige PHP 8.3 como mínimo y admite hasta 8.5. |
| Base de datos | PostgreSQL 16 o superior | Tipos `uuid`, `jsonb`, `timestamptz`, índices parciales y restricciones `CHECK`. |
| Identificadores | UUID v7 en todas las tablas | Ordenables por tiempo, lo que reduce la fragmentación de índices frente a UUID v4. |
| Trazabilidad | Historial propio por entidad | Ver ADR-04. |
| Autenticación | Laravel Sanctum en modo SPA (sesión con cookies) | Perfiles de MVP: docente y entrenador. Ver ADR-14. |
| Calidad | Pest, Laravel Pint, Larastan | Pruebas de dominio sin base de datos; pruebas de integración para repositorios y migraciones. |

## 3. Decisiones de arquitectura (ADR)

| ADR | Decisión | Alternativa descartada | Justificación |
|---|---|---|---|
| ADR-01 | **Monolito modular** con un bounded context por carpeta | Microservicios | 25 usuarios y un solo equipo no justifican la complejidad operativa. Los límites DDD permiten extraer un contexto más adelante. |
| ADR-02 | **Dominio puro en PHP**; Eloquent solo en Infraestructura, con repositorios que traducen | Eloquent como entidad de dominio | Protege las reglas de negocio (RN) de cambios del framework y permite probarlas sin base de datos. Costo: más código de mapeo. |
| ADR-03 | **Lecturas directas** (consultas especializadas) para listados, vista consolidada y gráficos; escrituras por casos de uso | Pasar toda lectura por agregados | Los agregados están pensados para proteger invariantes, no para armar pantallas. Evita consultas lentas y cargas innecesarias. |
| ADR-04 | **Historial propio por entidad**: tabla `{entidad}_historial`, solo de inserción | Tabla de auditoría central; extensiones de auditoría externas | Cumple RN-17 con claves foráneas reales y consultas simples por entidad. Se guardan valores anteriores y nuevos en `jsonb` para no duplicar columnas. |
| ADR-05 | **Borrado lógico** con `eliminado_en` y `eliminado_por`; unicidad mediante índices parciales (`WHERE eliminado_en IS NULL`) | Borrado físico | RN-17. Los índices parciales permiten reutilizar, por ejemplo, una matrícula solo si el registro anterior fue dado de baja lógicamente. |
| ADR-06 | **Visibilidad por asignación** aplicada en la capa de aplicación (políticas y filtros obligatorios de consulta) | Seguridad a nivel de fila en PostgreSQL | Más simple de probar y de evolucionar. Se reserva la seguridad a nivel de fila para una fase con más perfiles. |
| ADR-07 | **Alertas persistidas** y recalculadas al ocurrir eventos de dominio | Calcularlas en cada consulta | Permite listado rápido, filtro por tipo e historial de cuándo se activó y cerró cada alerta (HU-22). |
| ADR-08 | **Estadísticas por posición mediante catálogo de métricas** (`metricas` + `valores_metrica`) | Una columna por estadística; `jsonb` libre | Cada dato del backlog del PDF se agrega como fila de catálogo y no como migración. Se pueden hacer agregaciones SQL y validar tipo y rango. Costo: una unión adicional en lecturas. |
| ADR-09 | **Parámetros y umbrales en tablas**, con valores de fábrica sembrados | Constantes en código | RNF-09: el futuro perfil de administración los editará sin tocar el código. |
| ADR-10 | **Cadenas con `CHECK`** en lugar de tipos `ENUM` nativos de PostgreSQL; `enum` respaldado en PHP | `ENUM` nativo | Agregar un valor no exige alterar un tipo y es más sencillo de migrar. |
| ADR-11 | **Nombres de dominio en español** (tablas, columnas, clases de dominio), sin tildes ni ñ en ningún identificador de código o de base de datos; sufijos técnicos de Laravel en inglés (`Repository`, `Handler`) | Todo en inglés | El lenguaje ubicuo coincide con el de los usuarios. Se declara `$table` explícito en cada modelo para evitar problemas de pluralización. |
| ADR-12 | **Eventos de dominio** para comunicar contextos; entre contextos solo se referencian identificadores. Los eventos viven en `src/Compartido/Dominio/Eventos/` y llevan solo identificadores y valores primitivos | Llamadas directas entre contextos | Mantiene bajo acoplamiento y es la base para alertas y recalculo de riesgos. |
| ADR-13 | **API primero**: en la Fase 1 solo se construye el backend; el frontend es un proyecto aparte que se construye contra el contrato OpenAPI | Construir frontend y backend a la vez | Permite validar reglas y datos antes de invertir en pantallas. Soporta HU-01 a HU-30 y RNF-11. |
| ADR-14 | **Laravel Sanctum en modo SPA** (cookies de sesión), con bloqueo por intentos fallidos y caducidad de sesión | Tokens de API; autenticación nativa sin Sanctum | Frontend y API se despliegan en el mismo dominio. Si aparece una aplicación móvil se agrega el modo de tokens. Cubre HU-01, HU-27 y RNF-10. |
| ADR-15 | **Idioma**: dominio, base de datos, rutas y campos de la API en español; mensajes de error en `lang/es`; códigos de error estables y legibles por máquina | API en inglés | Coincide con el lenguaje de los usuarios (RNF-08). Cambiar el idioma del contrato equivale a una API nueva; los mensajes sí admiten más idiomas. |
| ADR-16 | **Entorno**: Docker Compose propio en la raíz de un monorepo (`backend/`, `frontend/`, `docs/`) | Laravel Sail | El monorepo exige que el archivo de Compose viva en la raíz y que el agente quede limitado a `backend/`. |

## 4. Bounded contexts

| Contexto | Responsabilidad | Agregados raíz | Reglas e invariantes principales | Requisitos |
|---|---|---|---|---|
| **Identidad** | Usuarios, roles, acceso, sesión y alta provisional por consola | Usuario | Un usuario tiene al menos un rol; credenciales válidas; bloqueo tras intentos fallidos (RNF-10) | HU-01, HU-27, RN-19, RNF-01, RNF-10 |
| **Plantel** | Jugadores, equipos, posiciones, categorías (catálogo), asignaciones y bajas | Jugador, Equipo | Matrícula única entre registros no eliminados; un jugador puede estar en varios equipos (RN-14); la posición es de cada equipo; quien crea un equipo queda asignado (RN-22); la baja exige motivo y fecha (RN-23); búsqueda por matrícula con datos mínimos (RN-20) | HU-02 a HU-05, HU-28 (categorías), HU-29, RN-12, RN-20, RN-22, RN-23 |
| **Académico** | Periodos escolares y asignaturas (catálogos), materias, inscripciones, calificaciones, asistencia a clases, criterios y riesgo | Materia, ExpedienteAcademico | Calificación entre 0 y 10; criterio propio prevalece sobre el general (RN-03); riesgo en tres niveles sobre el periodo vigente (RN-04) | HU-06 a HU-09, HU-28 (periodos y asignaturas), RN-01 a RN-05 |
| **Competencia** | Lugares, temporadas, torneos y rivales (catálogos), entrenamientos, partidos, convocatorias, alineaciones, asistencia a entrenamientos | Entrenamiento, Partido | Se advierte el empalme de lugar y horario sin impedirlo; solo convocados entran a la alineación; un partido jugado habilita la captura de rendimiento | HU-10 a HU-13, HU-28 (lugares, temporadas, torneos y rivales), RN-11 |
| **Rendimiento** | Métricas, rendimiento por partido, plantillas, umbrales y evaluación | RendimientoDePartido, PlantillaDeEvaluacion | Métricas válidas según la posición; resultado pasa, no pasa o datos insuficientes (RN-08); cada entrenador solo ve lo que registró (RN-14) | HU-14 a HU-16, RN-06 a RN-08 |
| **Condición Física** | Mediciones corporales y pruebas físicas | MedicionCorporal, PruebaFisica | IMC calculado; una medición por periodo salvo corrección confirmada | HU-17, HU-18 |
| **Salud** | Avisos, lesiones y estado oficial de disponibilidad | Lesion, EstadoDeDisponibilidad | Aviso queda pendiente hasta que un entrenador del jugador lo confirme; quien confirma queda como registrante y es el único que cambia el estado y su detalle (RN-15, RN-16) | HU-19 a HU-21, HU-30 |
| **Alertas** | Alertas persistidas y su recálculo | Alerta | Alerta activa mientras se cumpla la condición; la académica se activa en "en riesgo" (RN-21); riesgo deportivo calculado por equipo (RN-09, RN-10); "datos insuficientes" no activa alerta | HU-22, RN-09, RN-10, RN-21 |
| **Consultas** (solo lectura) | Listado de estatus, vista consolidada, gráficos, partidos pendientes de captura y consulta de historial | N/A (sin agregados) | Toda consulta de jugadores pasa por `AlcanceDeJugadores`; respeta RN-13, RN-14 y RN-15; los jugadores en baja no aparecen (RN-23) | HU-14 (pendientes de captura), HU-23 a HU-26, RN-18 |
| **Compartido (Shared Kernel)** | Tipos comunes: identificadores UUID, rangos de fecha, periodos, escritura de historial, eventos de dominio, contexto del usuario actual e interfaz `AlcanceDeJugadores` | N/A | N/A | RNF-02 |

### 4.1 Servicios de dominio

| Servicio | Contexto | Regla |
|---|---|---|
| `CalculadorDeRiesgoAcademico` | Académico | RN-04 |
| `ResolvedorDeCriterioDeAprobacion` | Académico | RN-03 (materia sobre general, y registra cuál se aplicó) |
| `EvaluadorDeRendimiento` | Rendimiento | RN-08 (promedio de la ventana contra el umbral; "datos insuficientes" si hay menos de N) |
| `DetectorDeSancionesDeportivas` | Alertas | RN-09 (por equipo) |
| `CalculadorDeRiesgoDeBajaDeportiva` | Alertas | RN-10 |
| `ConstructorDeVistaParaConvocar` | Consultas | HU-24 (cinco dimensiones, disponibilidad y aviso pendiente) |
| `CalculadorDeDesempeno` | Consultas | RN-18 (promedio de la ventana del umbral; sin datos al final) |
| `AlcanceDeJugadores` (interfaz) | Compartido | RNF-01 (implementación SQL de solo lectura en Consultas) |

### 4.2 Eventos de dominio

`CalificacionRegistrada`, `AsistenciaDeClaseRegistrada`, `CriterioDeMateriaModificado`, `AsistenciaDeEntrenamientoRegistrada`, `PartidoJugado`, `RendimientoRegistrado`, `UmbralModificado`, `MedicionRegistrada`, `AvisoDeLesionCreado`, `AvisoDeLesionDescartado`, `EstadoDeDisponibilidadConfirmado`, `JugadorAsignadoAEquipo`, `JugadorDadoDeBaja`.

Cada evento dispara el recalculo de riesgos y alertas del jugador afectado. Los escuchas son idempotentes.

### 4.3 Reglas de relación entre contextos
- **Plantel** es contexto proveedor: los demás lo referencian por `jugador_id`, `equipo_id` y `usuario_id`.
- Se permiten claves foráneas físicas hacia tablas de Plantel e Identidad para garantizar integridad; no se comparten objetos ni modelos entre contextos.
- **Alertas** es consumidor: escucha eventos de Plantel, Académico, Competencia, Rendimiento y Salud.
- Una consulta que cruza contextos (listado, vista consolidada, gráficos, pendientes de captura, historial) vive en el contexto **Consultas**, nunca dentro de un agregado. Consultas puede leer las tablas de otros contextos con SQL de solo lectura, pero no importa sus clases de dominio.
- `AlcanceDeJugadores` es una interfaz de Compartido; su implementación SQL vive en la Infraestructura de Consultas y lee `jugador_equipo`, `entrenador_equipo` e `inscripciones`.

## 5. Estructura de carpetas

Todo el código de la Fase 1 vive en `backend/` (ADR-16). Las rutas de este documento que empiezan con `src/`, `database/` o `tests/` se leen siempre como `backend/src/`, `backend/database/` y `backend/tests/`.

```
backend/
  app/
    Exceptions/                # Formato de error de la API (docs/convenciones-api.md)
    Http/                      # Interfaz: controladores, solicitudes, recursos, middleware
    Providers/
  docker/                      # Imagenes y scripts de inicializacion de Docker Compose
  lang/es/                     # Textos visibles al usuario
  src/                         # Espacio de nombres Src\ (PSR-4)
    Compartido/
    Identidad/
    Plantel/
    Academico/
    Competencia/
    Rendimiento/
    CondicionFisica/
    Salud/
    Alertas/
    Consultas/                 # Excepcion: solo Aplicacion/ e Infraestructura/, sin Dominio/
  database/
    migrations/{contexto}/     # Una subcarpeta por contexto (seccion 8)
    seeders/
  tests/
    Unit/                      # Dominio sin base de datos
    Integration/               # Repositorios, migraciones, restricciones e indices (PostgreSQL real)
    Feature/                   # Casos de uso, HTTP y comandos de consola
    Arch/                      # Reglas de arquitectura (Pest Arch)
docs/                          # Documentacion (el agente la lee; solo la edita si la tarea lo pide)
scripts/humo/                  # Guiones de humo por hito
frontend/                      # Fuera de la Fase 1: no se toca
```

**Plantilla de cada contexto** (todos salvo `Consultas`, que no tiene `Dominio/`):

```
src/{Contexto}/
  Dominio/                     # Entidades, objetos de valor, servicios, eventos, interfaces de repositorio. PHP puro
  Aplicacion/                  # Casos de uso (comandos y consultas), DTO, escuchas de eventos
  Infraestructura/             # Modelos Eloquent, repositorios, consultas de lectura
```

Los modelos Eloquent viven en `src/{Contexto}/Infraestructura/`, nunca en `app/Models/`. Si la autenticación de Laravel exige un modelo de usuario en otro lugar, la especificación de H1 lo resuelve y lo registra como decisión.

**Regla de dependencia:** `Infraestructura` y `Http` dependen de `Aplicacion`; `Aplicacion` depende de `Dominio`; `Dominio` no depende de nada del framework. Se verifica en la integración continua con pruebas de arquitectura.

## 6. Convenciones

| Ámbito | Convención |
|---|---|
| Tablas | `snake_case`, plural, en español (`jugadores`, `partidos`) |
| Columnas | `snake_case`; claves foráneas como `{entidad}_id`; booleanos como `es_` o `tiene_` |
| Claves primarias | `id` de tipo `uuid` |
| Auditoría básica | `creado_en`, `actualizado_en` (`timestamptz`), `creado_por`, `actualizado_por`, `eliminado_en`, `eliminado_por` |
| Historial | `{tabla}_historial`; solo se inserta |
| Restricciones | Nombres explícitos: `pk_`, `fk_`, `uq_`, `ck_`, `ix_` seguidos de tabla y columnas |
| Clases de dominio | Español, sin sufijos técnicos (`Jugador`, `Partido`); sufijos en inglés solo para patrones (`Repository`, `Handler`) |
| Casos de uso | Verbo en infinitivo y objeto: `RegistrarCalificacion`, `ConfirmarEstadoDeDisponibilidad` |

## 7. Modelo de datos

### 7.1 Principios
- Todas las tablas usan `id uuid` como clave primaria y las columnas de auditoría básica de la sección 6, salvo las marcadas como *sin auditoría* (tablas de unión y de solo inserción).
- Todos los instantes se guardan en `timestamptz` (UTC); la presentación usa la zona America/Mexico_City.
- Las claves foráneas usan `ON DELETE RESTRICT`: como no hay borrado físico, nunca debe haber eliminaciones en cascada.
- Valores cerrados (estados, tipos) como `varchar` con `CHECK` (ADR-10). Los valores abajo se muestran separados por barra.
- Cada tabla marcada con **(H)** tiene su tabla `{tabla}_historial` (sección 9).
- **Unicidad con borrado lógico.** Toda restricción de unicidad sobre una tabla con `eliminado_en` es un índice parcial `WHERE eliminado_en IS NULL` (ADR-05), incluidos los catálogos. Cuando una tabla dice "UQ parcial" sin condición, la condición es esa.
- **Nombres de catálogo sin distinguir mayúsculas.** Las columnas `nombre` de los catálogos (categorías, periodos escolares, lugares, temporadas, torneos, rivales) y `clave` de asignaturas son `citext`, para que "Sub-17" y "sub-17" choquen con 409 (HU-28).
- **Decimales exactos.** Promedios, umbrales, IMC y porcentajes nunca se calculan con `float`. La base usa `numeric`; el dominio usa un objeto de valor decimal de Compartido (con `bcmath`) y redondea al medio hacia arriba (`HALF_UP`) a dos decimales cuando una regla lo pide (RN-04).
- `resumenes_academicos` es **derivada**: se puede reconstruir desde los datos capturados. `evaluaciones_rendimiento` y `alertas` son registros **persistentes de solo inserción** (una evaluación nueva no reemplaza a la anterior y una alerta cerrada se conserva), porque lo que el entrenador vio al decidir y las fechas reales de activación y cierre no se reconstruyen.

### 7.2 Identidad

| Tabla | Columnas | Restricciones e índices |
|---|---|---|
| `usuarios` (H) | nombre, correo (`citext`), contrasena_hash, activo, intentos_fallidos, bloqueado_hasta | UQ parcial `correo` donde `eliminado_en IS NULL` |
| `roles` | codigo (docente/entrenador), nombre | UQ `codigo` |
| `usuario_rol` *(sin auditoría)* | usuario_id, rol_id | PK compuesta |

Los usuarios se crean con el comando de consola `usuarios:crear` (RN-19) mientras no exista perfil de administración; la contraseña se pide por consola y se guarda solo como hash. La tabla `sessions` de Laravel (driver `database`, DD-28) es de framework y no lleva auditoría.

### 7.3 Plantel

| Tabla | Columnas | Restricciones e índices |
|---|---|---|
| `categorias` | nombre (`citext`), activo | UQ parcial `nombre` |
| `posiciones` | codigo (portero/defensa/medio/delantero), nombre | UQ `codigo` |
| `jugadores` (H) | nombres, apellidos, fecha_nacimiento, matricula, estatus_academico (activo/baja), estatus_deportivo (activo/baja) | UQ parcial `matricula` donde `eliminado_en IS NULL`; `CHECK` de estatus |
| `equipos` (H) | nombre, categoria_id, activo | UQ parcial (`nombre`, `categoria_id`) |
| `jugador_equipo` (H) | jugador_id, equipo_id, posicion_id, desde, hasta | UQ parcial (`jugador_id`, `equipo_id`) donde `hasta IS NULL`; IX `equipo_id` |
| `entrenador_equipo` (H) | usuario_id, equipo_id, desde, hasta | UQ parcial (`usuario_id`, `equipo_id`) donde `hasta IS NULL`; IX `equipo_id` |
| `bajas_jugador` (H) | jugador_id, motivo, fecha, registrada_por | `CHECK` de motivo no vacío (RN-23); IX `jugador_id` |

**La posición es de cada equipo** y vive solo en `jugador_equipo.posicion_id`; la categoría se deduce de `equipos.categoria_id`. El cambio de posición (HU-05) se conserva en `jugador_equipo_historial`. Cada rendimiento guarda la `posicion_id` con la que se jugó, para que las estadísticas pasadas no cambien. Al crear un equipo, el creador queda con una fila vigente en `entrenador_equipo` (RN-22).

### 7.4 Académico

| Tabla | Columnas | Restricciones e índices |
|---|---|---|
| `periodos_escolares` | nombre (`citext`), fecha_inicio, fecha_fin | UQ parcial `nombre`; `CHECK (fecha_fin > fecha_inicio)` |
| `asignaturas` | clave (`citext`), nombre, grado | UQ parcial `clave` (la retícula; RN-12) |
| `materias` (H) | asignatura_id, periodo_id, docente_id | UQ parcial (`asignatura_id`, `periodo_id`, `docente_id`); IX `docente_id` |
| `inscripciones` (H) | materia_id, jugador_id | UQ parcial (`materia_id`, `jugador_id`); IX `jugador_id` |
| `criterios_aprobacion` (H) | ambito (general/materia), materia_id, calificacion_minima `numeric(4,2)`, vigente_desde, vigente_hasta | `CHECK` de 0 a 10; `CHECK` que `materia_id` sea nulo si y solo si el ámbito es general; UQ parcial de un solo criterio vigente por materia y uno general |
| `calificaciones` (H) | inscripcion_id, tipo (parcial/final), numero_parcial, valor `numeric(4,2)`, criterio_id (el aplicado, RN-03), fecha | `CHECK valor BETWEEN 0 AND 10`; `CHECK` que `numero_parcial` sea nulo si y solo si `tipo = 'final'`; UQ parcial (`inscripcion_id`, `tipo`, `numero_parcial`) con `NULLS NOT DISTINCT` |
| `asistencias_clase` (H) | inscripcion_id, fecha, estado (presente/falta/falta_justificada) | UQ parcial (`inscripcion_id`, `fecha`) |
| `resumenes_academicos` *(derivada, sin auditoría; periodo escolar vigente, RN-04)* | jugador_id, periodo_id, promedio, materias_reprobadas, materias_sin_calificar, porcentaje_asistencia, nivel_riesgo (sin_riesgo/en_observacion/en_riesgo), calculado_en | UQ (`jugador_id`, `periodo_id`); IX `nivel_riesgo` |

### 7.5 Competencia

| Tabla | Columnas | Restricciones e índices |
|---|---|---|
| `lugares` | nombre | UQ parcial `nombre` |
| `temporadas` | nombre, fecha_inicio, fecha_fin | UQ parcial `nombre`; `CHECK (fecha_fin > fecha_inicio)` |
| `torneos` | nombre, temporada_id | UQ parcial (`nombre`, `temporada_id`) |
| `rivales` | nombre | UQ parcial `nombre` |
| `entrenamientos` (H) | equipo_id, lugar_id, inicia_en, termina_en, responsable_id, estado (programado/realizado/cancelado) | `CHECK (termina_en > inicia_en)`; IX (`equipo_id`, `inicia_en`); IX (`lugar_id`, `inicia_en`) para detectar empalmes |
| `asistencias_entrenamiento` (H) | entrenamiento_id, jugador_id, estado (presente/falta/falta_justificada) | UQ parcial (`entrenamiento_id`, `jugador_id`); IX `jugador_id` |
| `partidos` (H) | equipo_id, temporada_id (la que contiene la fecha), torneo_id (opcional en amistosos), rival_id, lugar_id, inicia_en, tipo (amistoso/oficial/interno/torneo), condicion (local/visitante), estado (programado/jugado/cancelado), goles_favor, goles_contra | `CHECK` que el resultado exista solo si el estado es jugado; IX (`equipo_id`, `inicia_en`); UQ (`id`, `equipo_id`) para claves foráneas compuestas |
| `convocatorias` (H) | partido_id, jugador_id, posicion_id, rol_alineacion (titular/suplente, nulo mientras no haya alineación) | UQ parcial (`partido_id`, `jugador_id`); IX `jugador_id` |

El empalme de entrenamientos se **advierte** (HU-10) y no se impide, por lo que no se usa una restricción de exclusión. La categoría de un entrenamiento o partido se deduce de `equipos.categoria_id`. La visibilidad de `asistencias_entrenamiento` y `convocatorias` se resuelve por el equipo del entrenamiento o del partido (sección 10), no por `registrado_por`.

### 7.6 Rendimiento

| Tabla | Columnas | Restricciones e índices |
|---|---|---|
| `metricas` | codigo, nombre, tipo_dato (entero/decimal), minimo, maximo, activa | UQ `codigo` |
| `metricas_posicion` *(sin auditoría)* | metrica_id, posicion_id, obligatoria, orden | PK compuesta |
| `rendimientos_partido` (H) | partido_id, jugador_id, equipo_id, registrado_por, posicion_id, minutos, fue_titular, tarjetas_amarillas, tarjetas_rojas, calificacion_general `smallint`, observacion | `CHECK` de minutos 0 a 150 y calificación entera de 1 a 10; FK compuesta (`partido_id`, `equipo_id`) hacia `partidos` (`id`, `equipo_id`) para que el equipo coincida; UQ parcial (`partido_id`, `jugador_id`); IX (`jugador_id`, `partido_id`); IX (`registrado_por`) |
| `valores_metrica` (H) | rendimiento_id, metrica_id, valor `numeric(10,2)` | `id uuid` como clave primaria; UQ parcial (`rendimiento_id`, `metrica_id`); la aplicación valida que la métrica pertenezca a la posición |
| `plantillas_evaluacion` (H) | nombre, posicion_id, creada_por (nulo si es de fábrica), calificacion_minima `numeric(4,2)`, ventana_partidos, es_de_fabrica | `CHECK` de 1 a 10; `CHECK ventana_partidos > 0` |
| `umbrales_rendimiento` (H) | jugador_id, equipo_id, entrenador_id, plantilla_id (opcional), calificacion_minima `numeric(4,2)`, ventana_partidos, vigente_desde, vigente_hasta | `CHECK` de 1 a 10 y `CHECK ventana_partidos > 0` (igual que la plantilla); UQ parcial (`jugador_id`, `equipo_id`) donde `vigente_hasta IS NULL` |
| `evaluaciones_rendimiento` *(persistente, solo inserción, sin auditoría)* | jugador_id, equipo_id, umbral_id, promedio `numeric(4,2)`, partidos_considerados, resultado (pasa/no_pasa/datos_insuficientes), calculado_en | IX (`jugador_id`, `equipo_id`, `calculado_en` descendente) |

El mínimo de calificaciones para evaluar es `ventana_partidos` (RN-08): con menos, el resultado es `datos_insuficientes`. `rendimientos_partido` y `valores_metrica` son la base de los gráficos de historial (HU-25). Para mostrar al entrenador solo lo que él registró (RN-14) se filtra por `registrado_por`.

### 7.7 Condición Física

| Tabla | Columnas | Restricciones e índices |
|---|---|---|
| `mediciones_corporales` (H) | jugador_id, equipo_id, registrado_por, periodo (`char(7)`, `AAAA-MM`), peso_kg `numeric(5,2)`, estatura_cm `numeric(5,1)`, imc (columna generada) | `imc = peso_kg / power(estatura_cm / 100, 2)`; UQ parcial (`jugador_id`, `registrado_por`, `periodo`); `CHECK` de valores positivos |
| `tipos_prueba` | codigo (velocidad/resistencia/tiempo_prueba), nombre, unidad, mejor_resultado (mayor/menor) | UQ `codigo` |
| `pruebas_fisicas` (H) | jugador_id, equipo_id, registrado_por, tipo_prueba_id, fecha, periodo (`char(7)`, `AAAA-MM`), resultado `numeric(8,2)` | IX (`jugador_id`, `tipo_prueba_id`, `fecha`) |

Peso y estatura se guardan en una sola fila por periodo; el IMC nunca se captura, se calcula.

### 7.8 Salud

Se separa **el estado oficial** (visible para todos los entrenadores del jugador) del **detalle de la lesión** (visible solo para quien la registró), para que la regla RN-15 se cumpla por estructura y no solo por filtros.

| Tabla | Columnas | Restricciones e índices |
|---|---|---|
| `avisos_lesion` (H) | jugador_id, creado_por, reportado_por_jugador (booleano), nota, estado (pendiente/confirmado/descartado), lesion_id (opcional), fecha_aviso, descartado_por, nota_descarte | IX (`jugador_id`, `estado`) |
| `lesiones` (H) | jugador_id, registrada_por, tipo, zona, causa (entrenamiento/partido/otra), fecha_inicio, fecha_estimada_regreso, fecha_alta, nota | IX (`jugador_id`, `registrada_por`) |
| `estados_disponibilidad` (H) | jugador_id, estado (apto/con_molestia/lesionado), lesion_id (opcional), fecha_estimada_regreso, confirmado_por, vigente_desde, vigente_hasta | UQ parcial `jugador_id` donde `vigente_hasta IS NULL` (un solo estado vigente) |

`lesiones.registrada_por` es el entrenador que confirma el aviso (RN-16). Un aviso pendiente lo ven los entrenadores del jugador y quien lo creó (sección 10).

### 7.9 Alertas y parámetros

| Tabla | Columnas | Restricciones e índices |
|---|---|---|
| `parametros_institucionales` (H) | clave, valor `jsonb`, descripcion, vigente_desde, vigente_hasta | UQ parcial `clave` donde `vigente_hasta IS NULL`. Guarda niveles de riesgo académico, nivel que activa la alerta académica (RN-21), reglas de sanción, niveles de calificación de partido y días para marcar un dato como desactualizado (RNF-04) |
| `alertas` *(persistente, sin auditoría; no se borra)* | tipo (riesgo_baja_academica/riesgo_baja_deportiva/acumulacion_sanciones_deportivas), jugador_id, equipo_id (nulo en las académicas), causa `jsonb`, activada_en, cerrada_en | UQ parcial de una alerta abierta por (`tipo`, `jugador_id`, `COALESCE(equipo_id, uuid nulo)`) donde `cerrada_en IS NULL`; IX (`jugador_id`, `cerrada_en`) |

Una alerta cerrada se conserva: su historial es el propio registro (`activada_en` y `cerrada_en`), por lo que no necesita tabla de historial aparte. **La única modificación permitida** es asignar `cerrada_en` una vez (de nulo a una fecha); ninguna otra columna cambia y una alerta cerrada no se reabre: si la condición vuelve a cumplirse, se crea una alerta nueva. Un disparador rechaza cualquier otro `UPDATE` y todo `DELETE`.

## 8. Estrategia de migraciones (Laravel 13)

### 8.1 Reglas
1. **Una migración por tabla**, con `up()` y `down()` completos. Una migración ya integrada en la rama principal **no se edita**: se crea una nueva.
2. Las migraciones viven en `backend/database/migrations/{contexto}/` (una subcarpeta por contexto) y el proveedor de servicios de la aplicación registra cada subcarpeta con `loadMigrationsFrom`.
3. **El nombre incluye un prefijo de orden global**, porque Laravel las ejecuta por nombre y las claves foráneas exigen que la tabla de destino exista antes. Formato: `AAAA_MM_DD_GGGSSS_descripcion`, donde `GGG` es el grupo de 8.2 y `SSS` la secuencia dentro del grupo (por ejemplo `2026_10_20_020100_crear_tabla_jugadores`). Todas las migraciones iniciales llevan la misma fecha, de modo que el orden depende solo del grupo; las migraciones posteriores llevan su propia fecha.
4. Laravel no cubre `CHECK`, índices parciales ni columnas generadas con el constructor de esquema: se usan `DB::statement` dentro de la misma migración y se revierten en `down()`.
5. Un macro de `Blueprint` en Compartido (`auditoriaBasica()`) agrega `creado_en`, `actualizado_en`, `creado_por`, `actualizado_por`, `eliminado_en` y `eliminado_por`, y otro (`historial()`) crea la tabla de historial, para que todas sean idénticas.
6. Los **seeders son idempotentes** (se identifican por `codigo` o `clave`) y se ejecutan igual en cualquier ambiente.
7. Las pruebas ejecutan `migrate:fresh` en una base PostgreSQL real (no SQLite), porque el diseño depende de funciones propias de PostgreSQL.

### 8.2 Orden de ejecución

| Grupo | Contenido |
|---|---|
| 000 | Extensión `citext`; tablas del framework: `trabajos` y `trabajos_fallidos` (H0) y `sessions` (H1) |
| 010 | Identidad: `usuarios`, `roles`, `usuario_rol` |
| 020 | Plantel: `categorias`, `posiciones`, `jugadores`, `equipos`, `jugador_equipo`, `entrenador_equipo`, `bajas_jugador` |
| 030 | Académico: `periodos_escolares`, `asignaturas`, `materias`, `inscripciones`, `criterios_aprobacion`, `calificaciones`, `asistencias_clase`, `resumenes_academicos` |
| 040 | Competencia: `lugares`, `temporadas`, `torneos`, `rivales`, `entrenamientos`, `asistencias_entrenamiento`, `partidos`, `convocatorias` |
| 050 | Rendimiento: `metricas`, `metricas_posicion`, `rendimientos_partido`, `valores_metrica`, `plantillas_evaluacion`, `umbrales_rendimiento`, `evaluaciones_rendimiento` |
| 060 | Condición Física: `mediciones_corporales`, `tipos_prueba`, `pruebas_fisicas` |
| 070 | Salud: `lesiones`, `avisos_lesion`, `estados_disponibilidad` |
| 080 | Alertas: `parametros_institucionales`, `alertas` |
| 090 | Todas las tablas `*_historial`, más restricciones de solo inserción |

### 8.3 Ejemplo de migración

```php
public function up(): void
{
    Schema::create('jugadores', function (Blueprint $t) {
        $t->uuid('id')->primary();
        $t->string('nombres', 120);
        $t->string('apellidos', 120);
        $t->date('fecha_nacimiento');
        $t->string('matricula', 30);
        $t->string('estatus_academico', 20)->default('activo');
        $t->string('estatus_deportivo', 20)->default('activo');
        $t->auditoriaBasica();
    });

    DB::statement("ALTER TABLE jugadores ADD CONSTRAINT ck_jugadores_estatus
        CHECK (estatus_academico IN ('activo','baja') AND estatus_deportivo IN ('activo','baja'))");
    DB::statement('CREATE UNIQUE INDEX uq_jugadores_matricula ON jugadores (matricula) WHERE eliminado_en IS NULL');
}
```

### 8.4 Datos de fábrica (seeders)

| Seeder | Contenido | Estado |
|---|---|---|
| `RolesSeeder` | docente, entrenador | Definitivo |
| `PosicionesSeeder` | portero, defensa, medio, delantero | Definitivo |
| `MetricasSeeder` | goles, asistencias, tiros_a_porteria, atajadas, goles_recibidos, recuperaciones, faltas_cometidas, pases_completados, balones_perdidos, con su relación a cada posición (`metricas_posicion`): goles y asistencias en las cuatro posiciones (HU-14) | Definitivo (según el MVP) |
| `TiposPruebaSeeder` | velocidad, resistencia, tiempo_prueba | Definitivo |
| `CriterioGeneralSeeder` | Calificación mínima aprobatoria 7.0 | **[Propuesta]**: validar |
| `ParametrosInstitucionalesSeeder` | Niveles de riesgo académico, reglas de sanción deportiva y niveles de calificación de partido | **[Propuesta]**: validar |
| `UsuariosDemoSeeder` | Un docente y dos entrenadores de prueba | Solo desarrollo y pruebas; nunca en producción |
| `PlantillasEvaluacionSeeder` | Una plantilla de fábrica por posición (últimas 5 calificaciones, mínimo 6.0) | **[Propuesta]**: validar |

El catálogo de métricas es el punto de crecimiento principal: agregar una estadística del backlog del PDF es insertar una fila en `metricas` y `metricas_posicion`, sin migración de esquema.

## 9. Historial por entidad (RN-17, HU-26)

### 9.1 Estructura de cada tabla `{tabla}_historial`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | uuid | Clave primaria |
| `{entidad}_id` | uuid | FK a la entidad (`jugador_id`, `partido_id`, etc.) |
| `version` | integer | Consecutivo por entidad |
| `operacion` | varchar | `crear`, `actualizar`, `eliminar` o `restaurar` (`CHECK`) |
| `datos_anteriores` | jsonb | Estado previo (nulo al crear) |
| `datos_nuevos` | jsonb | Estado posterior (nulo al eliminar) |
| `usuario_id` | uuid | Quién hizo el cambio |
| `ocurrido_en` | timestamptz | Cuándo |
| `motivo` | varchar | Opcional (por ejemplo, corrección de una medición) |

Restricciones: UQ (`{entidad}_id`, `version`) e IX (`{entidad}_id`, `ocurrido_en` descendente).

**Excepción:** en `usuarios_historial` la columna de la entidad se llama `usuario_afectado_id`, porque `usuario_id` (autor del cambio) ya existe (DD-34).

### 9.2 Cómo se escribe
- El repositorio de cada agregado escribe el cambio y su registro de historial **en la misma transacción**; si una falla, fallan ambas.
- Las tablas de historial son de solo inserción: se revoca `UPDATE` y `DELETE` al rol de la aplicación y se agrega un disparador que rechaza ambas operaciones.
- **El historial hereda la visibilidad de su entidad.** El historial de `lesiones` solo lo ve quien la registró, y el de `rendimientos_partido` solo el entrenador que lo capturó (RN-14, RN-15).
- `resumenes_academicos` no lleva historial propio: se reconstruye desde los datos capturados, que sí lo tienen. `alertas` y `evaluaciones_rendimiento` son su propio registro histórico (solo inserción) y no se reconstruyen.

## 10. Visibilidad por asignación (RNF-01, ADR-06)

| Quién | Ve a un jugador si… |
|---|---|
| Entrenador | El jugador tiene una membresía vigente en `jugador_equipo` en un equipo con asignación vigente en `entrenador_equipo` |
| Docente | El jugador tiene una inscripción en una materia donde `docente_id` es el usuario |

Un jugador con baja registrada (RN-23) queda fuera de listados, convocatorias y alertas, pero sigue accesible por su historial para quien ya lo tenía asignado. Un recurso fuera del alcance responde 404.

- Un servicio `AlcanceDeJugadores` devuelve la restricción de consulta; **ninguna consulta de jugadores se escribe sin pasar por él**.
- Las pruebas de arquitectura verifican que las consultas de lectura lo usen.
- **Única excepción:** la búsqueda por matrícula (RN-20) devuelve solo nombres, apellidos y matrícula de un jugador no asignado; asignarlo o inscribirlo (HU-04, HU-06) otorga el alcance.
- Reglas por dato:
  - Rendimiento, estado físico y evaluaciones: filtro por `registrado_por` igual al usuario (RN-14).
  - Estado de disponibilidad: visible para todos los entrenadores con acceso al jugador.
  - Detalle de lesión: solo si `registrada_por` es el usuario.
  - Resumen académico: igual para todo entrenador asignado, sin calificaciones individuales.
  - Alertas académicas: docentes y entrenadores del jugador. Alertas deportivas: solo el entrenador del equipo en `equipo_id`; el docente no las ve.
  - Docente, del lado deportivo: solo el estado oficial de disponibilidad (RN-13).
  - Avisos pendientes: entrenadores del jugador y quien los creó.
  - Asistencia a entrenamientos y convocatorias: entrenadores de los equipos del entrenamiento o del partido.
  - Gráficos e historial: solo los datos que el usuario puede ver según estas reglas.

## 11. Alertas y recálculo (ADR-07)

1. Un evento de dominio (por ejemplo, `CalificacionRegistrada`) se publica al confirmar la transacción.
2. Un escucha en cola recalcula lo derivado del jugador: `resumenes_academicos` y alerta académica, o `evaluaciones_rendimiento` y alertas deportivas. La cola usa el driver `database` (no es un servicio externo): en Docker Compose corre un servicio `worker`; en las pruebas el driver es `sync`. Los eventos se despachan después de confirmar la transacción (`afterCommit`).
3. Si la condición se cumple y no hay alerta abierta, se crea; si deja de cumplirse, se cierra con `cerrada_en`.
4. Los escuchas son **idempotentes** y reintentables. Un comando de consola (`alertas:recalcular`) concilia los resúmenes y las alertas abiertas, útil tras cambiar parámetros de fábrica; nunca reescribe `activada_en` ni `cerrada_en` de alertas cerradas ni borra evaluaciones anteriores.

5. **Condiciones que vencen con el tiempo.** Algunas condiciones cambian sin que ocurra ningún evento: las faltas salen de la ventana de 30 días (RN-09), la tarjeta roja deja de contar al terminar la temporada y una fecha estimada de regreso se vence (HU-21). Por eso `alertas:recalcular` se programa en el planificador de Laravel **una vez al día a las 02:00 (America/Mexico_City)**, y en Docker Compose corre un servicio `scheduler` (`schedule:work`). Las marcas `requiere_actualizacion` (HU-21) y `posiblemente_desactualizado` (HU-24, RNF-04) no se guardan: se calculan al leer.

Las reglas de cálculo leen `parametros_institucionales`, `criterios_aprobacion` y `umbrales_rendimiento`, nunca constantes del código. La fecha de cálculo se obtiene de un reloj inyectable de Compartido, para que las pruebas fijen el día.

## 12. Pruebas y calidad

| Nivel | Qué se prueba |
|---|---|
| Unitarias de dominio | RN-01 a RN-23: riesgo académico, pasa o no pasa, sanciones, criterio aplicable. Sin base de datos |
| Integración | Repositorios, historial en la misma transacción, restricciones `CHECK` e índices parciales, migraciones `up` y `down` |
| Funcionales | Casos de uso y criterios de aceptación de HU-01 a HU-30 (ver `docs/criterios-aceptacion-fase-1.md`) |
| Consola | Comandos `usuarios:crear` y `alertas:recalcular`, y guion de humo de la API por hito (ver `docs/estrategia-pruebas.md`) |
| Seguridad | Un entrenador o docente no accede a jugadores no asignados ni al detalle de lesiones de otro |
| Arquitectura | Dominio sin dependencias del framework; consultas de jugadores pasan por `AlcanceDeJugadores` |

## 13. Riesgos técnicos y decisiones abiertas

| Tema | Riesgo o duda | Propuesta |
|---|---|---|
| Co-entrenadores | Si un equipo tiene dos entrenadores, RN-14 ("solo lo que él registró") impediría que se vean entre sí | Confirmar si los entrenadores de un mismo equipo comparten datos. Hasta definirlo se aplica la regla estricta |
| Reglas de sanción | RN-09 y RN-10 son propuestas, no valores institucionales | Validar antes de sembrar |
| Retención | El historial se conserva mientras el jugador esté en la institución; falta definir qué ocurre al darse de baja | Mantenerlo sin borrar y definir con la institución un plazo posterior |
| Aviso del jugador | `reportado_por_jugador` asume que otro usuario lo registra | Se reemplaza por un perfil de futbolista en la fase siguiente |
| Volumen | Crecimiento de `rendimientos_partido`, `valores_metrica` e historiales | Para este tamaño de institución no se requiere partición. Se revisa con datos reales después de una temporada |
| Respaldos y recuperación | No definidos | Definir frecuencia y retención de respaldos de la base antes de producción |
| Diagrama entidad-relación | Aún no incluido | Se puede generar a partir de las secciones 7.2 a 7.9 |
| Decisiones por defecto | RN-19 a RN-23, posición por equipo, alerta académica en "en riesgo" y docente sin riesgo deportivo se tomaron para avanzar | Validar con la institución (`docs/decisiones/registro-decisiones.md`) antes del hito que las usa |
| Tablas de la Fase 2 | Datos y alertas del PDF fuera del MVP (`docs/alcance-fases.md`) | Entran como filas de catálogo (métricas, tipos de prueba) o como migraciones nuevas; no se anticipan tablas en la Fase 1 |

## 14. Control de cambios

| Versión | Fecha | Cambios |
|---|---|---|
| 1.1 | 19/10/2026 | Correcciones de la auditoría cruzada |
| 1.2 | 21/10/2026 | Sección 5: todo bajo `backend/` y plantilla de contexto separada (Consultas sin Dominio). Sección 7.1: unicidad parcial con borrado lógico en todas las tablas, nombres de catálogo `citext`, decimales exactos con redondeo `HALF_UP`. Tipos `numeric(4,2)` en plantillas, umbrales y evaluaciones. Sección 7.9: única modificación permitida en `alertas`. Sección 11: cola `database` con servicio `worker`, eventos después del commit, recálculo diario programado y reloj inyectable |
| 1.2.1 | 22/10/2026 | Sección 7.2 y grupo 000 de 8.2: la tabla `sessions` (driver `database`) y las tablas de cola de H0 |
