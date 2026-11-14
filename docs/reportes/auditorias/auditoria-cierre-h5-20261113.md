Auditoría independiente de cierre de H5
Revisión: 39cc40050ed4263f1be36d7fecc6d41e55b4317e (hito/f1-h5 = origin/hito/f1-h5). Árbol limpio y sin commits locales sin subir. No edité, no commiteé ni ejecuté make.

Veredicto
H5 puede pasar a PR, con condiciones. No encontré bloqueantes de código: el respaldo 409 es correcto, no hay migraciones tocadas ni pruebas debilitadas, y las cifras del reporte coinciden con los logs. Las condiciones son un hueco de contrato (el 409 de PATCH /lesiones/{lesion} no está en OpenAPI), DA-80 sin registrar en el registro de decisiones y varios textos de cierre desactualizados. No pude comprobar la corrida #93.

Compuerta (docs/plan-fase-1.md §4)
Punto	Estado	Evidencia
1. make verificar local y CI	Cumple en local; CI sin verificar por mí	Log verificar/f1-h5-t09-20261113-1525.log: código 0 en 73b7bd0, 1 981 pruebas, 783 s. De 73b7bd0 a 39cc400 solo cambian documentos. El reporte cita #92 sobre 23e2b1d; #93 no aparece en el repositorio.
2. Historias con identificador, diferidos, trazabilidad	Cumple, con la excepción de DA-80	Conteos por nombre: HU-17 (6), HU-18 (5), HU-19 (6), HU-20 (7), HU-21 (3), HU-30 (8). No existe prueba con «HU-19 c3» ni «HU-21 c2» en el nombre; DA-80 lo acepta.
3. OpenAPI y respuestas reales	Cumple con una salvedad	Las rutas de H5 están en openapi.yaml:4630-5556. Salvedad: hallazgo M1.
4. make fresh y rollback	Cumple en local (fresh); rollback solo en CI	fresh codigo: 0 en el log de humo, línea 118. El rollback no se corrió en local (reporte §3.4); depende de #92.
5. Tablas de casos	Cumple por equivalencia (DA-80)	Todas las líneas citadas en trazabilidad.md:143-174 existen y corresponden al caso. No cumple la letra de f1-h5.md §10.5 («una prueba por fila»).
6. make humo	Cumple	Log humo/f1-h5-t09-20261113-1542.log: HUMO H5 OK: 18 pasos, humo codigo: 0. humo-h5.sh numera los pasos 1 a 18 sin huecos.
7. Criterios de aceptación	Cumple	criterios-aceptacion-fase-1.md:32; las cinco pruebas de la tabla 9.3 del reporte existen en las líneas citadas.
8. Reporte de fuera de alcance	Cumple	Reporte §7 y §8. El único código de producción de T09 es RepositorioDeLesionesSql.php, previsto en el plan.
9. Revisión del responsable	Abierto (de Luis)	—
10. make mutacion	No aplica a H5; se corrió	Log mutaciones/f1-h5-t09-infection-20261113-1539.log: 885 de 885, MSI 100 %, código 0, 184 s.
11. Etiqueta y fusión	Abierto (de Luis)	Etiquetas existentes: f1-h0 a f1-h4. La rama no está fusionada en main.
Código
El rango 96bd430..39cc400 que pide el encargo solo contiene documentos. El código de T09 está en e51cb46..73b7bd0, y eso fue lo que revisé.

Alto: ninguno.

Medio

M1. El 409 de corregir una lesión no está en el contrato. RepositorioDeLesionesSql.php:66-68 ahora produce 409 conflicto en PATCH /lesiones/{lesion}, pero OpenAPI solo documenta 200, 401, 403, 404 y 422 para esa ruta (openapi.yaml:5556). Descarte, confirmación, cambio de estado y alta sí documentan 409.
La prueba de ConfirmacionYEstadoTest.php:1060 no valida contra OpenAPI; la de contrato de T09 cubre solo el descarte.
Lo reproduciría validando la respuesta de esa prueba con el validador de OpenAPI, como hace ContratoDeAvisosTest.php:53: debería fallar por código no documentado.
En la práctica el bloqueo de fila lo hace casi inalcanzable.
Bajo

B1. La captura cubre cualquier índice único, no uno concreto. Captura UniqueConstraintViolationException sin distinguir el índice. Hoy equivale a «solo el historial»: lesiones no tiene más índice único que su clave primaria y la actualización no toca id. Los errores de check, de clave foránea y los demás siguen saliendo como 500. Un índice único futuro en lesiones respondería 409 sin avisar.
B2. El respaldo alcanza tres casos de uso y solo uno tiene prueba de choque. guardarCambio lo llaman CorregirLesion, DarDeAlta y ConfirmarAviso (línea 101, cierre por DA-76). El cambio de 500 a 409 en los otros dos es coherente con DA-79 y su 409 sí está documentado.
B3. Comentarios que quedaron cortos. El @throws de CorregirLesion.php:26 no nombra EstadoConcurrente. El comentario de la clase EstadoConcurrente habla solo del índice de un estado vigente por jugador.
Comprobaciones sin hallazgo

