@extends('layouts.app')

@section('titulo', __('interfaz.periodos.titulo'))

@push('estilos')
    <link href="/assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <link href="/assets/vendor/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="/assets/vendor/datatables/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="/assets/css/comun/tablas.css" rel="stylesheet">
@endpush

@section('contenido')
    <div class="pagetitle">
        <h1>{{ __('interfaz.periodos.titulo') }}</h1>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-body pt-3">
                <h2 class="card-title">{{ __('interfaz.periodos.nuevo') }}</h2>
                <div id="aviso-periodo" class="alert alert-danger d-none" role="alert"></div>
                <form id="formulario-periodo" data-endpoint="POST /periodos-escolares" class="row g-2" novalidate>
                    <div class="col-sm-12 col-lg-4">
                        <label for="nombre" class="form-label">{{ __('interfaz.periodos.nombre') }}</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" autocomplete="off">
                        <div class="invalid-feedback" data-error-de="nombre"></div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <label for="fecha_inicio" class="form-label">{{ __('interfaz.periodos.fecha_inicio') }}</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                        <div class="invalid-feedback" data-error-de="fecha_inicio"></div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <label for="fecha_fin" class="form-label">{{ __('interfaz.periodos.fecha_fin') }}</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                        <div class="invalid-feedback" data-error-de="fecha_fin"></div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">{{ __('interfaz.periodos.guardar') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body pt-3">
                <h2 class="card-title">{{ __('interfaz.periodos.vigentes') }}</h2>
                <table id="tabla-periodos" class="table table-striped table-hover text-nowrap w-100" data-endpoint="GET /periodos-escolares">
                    <thead>
                        <tr>
                            <th data-campo="nombre">{{ __('interfaz.periodos.nombre') }}</th>
                            <th data-campo="fecha_inicio" data-orden="fecha_inicio">{{ __('interfaz.periodos.fecha_inicio') }}</th>
                            <th data-campo="fecha_fin">{{ __('interfaz.periodos.fecha_fin') }}</th>
                            <th data-campo="vigente">{{ __('interfaz.periodos.vigente') }}</th>
                            <th class="no-exportar" data-campo="id">{{ __('interfaz.periodos.acciones') }}</th>
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
    <script src="/assets/js/modulos/periodos/index.js"></script>
@endpush
