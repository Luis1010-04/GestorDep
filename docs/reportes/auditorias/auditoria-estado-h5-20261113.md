Auditoría de estado de H5 antes de T09
Hash revisado: 75459b64edb101353f1f33d9841c01c03674341f (origin/hito/f1-h5, igual a HEAD local, árbol limpio). Solo lectura; no ejecuté make verificar ni make humo.

Resumen
Código: no encontré nada roto. Las 15 rutas de H5 existen, coinciden con OpenAPI en códigos y campos, y todas las historias y filas de §7 tienen prueba de comportamiento.
Documentos: están atrasados respecto al código. El registro no tiene ninguna DA de H5 posterior a DA-19, trazabilidad.md tiene las filas de H5 vacías, el progreso se detiene en T08, y la especificación y la descripción del contrato no reflejan DA-70 ni DA-76.
Bloqueo de planeación: varias de esas correcciones caen fuera de las rutas que §8 permite a T09. Necesita tu decisión antes del plan.
1. Pendientes
#	Elemento	Origen	Estado real	Destino
1	Registrar DA-20 a DA-79 en el registro	f1-h5-decisiones-del-agente.md:3; registro-decisiones.md:241	El registro §8 termina en DA-19 y dice «ninguna DA de H5 queda por aprobar»	T09
2	Trazabilidad de H5	f1-h5.md:546; trazabilidad.md:31-35, 44, 64-65	Filas HU-17 a HU-21, HU-30, RN-15 y RN-16 vacías; HU-04 c3, HU-11 c2 y HU-12 c3 siguen «diferido a H5» (líneas 25, 109, 110, 125); RNF-04 y RNF-09 no nombran H5 (81, 86)	T09
3	Guion de humo de H5	f1-h5.md:548-569	No existe scripts/humo/humo-h5.sh; Makefile:45-50 llega a H4; GuionDeHumoTest no menciona H5	T09
4	Reporte de cierre f1-h5-reporte.md y auditoría del Lote C	f1-h5.md:580, 582	No existen	T09 y después
5	DA-65 en progreso §17	f1-h5-progreso.md:806	Correcto: «aprobada por Luis el 11/11/2026»	Cerrado
6	DA-45 y DA-76	decisiones:208-215, 458-473	Correcto: DA-45 conserva su texto (el diff contra feb4734 solo agrega la nota de reversión); DA-76 aprobada con la subdecisión de lesiones futuras; código en ConfirmarAviso.php:88-97	Cerrado
7	Lesiones que ninguna ruta cierra	decisiones:211, 466; t03:179; progreso:595, 647	Tres casos siguen sin cierre posible: lesión de otro registrante tras DD-63; lesión propia al confirmar con un estado que no es apto (DA-45); lesión propia con inicio futuro al confirmar apto (DA-76). Todos son decisiones aprobadas; nadie tiene anotado qué pasa con ellas en HU-26	Decisión de Luis (¿H7?)
8	DA-72, desempate por id	decisiones:426-432; t08:129	Generador sin cambios; las pruebas fijan ids	H7
9	Baja simultánea «AB-26»	t02:158, t03:178, t05:131, t06:116, t08:135	AB-26 (registro:107) solo habla de inscripción y captura académica. Los casos de H5 (aviso, confirmación, medición, prueba) no están en ningún AB	T09 (ampliar AB-26 o un AB nuevo) y H7
10	AB-02 y AB-03	registro:83-84; plan-fase-1.md:82	Abiertas, institución, antes de H7. Ningún reporte de H5 las cita	H7
11	DA-57, Pest en paralelo	decisiones:305-312; progreso:655; medicion-verificar:79	La opción (i) está implementada (docker-compose.yml:19-22, backend/pint.json). El paralelo, sin plan	Posterior a H5, decisión de Luis
12	DA-30, mismo segundo	decisiones:92; t08:132	La mitad «nunca 500» está probada. «Decidir si merece otra solución» sigue sin respuesta	Decisión de Luis o H7
13	DA-33, par fechas y conflicto	t03:241; progreso:647	Sin prueba combinada. No aparece en la tabla de pendientes de T08 (t08:125-136)	Sin dueño: T09 o aceptarlo
14	Historial guarda la nota del aviso	t02:159	La consulta de H7 deberá respetar DD-58	H7
15	Symfony: proxies y prefijos; main con el lock anterior	progreso:559, 573	Sin cambios; main se corrige al fusionar	H7 y la fusión
16	Catálogos sin historial (RNF-02) y restauración manual de rangos	progreso:81; correctiva:75-84	DA-78 lo documenta; restaurar exige una migración aprobada	H7
17	make levantar no reconstruye la imagen; make humo exige make fresh	correctiva:95, 191-192	Sin cambios, por orden tuya	H7 según el reporte; afecta ya a T09 (humo)
18	Desviación de Respuesta.php	t07:83	Anotada. El diff de fe0bb51 confirma que solo cambia el docblock (3 líneas)	Cerrado
19	Infection no muta Aplicacion	progreso:816	El progreso dice «pendiente de decisión», pero DA-68 la aprobó el 11/11	T09 (corregir el progreso)
20	DA-42, RN-16 incumplida para el docente sin alcance	decisiones:183-189	Decidida; requisitos.md no cambió	F1-DOC posterior
21	DA-64, idempotencia	decisiones:365	Aplazada	Fase 2
22	F1-DOC de arquitectura, rangos [Propuesta], AB-28	f1-h5.md:587-589	Sin cambios	Después de H5; institución
23	HU-23 c4, HU-24, HU-25, DD-08, HU-12 c2, posiblemente_desactualizado	f1-h5.md:59-64, 590	Sin construir, como está previsto	H6
24	HU-26	f1-h5.md:62, 591	Solo se escribe el historial	H7
25	Rama local auditoria/f1-h5	correctiva:207	Sigue existiendo en local	Decisión de Luis
26	Corridas de CI #85 y #86 «en cola»	correctiva:149	No pude consultarlo	Decisión de Luis
27	Lentitud variable del entorno	progreso:138, 617	Hipótesis sin demostrar	Sin dueño; ligado a DA-57
DA-70 a DA-79: el archivo de decisiones es coherente con el código y con la correctiva. Las incoherencias están en los reportes que las rodean (hallazgos M3 y M4).

