/*
 * Página de un equipo (FRONT-R6, entrenador). No existe GET /equipos/{id} (PB-04): el nombre y la categoría salen de
 * GET /equipos. Los jugadores, de GET /jugadores con filtro[equipo_id]; la posición en este equipo, de su arreglo equipos.
 * Escrituras: PATCH /equipos/{equipo}/jugadores/{jugador} (posición) y POST /equipos/{equipo}/jugadores (asignar por
 * matrícula, RN-20). La API autoriza y valida; aquí solo se muestran sus respuestas.
 */
(function () {
    'use strict';

    var textos = window.Api.textos();
    var tp = textos.plantel;
    var tj = textos.jugadores;
    var id = document.getElementById('equipo').dataset.equipo;
    var ruta = '/equipos/' + encodeURIComponent(id);
    var tarjetaPosicion = document.getElementById('tarjeta-posicion');
    var formularioPosicion = document.getElementById('formulario-posicion');
    var avisoPosicion = document.getElementById('aviso-posicion');
    var formularioAsignar = document.getElementById('formulario-asignar');
    var avisoAsignar = document.getElementById('aviso-asignar');
    var jugadorDeLaPosicion = null;

    function posicionEnEsteEquipo(equipos) {
        var membresia = (equipos || []).find(function (equipo) { return equipo.equipo_id === id; });
        return membresia ? membresia.posicion : null;
    }

    function noDisponible() {
        var aviso = document.getElementById('aviso-no-disponible');
        aviso.textContent = tp.equipo_no_disponible;
        aviso.classList.remove('d-none');
        document.getElementById('contenido-equipo').hidden = true;
    }

    window.Tabla.todas('/equipos', { orden: 'nombre' }, Infinity).then(function (equipos) {
        var equipo = equipos.find(function (candidato) { return candidato.id === id; });
        if (!equipo) {
            noDisponible();
            return;
        }
        document.getElementById('titulo-equipo').textContent = equipo.nombre;
        document.getElementById('categoria-equipo').textContent = equipo.categoria.nombre;
    }, function (error) {
        noDisponible();
        window.Avisos.error(error);
    });

    var lista = window.Tabla.crear(document.getElementById('tabla-jugadores-equipo'), {
        ruta: '/jugadores',
        archivo: 'jugadores-del-equipo',
        titulo: function () { return document.getElementById('titulo-equipo').textContent; },
        filtros: function () { return { 'filtro[equipo_id]': id }; },
        columnas: [
            { data: 'apellidos', orden: 'apellidos' },
            { data: 'nombres', orden: 'nombres' },
            { data: 'matricula' },
            {
                data: 'equipos',
                texto: function (equipos) {
                    var posicion = posicionEnEsteEquipo(equipos);
                    return posicion ? tj.posiciones[posicion] : tj.sin_dato;
                }
            },
            {
                data: 'id',
                clase: 'no-exportar',
                html: function (jugador) {
                    return '<a class="btn btn-sm btn-outline-primary" href="/jugadores/' + encodeURIComponent(jugador) + '">'
                        + window.Tabla.escapar(tj.ver_ficha) + '</a> '
                        + '<button type="button" class="btn btn-sm btn-outline-secondary boton-posicion">'
                        + window.Tabla.escapar(tp.cambiar_posicion) + '</button>';
                }
            }
        ]
    });

    document.getElementById('tabla-jugadores-equipo').addEventListener('click', function (evento) {
        var boton = evento.target.closest('.boton-posicion');
        if (!boton) {
            return;
        }
        var fila = lista.dt.row(boton.closest('tr')).data();
        jugadorDeLaPosicion = fila.id;
        document.getElementById('jugador-de-la-posicion').textContent = fila.apellidos + ', ' + fila.nombres;
        formularioPosicion.elements.posicion.value = posicionEnEsteEquipo(fila.equipos) || '';
        window.Formularios.limpiar(formularioPosicion);
        window.Formularios.ocultarAviso(avisoPosicion);
        tarjetaPosicion.hidden = false;
        formularioPosicion.elements.posicion.focus();
    });

    document.getElementById('cancelar-posicion').addEventListener('click', function () {
        tarjetaPosicion.hidden = true;
        jugadorDeLaPosicion = null;
    });

    formularioPosicion.addEventListener('submit', function (evento) {
        evento.preventDefault();
        var boton = formularioPosicion.querySelector('button[type="submit"]');
        if (boton.disabled || !jugadorDeLaPosicion) {
            return;
        }
        window.Formularios.limpiar(formularioPosicion);
        window.Formularios.ocultarAviso(avisoPosicion);
        window.Formularios.ocupar(boton);
        window.Api.solicitar('PATCH', ruta + '/jugadores/' + encodeURIComponent(jugadorDeLaPosicion), window.Formularios.datos(formularioPosicion))
            .then(function () {
                tarjetaPosicion.hidden = true;
                jugadorDeLaPosicion = null;
                window.Avisos.aviso(tp.posicion_cambiada);
                lista.recargar();
            }, function (error) {
                return window.Formularios.mostrarError(formularioPosicion, avisoPosicion, error);
            })
            .then(function () {
                window.Formularios.liberar(boton);
            });
    });

    formularioAsignar.addEventListener('submit', function (evento) {
        evento.preventDefault();
        var boton = formularioAsignar.querySelector('button[type="submit"]');
        if (boton.disabled) {
            return;
        }
        window.Formularios.limpiar(formularioAsignar);
        window.Formularios.ocultarAviso(avisoAsignar);
        window.Formularios.ocupar(boton);
        window.Api.solicitar('POST', ruta + '/jugadores', window.Formularios.datos(formularioAsignar))
            .then(function () {
                formularioAsignar.reset();
                window.Avisos.aviso(tp.jugador_asignado);
                lista.recargar();
            }, function (error) {
                if (error.status === 404) {
                    // El equipo ya se comprobó al cargar la página: el 404 es de la matrícula (RN-20).
                    avisoAsignar.textContent = tp.matricula_sin_jugador;
                    avisoAsignar.classList.remove('d-none');
                    return null;
                }
                return window.Formularios.mostrarError(formularioAsignar, avisoAsignar, error);
            })
            .then(function () {
                window.Formularios.liberar(boton);
            });
    });
})();
