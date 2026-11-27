/*
 * Mis materias (FRONT-R5a, docente): GET /materias (solo las del usuario, filtro[periodo_id]) y POST /materias (HU-06). Las
 * listas de asignaturas y periodos salen de GET /asignaturas y GET /periodos-escolares. La API decide duplicados (409
 * materia_duplicada) y si la asignatura o el periodo existen (422); aquí solo se muestran sus respuestas.
 */
(function () {
    'use strict';

    var textos = window.Api.textos();
    var ta = textos.academico;
    var formulario = document.getElementById('formulario-materia');
    var boton = formulario.querySelector('button[type="submit"]');
    var aviso = document.getElementById('aviso-materia');
    var asignaturas = document.getElementById('asignatura_id');
    var periodos = document.getElementById('periodo_id');
    var filtroPeriodo = document.getElementById('filtro-periodo');
    var titulo = document.querySelector('.pagetitle h1').textContent;

    function llenar(lista, elementos, texto) {
        elementos.forEach(function (elemento) {
            var opcion = document.createElement('option');
            opcion.value = elemento.id;
            opcion.textContent = texto(elemento);
            lista.appendChild(opcion);
        });
    }

    var lista = window.Tabla.crear(document.getElementById('tabla-materias'), {
        ruta: '/materias',
        archivo: 'materias',
        titulo: function () {
            return filtroPeriodo.value ? titulo + ' · ' + filtroPeriodo.options[filtroPeriodo.selectedIndex].textContent : titulo;
        },
        filtros: function () { return { 'filtro[periodo_id]': filtroPeriodo.value }; },
        columnas: [
            { data: 'asignatura.clave' },
            { data: 'asignatura.nombre' },
            { data: 'periodo.nombre' },
            {
                data: 'criterio_aplicado',
                texto: function (criterio) {
                    return ta.criterio_valor.replace(':valor', criterio.calificacion_minima).replace(':ambito', ta.ambitos[criterio.ambito] || criterio.ambito);
                }
            },
            {
                data: 'id',
                clase: 'no-exportar',
                html: function (id) {
                    return '<a class="btn btn-sm btn-outline-primary" href="/academico/materias/' + encodeURIComponent(id) + '">'
                        + window.Tabla.escapar(ta.ver) + '</a>';
                }
            }
        ]
    });

    window.Tabla.todas('/asignaturas', { orden: 'clave' }, Infinity).then(function (elementos) {
        llenar(asignaturas, elementos, function (asignatura) { return asignatura.clave + ' · ' + asignatura.nombre; });
    }, function (error) {
        window.Avisos.error(error);
    });

    window.Tabla.todas('/periodos-escolares', { orden: '-fecha_inicio' }, Infinity).then(function (elementos) {
        function texto(periodo) { return periodo.nombre + ' (' + periodo.fecha_inicio + ' – ' + periodo.fecha_fin + ')'; }
        llenar(periodos, elementos, texto);
        llenar(filtroPeriodo, elementos, texto);
    }, function (error) {
        window.Avisos.error(error);
    });

    filtroPeriodo.addEventListener('change', lista.recargar);

    formulario.addEventListener('submit', function (evento) {
        evento.preventDefault();
        if (boton.disabled) {
            return;
        }
        window.Formularios.limpiar(formulario);
        window.Formularios.ocultarAviso(aviso);
        window.Formularios.ocupar(boton);
        window.Api.solicitar('POST', '/materias', window.Formularios.datos(formulario))
            .then(function () {
                formulario.reset();
                window.Avisos.aviso(ta.materia_creada);
                lista.recargar();
            }, function (error) {
                return window.Formularios.mostrarError(formulario, aviso, error);
            })
            .then(function () {
                window.Formularios.liberar(boton);
            });
    });
})();
