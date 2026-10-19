# Reglas permanentes del agente

## Proyecto
API backend de un sistema de seguimiento académico y deportivo de futbolistas (Fase 1, MVP).
Stack: Laravel 13, PHP 8.4, PostgreSQL 16, Docker Compose. Arquitectura DDD (monolito modular).
Solo backend. La carpeta `frontend/` no se toca nunca.
Idioma: todo el dominio, la base de datos, la API y los mensajes van en español, sin tildes ni ñ en identificadores de código o de base de datos.

## Fuentes de verdad (en orden de precedencia)
1. La especificación del hito actual: `docs/hitos/f1-hN.md`.
2. `docs/requisitos.md` (historias HU, reglas RN, requisitos RNF).
3. `docs/arquitectura.md` (contextos, tablas, ADR).
4. `docs/convenciones-api.md` (formato de la API y de los errores).
5. `docs/estrategia-pruebas.md` (pruebas, umbrales y tablas de casos).
6. `docs/alcance-fases.md` y `docs/criterios-aceptacion-fase-1.md` (qué entra y cuándo está aceptado).
7. `docs/decisiones/registro-decisiones.md` (decisiones por defecto y abiertas).
8. Este archivo.
Si dos fuentes se contradicen, o algo es ambiguo, DETENTE y pregunta. Nunca decidas tú.

## Alcance: regla principal
**Lo que no está en la tarea actual ni en la lista "Fase 1" de `docs/alcance-fases.md` NO se construye**, aunque parezca útil, fácil, "de paso" o esté mencionado en el PDF original o en el backlog.
- Haz exactamente lo que pide la tarea. Ni menos ni más.
- No agregues endpoints, rutas, campos, columnas, tablas, migraciones, comandos de consola, eventos, servicios, enumeraciones ni valores que la tarea y los documentos no nombren.
- No anticipes la Fase 2: reportes, exportación, notificaciones, perfiles de administrador, coordinador, directivo, futbolista o preparador físico, árbitros, incidencias, alertas distintas de las tres del MVP, subida de archivos, métricas o pruebas físicas fuera de las sembradas, análisis comparativo.
- No refactorices, renombres ni "mejores" código que la tarea no toca.
- No agregues funcionalidad "por si acaso", opciones de configuración nuevas, cachés, colas externas ni optimizaciones.
- No cambies requisitos, arquitectura ni contrato para que el código quede más cómodo; propón el cambio y espera.
- No dejes funcionalidad a medias detrás de banderas ni código comentado.
- Si notas algo útil que está fuera de alcance, NO lo hagas: anótalo en el reporte, en la sección "Fuera de alcance".

## Flujo obligatorio por tarea
1. Lee la tarea completa en la especificación del hito y los criterios de aceptación de las historias que cita.
2. Propone un plan breve (archivos que vas a crear o modificar, pruebas que vas a escribir) y espera aprobación.
3. Escribe primero las pruebas que traducen los criterios de aceptación. Nombra cada prueba con su identificador, por ejemplo: `HU-07: rechaza calificación fuera de rango`, `RN-04: promedio 7.49 sin reprobadas es en_observacion`.
4. Comprueba que las pruebas fallan por la razón correcta.
5. Implementa lo mínimo necesario para que pasen.
6. Ejecuta `make verificar`. La tarea NO está terminada si falla.
7. Haz un commit con el formato `F1-H0-T03: descripción breve`.
8. Reporta con este formato fijo:
   - Archivos tocados.
   - Pruebas agregadas (con su identificador).
   - Resultado de `make verificar` y, si el hito lo exige, de `make humo`.
   - **Lo que NO hiciste** y por qué.
   - **Fuera de alcance:** lo que notaste y no hiciste.
   - **Desviaciones:** cualquier diferencia con la especificación (idealmente ninguna).
   - Dudas.
9. DETENTE. No empieces la siguiente tarea sin aprobación explícita.

## Cuándo detenerte y preguntar
- Dos fuentes se contradicen.
- Un criterio de aceptación o una fila de una tabla de casos parece incorrecta.
- Falta un dato, un valor o una regla.
- La tarea parece requerir tocar rutas que no permite.
- La tarea parece requerir una dependencia nueva, una tabla o un endpoint no previstos.
- Una prueba pide algo que ningún requisito dice.
Pregunta con: qué encontraste, dónde (archivo y sección), qué opciones ves y cuál recomiendas. No continúes hasta recibir respuesta.

