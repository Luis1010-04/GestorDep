/*
 * Asignaturas (FRONT-R5a, docente): GET y POST /asignaturas y DELETE /asignaturas/{id} (HU-28). La API autoriza (solo
 * docente), valida el grado de 1 a 12 y decide duplicados de clave (409 asignatura_duplicada); aquí solo se muestran sus
 * respuestas. Eliminar es un borrado lógico sin restauración desde la aplicación, por eso pide confirmación de peligro
 * (CA-FOR-003).
 */
(function () {
    'use strict';

    var textos = window.Api.textos();
    var ta = textos.academico;
    var formulario = document.getElementById('formulario-asignatura');
    var boton = formulario.querySelector('button[type="submit"]');
    var aviso = document.getElementById('aviso-asignatura');
    var tabla = document.getElementById('tabla-asignaturas');
    var titulo = document.querySelector('.pagetitle h1').textContent;

    var lista = window.Tabla.crear(tabla, {
        ruta: '/asignaturas',
        archivo: 'asignaturas',
        titulo: function () { return titulo; },
        columnas: [
            { data: 'clave', orden: 'clave' },
            { data: 'nombre' },
            { data: 'grado' },
            {
                data: 'id',
                clase: 'no-exportar',
                html: function () {
                    return '<button type="button" class="btn btn-sm btn-outline-danger boton-eliminar">' + window.Tabla.escapar(ta.eliminar) + '</button>';
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
        window.Api.solicitar('POST', '/asignaturas', window.Formularios.datos(formulario))
            .then(function () {
                formulario.reset();
                window.Avisos.aviso(ta.asignatura_creada);
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
            titulo: ta.eliminar_asignatura_titulo.replace(':clave', fila.clave).replace(':nombre', fila.nombre),
            texto: ta.eliminar_texto,
            boton: ta.eliminar_boton,
            peligro: true
        }).then(function (confirmado) {
            if (!confirmado) {
                return;
            }
            botonEliminar.disabled = true;
            window.Api.solicitar('DELETE', '/asignaturas/' + encodeURIComponent(fila.id)).then(function () {
                window.Avisos.aviso(ta.asignatura_eliminada);
                lista.recargar();
            }, function (error) {
                botonEliminar.disabled = false;
                window.Avisos.error(error);
            });
        });
    });
})();
