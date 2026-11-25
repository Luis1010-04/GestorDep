/*
 * Registrar jugador (FRONT-R6, entrenador): POST /jugadores con nombres, apellidos, fecha_nacimiento, matricula,
 * equipo_id y posicion. Los equipos salen de GET /equipos (solo los del usuario); ?equipo= preselecciona uno. Si la
 * matrícula ya existe (409), se sugiere asignar al jugador por matrícula desde la página del equipo (RN-20).
 */
(function () {
    'use strict';

    var textos = window.Api.textos();
    var tp = textos.plantel;
    var formulario = document.getElementById('formulario-jugador');
    var boton = formulario.querySelector('button[type="submit"]');
    var aviso = document.getElementById('aviso-jugador');
    var equipos = document.getElementById('equipo_id');
    var preseleccion = new URLSearchParams(window.location.search).get('equipo');

    window.Tabla.todas('/equipos', { orden: 'nombre' }, Infinity).then(function (lista) {
        lista.forEach(function (equipo) {
            var opcion = document.createElement('option');
            opcion.value = equipo.id;
            opcion.textContent = equipo.nombre + ' (' + equipo.categoria.nombre + ')';
            equipos.appendChild(opcion);
        });
        if (preseleccion && lista.some(function (equipo) { return equipo.id === preseleccion; })) {
            equipos.value = preseleccion;
        }
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
        window.Api.solicitar('POST', '/jugadores', window.Formularios.datos(formulario))
            .then(function (respuesta) {
                return window.Avisos.exito(tp.jugador_registrado).then(function () {
                    window.location.assign('/jugadores/' + encodeURIComponent(respuesta.datos.id));
                });
            }, function (error) {
                var resultado = window.Formularios.mostrarError(formulario, aviso, error);
                if (error.codigo === 'matricula_duplicada') {
                    aviso.textContent = aviso.textContent + ' ' + tp.matricula_duplicada_ayuda;
                }
                return resultado;
            })
            .then(function () {
                window.Formularios.liberar(boton);
            });
    });
})();
