(function () {
    'use strict';

    var textos = window.Api.textos();

    function irAlIngreso(expirada) {
        window.location.assign(expirada ? '/ingreso?expirada=1' : '/ingreso');
    }

    function mostrarAviso(mensaje) {
        var aviso = document.getElementById('aviso');
        if (!aviso) {
            aviso = document.createElement('div');
            aviso.id = 'aviso';
            aviso.className = 'alert alert-danger';
            aviso.setAttribute('role', 'alert');
            document.querySelector('main').prepend(aviso);
        }
        aviso.textContent = mensaje;
    }

    /** Escribe el texto si la página tiene ese elemento; la bienvenida solo existe en el panel. */
    function escribir(id, texto) {
        var nodo = document.getElementById(id);
        if (nodo) {
            nodo.textContent = texto;
        }
    }

    /** Sin datos de sesión no se muestran «Bienvenido, .» ni «Roles: »: se oculta el párrafo entero. */
    function ocultarDatosDeSesion() {
        ['bienvenida-nombre', 'bienvenida-roles'].forEach(function (id) {
            var nodo = document.getElementById(id);
            if (nodo) {
                nodo.parentElement.classList.add('d-none');
            }
        });
    }

    window.Api.solicitar('GET', '/sesion')
        .then(function (respuesta) {
            var usuario = respuesta.datos;
            escribir('usuario-nombre', usuario.nombre);
            escribir('bienvenida-nombre', usuario.nombre);
            escribir('bienvenida-roles', usuario.roles.length > 0 ? usuario.roles.join(', ') : textos.roles_ninguno);
        })
        .catch(function (error) {
            if (error.status === 401) {
                irAlIngreso(true);
                return;
            }
            ocultarDatosDeSesion();
            mostrarAviso(error.status === 0 ? textos.sin_conexion : textos.error_generico);
        });

    document.getElementById('cerrar-sesion').addEventListener('click', function (evento) {
        var boton = evento.currentTarget;
        if (boton.disabled) {
            return;
        }
        boton.disabled = true;
        window.Api.solicitar('DELETE', '/sesion')
            .then(function () { irAlIngreso(false); })
            .catch(function (error) {
                if (error.status === 401) {
                    irAlIngreso(true);
                    return;
                }
                boton.disabled = false;
            });
    });
})();