Migraciones: ninguna cambió desde b9c75b6 (T01), incluida 2026_11_03_060100.
Dependencias: composer.json no cambió; composer.lock solo cambia el bloque de laravel/framework.
Makefile: una línea nueva, la de humo-h5.sh.
Pruebas: el diff de backend/tests solo tiene líneas agregadas; ninguna aserción existente cambió.
Archivo modificado	Qué se agregó	Motivo
ConfirmacionYEstadoTest.php	2 pruebas (líneas 1060 y 1121)	DA-79 en lesiones; DA-33, fechas frente a conflicto
ContratoDeAvisosTest.php	1 prueba	409 del descarte contra OpenAPI
ContratoDeMedicionesTest.php	1 prueba	422 rango_no_configurado contra OpenAPI
MedicionesTest.php	1 prueba (línea 147)	RN-22
PruebasFisicasTest.php	1 prueba (línea 164)	RN-22
GuionDeHumoTest.php	4 pruebas	Mecanismo de humo-h5.sh
Especificación y contrato
f1-h5.md v1.0.9: cambian el encabezado, un párrafo de §3.4, una viñeta de §3.5, una fila de §6.3 y el control de cambios. La fila nueva (rango_no_configurado, 422) documenta un código que ya existía desde T08; no hay rutas ni cuerpos nuevos.
openapi.yaml: un solo bloque, la description de la confirmación (líneas 4837-4844). Sin cambios en códigos ni esquemas.
Reporte de cierre
Cifras: todas coinciden con los logs: 1 981 pruebas y 12 299 aserciones, Pest 728.56 s, total 783 s, cobertura 100 / 100 / 99.6 %, Pint 852 archivos, 885 mutantes, 184 s.
Humo: el log tiene 156 líneas OK, pero son comprobaciones, no pasos. Los guiones declaran 78 pasos más el de H0 (14 + 16 + 16 + 14 + 18).
Pendientes: suman 27 y todos tienen destino: 9 cerrados, 1 mixto (el 4), 10 para H7, 1 para H6 y 6 pendientes de decisión.
DA-80: bien redactada en f1-h5-decisiones-del-agente.md:513 y coherente con §9.2 del reporte y con el punto 5 del plan.
Framework: composer.lock tiene v13.42.0 y el log de actualización muestra v13.38.0 => v13.42.0, un solo paquete.
Trazabilidad
Ninguna fila de H5 apunta a una línea o prueba inexistente. Revisé unas 60 referencias, incluida la de HU-02 (ConfirmacionYEstadoTest.php:72), y todos los archivos nombrados existen.

Incoherencias documentales
DA-80 no está en el registro. registro-decisiones.md:267 sigue titulado «DA-20 a DA-79» y dice «las 60». DA-80 solo existe en el archivo de decisiones del agente.
La trazabilidad se contradice sobre HU-02. trazabilidad.md:138 dice que HU-02 c2 «conserva el texto "Diferido a H5"; esta revisión no lo cambió», pero la línea 16 ya se cambió en 39cc400.
El reporte describe un estado de CI que ya no existe. El encabezado, §5 y §8 dicen que los commits de T09 son locales, que origin llega a e51cb46 y que #91 está en rojo. §9 lo corrige al final, pero el encabezado no se actualizó.
«Auditoría independiente del Lote C» sigue como pendiente en el encabezado del reporte, en §5, §8, §9.4 y en trazabilidad.md:136. Es el punto 10 de f1-h5.md §10 y no existe en la compuerta del plan.
f1-h5.md §10.5 dice «una prueba por fila» y DA-80 lo exceptúa sin que la especificación lo refleje.
f1-h5.md §10.8 pide tiempos por etapa; el reporte solo midió Pest y el total, y lo admite.
El texto de DA-79 quedó corto. Nombra solo guardarDescarte y guardarConfirmacion («quitar las dos capturas»), y T09 agregó una tercera en lesiones bajo el mismo identificador.
El reporte §5.3 dice «contrato de DA-70, DA-79 y DA-76 completado», y no lo está para lesiones (M1).
La numeración DA se repite entre hitos. DA-33 tiene tres sentidos en el registro (líneas 180, 221 y 286), y la trazabilidad usa el de H4 en la fila de RNF-09 y el de H5 en la sección de cierre.
No encontré ningún DA, DD o AB citado en el reporte o en el plan de T09 que no exista.

Lo que no pude verificar
Corridas de CI (#92 y #93): gh no está instalado y el conector de GitHub falló al conectar. Además, #93 no se menciona en ningún documento.
Cómo detecta Laravel 13.42 el choque de índice único: backend/vendor no está en el host. La prueba de DA-79 lo demuestra indirectamente con errcode = 'unique_violation'.
Las aprobaciones «de Luis» de DA-80 y del plan de T09: constan solo por lo escrito.
Las mutaciones Z1, R1 y R2: se hicieron en una copia borrada; solo quedan sus logs en docs/reportes/logs/t09/.
«134 pruebas de contrato» del reporte §2: no lo conté.
Decisiones que necesita Luis
M1: documentar el 409 en PATCH /lesiones/{lesion} antes del PR, o aceptarlo como pendiente anotado.
Lote C: decidir si este informe cuenta como la auditoría independiente del punto 10 de f1-h5.md §10, o si se exige otra.
DA-80: registrarla en registro-decisiones.md §9 antes de la etiqueta.
Textos desactualizados (incoherencias 2, 3 y 4): corregirlos antes del PR o dejarlos con §9 del reporte como corrección.
#93: confirmar que está en verde sobre 39cc400 y, si se quiere, citarla en el reporte.
