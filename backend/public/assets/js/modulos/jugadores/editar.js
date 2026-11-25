/*
 * Editar datos del jugador (FRONT-R6, entrenador): GET /jugadores/{id} para llenar el formulario y PATCH /jugadores/{id}
 * con nombres, apellidos y fecha_nacimiento (HU-05). La matrícula no se edita. Un jugador fuera del alcance responde 404
 * al leerlo; un usuario sin el rol entrenador, 403 al guardar. La API decide ambos.
 */
(function () {
    'use strict';

    var textos = window.Api.textos();
    var tp = textos.plantel;
    var tj = textos.jugadores;
    var id = document.getElementById('edicion').dataset.jugador;
    var ruta = '/jugadores/' + encodeURIComponent(id);
    var formulario = document.getElementById('formulario-edicion');
    var boton = formulario.querySelector('button[type="submit"]');
    var aviso = document.getElementById('aviso-edicion');

    function noDisponible() {
        var avisoGeneral = document.getElementById('aviso-no-disponible');
        avisoGeneral.textContent = tj.no_disponible;
        avisoGeneral.classList.remove('d-none');
        document.getElementById('contenido-edicion').hidden = true;
    }

    window.Api.solicitar('GET', ruta).then(function (respuesta) {
        ['nombres', 'apellidos', 'fecha_nacimiento'].forEach(function (campo) {
            formulario.elements[campo].value = respuesta.datos[campo];
        });
        boton.disabled = false;
    }, function (error) {
        if (error.status === 404) {
            noDisponible();
            return;
        }
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
        window.Api.solicitar('PATCH', ruta, window.Formularios.datos(formulario))
            .then(function () {
                return window.Avisos.exito(tp.jugador_actualizado).then(function () {
                    window.location.assign(ruta);
                });
            }, function (error) {
                return window.Formularios.mostrarError(formulario, aviso, error);
            })
            .then(function () {
                window.Formularios.liberar(boton);
            });
    });
})();
