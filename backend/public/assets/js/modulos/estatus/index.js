/*
 * Estatus de jugadores (FRONT-R4, docente y entrenador): GET /estatus-de-jugadores, paginada y sin orden (la ruta no lo
 * admite). La forma depende del rol (DA-105): todos reciben jugador, estado oficial, aviso pendiente y las alertas que
 * pueden ver; el entrenador, además, resumen_academico, y el docente, materias. Un campo que la API omite no se muestra:
 * la columna de cada rol solo aparece para quien lo tiene. Filtros: equipo (entrenador, de GET /equipos), materia
 * (docente, de GET /materias) y tipo de alerta (enum del contrato). Nada se calcula aquí: se muestra lo que llega.
 */
(function () {
    'use strict';

    var textos = window.Api.textos();
    var te = textos.estatus;
    var tj = textos.jugadores;
    var formulario = document.getElementById('filtros-estatus');
    var filtroEquipo = document.getElementById('filtro-equipo');
    var filtroMateria = document.getElementById('filtro-materia');
    var filtroTipo = document.getElementById('filtro-tipo');
    var titulo = document.querySelector('.pagetitle h1').textContent;

    function textoDeMaterias(materias) {
        if (!Array.isArray(materias)) {
            return '';
        }
        if (materias.length === 0) {
            return te.ninguna;
        }
        return materias.map(function (materia) {
            var estado = materia.aprobada === null ? te.materia_sin_calificar : (materia.aprobada ? te.materia_aprobada : te.materia_reprobada);
            return materia.asignatura.clave + ': ' + (materia.calificacion === null ? tj.sin_dato : materia.calificacion) + ', ' + estado;
        }).join('; ');
    }

    function textoDeResumen(resumen) {
        if (!resumen) {
            return '';
        }
        var nivel = resumen.nivel_riesgo ? tj.niveles[resumen.nivel_riesgo] : tj.sin_dato;
        return nivel + ' · ' + (resumen.promedio === null ? tj.sin_dato : resumen.promedio);
    }

    function llenar(lista, elementos, texto) {
        elementos.forEach(function (elemento) {
            var opcion = document.createElement('option');
            opcion.value = elemento.id;
            opcion.textContent = texto(elemento);
            lista.appendChild(opcion);
        });
    }

    window.Sesion.usuario.then(function (usuario) {
        var esEntrenador = usuario.roles.indexOf('entrenador') !== -1;
        var esDocente = usuario.roles.indexOf('docente') !== -1;

        var lista = window.Tabla.crear(document.getElementById('tabla-estatus'), {
            ruta: '/estatus-de-jugadores',
            archivo: 'estatus-de-jugadores',
            titulo: function () {
                var partes = [titulo];
                [filtroEquipo, filtroMateria, filtroTipo].forEach(function (control) {
                    if (control.value) {
                        partes.push(control.options[control.selectedIndex].textContent);
                    }
                });
                return partes.join(' · ');
            },
            filtros: function () {
                return {
                    'filtro[equipo_id]': esEntrenador ? filtroEquipo.value : '',
                    'filtro[materia_id]': esDocente ? filtroMateria.value : '',
                    'filtro[tipo_alerta]': filtroTipo.value
                };
            },
            columnas: [
                { data: 'jugador', texto: function (jugador) { return jugador.apellidos + ', ' + jugador.nombres; } },
                { data: 'jugador.matricula' },
                { data: 'estado_oficial', texto: window.Estatus.textoDeEstado },
                { data: 'aviso_pendiente', texto: function (pendiente) { return pendiente ? tj.si : tj.no; } },
                {
                    data: 'alertas',
                    texto: function (alertas) {
                        return alertas.length === 0 ? te.ninguna : alertas.map(window.Estatus.textoDeAlerta).join('; ');
                    }
                },
                { data: 'resumen_academico', visible: esEntrenador, texto: textoDeResumen },
                { data: 'materias', visible: esDocente, texto: textoDeMaterias },
                {
                    data: 'jugador.id',
                    clase: 'no-exportar',
                    html: function (id) {
                        return '<a class="btn btn-sm btn-outline-primary" href="/jugadores/' + encodeURIComponent(id) + '">'
                            + window.Tabla.escapar(tj.ver_ficha) + '</a>';
                    }
                }
            ]
        });

        if (esEntrenador) {
            window.Tabla.todas('/equipos', { orden: 'nombre' }, Infinity).then(function (equipos) {
                llenar(filtroEquipo, equipos, function (equipo) { return equipo.nombre + ' (' + equipo.categoria.nombre + ')'; });
            }, function (error) { window.Avisos.error(error); });
        }
        if (esDocente) {
            window.Tabla.todas('/materias', {}, Infinity).then(function (materias) {
                llenar(filtroMateria, materias, function (materia) {
                    return materia.asignatura.clave + ' · ' + materia.asignatura.nombre + ' (' + materia.periodo.nombre + ')';
                });
            }, function (error) { window.Avisos.error(error); });
        }

        formulario.addEventListener('change', lista.recargar);
    }, function () {
        // sesion.js ya trató el error de la sesión (redirección o aviso).
    });
})();