2. Trazabilidad
Rutas relativas a backend/tests/.

Regla o historia	Prueba que la cubre	Observación
HU-17 c1 a c3	Feature/CondicionFisica/MedicionesTest.php §7.5 filas 1, 4, 5 y 10; Unit/CondicionFisica/MedicionCorporalTest.php	Comportamiento
§7.5 filas 1 a 17	MedicionesTest.php:27-225	Una prueba por fila, con identificador
HU-18 c1 a c3	Feature/CondicionFisica/PruebasFisicasTest.php §7.6 filas 1, 6 y 5	Comportamiento
§7.6 filas 1 a 13	PruebasFisicasTest.php:31-200	Una prueba por fila
HU-19 c1 a c3	Feature/Salud/AvisosTest.php:23, 39, 479	Comportamiento
§7.1 filas 1 a 12	AvisosTest.php:23-208	Una prueba por fila
HU-20 c1 a c3	Feature/Salud/ConfirmacionYEstadoTest.php:31, 123, 160; 404 del detalle en :1008	Comportamiento
HU-21 c1 a c3	ConfirmacionYEstadoTest.php:55, 522, 221	Comportamiento
HU-30 c1 a c3	ConfirmacionYEstadoTest.php:190, 142; AvisosTest.php:98	Comportamiento
§7.2 filas 1 a 23	ConfirmacionYEstadoTest.php:31-336	Una prueba por fila
HU-04 c3	ConfirmacionYEstadoTest.php:55	Comportamiento
HU-11 c2 y HU-12 c3, §7.7 filas 1 a 7	Feature/Competencia/AdvertenciasDeLesionTest.php:48-143; ContratoDeAdvertenciasTest.php:14	Una por fila
RN-16, §7.3	Filas 3 a 5: Feature/Salud/RegistroRN16Test.php:10, una sola prueba. Filas 1 y 2: solo por §7.2 filas 1 y 7	Ninguna prueba lleva «§7.3 fila 1» ni «fila 2»
RN-15, §7.4	Fila 3: Feature/Salud/RegistroRN15Test.php:12. Las demás por equivalencia: ConfirmacionYEstadoTest.php:994, 1008, 552, 1034, 512; AvisosTest.php:393, 404	Solo la fila 3 lleva «§7.4» en el nombre
RNF-01, §7.8	Fila 1: Feature/Seguridad/RecorridoDeAlcanceH5Test.php:14. Filas 2 a 6 por equivalencia: ConfirmacionYEstadoTest.php:552, 565, 592; AvisosTest.php:164, 273, 296, 306; MedicionesTest.php:473, 488, 497; PruebasFisicasTest.php:504, 519, 528	Solo la fila 1 lleva «§7.8». El «recorrido» es un archivo con una prueba (401)
RN-13, RN-14	ConfirmacionYEstadoTest.php:72, 55; MedicionesTest.php:126, 136; PruebasFisicasTest.php:151	Sin prueba con «RN-14» en el nombre
RN-22, regla estricta en el mismo equipo	Salud: RegistroRN15Test.php:12 (E3). Mediciones y pruebas: sin prueba con dos entrenadores del mismo equipo	E3 solo aparece en los casos de equipo_id; DD-59 se prueba con E2, de otro equipo
RN-17, RNF-02	ConfirmacionYEstadoTest.php:485; Integration/Salud/MigracionesDeSaludTest.php:252; Integration/CondicionFisica/MigracionesDeCondicionFisicaTest.php:286	Comportamiento
RN-23, DD-60, DA-12	AvisosTest.php:194, 317; ConfirmacionYEstadoTest.php:263, 750, 763; MedicionesTest.php:205, 375; PruebasFisicasTest.php:164, 271	Comportamiento
RNF-03, §5.1	Los dos archivos MigracionesDe…Test.php	Contra PostgreSQL real
RNF-04, DA-56	ConfirmacionYEstadoTest.php:1100-1183; AvisosTest.php:418	Comportamiento
RNF-09, DD-62	MedicionesTest.php:225; PruebasFisicasTest.php:189; Integration/CondicionFisica/SeedersDeCondicionFisicaTest.php	Comportamiento
T04, puerto	Integration/Salud/EstadoDeDisponibilidadOficialTest.php; Arch/CompetenciaSinSaludTest.php:62	La de arquitectura es prueba de texto: busca importaciones y nombres de tabla en el código
Concurrencia, DA-74 y DA-79	Integration/CondicionFisica/ConcurrenciaTest.php:38, 69; Integration/Salud/RepositorioDeAvisosTest.php:130, 156, 187	Dos conexiones reales. Ya no queda ninguna prueba que revise for update como cadena
DA-33, fechas frente a conflicto	Sin prueba	Pendiente 13
§9, humo de H5	Sin prueba	T09
Pruebas que no prueban comportamiento:

