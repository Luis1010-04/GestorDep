# Plan de F1-H5-T06: pruebas físicas (HU-18)

**Fecha:** 6 de noviembre de 2026
**Rama:** `hito/f1-h5` · **Especificación:** `docs/hitos/f1-h5.md` v1.0.8, §5.1, §6, §7.6 y §8 (T06)
**Estado:** corregido el 6/11/2026 tras la revisión de Luis, **a la espera de su aprobación**. T06 no empieza hasta que apruebe este plan, termine la auditoría de T05 y se haga T03b (DA-56). DA-58 y DA-62 están aprobadas; DA-59, DA-60 y DA-61, aprobadas por Luis el 6/11/2026; DA-63, aprobada por Luis en el chat (opción A).

## 1. Rutas

| Ruta | Rol | Éxito |
|---|---|---|
| `GET /tipos-prueba` | `rol:entrenador` | 200, los tres tipos, sin paginar |
| `GET /jugadores/{jugador}/pruebas-fisicas` | `rol:entrenador` | 200, solo las suyas, por periodo ascendente, paginado |
| `GET /jugadores/{jugador}/pruebas-fisicas?filtro[periodo]=AAAA-MM` | `rol:entrenador` | 200, un elemento por tipo, con `prueba` nula si no hay, sin paginar |
| `POST /jugadores/{jugador}/pruebas-fisicas` | `rol:entrenador` | 201; 200 si es una corrección confirmada |

## 2. Reglas que se reutilizan de T05

- **Alcance, baja, equipo y transacción:** `ComprobarJugadorDelEntrenador`, `VisibilidadDeJugador`, `Transaccion`, `EquipoInexistente` y `NadaQueCorregir`. Sin puertos nuevos.
- **Periodo (DA-03):** se deriva de `fecha`, que es opcional y puede ser futura.
- **`motivo` (DA-39, DA-40, DA-44):** obligatorio con `es_correccion`, prohibido sin él, hasta 255 caracteres. Una corrección idéntica responde 200 sin historial. T06 prueba 255 y 256.
- **Rango (DA-16):** sale de `tipos_prueba.minimo` y `maximo`, sin constantes en el código. Se valida en el caso de uso, antes de llegar a la base.
- **Unicidad (DA-04):** por jugador, tipo, registrante y periodo. La corrección puede cambiar el equipo (DA-13), no el tipo, porque el tipo identifica la fila.

## 3. Decisiones que necesito

| Propuesta | Vacío | Recomendación |
|---|---|---|
| **DA-58.** Orden de errores (**aprobada**) | DA-38 solo cubre mediciones | 403 rol, 404 alcance, 422 cuerpo, 409 `jugador_en_baja`, 422 `equipo_inexistente`, 422 `tipo_prueba_inexistente`, 422 `valor_fuera_de_rango`, 422 `nada_que_corregir`, 409 `prueba_ya_registrada`, 409 `conflicto` |
| **DA-59.** Mensaje de baja | El texto de DA-41 habla de mediciones | Clave propia: «El jugador está en baja: no se pueden registrar ni corregir pruebas físicas.» |
| **DA-60.** Textos nuevos | §6.3 no da mensajes | `prueba_ya_registrada`: «Ya hay una prueba de ese tipo registrada en ese periodo. Si quieres reemplazarla, confírmalo como corrección.»; `tipo_prueba_inexistente`: «El tipo de prueba indicado no existe.» |
| **DA-61.** Forma de `tipo_prueba_id` en el cuerpo | §6.3 solo dice «inexistente» | Como DA-55: un texto que no es UUID da 422 `validacion_fallida`; un UUID no v7 o un tipo eliminado da 422 `tipo_prueba_inexistente` |
| **DA-62.** Los dos filtros juntos (**aprobada**) | §6.1 no dice qué pasa con `filtro[periodo]` y `filtro[tipo_prueba_id]` a la vez | La vista del periodo, solo con ese tipo. Con un UUID no v7, lista vacía, como DA-26 de H3 |
| **DA-63.** Formato y orden de la vista (**aprobada**, opción A) | §6.1 la excluye de la paginación, pero no fija el orden | Se mantiene §6.1. Mismo formato que los listados sin paginar, como `GET /metricas`: `{"datos": [...]}`, sin `meta`, sin leer `pagina` ni `por_pagina`. Por `codigo` ascendente, también en `GET /tipos-prueba` |
| **DA-64.** Doble envío del mismo entrenador (**aprobada**) | El plan no dice qué pasa si se envía dos veces la misma prueba | La unicidad de la base (DA-04) y el 409 `prueba_ya_registrada`, con una prueba del doble envío. Sin bloqueo en T06. La idempotencia queda para la siguiente fase |

