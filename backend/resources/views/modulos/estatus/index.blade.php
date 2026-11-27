@extends('layouts.app')

@section('titulo', __('interfaz.estatus.titulo'))

@push('estilos')
    <link href="/assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <link href="/assets/vendor/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="/assets/vendor/datatables/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="/assets/css/comun/tablas.css" rel="stylesheet">
@endpush

@section('contenido')
    <div class="pagetitle">
        <h1>{{ __('interfaz.estatus.titulo') }}</h1>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-body pt-3">
                <p class="text-muted">{{ __('interfaz.estatus.ayuda') }}</p>
                <form id="filtros-estatus" data-endpoint="GET /estatus-de-jugadores" class="row g-2 mb-3" novalidate>
                    <div class="col-sm-6 col-lg-4" data-roles="entrenador" hidden>
                        <label for="filtro-equipo" class="form-label">{{ __('interfaz.estatus.filtro_equipo') }}</label>
                        <select id="filtro-equipo" name="filtro[equipo_id]" class="form-select">
                            <option value="">{{ __('interfaz.estatus.todos') }}</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-4" data-roles="docente" hidden>
                        <label for="filtro-materia" class="form-label">{{ __('interfaz.estatus.filtro_materia') }}</label>
                        <select id="filtro-materia" name="filtro[materia_id]" class="form-select">
                            <option value="">{{ __('interfaz.estatus.todos') }}</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <label for="filtro-tipo" class="form-label">{{ __('interfaz.estatus.filtro_tipo_alerta') }}</label>
                        <select id="filtro-tipo" name="filtro[tipo_alerta]" class="form-select">
                            <option value="">{{ __('interfaz.estatus.todos') }}</option>
                            @foreach (['riesgo_baja_academica', 'riesgo_baja_deportiva', 'acumulacion_sanciones_deportivas'] as $tipo)
                                <option value="{{ $tipo }}">{{ __('interfaz.tipos_de_alerta.'.$tipo) }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
                <table id="tabla-estatus" class="table table-striped table-hover w-100" data-endpoint="GET /estatus-de-jugadores">
                    <thead>
                        <tr>
                            <th data-campo="jugador.apellidos jugador.nombres">{{ __('interfaz.estatus.columnas.jugador') }}</th>
                            <th data-campo="jugador.matricula">{{ __('interfaz.estatus.columnas.matricula') }}</th>
                            <th data-campo="estado_oficial.estado estado_oficial.fecha_estimada_regreso estado_oficial.requiere_actualizacion">{{ __('interfaz.estatus.columnas.estado_oficial') }}</th>
                            <th data-campo="aviso_pendiente">{{ __('interfaz.estatus.columnas.aviso_pendiente') }}</th>
                            <th data-campo="alertas.tipo alertas.causa">{{ __('interfaz.estatus.columnas.alertas') }}</th>
                            <th data-columna-de="entrenador" data-campo="resumen_academico.nivel_riesgo resumen_academico.promedio">{{ __('interfaz.estatus.columnas.resumen_academico') }}</th>
                            <th data-columna-de="docente" data-campo="materias.asignatura.clave materias.aprobada materias.calificacion">{{ __('interfaz.estatus.columnas.materias') }}</th>
                            <th class="no-exportar" data-campo="jugador.id">{{ __('interfaz.estatus.columnas.acciones') }}</th>
                        </tr>
                    </thead>
                </table>
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
    <script src="/assets/js/modulos/estatus/index.js"></script>
@endpush
