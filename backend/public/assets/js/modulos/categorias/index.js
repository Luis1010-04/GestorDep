/*
 * Categorías (FRONT-R6, entrenador): GET y POST /categorias y DELETE /categorias/{id}. La API autoriza (solo entrenador)
 * y decide duplicados (409); aquí solo se muestran sus respuestas. Eliminar es un borrado lógico sin restauración desde la
 * aplicación, por eso pide confirmación de peligro (CA-FOR-003).
 */
(function () {
    'use strict';

    var textos = window.Api.textos();
    var tp = textos.plantel;
    var formulario = document.getElementById('formulario-categoria');
    var boton = formulario.querySelector('button[type="submit"]');
    var aviso = document.getElementById('aviso-categoria');
    var tabla = document.getElementById('tabla-categorias');
    var titulo = document.querySelector('.pagetitle h1').textContent;

    var lista = window.Tabla.crear(tabla, {
        ruta: '/categorias',
        archivo: 'categorias',
        titulo: function () { return titulo; },
        columnas: [
            { data: 'nombre', orden: 'nombre' },
            {
                data: 'id',
                clase: 'no-exportar',
                html: function () {
                    return '<button type="button" class="btn btn-sm btn-outline-danger boton-eliminar">' + window.Tabla.escapar(tp.eliminar) + '</button>';
                }
            }
        ]
    });

    formulario.addEventListener('submit', function (evento) {
        evento.preventDefault();
        if (boton.disabled) {
            return;
        }
        window.Formularios.limpiar(formulario);
        window.Formularios.ocultarAviso(aviso);
        window.Formularios.ocupar(boton);
        window.Api.solicitar('POST', '/categorias', window.Formularios.datos(formulario))
            .then(function () {
                formulario.reset();
                window.Avisos.aviso(tp.categoria_creada);
                lista.recargar();
            }, function (error) {
                return window.Formularios.mostrarError(formulario, aviso, error);
            })
            .then(function () {
                window.Formularios.liberar(boton);
            });
    });

    tabla.addEventListener('click', function (evento) {
        var botonEliminar = evento.target.closest('.boton-eliminar');
        if (!botonEliminar || botonEliminar.disabled) {
            return;
        }
        var fila = lista.dt.row(botonEliminar.closest('tr')).data();
        window.Avisos.confirmar({
            titulo: tp.eliminar_categoria_titulo.replace(':nombre', fila.nombre),
            texto: tp.eliminar_categoria_texto,
            boton: tp.eliminar_boton,
            peligro: true
        }).then(function (confirmado) {
            if (!confirmado) {
                return;
            }
            botonEliminar.disabled = true;
            window.Api.solicitar('DELETE', '/categorias/' + encodeURIComponent(fila.id)).then(function () {
                window.Avisos.aviso(tp.categoria_eliminada);
                lista.recargar();
            }, function (error) {
                botonEliminar.disabled = false;
                window.Avisos.error(error);
            });
        });
    });
})();
