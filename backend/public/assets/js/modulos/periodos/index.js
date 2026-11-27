/*
 * Periodos escolares (FRONT-R5a, docente): GET y POST /periodos-escolares y DELETE /periodos-escolares/{id} (HU-28). La
 * API autoriza (solo docente), decide duplicados (409 periodo_duplicado) y el rango de fechas (422
 * rango_de_fechas_invalido) y marca el periodo vigente (DD-45); aquí solo se muestran sus respuestas. Eliminar es un
 * borrado lógico sin restauración desde la aplicación, por eso pide confirmación de peligro (CA-FOR-003).
 */
(function () {
    'use strict';

    var textos = window.Api.textos();
    var ta = textos.academico;
    var tj = textos.jugadores;
    var formulario = document.getElementById('formulario-periodo');
    var boton = formulario.querySelector('button[type="submit"]');
    var aviso = document.getElementById('aviso-periodo');
    var tabla = document.getElementById('tabla-periodos');
    var titulo = document.querySelector('.pagetitle h1').textContent;

    var lista = window.Tabla.crear(tabla, {
        ruta: '/periodos-escolares',
        archivo: 'periodos-escolares',
        titulo: function () { return titulo; },
        columnas: [
            { data: 'nombre' },
            { data: 'fecha_inicio', orden: 'fecha_inicio' },
            { data: 'fecha_fin' },
            { data: 'vigente', texto: function (vigente) { return vigente ? tj.si : tj.no; } },
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
        window.Api.solicitar('POST', '/periodos-escolares', window.Formularios.datos(formulario))
            .then(function () {
                formulario.reset();
                window.Avisos.aviso(ta.periodo_creado);
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
            titulo: ta.eliminar_periodo_titulo.replace(':nombre', fila.nombre),
            texto: ta.eliminar_texto,
            boton: ta.eliminar_boton,
            peligro: true
        }).then(function (confirmado) {
            if (!confirmado) {
                return;
            }
            botonEliminar.disabled = true;
            window.Api.solicitar('DELETE', '/periodos-escolares/' + encodeURIComponent(fila.id)).then(function () {
                window.Avisos.aviso(ta.periodo_eliminado);
                lista.recargar();
            }, function (error) {
                botonEliminar.disabled = false;
                window.Avisos.error(error);
            });
        });
    });
})();
