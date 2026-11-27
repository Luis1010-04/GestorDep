/*
 * Cambiar contraseña (FRONT-R2): PUT /api/v1/contrasena con contrasena_actual y contrasena_nueva (HU-27).
 * Antes de enviar se confirma, porque la API cierra las demás sesiones del usuario.
 */
(function () {
    'use strict';

    var textos = window.Api.textos();
    var formulario = document.getElementById('formulario-contrasena');
    var boton = formulario.querySelector('button[type="submit"]');
    var aviso = document.getElementById('aviso-formulario');

    function mostrarAviso(mensaje) {
        aviso.textContent = mensaje;
        aviso.classList.remove('d-none');
    }

    function ocultarAviso() {
        aviso.textContent = '';
        aviso.classList.add('d-none');
    }

    function tratarError(error) {
        window.Formularios.vaciarContrasenas(formulario);
        if (error.status !== 422) {
            return window.Avisos.error(error);
        }
        var sinCampo = window.Formularios.mostrarErrores(formulario, error.errores);
        if (error.errores.length === 0 || sinCampo.length > 0) {
            mostrarAviso(error.mensaje || textos.error_generico);
        }
        return null;
    }

    function cambiar() {
        window.Formularios.ocupar(boton);
        window.Api.solicitar('PUT', '/contrasena', {
            contrasena_actual: formulario.elements.contrasena_actual.value,
            contrasena_nueva: formulario.elements.contrasena_nueva.value
        })
            .then(function () {
                formulario.reset();
                return window.Avisos.exito(textos.contrasena.exito);
            }, tratarError)
            .then(function () {
                window.Formularios.liberar(boton);
            });
    }

    formulario.addEventListener('submit', function (evento) {
        evento.preventDefault();
        if (boton.disabled) {
            return;
        }
        window.Formularios.limpiar(formulario);
        ocultarAviso();
        window.Avisos.confirmar({
            titulo: textos.contrasena.confirmar_titulo,
            texto: textos.contrasena.confirmar_texto,
            boton: textos.contrasena.confirmar_boton
        }).then(function (confirmado) {
            if (confirmado) {
                cambiar();
            }
        });
    });
})();