## Límites
- Modifica solo las rutas que la tarea permite; todo lo demás requiere aprobación.
- No inventes reglas de negocio ni valores. Los valores marcados [Propuesta] o [Decisión por defecto] solo se usan como datos de fábrica (seeders y `parametros_institucionales`) y llevan un comentario que lo indique.
- No edites una migración ya integrada en la rama principal: crea una nueva.
- No agregues dependencias sin aprobación.
- No desactives, omitas ni debilites pruebas, reglas de análisis, estilo ni umbrales de cobertura para que algo pase.
- No uses `--no-verify` ni fuerces pushes.
- No guardes secretos en el repositorio. Las credenciales de los seeders de desarrollo son solo de prueba.
- No hagas borrado físico de datos de negocio: solo borrado lógico.
- No hagas consultas de jugadores sin pasar por `AlcanceDeJugadores`.
- No llames a servicios externos.
- No dejes textos visibles al usuario escritos en el código: van en `lang/es`.
- No escribas datos reales de personas en pruebas ni seeders.
- No modifiques `docs/requisitos.md`, `docs/arquitectura.md` ni este archivo salvo que la tarea lo pida.

## Arquitectura
- Código de negocio en `backend/src/{Contexto}/{Dominio,Aplicacion,Infraestructura}`. Contextos: Compartido, Identidad, Plantel, Academico, Competencia, Rendimiento, CondicionFisica, Salud, Alertas y Consultas (este último sin Dominio).
- Dependencias: Infraestructura y Http dependen de Aplicacion; Aplicacion de Dominio; Dominio no depende del framework.
- Los contextos solo se referencian por identificadores y se comunican por eventos de dominio. Los eventos viven en `src/Compartido/Dominio/Eventos/` y llevan solo identificadores y valores primitivos.
- Identificadores UUID v7. Tablas y columnas en español, `snake_case`. Migraciones en `backend/database/migrations/{contexto}/` con el formato de nombre de `docs/arquitectura.md` (8.1).
- Cada cambio de datos escribe su historial en la misma transacción (`{tabla}_historial`, solo inserción).
- Controladores delgados: reciben la petición, llaman a un caso de uso y devuelven un recurso.
- Las reglas de cálculo leen parámetros de tablas, nunca constantes del código (RNF-09).

## API
- Prefijo `/api/v1`, JSON, rutas y campos en español y `snake_case`.
- Errores, advertencias, códigos HTTP y paginación: `docs/convenciones-api.md`.
- Un recurso fuera del alcance del usuario responde 404.
- Toda ruta nueva se documenta en `docs/api/openapi.yaml` en la misma tarea; no existen rutas sin documentar.

## Pruebas
- Estrategia, umbrales y tablas de casos: `docs/estrategia-pruebas.md`.
- PostgreSQL real; nunca SQLite.
- Cada criterio de aceptación tiene al menos una prueba. Cada fila de una tabla de casos es una prueba.
- Los comandos de consola del proyecto tienen pruebas con `$this->artisan(...)`.
- Cada hito termina con un guion de humo en `scripts/humo/`.

## Comandos (siempre dentro de Docker)
- `make levantar` / `make bajar`: iniciar y detener el entorno.
- `make consola`: abrir una terminal dentro del contenedor de la aplicación.
- `make migrar` / `make fresh`: migrar / reconstruir la base con datos de fábrica.
- `make pruebas`: ejecutar solo las pruebas.
- `make humo`: ejecutar el guion de humo del hito contra la aplicación levantada.
- `make verificar`: estilo + análisis estático + pruebas + auditoría de dependencias. Es el árbitro de qué está terminado.

## Definición de terminado de una tarea
- `make verificar` pasa sin errores.
- Cada criterio de aceptación de la tarea tiene al menos una prueba.
- El contrato OpenAPI refleja los cambios.
- Solo se tocaron las rutas permitidas y no se construyó nada fuera de alcance.
- El reporte final se entregó con el formato fijo y se esperó aprobación.
