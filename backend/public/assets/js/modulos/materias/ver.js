/*
 * Página de una materia (FRONT-R5a, docente): GET /materias/{materia} para sus datos y el criterio aplicado (RN-03), y
 * PUT /materias/{materia}/criterio para definir el criterio propio (HU-08). La API responde 404 a una materia ajena o
 * inexistente (DD-50), valida el valor (422 calificacion_fuera_de_rango) y recalcula los resúmenes de los inscritos; aquí
 * solo se muestran sus respuestas. Definir el criterio pide confirmación, porque cierra el vigente y recalcula (CA-FOR-003).
 */
(function () {
    'use strict';

    var textos = window.Api.textos();
    var ta = textos.academico;
    var id = document.getElementById('materia').dataset.materia;
    var ruta = '/materias/' + encodeURIComponent(id);
    var datos = document.getElementById('datos-materia');
    var formulario = document.getElementById('formulario-criterio');
    var boton = formulario.querySelector('button[type="submit"]');
    var aviso = document.getElementById('aviso-criterio');

    function poner(campo, texto) {
        datos.querySelector('[data-campo="' + campo + '"]').textContent = texto;
    }

    function ponerCriterio(criterio) {
        poner('criterio_aplicado.calificacion_minima criterio_aplicado.ambito', ta.criterio_valor
            .replace(':valor', criterio.calificacion_minima)
            .replace(':ambito', ta.ambitos[criterio.ambito] || criterio.ambito));
    }

    function noDisponible() {
        var avisoGeneral = document.getElementById('aviso-no-disponible');
        avisoGeneral.textContent = ta.materia_no_disponible;
        avisoGeneral.classList.remove('d-none');
        document.getElementById('contenido-materia').hidden = true;
    }

    window.Api.solicitar('GET', ruta).then(function (respuesta) {
        var materia = respuesta.datos;
        document.getElementById('titulo-materia').textContent = materia.asignatura.clave + ' · ' + materia.asignatura.nombre;
        document.getElementById('periodo-materia').textContent = materia.periodo.nombre;
        poner('asignatura.clave', materia.asignatura.clave);
        poner('asignatura.nombre', materia.asignatura.nombre);
        poner('periodo.nombre', materia.periodo.nombre);
        ponerCriterio(materia.criterio_aplicado);
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
        window.Avisos.confirmar({
            titulo: ta.criterio_titulo.replace(':valor', formulario.elements.calificacion_minima.value.trim()),
            texto: ta.criterio_texto,
            boton: ta.criterio_boton
        }).then(function (confirmado) {
            if (!confirmado) {
                return;
            }
            window.Formularios.ocupar(boton);
            window.Api.solicitar('PUT', ruta + '/criterio', window.Formularios.datos(formulario))
                .then(function (respuesta) {
                    formulario.reset();
                    ponerCriterio(respuesta.datos);
                    window.Avisos.aviso(ta.criterio_guardado);
                }, function (error) {
                    return window.Formularios.mostrarError(formulario, aviso, error);
                })
                .then(function () {
                    window.Formularios.liberar(boton);
                });
        });
    });
})();
