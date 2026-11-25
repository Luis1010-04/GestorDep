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
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="/assets/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script src="/assets/js/comun/avisos.js"></script>
    <script src="/assets/vendor/datatables/js/dataTables.min.js"></script>
    <script src="/assets/vendor/datatables/js/dataTables.bootstrap5.min.js"></script>
    <script src="/assets/vendor/datatables/js/dataTables.buttons.min.js"></script>
    <script src="/assets/vendor/datatables/js/buttons.bootstrap5.min.js"></script>
    <script src="/assets/vendor/jszip/jszip.min.js"></script>
    <script src="/assets/vendor/pdfmake/pdfmake.min.js"></script>
    <script src="/assets/vendor/pdfmake/vfs_fonts.js"></script>
    <script src="/assets/js/comun/tabla.js"></script>
    <script src="/assets/js/modulos/jugadores/ver.js"></script>
@endpush
