/*
 * Equipos (FRONT-R6, entrenador): GET y POST /equipos; la lista de categorías sale de GET /categorias. La API lista solo
 * los equipos del usuario (RN-22) y deja como entrenador a quien crea el equipo.
 */
(function () {
    'use strict';

    var textos = window.Api.textos();
    var tp = textos.plantel;
    var formulario = document.getElementById('formulario-equipo');
    var boton = formulario.querySelector('button[type="submit"]');
    var aviso = document.getElementById('aviso-equipo');
    var categorias = document.getElementById('categoria_id');
    var titulo = document.querySelector('.pagetitle h1').textContent;

    var lista = window.Tabla.crear(document.getElementById('tabla-equipos'), {
        ruta: '/equipos',
        archivo: 'equipos',
        titulo: function () { return titulo; },
        columnas: [
            { data: 'nombre', orden: 'nombre' },
            { data: 'categoria.nombre' },
            {
                data: 'id',
                clase: 'no-exportar',
                html: function (id) {
                    return '<a class="btn btn-sm btn-outline-primary" href="/plantel/equipos/' + encodeURIComponent(id) + '">'
                        + window.Tabla.escapar(tp.ver) + '</a>';
                }
            }
        ]
    });

    window.Tabla.todas('/categorias', { orden: 'nombre' }, Infinity).then(function (lista) {
        lista.forEach(function (categoria) {
            var opcion = document.createElement('option');
            opcion.value = categoria.id;
            opcion.textContent = categoria.nombre;
            categorias.appendChild(opcion);
        });
    }, function (error) {
        window.Avisos.error(error);
    });

    formulario.addEventListener('submit', function (evento) {
        evento.preventDefault();
        if (boton.disabled) {
            return;
        }
        window.Formularios.limpiar(formulario);
        window.Formularios.ocultarAviso(aviso);
        window.Formularios.ocupar(boton);
        window.Api.solicitar('POST', '/equipos', window.Formularios.datos(formulario))
            .then(function () {
                formulario.reset();
                window.Avisos.aviso(tp.equipo_creado);
                lista.recargar();
            }, function (error) {
                return window.Formularios.mostrarError(formulario, aviso, error);
            })
            .then(function () {
                window.Formularios.liberar(boton);
            });
    });
})();
