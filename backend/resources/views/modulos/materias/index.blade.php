@extends('layouts.app')

@section('titulo', __('interfaz.materias.titulo'))

@push('estilos')
    <link href="/assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <link href="/assets/vendor/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="/assets/vendor/datatables/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="/assets/css/comun/tablas.css" rel="stylesheet">
@endpush

@section('contenido')
    <div class="pagetitle">
        <h1>{{ __('interfaz.materias.titulo') }}</h1>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-body pt-3">
                <h2 class="card-title">{{ __('interfaz.materias.nueva') }}</h2>
                <p class="text-muted small">{{ __('interfaz.materias.nueva_ayuda') }}</p>
                <div id="aviso-materia" class="alert alert-danger d-none" role="alert"></div>
                <form id="formulario-materia" data-endpoint="POST /materias" class="row g-2" novalidate>
                    <div class="col-md-6">
                        <label for="asignatura_id" class="form-label">{{ __('interfaz.materias.asignatura') }}</label>
                        <select class="form-select" id="asignatura_id" name="asignatura_id">
                            <option value="">{{ __('interfaz.formulario.elegir') }}</option>
                        </select>
                        <div class="invalid-feedback" data-error-de="asignatura_id"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="periodo_id" class="form-label">{{ __('interfaz.materias.periodo') }}</label>
                        <select class="form-select" id="periodo_id" name="periodo_id">
                            <option value="">{{ __('interfaz.formulario.elegir') }}</option>
                        </select>
                        <div class="invalid-feedback" data-error-de="periodo_id"></div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">{{ __('interfaz.materias.guardar') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body pt-3">
                <h2 class="card-title">{{ __('interfaz.materias.listado') }}</h2>
                <form id="filtros-materias" data-endpoint="GET /materias" class="row g-2 mb-3" novalidate>
                    <div class="col-md-8 col-lg-6">
                        <label for="filtro-periodo" class="form-label">{{ __('interfaz.materias.filtro_periodo') }}</label>
                        <select id="filtro-periodo" name="filtro[periodo_id]" class="form-select">
                            <option value="">{{ __('interfaz.materias.todos') }}</option>
                        </select>
                    </div>
                </form>
                <table id="tabla-materias" class="table table-striped table-hover text-nowrap w-100" data-endpoint="GET /materias">
                    <thead>
                        <tr>
                            <th data-campo="asignatura.clave">{{ __('interfaz.materias.clave') }}</th>
                            <th data-campo="asignatura.nombre">{{ __('interfaz.materias.asignatura') }}</th>
                            <th data-campo="periodo.nombre">{{ __('interfaz.materias.periodo') }}</th>
                            <th data-campo="criterio_aplicado.calificacion_minima criterio_aplicado.ambito">{{ __('interfaz.materias.criterio') }}</th>
                            <th class="no-exportar" data-campo="id">{{ __('interfaz.materias.acciones') }}</th>
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
    <script src="/assets/js/modulos/materias/index.js"></script>
@endpush
