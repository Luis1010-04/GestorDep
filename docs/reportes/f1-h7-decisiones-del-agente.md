# F1-H7: decisiones del agente

Son decisiones que el agente tomó durante H7 y que no estaban escritas en la especificación ni en el registro. Se anotan aquí para que el responsable las confirme o las cambie. Ninguna cambia un requisito, una regla ni un valor de fábrica.

La numeración empieza en DA-134 (orden del responsable del 23/11/2026 a las 13:52). DA-123 a DA-133 están en `docs/decisiones/registro-decisiones.md`, sección 11. Antes del 23/11/2026, H7 no tenía decisiones del agente aparte de esas: este archivo, que `docs/hitos/f1-h7.md` §0 cita, no existía hasta ahora.

## Actividad 1.1: especificación v0.6 (PR #35)

| # | Decisión | Por qué | Dónde está |
|---|---|---|---|
| DA-134 | §12 conserva los riesgos aceptados que ya listaba la v0.5 (autor visible por D-3, `TipoDePrueba.codigo` como `enum` cerrado y la fórmula de DD-51 duplicada) y les suma los del 23/11/2026 | La orden enumera los riesgos del cierre, pero no dice que se quiten los que ya estaban aceptados. Quitarlos cambiaría decisiones del responsable | `docs/hitos/f1-h7.md` v0.6, §12 |
| DA-135 | En §12, los riesgos D1, D2 y D3 del informe de conciliación se describen por su contenido, con sus IDs de la auditoría (A6-11, A6-12, A6-07 y A6-08), y no como «D1», «D2» y «D3» | En `docs/hitos/f1-h7.md` §3.1, D-1 a D-3 ya nombran otras decisiones (rutas, campos sensibles y autor) | `docs/hitos/f1-h7.md` v0.6, §12 |
| DA-136 | Las pruebas de RN-13 de T06b se ciñen a la visibilidad de §5.3, sin reglas nuevas, e incluyen un usuario con los dos roles que da clase a F sin entrenarlo | RN-13 es «[Decisión por defecto]» y §5.3 ya fija qué ve cada perfil. El usuario con los dos roles cubre el hueco que dejó la mutación 3.05 (`docs/reportes/f1-h7-t05.md`, «Fuera de alcance») | `docs/hitos/f1-h7.md` v0.6, §8 (T06b) |
| DA-137 | T06b lleva «Lote B» y T06c «antes de T09» en sus títulos de §8. No se tocaron la tabla de lotes de §0 ni el objetivo de §1, que no menciona AB-31 | La orden limitaba los cambios de §0 a los commits y no incluía §1. Quedan para que el responsable los revise | `docs/hitos/f1-h7.md` v0.6, §8; PR #35 |

## Actividad 1.2: T06c (PR #36)

| # | Decisión | Por qué | Dónde está |
|---|---|---|---|
| DA-138 | El arreglo de `make levantar` es `export BUILDX_BAKE_ENTITLEMENTS_FS := 0` en el `Makefile`. No se cambian `docker-compose.yml` ni las políticas de reinicio | La causa es que bake compara con distinción de mayúsculas la unidad del contexto (`c:`) y la del Dockerfile (`C:`). Compose no ofrece una opción para conceder el permiso, y esta variable es la vía que el propio bake indica. Funciona con cualquier intérprete de las recetas. Quitar `--build` dejaría de reconstruir la imagen. La política de reinicio no es imprescindible para `make levantar` | `Makefile`; `docs/reportes/f1-h7-t06c.md` §2 |

## Actividad 1.3: plan formal de T06

| # | Decisión | Por qué | Dónde está |
|---|---|---|---|
| DA-139 | Un solo puerto, `CandadoDeJugadores`, en `src/Compartido/Aplicacion/`, con un método que sirve para uno o varios jugadores. Lo implementa Plantel, y la consulta `FOR SHARE` vive en `RepositorioDeJugadoresEloquent` | Es lo que aprobó S3: un puerto para todas las capturas. Los lotes (asistencias de entrenamiento y alineación) necesitan bloquear varios jugadores. Ese repositorio ya está en la lista cerrada del detector de RNF-01 y ya bloquea en la baja; una clase nueva obligaría a ampliar la lista | `docs/reportes/f1-h7-plan-t06.md` §3 |
| DA-140 | El predicado del candado es `eliminado_en is null and estatus_deportivo <> 'baja'` | Es el mismo de `AlcanceDeJugadoresSql`, así que la comprobación rápida y la del candado no pueden discrepar. La baja cambia los dos estatus a la vez | Plan §3 |
| DA-141 | Cada caso de uso conserva su comprobación rápida y su excepción. El puerto recibe una función que construye la excepción a partir de los jugadores inactivos | No cambia ningún código ni texto de error, ni OpenAPI, ni `lang/es` | Plan §3 |
| DA-142 | AB-17 se corrige bloqueando la fila del usuario con `FOR UPDATE`, solo en el camino del fallo, y la regla de reinicio y bloqueo se queda en `Usuario`. No se usa un incremento atómico en SQL | El incremento en SQL obligaría a duplicar en SQL la regla del dominio. La comparación del hash queda fuera del candado. El acceso correcto no cambia: su carrera residual es un riesgo aceptado | Plan §4.1 |
| DA-143 | AB-21: los instantes de `usuarios` (`actualizado_en` al cambiar la contraseña, `creado_en` y `actualizado_en` al crear, y `bloqueado_hasta`) salen de `Reloj`, truncados a segundos | Es lo que hace el resto de las tablas desde `F1-H1-FIX` (PT-04) | Plan §4.2 |
| DA-144 | Quedan fuera del candado `CambiarPosicionDeJugador` y la actualización del expediente (DD-40); `DarDeAlta`, `DescartarAviso` y `CorregirLesion` (DA-12 de H5); la corrección de rendimiento y `FijarUmbral`, que no excluyen las bajas (DA-12 de H4); `CorregirAsistencia` de Competencia; y `ProgramarEntrenamiento` y `ModificarEntrenamiento`, que solo listan jugadores | Esas operaciones se permiten con el jugador en baja o no escriben un registro del jugador, así que no hay carrera que corregir | Plan §10 |
| DA-145 | El ayudante de concurrencia lanza un proceso aparte con PDO. Ese proceso sostiene la transacción de «la otra petición», espera a ver una sesión bloqueada por él (`pg_blocking_pids`, máximo 3 s) y confirma. Cada prueba exige `hubo_espera` | Reproduce cada carrera de forma determinista sin depender de tiempos. Usa `Illuminate\Support\Facades\Process`, que ya está en `laravel/framework` | Plan §5 |
| DA-146 | `DefinirAlineacion` bloquea solo a los jugadores a los que la alineación da un rol | Quitar el rol a un convocado no crea un registro de un jugador en baja | Plan §3 |
| DA-147 | Las pruebas de concurrencia van en `tests/Feature/Seguridad/` (`AccesoConcurrenteTest.php` y `BajaConcurrenteTest.php`), y las de AB-21 en `tests/Feature/Identidad/RelojDeUsuariosTest.php` | Es donde están las pruebas de seguridad de H7 (T05) y las de Identidad | Plan §7 |
