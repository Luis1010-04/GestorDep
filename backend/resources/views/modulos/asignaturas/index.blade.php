@extends('layouts.app')

@section('titulo', __('interfaz.asignaturas.titulo'))

@push('estilos')
    <link href="/assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <link href="/assets/vendor/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="/assets/vendor/datatables/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="/assets/css/comun/tablas.css" rel="stylesheet">
@endpush

@section('contenido')
    <div class="pagetitle">
        <h1>{{ __('interfaz.asignaturas.titulo') }}</h1>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-body pt-3">
                <h2 class="card-title">{{ __('interfaz.asignaturas.nueva') }}</h2>
                <div id="aviso-asignatura" class="alert alert-danger d-none" role="alert"></div>
                <form id="formulario-asignatura" data-endpoint="POST /asignaturas" class="row g-2" novalidate>
                    <div class="col-sm-6 col-lg-3">
                        <label for="clave" class="form-label">{{ __('interfaz.asignaturas.clave') }}</label>
                        <input type="text" class="form-control" id="clave" name="clave" autocomplete="off">
                        <div class="invalid-feedback" data-error-de="clave"></div>
                    </div>
                    <div class="col-sm-12 col-lg-6">
                        <label for="nombre" class="form-label">{{ __('interfaz.asignaturas.nombre') }}</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" autocomplete="off">
                        <div class="invalid-feedback" data-error-de="nombre"></div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label for="grado" class="form-label">{{ __('interfaz.asignaturas.grado') }}</label>
                        <input type="number" class="form-control" id="grado" name="grado" step="1">
                        <div class="invalid-feedback" data-error-de="grado"></div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">{{ __('interfaz.asignaturas.guardar') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body pt-3">
                <h2 class="card-title">{{ __('interfaz.asignaturas.vigentes') }}</h2>
                <table id="tabla-asignaturas" class="table table-striped table-hover text-nowrap w-100" data-endpoint="GET /asignaturas">
                    <thead>
                        <tr>
                            <th data-campo="clave" data-orden="clave">{{ __('interfaz.asignaturas.clave') }}</th>
                            <th data-campo="nombre">{{ __('interfaz.asignaturas.nombre') }}</th>
                            <th data-campo="grado">{{ __('interfaz.asignaturas.grado') }}</th>
                            <th class="no-exportar" data-campo="id">{{ __('interfaz.asignaturas.acciones') }}</th>
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
    <script src="/assets/js/modulos/asignaturas/index.js"></script>
@endpush
