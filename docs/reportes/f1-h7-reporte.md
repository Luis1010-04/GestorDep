# Reporte de H7: Cierre de la Fase 1

**Fecha:** 27 de noviembre de 2026 · **Especificación:** `docs/hitos/f1-h7.md` v0.6 · **Rama:** `tecnico/f1-h7-t09` (PR en borrador)

**Estado: H7 NO está cerrado.** Quedan tres cosas que no hace el agente:
- la auditoría independiente de sistema (§10, punto 10);
- la revisión del responsable, la etiqueta `f1-h7` y la fusión (punto 11);
- la firma de los riesgos aceptados (§6 de este reporte).

Los PR de T08 (#43) y T09 siguen en borrador. Este reporte registra el estado de cada punto de la compuerta; no la declara cumplida.

## 1. Qué entrega H7

- **Historial (HU-26):** por la API se consulta el historial de cambios de los datos de H2 y de H5, en siete rutas `GET /api/v1/jugadores/{jugador}/historial/{recurso}`:
  - H2: calificaciones y asistencia a clase;
  - H5: avisos de lesión, lesiones, estados de disponibilidad, mediciones corporales y pruebas físicas.
- **Contenido de cada entrada:**
  - fecha, valor anterior y valor nuevo, con la visibilidad del recurso;
  - autor en H2 (D-3); en H5 se omite por DA-18 (DA-125);
  - el borrado lógico no aparece en la lectura pero sí en el historial (D-5).
- **Seguridad:**
  - recorrido y paridad de las siete rutas (T05);
  - corrección de AB-17, AB-20, AB-21, AB-26, AB-29 y AB-31 (T06);
  - A6-10, A6-16 y el hallazgo 5 (T06b);
  - actualización de seguridad de Symfony (PB-07, #42).
- **Capacidad (RNF-06):**
  - prueba de volumen con 15 equipos, 25 usuarios y 375 jugadores, dentro de `make verificar`;
  - sus escrituras pasan por los candados de T06 (T08).
- **Cierre documental:** valores de fábrica por escrito, trazabilidad, humo de H0 a H7 y mutación (T09).

## 2. Compuerta de §10, punto por punto

| # | Punto | Estado | Evidencia |
|---|---|---|---|
| 1 | `make verificar` pasa en local y en la integración continua; `make mutacion` pasa al cerrar, con log | **Local: cumplido. CI: cumplido en `main` (`c419503`) y en T08 (#43); el de T09 lo informa el mensaje de cierre** | `make verificar` de T09: §5 y `docs/reportes/logs/f1-h7/f1-h7-t09-verificar.log`. CI de `main` en verde tras #42 (corrida 45944983275); el de #41 (`b4ad2b3`) había fallado solo en `composer audit` (PB-07). `make mutacion`: código 0, 999 mutantes muertos, MSI 100 % (`f1-h7-t09-mutacion-20261127-0530.log`) |
| 2 | HU-26 tiene pruebas con su identificador; la trazabilidad relaciona HU-26, RN-17 (consulta) y RNF-06 con sus pruebas | **Cumplido** | `docs/trazabilidad.md` (`c3c6da7`): filas de HU-26, RN-17, RNF-06 y RNF-01, y «Estado al cierre de H7» con T06, T06b, T06c, T07, T08 y T09 |
| 3 | OpenAPI incluye las rutas de §6.1 y las descripciones de orden de T07, y las respuestas coinciden | **Cumplido** | Las siete rutas están en `docs/api/openapi.yaml`. Las tres pruebas «HU-26: el historial … cumple el contrato de OpenAPI» validan las respuestas reales. Las cuatro descripciones de orden de T07 entraron con #40 (`1d59453`) |
| 4 | `make fresh` y `migrate:rollback` funcionan sin errores, con autorización explícita | **Cumplido** (autorizados el 26/11/2026 a las 22:38, un uso cada uno) | `make fresh`: código 0. `migrate:rollback`: código 0, de `Ran=76` a `Ran=0`. `make migrar`: código 0, `Ran=76`, `Pending=0`. Logs en `docs/reportes/logs/humo/f1-h7-t09-20261127-05*` |
| 5 | Las tablas de §7 pasan, una prueba por fila | **Cumplido** | §7.1: filas 1, 2, 3, 11 y 12 en `HistorialAcademicoTest`; 4, 5, 6, 8, 9 y 10 en `HistorialDeSaludTest`; 7 en `HistorialDeCondicionFisicaTest`. Cada título lleva su fila. Las filas 11 y 12 se repiten en las siete rutas en `RecorridoDeHistorialH7Test`. §7.2: fila 1 en «HU-26 c1: el historial de una calificación muestra autor…»; fila 2 en «HU-26 c1 (§7.2 fila 2): …»; fila 3 en las siete «HU-26 c2…»; fila 4 en «HU-26 c1 (§7.2 fila 4, DA-125): …» |
| 6 | `make humo` (H0 a H7) termina sin fallas | **Cumplido** (a la espera de que el responsable lo pruebe, punto 11) | Código 0: `HUMO H0 OK` a `HUMO H7 OK: 9 pasos` (`f1-h7-t09-20261127-0528-humo-h0-h7.log`) |
| 7 | Criterios propios de H7 | **Cumplido** con las desviaciones documentadas de §12 | **Historial con autor, fecha y valores según la visibilidad:** T02 a T05. En H5, sin autor (DA-125). **Borrado lógico:** las siete «HU-26 c2». **Seguridad sin hallazgos abiertos:** las seis AB de T06 corregidas con prueba; A6-10, A6-16 y el hallazgo 5 corregidos; PB-07 corregido; los riesgos aceptados de §6 no cuentan. **Volumen:** las dos «RNF-06» de T08. **Valores de fábrica:** por escrito en el registro §12 |
| 8 | El registro de decisiones queda cerrado | **Cumplido** | AB-02 y AB-03 con su supuesto marcado (`b51319c`). Cada AB de T06 evaluada y atendida (filas del registro, `b5e5962`). Valores de fábrica en el registro §12: todos **pendientes** de la institución, ninguno validado. DA-157 a DA-161 registradas (§3.3) |
| 9 | Los riesgos aceptados se listan en el reporte de cierre | **Cumplido; falta la firma** | §6 de este reporte, con todos los de §12 de la especificación |
| 10 | Auditoría independiente de sistema entregada, sin bloqueantes abiertos | **PENDIENTE** | No la hace el agente: la pide el responsable sobre este reporte |
| 11 | El responsable revisa, prueba `make humo` y aprueba; etiqueta `f1-h7` y fusión | **PENDIENTE** | Las ordena el responsable. Para probar `make humo` hace falta un `make fresh` antes: tras el rollback, la base quedó migrada y sin datos sembrados |

## 3. Por tarea

| Tarea | PR | Fusión en `main` | Reporte |
|---|---|---|---|
| T01 Parámetro de conservación (AB-03) | #25 | `9a5fe19` | `f1-h7-t01.md` |
| T02 Historial de calificaciones y asistencia | #27 | `f254728` | `f1-h7-t02.md` |
| T03 Historial de avisos, lesiones y estados | #29 | `8e87e5f` | `f1-h7-t03.md` |
| T04 Historial de mediciones y pruebas (y riesgo 3) | #30 y #31 | `c98da6d`, `f257f59` | `f1-h7-t04.md` |
| T05 Seguridad de las rutas de historial | #33 | `57e434d` | `f1-h7-t05.md` |
| T06 AB-17, AB-20, AB-21, AB-26, AB-29 y AB-31 | #37 | `2bfe9ee` | `f1-h7-plan-t06.md`, `f1-h7-t06.md` |
| T06b Pendientes de la conciliación | #39 | `5a3ae93` | `f1-h7-t06b.md` |
| T06c `make levantar` con Compose v5.6.1; reinicio e `init` | #36 y #41 | `ae680a7`, `b4ad2b3` | `f1-h7-t06c.md` |
| T07 Deudas de orden y documentación de H6 | #40 | `1f05537` | `f1-h7-t07.md` |
| PB-07 Seguridad de Symfony | #42 | `c419503` | Mensaje del commit `72ca80b` |
| T08 Capacidad (RNF-06) | #43 (borrador) | Sin fusionar | `f1-h7-t08.md` |
| T09 Valores de fábrica, humo, trazabilidad y cierre | PR en borrador | Sin fusionar | `f1-h7-t09.md` |

## 4. Tiempos

| Medida | Valor | Log |
|---|---|---|
| `make verificar` antes de T08 | 352 s (pruebas, 276 s; 2438 pruebas) | `docs/reportes/logs/f1-h7/f1-h7-t08-verificar-antes.log` |
| `make verificar` con T08 | 438 s (pruebas, 350 s; 2440 pruebas), con 10 s de un tiempo de red agotado en `composer audit` | `f1-h7-t08-verificar-despues.log` |
| Lo que añade la prueba de volumen | Unos 74 s en las pruebas | `docs/reportes/f1-h7-t08.md` §4 |
| `make mutacion` | 207 s (999 mutantes) | `f1-h7-t09-mutacion-20261127-0530.log` |
| `make humo` (H0 a H7) | Unos 90 s | `f1-h7-t09-20261127-0528-humo-h0-h7.log` |

## 5. `make verificar` final (T09)

- **Resultado:** código 0 en 426 s, sobre la punta `d37c590`. Log: `docs/reportes/logs/f1-h7/f1-h7-t09-verificar.log`. El agente del front no corría pest ni al empezar ni al terminar.
- **Estilo:** 1038 archivos sin cambios. **PHPStan:** sin errores. **Pruebas:** 2440 pasan (15892 aserciones), en 355 s.
- **Cobertura:** Dominio 100.0 % (umbral 95 %), Aplicación 99.7 % (85 %), Infraestructura y HTTP 99.5 % (70 %); total 99.6 %.
- **`composer audit`:** «No security vulnerability advisories found».

## 6. Riesgos aceptados, para la firma del responsable

No cuentan como hallazgos abiertos (§3.1). Los toma de `docs/hitos/f1-h7.md` §12. Se marca cada fila y se firma al final.

| # | Riesgo | Fuente | Aceptado |
|---|---|---|---|
| 1 | **M-3:** sin límite de tasa por IP ni por origen en `POST /sesion`; el único freno es el bloqueo por cuenta (RNF-10, DD-29) | `docs/reportes/pt-12-reporte.md` | ☐ |
| 2 | **429 de DD-29:** una cuenta bloqueada responde 429 sin generar hash y revela que el correo existe; se pueden enumerar cuentas con intentos fallidos. El `UPDATE` de `guardarAcceso` cambia el tiempo de respuesta | `docs/reportes/pt-14-reporte.md` | ☐ |
| 3 | **PT-16:** las visitas anónimas siguen recibiendo una `XSRF-TOKEN` que no corresponde a una sesión guardada; la lista de páginas está fija en el middleware; `url.intended` ya no se guarda; `/sanctum/csrf-cookie` y `POST /sesion` escriben una fila por llamada; la recolección de basura no corre en las visitas anónimas | `docs/reportes/pt-16-reporte.md` | ☐ |
| 4 | **Autor visible (D-3):** en los historiales de H2, un usuario ve el nombre de quien capturó o modificó un dato, que ninguna otra ruta le entrega | §3.1 de la especificación | ☐ |
| 5 | **`TipoDePrueba.codigo` como `enum` cerrado** en OpenAPI: un tipo de prueba nuevo exige cambiar el contrato | §2.3 de la especificación | ☐ |
| 6 | **Fórmula de asistencia de DD-51 duplicada** en `ConsultaDeVistaParaConvocarSql` (DA-98) | `docs/decisiones/pendientes.md`, deudas tras T05 | ☐ |
| 7 | **Búsqueda por matrícula y 409 que revela una baja** (RN-20, DD-52; A6-11, A6-03 y la primera mitad de AB-26): sin alcance, devuelve también a los jugadores en baja y no tiene límite de tasa; inscribir o asignar una matrícula ajena en baja responde 409 `jugador_en_baja` | DA-129; `docs/reportes/etapa-0-conciliacion.md` | ☐ |
| 8 | **Autoconcesión** (RN-20; A6-12): asignar a un jugador a un equipo o inscribirlo en una materia da acceso completo a quien lo hace | Ídem | ☐ |
| 9 | **Historial en catálogos y seeders** (A6-07 y A6-08): las tablas con `eliminado_en` sin (H) en `docs/arquitectura.md` §7 no tienen `_historial`, y los seeders no escriben historial | Ídem | ☐ |
| 10 | **A6-06:** cualquier usuario del rol puede eliminar un catálogo compartido, aunque esté en uso o sea el periodo vigente (HU-28 c4, borrado lógico) | Ídem | ☐ |
| 11 | **A6-13:** los periodos escolares pueden solaparse, y con solapamiento DD-45 y DA-89 pueden elegir periodos distintos | Ídem | ☐ |
| 12 | **A6-17:** `usuarios:crear --contrasena=` deja la contraseña en el historial del shell y en la lista de procesos | Ídem | ☐ |
| 13 | **A6-18:** el cambio de contraseña no limita los intentos con una `contrasena_actual` errónea | Ídem | ☐ |
| 14 | **Carrera residual de AB-17:** un acceso correcto simultáneo con el fallo que bloquea la cuenta puede reiniciar el bloqueo | Ídem; `docs/reportes/f1-h7-t06.md` | ☐ |
| 15 | **Riesgo 3 de la auditoría A3 (DA-129):** `registrado_por` lo protege solo el código, no la base de datos; se corrige después de H7 | DA-129 | ☐ |

**Desviaciones documentadas del requisito** (§12; no son riesgos, se listan para la auditoría):
- **DA-123:** HU-26 se acota a los historiales de H2 y H5.
- **DA-125:** en H5 el historial no entrega el autor.
- **D-5:** HU-26 c2 se demuestra con filas creadas por la prueba.

**Evaluados y sin cambio** (§12): A6-14 (DA-20 del registro, sección 5), A6-15 (DD-35) y A6-19 (`docs/convenciones-api.md` §2).

Firma del responsable: ______________________ · Fecha: ____ / ____ / ________

## 7. Pendientes que H7 deja

- **De §12:**
  - P-15 (agregados de los gráficos del equipo con jugadores en baja): deuda documentada.
  - P-16 (alertas de un equipo que el jugador dejó): diferido hasta el traspaso de jugadores.
  - AB-02 y AB-03: supuestos pendientes de la institución.
- **Valores de fábrica:** todos los de `docs/decisiones/registro-decisiones.md` §12 siguen pendientes de la institución.
- **Deuda posterior a H7**, que el responsable fijó el 26/11/2026 a las 22:38:
  - `pcntl` en la imagen, para que el worker termine el trabajo en curso al detenerse; hoy los servicios PHP salen con 143, aceptado;
  - fijar por digest la etiqueta `composer:2` del Dockerfile.
- **`docs/arquitectura.md`:** la nota de §4.1 de T07 no subió la versión del documento (DA-153).
- **AB-28:** el identificador se usa para dos cosas: la pregunta del aviso reportado por el jugador y los rangos de condición física. Decidir si los rangos llevan un identificador propio (registro §12, DA-163).
- **`composer audit` depende de la red:** un tiempo agotado contra packagist hace fallar `make verificar` aunque no haya avisos (T08 §4).
- **Preguntas de fase del PDF fuera de H7** (8.5, 1.12, 6.13, 6.b, 4.10 y las filas sin fase): van con la deuda posterior a H7, en la planificación de la Fase 2 (DA-133).

## 8. Decisiones

- **En el archivo de DA:** las del agente durante H7 son DA-134 a DA-156 y DA-162 a DA-163, en `docs/reportes/f1-h7-decisiones-del-agente.md`. Las de esta ronda son DA-154 (T06c), DA-155 y DA-156 (T08), y DA-162 y DA-163 (T09).
- **En el registro:** DA-157 a DA-161 son las [Decisión por defecto] de §3.3 de la especificación, en `docs/decisiones/registro-decisiones.md` §11, por confirmar en la revisión de cierre.
