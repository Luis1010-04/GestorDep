@extends('layouts.app')

@section('titulo', __('interfaz.jugadores.titulo'))

@push('estilos')
    <link href="/assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <link href="/assets/vendor/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="/assets/vendor/datatables/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="/assets/css/comun/tablas.css" rel="stylesheet">
@endpush

@section('contenido')
    <div class="pagetitle">
        <h1>{{ __('interfaz.jugadores.titulo') }}</h1>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-body pt-3">
                <h2 class="card-title">{{ __('interfaz.jugadores.buscar_titulo') }}</h2>
                <p class="text-muted small">{{ __('interfaz.jugadores.buscar_ayuda') }}</p>
                <form id="formulario-busqueda" data-endpoint="GET /jugadores/busqueda" class="row g-2" novalidate>
                    <div class="col-sm-6 col-lg-4">
                        <label for="matricula" class="form-label">{{ __('interfaz.jugadores.campos.matricula') }}</label>
                        <input type="text" class="form-control" id="matricula" name="matricula" autocomplete="off">
                        <div class="invalid-feedback" data-error-de="matricula"></div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">{{ __('interfaz.jugadores.buscar') }}</button>
                    </div>
                </form>
                <div id="aviso-busqueda" class="alert alert-secondary mt-3 d-none" role="status"></div>
                <div id="resultado-busqueda" class="mt-3" data-endpoint="GET /jugadores/busqueda" hidden>
                    <dl class="row mb-0">
                        <dt class="col-sm-3">{{ __('interfaz.jugadores.campos.apellidos') }}</dt>
                        <dd class="col-sm-9" data-campo="apellidos"></dd>
                        <dt class="col-sm-3">{{ __('interfaz.jugadores.campos.nombres') }}</dt>
                        <dd class="col-sm-9" data-campo="nombres"></dd>
                        <dt class="col-sm-3">{{ __('interfaz.jugadores.campos.matricula') }}</dt>
                        <dd class="col-sm-9" data-campo="matricula"></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body pt-3">
                <h2 class="card-title">{{ __('interfaz.jugadores.listado_titulo') }}</h2>
                <form id="filtros-jugadores" data-endpoint="GET /jugadores" class="row g-2 mb-3" data-roles="entrenador" hidden>
                    <div class="col-sm-6 col-lg-4">
                        <label for="filtro-equipo" class="form-label">{{ __('interfaz.jugadores.filtro_equipo') }}</label>
                        <select id="filtro-equipo" name="filtro[equipo_id]" class="form-select">
                            <option value="">{{ __('interfaz.jugadores.todos_mis_equipos') }}</option>
                        </select>
                    </div>
                </form>
                <table id="tabla-jugadores" class="table table-striped table-hover text-nowrap w-100" data-endpoint="GET /jugadores">
                    <thead>
                        <tr>
                            <th data-campo="apellidos" data-orden="apellidos">{{ __('interfaz.jugadores.campos.apellidos') }}</th>
                            <th data-campo="nombres" data-orden="nombres">{{ __('interfaz.jugadores.campos.nombres') }}</th>
                            <th data-campo="matricula">{{ __('interfaz.jugadores.campos.matricula') }}</th>
                            <th data-campo="equipos.nombre equipos.categoria.nombre equipos.posicion">{{ __('interfaz.jugadores.campos.equipos') }}</th>
                            <th class="no-exportar" data-campo="id">{{ __('interfaz.jugadores.campos.acciones') }}</th>
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
    <script src="/assets/js/modulos/jugadores/index.js"></script>
@endpush
