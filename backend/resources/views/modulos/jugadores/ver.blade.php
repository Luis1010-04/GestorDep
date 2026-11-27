@extends('layouts.app')

@section('titulo', __('interfaz.jugadores.ficha_titulo'))

@push('estilos')
    <link href="/assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <link href="/assets/vendor/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="/assets/vendor/datatables/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="/assets/css/comun/tablas.css" rel="stylesheet">
@endpush

@section('contenido')
    <div class="pagetitle">
        <h1 id="titulo-ficha">{{ __('interfaz.jugadores.ficha_titulo') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/jugadores">{{ __('interfaz.jugadores.titulo') }}</a></li>
                <li class="breadcrumb-item active">{{ __('interfaz.jugadores.ficha_titulo') }}</li>
            </ol>
        </nav>
    </div>
    <section class="section" id="ficha" data-jugador="{{ $jugador }}" data-zona="{{ config('app.zona_presentacion') }}">
        <div id="aviso-ficha" class="alert alert-warning d-none" role="alert"></div>
        <div id="acciones-del-entrenador" class="mb-3" data-roles="entrenador" hidden>
            <a id="enlace-editar" class="btn btn-outline-primary btn-sm" href="/jugadores/{{ $jugador }}/editar">{{ __('interfaz.jugadores.editar_datos') }}</a>
            <button type="button" id="boton-baja" class="btn btn-outline-danger btn-sm">{{ __('interfaz.jugadores.registrar_baja') }}</button>
        </div>
        <div class="card" id="tarjeta-baja" hidden>
            <div class="card-body pt-3">
                <h2 class="card-title">{{ __('interfaz.jugadores.baja_titulo') }}</h2>
                <p class="text-muted small">{{ __('interfaz.jugadores.baja_ayuda') }}</p>
                <div id="aviso-baja" class="alert alert-danger d-none" role="alert"></div>
                <form id="formulario-baja" data-endpoint="POST /jugadores/{jugador}/baja" class="row g-3" novalidate hidden>
                    <div class="col-12">
                        <label for="motivo" class="form-label">{{ __('interfaz.jugadores.motivo') }}</label>
                        <textarea class="form-control" id="motivo" name="motivo" rows="2"></textarea>
                        <div class="invalid-feedback" data-error-de="motivo"></div>
                    </div>
                    <div class="col-sm-6">
                        <label for="fecha" class="form-label">{{ __('interfaz.jugadores.fecha_baja') }}</label>
                        <input type="date" class="form-control" id="fecha" name="fecha">
                        <div class="invalid-feedback" data-error-de="fecha"></div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-danger">{{ __('interfaz.jugadores.registrar_baja') }}</button>
                        <button type="button" class="btn btn-outline-secondary" id="cancelar-baja">{{ __('interfaz.formulario.cancelar') }}</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card" id="tarjeta-ficha">
            <div class="card-body pt-3">
                <ul class="nav nav-tabs nav-tabs-bordered" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="boton-datos" data-bs-toggle="tab" data-bs-target="#pestana-datos" type="button" role="tab">{{ __('interfaz.jugadores.pestanas.datos') }}</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="boton-academica" data-bs-toggle="tab" data-bs-target="#pestana-academica" type="button" role="tab">{{ __('interfaz.jugadores.pestanas.academica') }}</button>
                    </li>
                    <li class="nav-item" role="presentation" data-roles="entrenador" hidden>
                        <button class="nav-link" id="boton-asistencia" data-bs-toggle="tab" data-bs-target="#pestana-asistencia" type="button" role="tab">{{ __('interfaz.jugadores.pestanas.asistencia') }}</button>
                    </li>
                    <li class="nav-item" role="presentation" data-roles="docente entrenador" hidden>
                        <button class="nav-link" id="boton-alertas" data-bs-toggle="tab" data-bs-target="#pestana-alertas" type="button" role="tab">{{ __('interfaz.jugadores.pestanas.alertas') }}</button>
                    </li>
                    <li class="nav-item" role="presentation" data-roles="docente entrenador" hidden>
                        <button class="nav-link" id="boton-disponibilidad" data-bs-toggle="tab" data-bs-target="#pestana-disponibilidad" type="button" role="tab">{{ __('interfaz.jugadores.pestanas.disponibilidad') }}</button>
                    </li>
                    <li class="nav-item" role="presentation" data-roles="docente entrenador" hidden>
                        <button class="nav-link" id="boton-avisos" data-bs-toggle="tab" data-bs-target="#pestana-avisos" type="button" role="tab">{{ __('interfaz.jugadores.pestanas.avisos') }}</button>
                    </li>
                </ul>
                <div class="tab-content pt-3">
                    <div class="tab-pane fade show active" id="pestana-datos" role="tabpanel" data-endpoint="GET /jugadores/{jugador}">
                        <p class="text-muted" data-mensaje>{{ __('interfaz.jugadores.cargando') }}</p>
                        <div data-contenido hidden>
                            <dl class="row">
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.apellidos') }}</dt>
                                <dd class="col-sm-8" data-campo="apellidos"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.nombres') }}</dt>
                                <dd class="col-sm-8" data-campo="nombres"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.matricula') }}</dt>
                                <dd class="col-sm-8" data-campo="matricula"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.fecha_nacimiento') }}</dt>
                                <dd class="col-sm-8" data-campo="fecha_nacimiento"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.estatus_academico') }}</dt>
                                <dd class="col-sm-8" data-campo="estatus_academico"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.estatus_deportivo') }}</dt>
                                <dd class="col-sm-8" data-campo="estatus_deportivo"></dd>
                            </dl>
                            <h2 class="card-title">{{ __('interfaz.jugadores.equipos_titulo') }}</h2>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th data-campo="equipos.nombre">{{ __('interfaz.jugadores.campos.equipo') }}</th>
                                        <th data-campo="equipos.categoria.nombre">{{ __('interfaz.jugadores.campos.categoria') }}</th>
                                        <th data-campo="equipos.posicion">{{ __('interfaz.jugadores.campos.posicion') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="equipos-del-jugador"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pestana-academica" role="tabpanel" data-endpoint="GET /jugadores/{jugador}/situacion-academica">
                        <p class="text-muted" data-mensaje>{{ __('interfaz.jugadores.cargando') }}</p>
                        <div data-contenido hidden>
                            <dl class="row">
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.periodo') }}</dt>
                                <dd class="col-sm-8" data-campo="periodo.nombre"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.promedio') }}</dt>
                                <dd class="col-sm-8" data-campo="promedio"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.materias_reprobadas') }}</dt>
                                <dd class="col-sm-8" data-campo="materias_reprobadas"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.materias_sin_calificar') }}</dt>
                                <dd class="col-sm-8" data-campo="materias_sin_calificar"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.porcentaje_asistencia') }}</dt>
                                <dd class="col-sm-8" data-campo="porcentaje_asistencia"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.nivel_riesgo') }}</dt>
                                <dd class="col-sm-8" data-campo="nivel_riesgo"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.dato_incompleto') }}</dt>
                                <dd class="col-sm-8" data-campo="dato_incompleto"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.calculado_en') }}</dt>
                                <dd class="col-sm-8" data-campo="calculado_en"></dd>
                            </dl>
                            <div id="bloque-materias" hidden>
                                <h2 class="card-title">{{ __('interfaz.jugadores.materias_titulo') }}</h2>
                                <table id="tabla-materias" class="table table-striped text-nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th data-campo="materias.asignatura.clave">{{ __('interfaz.jugadores.campos.clave') }}</th>
                                            <th data-campo="materias.asignatura.nombre">{{ __('interfaz.jugadores.campos.asignatura') }}</th>
                                            <th data-campo="materias.calificacion">{{ __('interfaz.jugadores.campos.calificacion') }}</th>
                                            <th data-campo="materias.criterio.calificacion_minima">{{ __('interfaz.jugadores.campos.calificacion_minima') }}</th>
                                            <th data-campo="materias.aprobada">{{ __('interfaz.jugadores.campos.aprobada') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pestana-asistencia" role="tabpanel" data-endpoint="GET /jugadores/{jugador}/asistencia-entrenamientos">
                        <p class="text-muted" data-mensaje>{{ __('interfaz.jugadores.cargando') }}</p>
                        <div data-contenido hidden>
                            <dl class="row">
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.porcentaje') }}</dt>
                                <dd class="col-sm-8" data-campo="porcentaje"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.presentes') }}</dt>
                                <dd class="col-sm-8" data-campo="presentes"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.faltas') }}</dt>
                                <dd class="col-sm-8" data-campo="faltas"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.jugadores.campos.faltas_justificadas') }}</dt>
                                <dd class="col-sm-8" data-campo="faltas_justificadas"></dd>
                            </dl>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pestana-alertas" role="tabpanel" data-endpoint="GET /jugadores/{jugador}/alertas">
                        <p class="text-muted" data-mensaje>{{ __('interfaz.jugadores.cargando') }}</p>
                        <div data-contenido hidden>
                            <h2 class="card-title">{{ __('interfaz.alertas.titulo') }}</h2>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th data-campo="tipo">{{ __('interfaz.alertas.columnas.tipo') }}</th>
                                        <th data-campo="causa">{{ __('interfaz.alertas.columnas.causa') }}</th>
                                        <th data-campo="equipo_id">{{ __('interfaz.alertas.columnas.equipo') }}</th>
                                        <th data-campo="activada_en">{{ __('interfaz.alertas.columnas.activada_en') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="alertas-del-jugador"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pestana-disponibilidad" role="tabpanel" data-endpoint="GET /jugadores/{jugador}/estado-disponibilidad">
                        <p class="text-muted" data-mensaje>{{ __('interfaz.jugadores.cargando') }}</p>
                        <div data-contenido hidden>
                            <dl class="row">
                                <dt class="col-sm-4">{{ __('interfaz.disponibilidad.estado') }}</dt>
                                <dd class="col-sm-8" data-campo="estado"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.disponibilidad.fecha_estimada_regreso') }}</dt>
                                <dd class="col-sm-8" data-campo="fecha_estimada_regreso"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.disponibilidad.vigente_desde') }}</dt>
                                <dd class="col-sm-8" data-campo="vigente_desde"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.disponibilidad.es_implicito') }}</dt>
                                <dd class="col-sm-8" data-campo="es_implicito"></dd>
                                <dt class="col-sm-4">{{ __('interfaz.disponibilidad.requiere_actualizacion') }}</dt>
                                <dd class="col-sm-8" data-campo="requiere_actualizacion"></dd>
                            </dl>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pestana-avisos" role="tabpanel">
                        <h2 class="card-title">{{ __('interfaz.avisos.nuevo') }}</h2>
                        <p class="text-muted small">{{ __('interfaz.avisos.ayuda') }}</p>
                        <div id="aviso-de-lesion" class="alert alert-danger d-none" role="alert"></div>
                        <form id="formulario-aviso" data-endpoint="POST /jugadores/{jugador}/avisos-lesion" class="row g-3" novalidate>
                            <div class="col-12">
                                <label for="nota-aviso" class="form-label">{{ __('interfaz.avisos.nota') }}</label>
                                <textarea class="form-control" id="nota-aviso" name="nota" rows="2"></textarea>
                                <div class="invalid-feedback" data-error-de="nota"></div>
                            </div>
                            <div class="col-sm-6">
                                <label for="fecha-aviso" class="form-label">{{ __('interfaz.avisos.fecha') }}</label>
                                <input type="date" class="form-control" id="fecha-aviso" name="fecha">
                                <div class="invalid-feedback" data-error-de="fecha"></div>
                            </div>
                            <div class="col-sm-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="reportado-por-jugador" name="reportado_por_jugador">
                                    <label for="reportado-por-jugador" class="form-check-label">{{ __('interfaz.avisos.reportado_por_jugador') }}</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">{{ __('interfaz.avisos.guardar') }}</button>
                            </div>
                        </form>
                        <h2 class="card-title mt-3">{{ __('interfaz.avisos.titulo') }}</h2>
                        <table id="tabla-avisos" class="table table-striped w-100" data-endpoint="GET /jugadores/{jugador}/avisos-lesion">
                            <thead>
                                <tr>
                                    <th data-campo="fecha_aviso">{{ __('interfaz.avisos.columnas.fecha_aviso') }}</th>
                                    <th data-campo="estado">{{ __('interfaz.avisos.columnas.estado') }}</th>
                                    <th data-campo="reportado_por_jugador">{{ __('interfaz.avisos.columnas.reportado_por_jugador') }}</th>
                                    <th data-campo="nota">{{ __('interfaz.avisos.columnas.nota') }}</th>
                                    <th data-campo="es_propio">{{ __('interfaz.avisos.columnas.es_propio') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="/assets/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script src="/assets/js/comun/avisos.js"></script>
    <script src="/assets/js/comun/formularios.js"></script>
    <script src="/assets/vendor/datatables/js/dataTables.min.js"></script>
    <script src="/assets/vendor/datatables/js/dataTables.bootstrap5.min.js"></script>
    <script src="/assets/vendor/datatables/js/dataTables.buttons.min.js"></script>
    <script src="/assets/vendor/datatables/js/buttons.bootstrap5.min.js"></script>
    <script src="/assets/vendor/jszip/jszip.min.js"></script>
    <script src="/assets/vendor/pdfmake/pdfmake.min.js"></script>
    <script src="/assets/vendor/pdfmake/vfs_fonts.js"></script>
    <script src="/assets/js/comun/tabla.js"></script>
    <script src="/assets/js/comun/estatus.js"></script>
    <script src="/assets/js/modulos/jugadores/ver.js"></script>
@endpush
