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

    /**
     * Los valores de los controles con name del formulario, tal como los escribió el usuario (la API los recorta y valida).
     * Una casilla envía true o false, que es lo que espera un campo booleano del contrato. Un campo numérico con valor envía
     * un número, que es lo que espera un campo entero; vacío se envía como texto vacío, para que la API diga que falta.
     */
    function datos(formulario) {
        var valores = {};
        Array.prototype.forEach.call(formulario.elements, function (control) {
            if (!control.name) {
                return;
            }
            if (control.type === 'checkbox') {
                valores[control.name] = control.checked;
            } else {
                valores[control.name] = control.type === 'number' && control.value !== '' ? Number(control.value) : control.value;
            }
        });
        return valores;
    }

    function ocultarAviso(aviso) {
        aviso.textContent = '';
        aviso.classList.add('d-none');
    }

    /**
     * Error de un envío. 422 y 409 se quedan en el formulario: junto a su campo si la API lo indica y, si no, en el aviso
     * del formulario con el mensaje de la API (las reglas de negocio llegan sin campo). El resto lo trata Avisos.error.
     */
    function mostrarError(formulario, aviso, error) {
        if (error.status !== 422 && error.status !== 409) {
            return window.Avisos.error(error);
        }
        var sinCampo = mostrarErrores(formulario, error.errores);
        if (error.errores.length === 0 || sinCampo.length > 0) {
            aviso.textContent = error.mensaje || textos.error_generico;
            aviso.classList.remove('d-none');
        }
        return null;
    }

    window.Formularios = {
        limpiar: limpiar,
        mostrarErrores: mostrarErrores,
        vaciarContrasenas: vaciarContrasenas,
        ocupar: ocupar,
        liberar: liberar,
        datos: datos,
        ocultarAviso: ocultarAviso,
        mostrarError: mostrarError
    };
})();
