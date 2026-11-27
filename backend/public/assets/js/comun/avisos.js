/*
 * Avisos y confirmaciones sobre SweetAlert2 (F3). Es el único archivo que llama a Swal: las vistas y los scripts de
 * los módulos usan window.Avisos. Los textos salen de lang/es/interfaz.php por data-textos.
 *
 * - exito(texto): ventana con icono de éxito y botón Aceptar.
 * - error(detalle): un 401 lleva al ingreso; cualquier otro error abre una ventana con icon: 'error' y el mensaje que
 *   corresponde a su código (CA-GEN-003), sin trazas ni detalles internos. Acepta un error de Api o un texto.
 * - aviso(texto, icono): aviso breve en la esquina superior derecha, sin botón, que se cierra solo a los 1,5 s.
 * - confirmar(opciones): resuelve con true solo si el usuario confirma; peligro pinta el botón de rojo.
 * Ningún aviso de éxito se muestra antes de la respuesta del servidor (CA-FOR-003): quien llama decide cuándo.
 */
(function () {
    'use strict';

    var textos = window.Api.textos();
    var clasesDeBotones = { confirmButton: 'btn btn-primary mx-1', cancelButton: 'btn btn-outline-secondary mx-1' };

    function mostrar(opciones) {
        return window.Swal.fire(Object.assign({
            buttonsStyling: false,
            customClass: clasesDeBotones,
            confirmButtonText: textos.aceptar,
            cancelButtonText: textos.cancelar
        }, opciones));
    }

    function mensajeDeError(detalle) {
        if (detalle.status === 0) {
            return textos.sin_conexion;
        }
        if (detalle.status === 403) {
            return textos.sin_autorizacion;
        }
        if (detalle.status === 404) {
            return textos.no_disponible;
        }
        if (detalle.status === 419) {
            return textos.error_de_sesion;
        }
        if (detalle.status === 429) {
            var minutos = detalle.retryAfter === null ? null : Math.ceil(detalle.retryAfter / 60);
            return minutos === null ? (detalle.mensaje || textos.error_generico) : textos.bloqueada.replace(':minutos', minutos);
        }
        if ((detalle.status === 409 || detalle.status === 422) && detalle.mensaje) {
            return detalle.mensaje;
        }
        return textos.error_generico;
    }

    function exito(texto) {
        return mostrar({ icon: 'success', text: texto });
    }

    function error(detalle) {
        if (typeof detalle === 'string') {
            return mostrar({ icon: 'error', text: detalle });
        }
        if (detalle && detalle.status === 401) {
            window.location.assign('/ingreso?expirada=1');
            return Promise.resolve();
        }
        return mostrar({ icon: 'error', text: mensajeDeError(detalle || {}) });
    }

    function aviso(texto, icono) {
        return window.Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icono || 'success',
            text: texto,
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true
        });
    }

    /** El título va como texto (titleText): suele llevar nombres que llegan de la API, y title de SweetAlert2 es HTML. */
    function confirmar(opciones) {
        return mostrar({
            icon: opciones.peligro ? 'warning' : 'question',
            titleText: opciones.titulo,
            text: opciones.texto,
            showCancelButton: true,
            focusCancel: true,
            confirmButtonText: opciones.boton || textos.aceptar,
            customClass: {
                confirmButton: (opciones.peligro ? 'btn btn-danger' : 'btn btn-primary') + ' mx-1',
                cancelButton: clasesDeBotones.cancelButton
            }
        }).then(function (resultado) {
            return resultado.isConfirmed === true;
        });
    }

    window.Avisos = { exito: exito, error: error, aviso: aviso, confirmar: confirmar };
})();
