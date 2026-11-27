/*
 * Ficha del jugador (FRONT-R3). Solo lectura: GET /jugadores/{id}, /situacion-academica y /asistencia-entrenamientos.
 * Cada pestaña pide sus datos la primera vez que se abre (D9: php -S atiende una solicitud a la vez). Los decimales y
 * las fechas AAAA-MM-DD se muestran tal como llegan (DAF-13); las fechas con hora, en la zona de presentación de la
 * aplicación (config app.zona_presentacion, DAF-18), sin cambiar el dato.
 */
(function () {
    'use strict';

    var textos = window.Api.textos();
    var tj = textos.jugadores;
    var ficha = document.getElementById('ficha');
    var id = ficha.dataset.jugador;
    var zona = ficha.dataset.zona;
    var ruta = '/jugadores/' + encodeURIComponent(id);

    function panel(nombre) {
        return document.getElementById('pestana-' + nombre);
    }

    function poner(contenedor, campo, texto) {
        contenedor.querySelector('[data-campo="' + campo + '"]').textContent = texto;
    }

    function conDato(valor) {
        return valor === null || valor === undefined ? tj.sin_dato : String(valor);
    }

    function fechaHora(valor) {
        if (!valor) {
            return tj.sin_dato;
        }
        return new Intl.DateTimeFormat('es-MX', { timeZone: zona, dateStyle: 'medium', timeStyle: 'short' }).format(new Date(valor));
    }

    function mensaje(contenedor, texto) {
        contenedor.querySelector('[data-mensaje]').textContent = texto;
        contenedor.querySelector('[data-mensaje]').hidden = false;
        contenedor.querySelector('[data-contenido]').hidden = true;
    }

    function listo(contenedor) {
        contenedor.querySelector('[data-mensaje]').hidden = true;
        contenedor.querySelector('[data-contenido]').hidden = false;
    }

    /** 404 o 401 en la ficha: no se revela nada del jugador (CA-GEN-003). */
    function noDisponible() {
        var aviso = document.getElementById('aviso-ficha');
        aviso.textContent = tj.no_disponible;
        aviso.classList.remove('d-none');
        document.getElementById('tarjeta-ficha').hidden = true;
        // d-none y no hidden: sesion.js quita hidden a los elementos por rol cuando llega la sesión.
        document.getElementById('acciones-del-entrenador').classList.add('d-none');
    }

    function cargarDatos() {
        var contenedor = panel('datos');
        window.Api.solicitar('GET', ruta).then(function (respuesta) {
            var jugador = respuesta.datos;
            document.getElementById('titulo-ficha').textContent = jugador.apellidos + ', ' + jugador.nombres;
            ['apellidos', 'nombres', 'matricula', 'fecha_nacimiento'].forEach(function (campo) {
                poner(contenedor, campo, jugador[campo]);
            });
            poner(contenedor, 'estatus_academico', tj.estatus[jugador.estatus_academico]);
            poner(contenedor, 'estatus_deportivo', tj.estatus[jugador.estatus_deportivo]);

            var cuerpo = document.getElementById('equipos-del-jugador');
            cuerpo.textContent = '';
            if (jugador.equipos.length === 0) {
                var fila = cuerpo.insertRow();
                var celda = fila.insertCell();
                celda.colSpan = 3;
                celda.className = 'text-muted';
                celda.textContent = tj.sin_equipos;
            }
            jugador.equipos.forEach(function (equipo) {
                var fila = cuerpo.insertRow();
                fila.insertCell().textContent = equipo.nombre;
                fila.insertCell().textContent = equipo.categoria.nombre;
                fila.insertCell().textContent = tj.posiciones[equipo.posicion];
            });
            nombreDelJugador = jugador.nombres + ' ' + jugador.apellidos;
            equiposDelJugador = jugador.equipos;
            if (jugador.estatus_deportivo === 'baja' || jugador.estatus_academico === 'baja') {
                document.getElementById('boton-baja').classList.add('d-none');
            }
            listo(contenedor);
        }, function (error) {
            if (error.status === 404) {
                noDisponible();
                return;
            }
            mensaje(contenedor, tj.error_de_carga);
            window.Avisos.error(error);
        });
    }

    function cargarSituacionAcademica() {
        var contenedor = panel('academica');
        window.Api.solicitar('GET', ruta + '/situacion-academica').then(function (respuesta) {
            var situacion = respuesta.datos;
            poner(contenedor, 'periodo.nombre', situacion.periodo ? situacion.periodo.nombre : tj.sin_periodo);
            poner(contenedor, 'promedio', conDato(situacion.promedio));
            poner(contenedor, 'materias_reprobadas', String(situacion.materias_reprobadas));
            poner(contenedor, 'materias_sin_calificar', String(situacion.materias_sin_calificar));
            poner(contenedor, 'porcentaje_asistencia', conDato(situacion.porcentaje_asistencia));
            poner(contenedor, 'nivel_riesgo', situacion.nivel_riesgo ? tj.niveles[situacion.nivel_riesgo] : tj.sin_dato);
            poner(contenedor, 'dato_incompleto', situacion.dato_incompleto ? tj.si : tj.no);
            poner(contenedor, 'calculado_en', fechaHora(situacion.calculado_en));

            // materias solo llega a quien es docente del jugador (DD-48); si no llega, la tabla no se muestra.
            if (Array.isArray(situacion.materias)) {
                document.getElementById('bloque-materias').hidden = false;
                window.Tabla.crear(document.getElementById('tabla-materias'), {
                    datos: situacion.materias,
                    archivo: 'materias',
                    titulo: function () { return tj.materias_titulo + ' · ' + document.getElementById('titulo-ficha').textContent; },
                    columnas: [
                        { data: 'asignatura.clave' },
                        { data: 'asignatura.nombre' },
                        { data: 'calificacion', texto: conDato },
                        { data: 'criterio.calificacion_minima' },
                        { data: 'aprobada', texto: function (aprobada) { return aprobada === null ? tj.sin_dato : (aprobada ? tj.si : tj.no); } }
                    ]
                });
            }
            listo(contenedor);
        }, function (error) {
            if (error.status === 404) {
                noDisponible();
                return;
            }
            mensaje(contenedor, tj.error_de_carga);
            window.Avisos.error(error);
        });
    }

    function cargarAsistencia() {
        var contenedor = panel('asistencia');
        window.Api.solicitar('GET', ruta + '/asistencia-entrenamientos').then(function (respuesta) {
            var asistencia = respuesta.datos;
            poner(contenedor, 'porcentaje', conDato(asistencia.porcentaje));
            ['presentes', 'faltas', 'faltas_justificadas'].forEach(function (campo) {
                poner(contenedor, campo, String(asistencia[campo]));
            });
            listo(contenedor);
        }, function (error) {
            if (error.status === 404) {
                // Sin alcance de entrenador sobre el jugador (DA-20): la ficha sigue, la pestaña no tiene datos para él.
                mensaje(contenedor, tj.asistencia_no_disponible);
                return;
            }
            mensaje(contenedor, tj.error_de_carga);
            window.Avisos.error(error);
        });
    }

    function alAbrirPorPrimeraVez(boton, cargar) {
        var cargada = false;
        boton.addEventListener('shown.bs.tab', function () {
            if (!cargada) {
                cargada = true;
                cargar();
            }
        });
    }

    /*
     * Baja del jugador (FRONT-R6, entrenador): POST /jugadores/{id}/baja con motivo y fecha (HU-29, RN-23). Es definitiva
     * y la API no ofrece forma de revertirla, así que pide confirmación de peligro antes de enviar (CA-FOR-003).
     */
    var tarjetaBaja = document.getElementById('tarjeta-baja');
    var formularioBaja = document.getElementById('formulario-baja');
    var avisoBaja = document.getElementById('aviso-baja');
    var nombreDelJugador = '';

    function mostrarBaja(visible) {
        tarjetaBaja.hidden = !visible;
        formularioBaja.hidden = !visible;
    }

    document.getElementById('boton-baja').addEventListener('click', function () {
        window.Formularios.limpiar(formularioBaja);
        window.Formularios.ocultarAviso(avisoBaja);
        mostrarBaja(true);
        formularioBaja.elements.motivo.focus();
    });

    document.getElementById('cancelar-baja').addEventListener('click', function () {
        mostrarBaja(false);
    });

    formularioBaja.addEventListener('submit', function (evento) {
        evento.preventDefault();
        var boton = formularioBaja.querySelector('button[type="submit"]');
        if (boton.disabled) {
            return;
        }
        window.Avisos.confirmar({
            titulo: textos.plantel.baja_titulo.replace(':nombre', nombreDelJugador),
            texto: textos.plantel.baja_texto,
            boton: textos.plantel.baja_boton,
            peligro: true
        }).then(function (confirmado) {
            if (!confirmado) {
                return;
            }
            window.Formularios.limpiar(formularioBaja);
            window.Formularios.ocultarAviso(avisoBaja);
            window.Formularios.ocupar(boton);
            window.Api.solicitar('POST', ruta + '/baja', window.Formularios.datos(formularioBaja))
                .then(function () {
                    return window.Avisos.exito(textos.plantel.baja_registrada).then(function () {
                        window.location.reload();
                    });
                }, function (error) {
                    return window.Formularios.mostrarError(formularioBaja, avisoBaja, error);
                })
                .then(function () {
                    window.Formularios.liberar(boton);
                });
        });
    });

    /*
     * Alertas, disponibilidad y avisos de lesión (FRONT-R4, docente y entrenador). Solo se muestra lo que manda la API:
     * cada quien ve las alertas que le corresponden (DA-89, DA-90) y la nota de un aviso solo si la escribió o lo confirmó
     * (DD-58); para los demás llega nula y se dice que no es visible.
     */
    var te = textos.estatus;
    var equiposDelJugador = [];

    function nombreDeEquipo(equipoId) {
        var equipo = equiposDelJugador.find(function (candidato) { return candidato.equipo_id === equipoId; });
        return equipo ? equipo.nombre : tj.sin_dato;
    }

    function cargarAlertas() {
        var contenedor = panel('alertas');
        window.Api.solicitar('GET', ruta + '/alertas').then(function (respuesta) {
            var cuerpo = document.getElementById('alertas-del-jugador');
            cuerpo.textContent = '';
            if (respuesta.datos.length === 0) {
                var celda = cuerpo.insertRow().insertCell();
                celda.colSpan = 4;
                celda.className = 'text-muted';
                celda.textContent = te.sin_alertas;
            }
            respuesta.datos.forEach(function (alerta) {
                var fila = cuerpo.insertRow();
                fila.insertCell().textContent = window.Estatus.textoDeTipo(alerta.tipo);
                fila.insertCell().textContent = window.Estatus.textoDeCausa(alerta.causa);
                fila.insertCell().textContent = alerta.equipo_id ? nombreDeEquipo(alerta.equipo_id) : tj.sin_dato;
                fila.insertCell().textContent = fechaHora(alerta.activada_en);
            });
            listo(contenedor);
        }, function (error) {
            if (error.status === 404) {
                // Un jugador en baja o fuera de alcance no tiene alertas visibles (RN-23).
                mensaje(contenedor, te.alertas_no_disponibles);
                return;
            }
            mensaje(contenedor, tj.error_de_carga);
            window.Avisos.error(error);
        });
    }

    function cargarDisponibilidad() {
        var contenedor = panel('disponibilidad');
        window.Api.solicitar('GET', ruta + '/estado-disponibilidad').then(function (respuesta) {
            var estado = respuesta.datos;
            poner(contenedor, 'estado', te.estados[estado.estado]);
            poner(contenedor, 'fecha_estimada_regreso', conDato(estado.fecha_estimada_regreso));
            poner(contenedor, 'vigente_desde', fechaHora(estado.vigente_desde));
            poner(contenedor, 'es_implicito', estado.es_implicito ? te.es_implicito : te.registrado);
            poner(contenedor, 'requiere_actualizacion', estado.requiere_actualizacion ? tj.si : tj.no);
            listo(contenedor);
        }, function (error) {
            mensaje(contenedor, error.status === 404 ? tj.no_disponible : tj.error_de_carga);
            if (error.status !== 404) {
                window.Avisos.error(error);
            }
        });
    }

    var tablaDeAvisos = null;
    var formularioAviso = document.getElementById('formulario-aviso');
    var avisoDeLesion = document.getElementById('aviso-de-lesion');

    function prepararAvisos() {
        tablaDeAvisos = window.Tabla.crear(document.getElementById('tabla-avisos'), {
            ruta: ruta + '/avisos-lesion',
            archivo: 'avisos-de-lesion',
            titulo: function () { return document.getElementById('titulo-ficha').textContent; },
            descargas: false,
            columnas: [
                { data: 'fecha_aviso' },
                { data: 'estado', texto: function (estado) { return te.estados_de_aviso[estado] || estado; } },
                { data: 'reportado_por_jugador', texto: function (valor) { return valor ? tj.si : tj.no; } },
                { data: 'nota', texto: function (nota) { return nota === null ? te.nota_no_visible : nota; } },
                { data: 'es_propio', texto: function (valor) { return valor ? tj.si : tj.no; } }
            ]
        });
    }

    formularioAviso.addEventListener('submit', function (evento) {
        evento.preventDefault();
        var boton = formularioAviso.querySelector('button[type="submit"]');
        if (boton.disabled) {
            return;
        }
        window.Formularios.limpiar(formularioAviso);
        window.Formularios.ocultarAviso(avisoDeLesion);
        window.Formularios.ocupar(boton);
        window.Api.solicitar('POST', ruta + '/avisos-lesion', window.Formularios.datos(formularioAviso))
            .then(function () {
                formularioAviso.reset();
                window.Avisos.aviso(te.aviso_creado);
                if (tablaDeAvisos) {
                    tablaDeAvisos.recargar();
                }
            }, function (error) {
                return window.Formularios.mostrarError(formularioAviso, avisoDeLesion, error);
            })
            .then(function () {
                window.Formularios.liberar(boton);
            });
    });

    alAbrirPorPrimeraVez(document.getElementById('boton-academica'), cargarSituacionAcademica);
    alAbrirPorPrimeraVez(document.getElementById('boton-asistencia'), cargarAsistencia);
    alAbrirPorPrimeraVez(document.getElementById('boton-alertas'), cargarAlertas);
    alAbrirPorPrimeraVez(document.getElementById('boton-disponibilidad'), cargarDisponibilidad);
    alAbrirPorPrimeraVez(document.getElementById('boton-avisos'), prepararAvisos);
    cargarDatos();
})();
