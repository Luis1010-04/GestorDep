(function () {
    'use strict';

    var textos = window.Api.textos();
    var formulario = document.getElementById('formulario-ingreso');
    var boton = document.getElementById('entrar');
    var aviso = document.getElementById('aviso');
    var textoDelBoton = boton.textContent;

    function mostrarAviso(mensaje) {
        aviso.textContent = mensaje;
        aviso.classList.remove('d-none');
    }

    function limpiar() {
        aviso.classList.add('d-none');
        formulario.querySelectorAll('.is-invalid').forEach(function (campo) { campo.classList.remove('is-invalid'); });
        formulario.querySelectorAll('.invalid-feedback').forEach(function (nodo) { nodo.textContent = ''; });
    }

    function mostrarError(error) {
        if (error.status === 422 && error.errores.length > 0) {
            error.errores.forEach(function (detalle) {
                var campo = document.getElementById(detalle.campo);
                var nodo = formulario.querySelector('[data-campo="' + detalle.campo + '"]');
                if (campo && nodo) {
                    campo.classList.add('is-invalid');
                    nodo.textContent = detalle.mensaje;
                }
            });
        } else if (error.status === 401 && error.mensaje) {
            mostrarAviso(error.mensaje);
        } else if (error.status === 429) {
            var minutos = error.retryAfter === null ? null : Math.ceil(error.retryAfter / 60);
            mostrarAviso(minutos === null ? error.mensaje : textos.bloqueada.replace(':minutos', minutos));
        } else if (error.status === 403 || error.status === 419) {
            mostrarAviso(textos.error_de_sesion);
        } else if (error.status === 0) {
            mostrarAviso(textos.sin_conexion);
        } else {
            mostrarAviso(textos.error_generico);
        }
    }

    if (new URLSearchParams(window.location.search).get('expirada') === '1') {
        mostrarAviso(textos.sesion_expirada);
    }

    formulario.addEventListener('submit', function (evento) {
        evento.preventDefault();
        if (boton.disabled) {
            return;
        }
        limpiar();
        boton.disabled = true;
        boton.textContent = textos.cargando;

        window.Api.obtenerCookieCsrf()
            .then(function () {
                return window.Api.solicitar('POST', '/sesion', {
                    correo: document.getElementById('correo').value,
                    contrasena: document.getElementById('contrasena').value
                });
            })
            .then(function () {
                window.location.assign('/panel');
            })
            .catch(mostrarError)
            .then(function () {
                boton.disabled = false;
                boton.textContent = textoDelBoton;
            });
    });
})();
