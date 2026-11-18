/*
 * Cliente mínimo de la API: sesión por cookies de Sanctum, sin tokens Bearer.
 * Todo error se normaliza a {status, codigo, mensaje, errores, retryAfter}.
 */
(function () {
    'use strict';

    function textos() {
        try {
            return JSON.parse(document.body.dataset.textos || '{}');
        } catch (e) {
            return {};
        }
    }

    function leerCookie(nombre) {
        var par = document.cookie.split('; ').find(function (c) { return c.indexOf(nombre + '=') === 0; });
        return par ? decodeURIComponent(par.substring(nombre.length + 1)) : null;
    }

    function errorNormalizado(estado, cuerpo, cabeceras) {
        var retry = cabeceras ? parseInt(cabeceras.get('Retry-After'), 10) : NaN;
        return {
            status: estado,
            codigo: cuerpo && cuerpo.codigo ? cuerpo.codigo : null,
            mensaje: cuerpo && cuerpo.mensaje ? cuerpo.mensaje : null,
            errores: cuerpo && Array.isArray(cuerpo.errores) ? cuerpo.errores : [],
            retryAfter: isNaN(retry) ? null : retry
        };
    }

    /** Pide la cookie XSRF-TOKEN que Sanctum exige antes de una escritura. */
    function obtenerCookieCsrf() {
        return fetch('/sanctum/csrf-cookie', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        }).then(function (respuesta) {
            if (!respuesta.ok) {
                return Promise.reject(errorNormalizado(respuesta.status, null, respuesta.headers));
            }
        }, function () {
            return Promise.reject(errorNormalizado(0, null, null));
        });
    }

    /** Resuelve con el cuerpo JSON (o null en 204); rechaza con el error normalizado. */
    function solicitar(metodo, ruta, cuerpo) {
        var cabeceras = { Accept: 'application/json' };
        var token = leerCookie('XSRF-TOKEN');
        if (token) {
            cabeceras['X-XSRF-TOKEN'] = token;
        }
        if (cuerpo !== undefined) {
            cabeceras['Content-Type'] = 'application/json';
        }

        return fetch('/api/v1' + ruta, {
            method: metodo,
            credentials: 'same-origin',
            headers: cabeceras,
            body: cuerpo === undefined ? undefined : JSON.stringify(cuerpo)
        }).then(function (respuesta) {
            return respuesta.text().then(function (texto) {
                var json = null;
                try {
                    json = texto ? JSON.parse(texto) : null;
                } catch (e) {
                    json = null;
                }
                if (!respuesta.ok) {
                    return Promise.reject(errorNormalizado(respuesta.status, json, respuesta.headers));
                }
                return json;
            });
        }, function () {
            return Promise.reject(errorNormalizado(0, null, null));
        });
    }

    window.Api = { textos: textos, obtenerCookieCsrf: obtenerCookieCsrf, solicitar: solicitar };
})();