## 4. Pruebas primero

| Archivo | Cubre | Falla esperada |
|---|---|---|
| `tests/Unit/CondicionFisica/PruebaFisicaTest.php` | Periodo derivado, corrección, valores idénticos, rango del tipo, y código y campo de cada excepción nueva | Clase no encontrada |
| `tests/Feature/CondicionFisica/PruebasFisicasTest.php` | §7.6 filas 1 a 13, una prueba por fila, más DA-39, DA-44, DA-58 a DA-63, baja al corregir, filtros inválidos, 404 igual a inexistente, UUID no v7 y doble envío del mismo entrenador (DA-64: una sola fila y 409) | 404, ruta inexistente |
| `tests/Feature/CondicionFisica/ContratoDePruebasFisicasTest.php` | Las tres rutas contra OpenAPI, incluida la vista del periodo | 404, ruta inexistente |
| `tests/Integration/CondicionFisica/RepositorioDePruebasFisicasTest.php` | Historial `crear` y `actualizar`, `conflicto`, lectura de rangos tras cambiarlos en la base y borrado lógico | Clase no encontrada |

Las pruebas que esperan 404 pasarán sin rutas. Lo demostraré con mutación de filtros al terminar, con control antes y después.

**Lección de T05:** las excepciones se prueban en la suite `Unit`, con closures en los *datasets*, porque Infection solo corre esa suite.

## 5. Archivos de producción

- **`src/CondicionFisica/Dominio/`:** `PruebaFisica`, `TipoDePrueba`, `TipoPruebaInexistente`, `PruebaYaRegistrada`, `RepositorioDePruebasFisicas` y `CatalogoDeTiposDePrueba`.
- **`src/CondicionFisica/Aplicacion/`:** `RegistrarPrueba`, `ListarPruebas`, `ConsultarPeriodoDePruebas`, `ListarTiposDePrueba`, `PruebaConcurrente` y `JugadorEnBajaSinPrueba`.
- **`src/CondicionFisica/Infraestructura/`:** `RepositorioDePruebasFisicasSql`, `CatalogoDeTiposDePruebaSql` y sus enlaces.
- **Rutas base:** `PruebaFisicaController`, `TipoDePruebaController`, `SolicitudDePruebaFisica`, los recursos, `FormatoDeError.php`, `routes/api.php` y claves nuevas en `lang/es/errores.php`.
- **Contrato:** `docs/api/openapi.yaml`, con `maxLength: 255` en `motivo` (DA-44).

Sin migraciones.

## 6. Cierre

- **Corridas, en serie:** PHPStan tras editar pruebas, `make verificar`, `make mutacion`, `make fresh` y `make humo`.
- **Logs:** los que respaldan el reporte, en `docs/reportes/logs/`; los demás, en `backend/build/`. Ninguno se borra.
- **Tiempo:** aviso a los 13 minutos y alto a los 15. T06 agrega unas 100 pruebas sobre una base que, en las cuatro corridas de DA-57 y §1.8.8 de `f1-h5-progreso.md`, tardó entre 721 y 836 s en total, con Pest entre 663 y 736 s. Dos de esas cuatro corridas superaron el aviso de 13 minutos (780 s). Los umbrales de 13 y 15 minutos siguen igual; si se ajustan, lo decide Luis.
- **Commit:** `F1-H5-T06: pruebas físicas`, local, sin push.

## 7. Riesgos

- **Tiempo de `make verificar`:** DA-57 ya se midió (progreso §1.8.6 a §1.8.8) y la caché persistente quedó como opción (i). Lo que queda pendiente es decidir la ejecución en paralelo de Pest, aparte de T06.
- **Rango faltante:** no aplica a las pruebas, porque `minimo` y `maximo` son obligatorios en `tipos_prueba`.
- **Baja simultánea:** igual que en T02, T03 y T05 (AB-26).
- **Doble envío del mismo entrenador:** se cubre con la unicidad de la base y el 409, con prueba (DA-64). Sin bloqueo en T06. La idempotencia queda para la siguiente fase.
