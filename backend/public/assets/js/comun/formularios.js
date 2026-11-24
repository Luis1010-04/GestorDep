/*
 * Ayudas de formulario: cada error 422 junto a su campo (CA-FOR-002) y el botón ocupado mientras dura la solicitud,
 * para no enviar dos veces la misma operación (CA-GEN-002). La validación la hace la API; aquí no se duplica.
 */
(function () {
    'use strict';

    var textos = window.Api.textos();

    function selectorDeCampo(atributo, campo) {
        return '[' + atributo + '="' + window.CSS.escape(campo) + '"]';
    }

    function limpiar(formulario) {
        formulario.querySelectorAll('.is-invalid').forEach(function (nodo) { nodo.classList.remove('is-invalid'); });
        formulario.querySelectorAll('[data-error-de]').forEach(function (nodo) { nodo.textContent = ''; });
    }

    /** Coloca cada error junto a su control; devuelve los errores sin campo o cuyo campo no está en el formulario. */
    function mostrarErrores(formulario, errores) {
        var sinCampo = [];
        errores.forEach(function (detalle) {
            var control = detalle.campo ? formulario.querySelector(selectorDeCampo('name', detalle.campo)) : null;
            var nodo = detalle.campo ? formulario.querySelector(selectorDeCampo('data-error-de', detalle.campo)) : null;
            if (control && nodo) {
                control.classList.add('is-invalid');
                nodo.textContent = detalle.mensaje;
            } else {
                sinCampo.push(detalle);
            }
        });
        return sinCampo;
    }

    /** Tras un envío fallido, las contraseñas escritas no se conservan. */
    function vaciarContrasenas(formulario) {
        formulario.querySelectorAll('input[type="password"]').forEach(function (control) { control.value = ''; });
    }

    function ocupar(boton) {
        boton.disabled = true;
        boton.dataset.texto = boton.textContent;
        boton.textContent = textos.cargando;
    }

    function liberar(boton) {
        boton.disabled = false;
        if (boton.dataset.texto) {
            boton.textContent = boton.dataset.texto;
        }
    }

    window.Formularios = {
        limpiar: limpiar,
        mostrarErrores: mostrarErrores,
        vaciarContrasenas: vaciarContrasenas,
        ocupar: ocupar,
        liberar: liberar
    };
})();