expect(true): ninguna en todo backend/tests.
Solo texto de lang: AvisosTest.php:204 (DA-27) y ConfirmacionYEstadoTest.php:796 (punto 7 del plan) afirman una cadena.
Solo constantes: las pruebas «§5.1: los valores de … son los de la tabla» y «§6.3: los códigos…» de Unit/Salud.
Sin mutación que la rompa: AdvertenciasDeLesionTest.php:178, aceptada en DA-69.
Mutaciones manuales: las series F, G, K, M, N, P, X e Y son corridas únicas con log; no forman parte de la suite.
3. Hallazgos de documentos y contrato
Alto: ninguno.

Medio

M1. La especificación no refleja decisiones ya implementadas. docs/hitos/f1-h5.md, v1.0.8:
§6.3 (líneas 328-339) no tiene rango_no_configurado (DA-70), que sí está en el código y en OpenAPI.
§3.4 (línea 130) y §3.5 (línea 142) no dicen que confirmar apto cierra la lesión propia (DA-76).
M2. OpenAPI no describe el efecto de DA-76. docs/api/openapi.yaml:4837-4841, confirmación: no menciona que con apto la lesión propia recibe fecha_alta. Códigos y esquemas están bien.
M3. El progreso está atrasado. docs/reportes/f1-h5-progreso.md:
Encabezado (líneas 3-4): «v1.0.4» y «Lote A terminado».
Termina en T08 (línea 826): no nombra C01, DA-75 a DA-79 ni la medición del 13/11.
Línea 816: Infection «pendiente de decisión» (DA-68 aprobada).
Líneas 649-653: observaciones «para T08» ya cerradas por DA-71, DA-72 y DA-73.
Línea 723: rango faltante con 500.
M4. Reportes con un estado que el código ya no tiene:
f1-h5-t08.md:133: «las dos pruebas DA-45»; hoy hay una DA-45 y cinco DA-76.
f1-h5-t08.md:37-64: unas 14 referencias de línea desplazadas tras C01. En ConfirmacionYEstadoTest.php, :880 es ahora :1008, :866 es :994, :471 es :552, :431 es :512. En PruebasFisicasTest.php, :510, :501 y :486 son :528, :519 y :504.
f1-h5-t08.md:172: dice que el plan de T08 sigue «a la espera»; f1-h5-plan-t08.md:5 ya dice aprobado.
f1-h5-correctiva-t08.md:21, 34-47: DA-76 «detenido», superado por §9 y §11 del mismo archivo. Las líneas 118-129 citan «484 a 524» y «526», hoy 565-605 y 607. El archivo salta de §7 a §9.
f1-h5-t05.md:130: rango faltante responde 500; hoy 422.
f1-h5-t02.md:104 y f1-h5-t03.md:166: bloqueos comprobados por for update; C01 los reemplazó.
f1-h5-plan-t03b.md:5: «a la espera de aprobación», con T03b ya ejecutada.
M5. Tablas de §7.3, §7.4 y §7.8 sin identificador de fila. La compuerta pide una prueba por fila con su identificador (f1-h5.md:345, 577). Solo cuatro pruebas lo llevan; el resto se cubre por equivalencia, documentada en f1-h5-t08.md:33-64 con líneas ya obsoletas.
M6. AB-26 se cita con un significado más amplio que el registrado (pendiente 9).
M7. Las auditorías de H5 no están en el repositorio. Los reportes citan AU-09, A-1 y B-5 (decisiones:203, 297; progreso:765) sin documento fuente en docs/reportes/.
Bajo

