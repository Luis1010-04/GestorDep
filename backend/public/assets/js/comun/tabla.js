/*
 * Tablas con DataTables 3 (F2; DAF-03: sin jQuery) conectadas a la API, con los textos en español de
 * lang/es/interfaz.php (interfaz.js.tabla).
 *
 * - Rutas paginadas: modo servidor. start y length se traducen a pagina y por_pagina (15, 25, 50 o 100), el orden a
 *   orden=campo o orden=-campo solo en las columnas que la ruta admite, y los filtros a filtro[campo]. El total sale de
 *   meta (CA-TAB-002 y 003). No hay búsqueda libre, porque ninguna ruta la ofrece.
 * - Rutas sin paginar: modo cliente, con las filas ya cargadas.
 * - Descarga (F4, DAF-09 ajustada): «Descargar esta página» exporta las filas que la tabla muestra; «Descargar todo»
 *   confirma cuántas filas se descargarán y, en modo servidor, pide a la API las páginas una tras otra (por_pagina=100)
 *   con los mismos filtros y orden, hasta un tope de 1000 filas. Ambas en Excel y PDF, sin endpoints nuevos.
 */
(function () {
    'use strict';

    var textos = window.Api.textos();
    var t = textos.tabla;
    var TOPE = 1000;
    var POR_PAGINA_MAXIMA = 100;

    function escapar(valor) {
        var nodo = document.createElement('div');
        nodo.textContent = valor === null || valor === undefined ? '' : String(valor);
        return nodo.innerHTML;
    }

    /** Sustituye :clave por su valor; las claves largas primero, para que :pagina no se coma el inicio de :paginas. */
    function reemplazar(texto, valores) {
        return Object.keys(valores).sort(function (a, b) { return b.length - a.length; }).reduce(function (resultado, clave) {
            return resultado.split(':' + clave).join(String(valores[clave]));
        }, texto);
    }

    function idioma() {
        return {
            emptyTable: t.vacia,
            zeroRecords: t.vacia,
            info: t.info,
            infoEmpty: t.info_vacia,
            lengthMenu: t.por_pagina,
            loadingRecords: t.cargando,
            processing: t.cargando,
            paginate: { first: t.primera, last: t.ultima, next: t.siguiente, previous: t.anterior }
        };
    }

    /** Pide una ruta paginada página por página (por_pagina=100) hasta reunir el tope de filas o agotar las páginas. */
    function todas(ruta, parametros, tope) {
        var acumulado = [];
        var consulta = new URLSearchParams(parametros || {});

        function pagina(numero) {
            consulta.set('pagina', numero);
            consulta.set('por_pagina', POR_PAGINA_MAXIMA);
            return window.Api.solicitar('GET', ruta + '?' + consulta.toString()).then(function (respuesta) {
                acumulado = acumulado.concat(respuesta.datos);
                if (acumulado.length >= tope || numero >= respuesta.meta.total_paginas || respuesta.datos.length === 0) {
                    return acumulado.slice(0, tope);
                }
                return pagina(numero + 1);
            });
        }

        return pagina(1);
    }

    /** Columna de DataTables a partir de la definición: texto (escapado), html (ya escapado por quien llama) o el dato tal cual. */
    function columnaDeDataTables(columna) {
        var definicion = {
            data: columna.data,
            orderable: Boolean(columna.orden),
            className: columna.clase || '',
            visible: columna.visible !== false,
            defaultContent: ''
        };
        if (columna.html) {
            definicion.render = function (dato, tipo, fila) { return columna.html(dato, fila); };
        } else if (columna.texto) {
            definicion.render = function (dato, tipo, fila) {
                var valor = columna.texto(dato, fila);
                return tipo === 'display' ? escapar(valor) : valor;
            };
        } else {
            definicion.render = window.DataTable.render.text();
        }
        return definicion;
    }

    /**
     * Crea una tabla. config: ruta (sin /api/v1) o datos (modo cliente), columnas, filtros(), titulo(), archivo.
     * Devuelve { dt, recargar }.
     */
    function crear(elemento, config) {
        var servidor = Boolean(config.ruta);
        var columnas = config.columnas.map(columnaDeDataTables);
        var ultimaMeta = null;
        var errorDeCarga = false;
        var dt;

        function parametros(orden) {
            var consulta = {};
            var filtros = config.filtros ? config.filtros() : {};
            Object.keys(filtros).forEach(function (clave) {
                if (filtros[clave] !== '' && filtros[clave] !== null && filtros[clave] !== undefined) {
                    consulta[clave] = filtros[clave];
                }
            });
            var primero = orden && orden.length > 0 ? orden[0] : null;
            if (primero) {
                var indice = Array.isArray(primero) ? primero[0] : primero.column;
                var sentido = Array.isArray(primero) ? primero[1] : primero.dir;
                var campo = config.columnas[indice] ? config.columnas[indice].orden : null;
                if (campo) {
                    consulta.orden = (sentido === 'desc' ? '-' : '') + campo;
                }
            }
            return consulta;
        }

        function fechaDeHoy() {
            var hoy = new Date();
            return hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0') + '-' + String(hoy.getDate()).padStart(2, '0');
        }

        function nombreDeArchivo() {
            return config.archivo + '-' + fechaDeHoy();
        }

        function titulo() {
            return config.titulo ? config.titulo() : '';
        }

        function generado() {
            return new Date().toLocaleString('es-MX');
        }

        function mensajeDePagina() {
            if (!servidor || !ultimaMeta) {
                var info = dt.page.info();
                return reemplazar(t.mensaje_pagina, {
                    pagina: info.page + 1,
                    paginas: Math.max(info.pages, 1),
                    filas: dt.rows({ page: 'current' }).count(),
                    total: info.recordsTotal,
                    fecha: generado()
                });
            }
            return reemplazar(t.mensaje_pagina, {
                pagina: ultimaMeta.pagina,
                paginas: ultimaMeta.total_paginas,
                filas: dt.rows().count(),
                total: ultimaMeta.total,
                fecha: generado()
            });
        }

        /**
         * Columnas que se exportan: las visibles de la tabla de la pantalla, sin las de acciones (clase no-exportar). Es una
         * función y no el selector ':visible:not(...)': sin jQuery (DAF-03), ':visible' no es un selector válido del navegador.
         * La tabla oculta de «Descargar todo» tiene las mismas columnas, así que sirve para las dos.
         */
        function exportable(indice) {
            var columna = config.columnas[indice];
            return dt.column(indice).visible() && !(columna && columna.clase === 'no-exportar');
        }

        function exportacion(tipo, texto, mensaje, soloPagina) {
            var opciones = {
                extend: tipo,
                text: texto,
                title: titulo,
                messageTop: mensaje,
                filename: nombreDeArchivo,
                exportOptions: { columns: exportable }
            };
            if (soloPagina) {
                opciones.exportOptions.modifier = { page: 'current' };
            }
            if (tipo === 'pdfHtml5') {
                opciones.orientation = 'landscape';
            }
            return opciones;
        }

        /** Exporta filas que no están en la tabla visible: una tabla oculta con las mismas columnas, que se destruye al terminar. */
        function exportarFilas(filas, formato, total) {
            return new Promise(function (resolver) {
                var contenedor = document.createElement('div');
                var tablaOculta = document.createElement('table');
                contenedor.hidden = true;
                tablaOculta.appendChild(elemento.querySelector('thead').cloneNode(true));
                contenedor.appendChild(tablaOculta);
                document.body.appendChild(contenedor);

                var visibles = dt.columns().visible().toArray();
                var tipo = formato === 'excel' ? 'excelHtml5' : 'pdfHtml5';
                var boton = exportacion(tipo, '', function () {
                    return reemplazar(t.mensaje_todo, { filas: filas.length, total: total, fecha: generado() });
                }, false);
                boton.action = function (e, tablaTemporal, nodo, opciones, hecho) {
                    window.DataTable.ext.buttons[tipo].action.call(this, e, tablaTemporal, nodo, opciones, function () {
                        hecho();
                        tablaTemporal.destroy();
                        contenedor.remove();
                        resolver();
                    });
                };

                var temporal = new window.DataTable(tablaOculta, {
                    data: filas,
                    columns: columnas.map(function (columna, indice) {
                        return Object.assign({}, columna, { visible: visibles[indice] });
                    }),
                    paging: false,
                    ordering: false,
                    searching: false,
                    info: false,
                    layout: { topStart: 'buttons', topEnd: null, bottomStart: null, bottomEnd: null },
                    buttons: [boton]
                });
                temporal.button(0).trigger();
            });
        }

        function descargarTodo(formato) {
            return function () {
                var boton = this;
                var total = servidor ? (ultimaMeta ? ultimaMeta.total : 0) : dt.rows().count();
                if (total === 0) {
                    window.Avisos.aviso(t.sin_filas, 'info');
                    return;
                }
                var filas = Math.min(total, TOPE);
                var texto = total > TOPE
                    ? reemplazar(t.todo_tope, { filas: filas, total: total, tope: TOPE })
                    : (filas === 1 ? t.todo_confirmar_una : reemplazar(t.todo_confirmar, { filas: filas }));

                window.Avisos.confirmar({ titulo: t.todo_titulo, texto: texto, boton: t.descargar }).then(function (confirmado) {
                    if (!confirmado) {
                        return;
                    }
                    boton.processing(true);
                    var obtener = servidor
                        ? todas(config.ruta, parametros(dt.order()), filas)
                        : Promise.resolve(dt.rows().data().toArray().slice(0, filas));
                    obtener
                        .then(function (datos) { return exportarFilas(datos, formato, total); })
                        .then(function () { window.Avisos.aviso(t.descarga_lista); }, function (error) { window.Avisos.error(error); })
                        .then(function () { boton.processing(false); });
                });
            };
        }

        function botones() {
            return [
                {
                    extend: 'collection',
                    text: t.descargar_pagina,
                    autoClose: true,
                    buttons: [
                        exportacion('excelHtml5', t.excel, mensajeDePagina, true),
                        exportacion('pdfHtml5', t.pdf, mensajeDePagina, true)
                    ]
                },
                {
                    extend: 'collection',
                    text: t.descargar_todo,
                    autoClose: true,
                    buttons: [
                        { text: t.excel, action: descargarTodo('excel') },
                        { text: t.pdf, action: descargarTodo('pdf') }
                    ]
                }
            ];
        }

        var opciones = {
            columns: columnas,
            searching: false,
            order: [],
            pageLength: 15,
            lengthMenu: [15, 25, 50, 100],
            language: idioma(),
            layout: { topStart: 'pageLength', topEnd: 'buttons', bottomStart: 'info', bottomEnd: 'paging' },
            buttons: botones()
        };

        if (servidor) {
            opciones.serverSide = true;
            opciones.processing = true;
            opciones.ajax = function (solicitud, responder) {
                var consulta = new URLSearchParams(parametros(solicitud.order));
                consulta.set('pagina', Math.floor(solicitud.start / solicitud.length) + 1);
                consulta.set('por_pagina', solicitud.length);
                window.Api.solicitar('GET', config.ruta + '?' + consulta.toString()).then(function (respuesta) {
                    errorDeCarga = false;
                    ultimaMeta = respuesta.meta;
                    responder({ draw: solicitud.draw, data: respuesta.datos, recordsTotal: respuesta.meta.total, recordsFiltered: respuesta.meta.total });
                }, function (error) {
                    errorDeCarga = true;
                    ultimaMeta = null;
                    responder({ draw: solicitud.draw, data: [], recordsTotal: 0, recordsFiltered: 0 });
                    window.Avisos.error(error);
                });
            };
        } else {
            opciones.data = config.datos || [];
        }

        dt = new window.DataTable(elemento, opciones);

        /** Estado de error en la propia tabla (CA-TAB-001): la fila vacía dice que no se pudo cargar. */
        dt.on('draw', function () {
            var vacia = elemento.querySelector('td.dt-empty');
            if (vacia && errorDeCarga) {
                vacia.textContent = t.error_carga;
            }
        });

        return {
            dt: dt,
            recargar: function () { dt.ajax.reload(); }
        };
    }

    window.Tabla = { crear: crear, todas: todas, escapar: escapar };
})();
