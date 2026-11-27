/*
 * Texto de una alerta y de un estado oficial de disponibilidad, para el estatus y la ficha (FRONT-R4). Solo presenta lo
 * que manda la API: la causa de una alerta son los datos que la activaron (DA-105), y aquí no se recalcula nada.
 */
(function () {
    'use strict';

    var textos = window.Api.textos();
    var te = textos.estatus;
    var tj = textos.jugadores;

    function valorDeCausa(clave, valor) {
        if (valor === null || valor === undefined) {
            return tj.sin_dato;
        }
        if (clave === 'nivel_riesgo') {
            return tj.niveles[valor] || valor;
        }
        if (clave === 'resultado') {
            return te.resultados[valor] || valor;
        }
        if (typeof valor === 'boolean') {
            return valor ? tj.si : tj.no;
        }
        return String(valor);
    }

    /** «Clave: valor · Clave: valor» con las claves que trae la causa. */
    function textoDeCausa(causa) {
        return Object.keys(causa || {}).map(function (clave) {
            return (te.causa[clave] || clave) + ': ' + valorDeCausa(clave, causa[clave]);
        }).join(' · ');
    }

    function textoDeTipo(tipo) {
        return te.tipos[tipo] || tipo;
    }

    /** «Tipo (causa)». */
    function textoDeAlerta(alerta) {
        var causa = textoDeCausa(alerta.causa);
        return textoDeTipo(alerta.tipo) + (causa ? ' (' + causa + ')' : '');
    }

    /** «Estado · regreso estimado AAAA-MM-DD · Requiere actualización». */
    function textoDeEstado(estado) {
        var partes = [te.estados[estado.estado]];
        if (estado.fecha_estimada_regreso) {
            partes.push(te.regreso.replace(':fecha', estado.fecha_estimada_regreso));
        }
        if (estado.requiere_actualizacion) {
            partes.push(te.requiere_actualizacion);
        }
        return partes.join(' · ');
    }

    window.Estatus = {
        textoDeCausa: textoDeCausa,
        textoDeTipo: textoDeTipo,
        textoDeAlerta: textoDeAlerta,
        textoDeEstado: textoDeEstado
    };
})();