B1. Nota de la migración. backend/database/migrations/condicion_fisica/2026_11_03_060100_crear_tabla_mediciones_corporales.php:12 dice que «la solicitud valida el rango». Ya no es cierto: SolicitudDeMedicion.php:12 solo valida la forma y el rango lo decide RegistrarMedicion (DA-38). No se corrige, por tu orden (progreso:748).
B2. Ejemplo con otro texto. docs/convenciones-api.md:70 usa «El jugador tiene un estado oficial de lesionado.»; el real es «El jugador no está disponible por una lesión.» (DA-66, lang/es/advertencias.php:7).
B3. plan-fase-1.md frente a la especificación:
Línea 49: H5 depende de «H1, H2». f1-h5.md:7 dice H4 cerrado y uso de H3 (T07 toca asistencia y convocatoria).
Línea 103: «H0 y H1 están escritas».
La compuerta del plan (§4) no pide make mutacion, el reporte de cierre ni la auditoría, que §10 de H5 sí pide.
B4. Tiempos verbales en decisiones. decisiones:155-181, 202, 309, 318-344: «se implementa en T05/T06», «rutas que aún no existen», ya implementadas.
B5. Catálogo cerrado en el contrato. openapi.yaml:5780: TipoDePrueba.codigo es un enum de tres valores; un tipo sembrado nuevo rompería el contrato sin tocar código.
B6. Asimetría en el respaldo de DA-79. RepositorioDeLesionesSql no convierte un choque de índice único en 409, a diferencia de avisos, estados, mediciones y pruebas. El bloqueo de fila lo protege (RepositorioDeEstadosYLesionesTest.php:183). Lo reproduciría como RepositorioDeAvisosTest.php:187: un disparador que lance unique_violation en lesiones_historial durante un PATCH /lesiones/{lesion}, esperando 500.
B7. Pruebas de contrato. No ejercitan el 409 conflicto del descarte ni el 422 rango_no_configurado; ambos tienen prueba fuera del validador de OpenAPI.
Contrato, sin diferencias:

Las 15 rutas de routes/api.php:53-55, 147-160 están en OpenAPI, con el mismo middleware de rol.
Los campos de los seis recursos coinciden con los esquemas y con §6.2.
advertencias con campo está en asistencia (openapi.yaml:3063-3075) y convocatoria (3692-3704).
El 409 del descarte nombra conflicto (4822).
No hay números de DA o DD inexistentes en los reportes de H5, ni hashes citados que no existan, salvo fea05b1, ya explicado en progreso:186.
4. Decisiones que necesita Luis
Rutas de T09. §8 solo permite humo, Makefile, tests/Unit/Herramientas, trazabilidad, registro y reportes. ¿M1 y M2 (especificación a v1.0.9 y descripción de OpenAPI) entran en T09, o van en un F1-DOC aparte?
Alcance documental de T09. ¿Se corrige el progreso (M3) y se deja nota en los reportes obsoletos (M4), o quedan como registro histórico?
Baja simultánea en H5. ¿Se amplía AB-26 o se crea un AB nuevo?
Lesiones sin cierre posible (pendiente 7). ¿Se anota para H7 o se acepta como definitivo?
DA-33, par fechas y conflicto. ¿Prueba en T09 o se acepta sin ella?
Filas de §7.3, §7.4 y §7.8. ¿Basta la tabla de equivalencias, actualizada en la trazabilidad, o quieres pruebas con el identificador de fila?
RN-22 en mediciones y pruebas. ¿Hace falta una prueba con dos entrenadores del mismo equipo?
Auditorías de H5. ¿Se versionan (M7)?
DA-30. ¿Queda definitivo el 409, o se revisa en H7?
Operativo. Rama local auditoria/f1-h5, corridas #85 y #86, y make levantar antes del humo de T09.
5. Lo que no pude verificar
Que la suite pase en este hash. No ejecuté make verificar, make mutacion ni make humo, por el encargo. Las cifras son las de los reportes.
Estado de CI. gh no está instalado y el servidor MCP de GitHub falló al conectar (error 400 de autorización).
Aprobaciones «en el chat». DA-63 a DA-79 solo constan por lo escrito en el repositorio.
Mutaciones manuales y logs. No reproduje ninguna ni cotejé cada log con su reporte.
Hallazgos de las auditorías anteriores. Sin los documentos (M7), no puedo confirmar que cada uno quedó atendido.
B6. Es una lectura del código, no una reproducción.
Validación completa de OpenAPI. Comparé a mano rutas, códigos, cuerpos y esquemas de H5; no corrí el validador.
