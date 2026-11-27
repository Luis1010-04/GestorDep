@extends('layouts.app')

@section('titulo', __('interfaz.equipos.titulo'))

@push('estilos')
    <link href="/assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <link href="/assets/vendor/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="/assets/vendor/datatables/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="/assets/css/comun/tablas.css" rel="stylesheet">
@endpush

@section('contenido')
    <div class="pagetitle">
        <h1>{{ __('interfaz.equipos.titulo') }}</h1>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-body pt-3">
                <h2 class="card-title">{{ __('interfaz.equipos.nuevo') }}</h2>
                <div id="aviso-equipo" class="alert alert-danger d-none" role="alert"></div>
                <form id="formulario-equipo" data-endpoint="POST /equipos" class="row g-2" novalidate>
                    <div class="col-sm-6 col-lg-4">
                        <label for="nombre" class="form-label">{{ __('interfaz.equipos.nombre') }}</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" autocomplete="off">
                        <div class="invalid-feedback" data-error-de="nombre"></div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <label for="categoria_id" class="form-label">{{ __('interfaz.equipos.categoria') }}</label>
                        <select class="form-select" id="categoria_id" name="categoria_id">
                            <option value="">{{ __('interfaz.formulario.elegir') }}</option>
                        </select>
                        <div class="invalid-feedback" data-error-de="categoria_id"></div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">{{ __('interfaz.equipos.guardar') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body pt-3">
                <h2 class="card-title">{{ __('interfaz.equipos.mis_equipos') }}</h2>
                <table id="tabla-equipos" class="table table-striped table-hover text-nowrap w-100" data-endpoint="GET /equipos">
                    <thead>
                        <tr>
                            <th data-campo="nombre" data-orden="nombre">{{ __('interfaz.equipos.nombre') }}</th>
                            <th data-campo="categoria.nombre">{{ __('interfaz.equipos.categoria') }}</th>
                            <th class="no-exportar" data-campo="id">{{ __('interfaz.equipos.acciones') }}</th>
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
    <script src="/assets/js/modulos/equipos/index.js"></script>
@endpush
