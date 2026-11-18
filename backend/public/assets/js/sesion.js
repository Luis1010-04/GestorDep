(function () {
    'use strict';

    var textos = window.Api.textos();

    function irAlIngreso(expirada) {
        window.location.assign(expirada ? '/ingreso?expirada=1' : '/ingreso');
    }

    window.Api.solicitar('GET', '/sesion')
        .then(function (respuesta) {
            var usuario = respuesta.datos;
            document.getElementById('usuario-nombre').textContent = usuario.nombre;
            document.getElementById('bienvenida-nombre').textContent = usuario.nombre;
            document.getElementById('bienvenida-roles').textContent = usuario.roles.length > 0 ? usuario.roles.join(', ') : textos.roles_ninguno;
        })
        .catch(function (error) {
            if (error.status === 401) {
                irAlIngreso(true);
            }
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
