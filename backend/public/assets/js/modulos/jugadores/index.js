/*
 * Jugadores, listado y búsqueda (FRONT-R3). Solo lectura: GET /jugadores, GET /equipos (filtro del entrenador) y
 * GET /jugadores/busqueda. La columna de equipos y el filtro solo se muestran al entrenador: el docente recibe
 * equipos vacío (RN-13) y no puede listar equipos. La API sigue decidiendo qué ve cada quien.
 */
(function () {
    'use strict';

    var textos = window.Api.textos();
    var tj = textos.jugadores;
    var tabla = document.getElementById('tabla-jugadores');
    var filtroEquipo = document.getElementById('filtro-equipo');
    var busqueda = document.getElementById('formulario-busqueda');
    var botonBuscar = busqueda.querySelector('button[type="submit"]');
    var avisoBusqueda = document.getElementById('aviso-busqueda');
    var resultado = document.getElementById('resultado-busqueda');

    function textoDeEquipos(equipos) {
        return (equipos || []).map(function (equipo) {
            return equipo.nombre + ' (' + equipo.categoria.nombre + ') · ' + tj.posiciones[equipo.posicion];
        }).join('; ');
    }

    function nombreDelEquipoElegido() {
        var opcion = filtroEquipo.options[filtroEquipo.selectedIndex];
        return filtroEquipo.value ? opcion.textContent : '';
    }

    function cargarEquipos() {
        return window.Tabla.todas('/equipos', { orden: 'nombre' }, Infinity).then(function (equipos) {
            equipos.forEach(function (equipo) {
                var opcion = document.createElement('option');
                opcion.value = equipo.id;
                opcion.textContent = equipo.nombre + ' (' + equipo.categoria.nombre + ')';
                filtroEquipo.appendChild(opcion);
            });
        }, function (error) {
            window.Avisos.error(error);
        });
    }

    window.Sesion.usuario.then(function (usuario) {
        var esEntrenador = usuario.roles.indexOf('entrenador') !== -1;
        var lista = window.Tabla.crear(tabla, {
            ruta: '/jugadores',
            archivo: 'jugadores',
            titulo: function () {
                var equipo = nombreDelEquipoElegido();
                return equipo ? tj.titulo + ' · ' + equipo : tj.titulo;
            },
            filtros: function () {
                return { 'filtro[equipo_id]': esEntrenador ? filtroEquipo.value : '' };
            },
            columnas: [
                { data: 'apellidos', orden: 'apellidos' },
                { data: 'nombres', orden: 'nombres' },
                { data: 'matricula' },
                { data: 'equipos', visible: esEntrenador, texto: textoDeEquipos },
                {
                    data: 'id',
                    clase: 'no-exportar',
                    html: function (id) {
                        return '<a class="btn btn-sm btn-outline-primary" href="/jugadores/' + encodeURIComponent(id) + '">'
                            + window.Tabla.escapar(tj.ver_ficha) + '</a>';
                    }
                }
            ]
        });

        if (esEntrenador) {
            cargarEquipos();
            filtroEquipo.addEventListener('change', lista.recargar);
        }
    }, function () {
        // sesion.js ya trató el error de la sesión (redirección o aviso).
    });

    function mostrarAvisoDeBusqueda(texto) {
        avisoBusqueda.textContent = texto;
        avisoBusqueda.classList.remove('d-none');
    }

    busqueda.addEventListener('submit', function (evento) {
        evento.preventDefault();
        if (botonBuscar.disabled) {
            return;
        }
        window.Formularios.limpiar(busqueda);
        avisoBusqueda.classList.add('d-none');
        resultado.hidden = true;
        window.Formularios.ocupar(botonBuscar);

        var consulta = new URLSearchParams({ matricula: busqueda.elements.matricula.value });
        window.Api.solicitar('GET', '/jugadores/busqueda?' + consulta.toString())
            .then(function (respuesta) {
                ['nombres', 'apellidos', 'matricula'].forEach(function (campo) {
                    resultado.querySelector('[data-campo="' + campo + '"]').textContent = respuesta.datos[campo];
                });
                resultado.hidden = false;
            }, function (error) {
                if (error.status === 404) {
                    mostrarAvisoDeBusqueda(tj.busqueda_sin_resultado);
                } else if (error.status === 422) {
                    window.Formularios.mostrarErrores(busqueda, error.errores);
                } else {
                    window.Avisos.error(error);
                }
            })
            .then(function () {
                window.Formularios.liberar(botonBuscar);
            });
    });
})();
