@extends('layouts.app')

@section('titulo', __('interfaz.equipos.ficha_titulo'))

@push('estilos')
    <link href="/assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <link href="/assets/vendor/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="/assets/vendor/datatables/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="/assets/css/comun/tablas.css" rel="stylesheet">
@endpush

@php($posiciones = ['portero', 'defensa', 'medio', 'delantero'])

@section('contenido')
    <div class="pagetitle">
        <h1 id="titulo-equipo">{{ __('interfaz.equipos.ficha_titulo') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/plantel/equipos">{{ __('interfaz.equipos.titulo') }}</a></li>
                <li class="breadcrumb-item active" id="categoria-equipo">{{ __('interfaz.equipos.ficha_titulo') }}</li>
            </ol>
        </nav>
    </div>
    <section class="section" id="equipo" data-equipo="{{ $equipo }}">
        <div id="aviso-no-disponible" class="alert alert-warning d-none" role="alert"></div>
        <div id="contenido-equipo">
            <div class="card">
                <div class="card-body pt-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <h2 class="card-title">{{ __('interfaz.equipos.jugadores_titulo') }}</h2>
                        <a class="btn btn-primary btn-sm" href="/jugadores/crear?equipo={{ $equipo }}">{{ __('interfaz.equipos.registrar_jugador') }}</a>
                    </div>
                    <table id="tabla-jugadores-equipo" class="table table-striped table-hover text-nowrap w-100" data-endpoint="GET /jugadores">
                        <thead>
                            <tr>
                                <th data-campo="apellidos" data-orden="apellidos">{{ __('interfaz.jugadores.campos.apellidos') }}</th>
                                <th data-campo="nombres" data-orden="nombres">{{ __('interfaz.jugadores.campos.nombres') }}</th>
                                <th data-campo="matricula">{{ __('interfaz.jugadores.campos.matricula') }}</th>
                                <th data-campo="equipos.equipo_id equipos.posicion">{{ __('interfaz.equipos.posicion_en_el_equipo') }}</th>
                                <th class="no-exportar" data-campo="id">{{ __('interfaz.equipos.acciones') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="card" id="tarjeta-posicion" hidden>
                <div class="card-body pt-3">
                    <h2 class="card-title">{{ __('interfaz.equipos.posicion_titulo') }}: <span id="jugador-de-la-posicion"></span></h2>
                    <div id="aviso-posicion" class="alert alert-danger d-none" role="alert"></div>
                    <form id="formulario-posicion" data-endpoint="PATCH /equipos/{equipo}/jugadores/{jugador}" class="row g-2" novalidate>
                        <div class="col-sm-6 col-lg-4">
                            <label for="posicion-nueva" class="form-label">{{ __('interfaz.equipos.posicion') }}</label>
                            <select class="form-select" id="posicion-nueva" name="posicion">
                                @foreach ($posiciones as $posicion)
                                    <option value="{{ $posicion }}">{{ __('interfaz.posiciones.'.$posicion) }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-error-de="posicion"></div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">{{ __('interfaz.formulario.guardar') }}</button>
                            <button type="button" class="btn btn-outline-secondary" id="cancelar-posicion">{{ __('interfaz.formulario.cancelar') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body pt-3">
                    <h2 class="card-title">{{ __('interfaz.equipos.asignar_titulo') }}</h2>
                    <p class="text-muted small">{{ __('interfaz.equipos.asignar_ayuda') }}</p>
                    <div id="aviso-asignar" class="alert alert-danger d-none" role="alert"></div>
                    <form id="formulario-asignar" data-endpoint="POST /equipos/{equipo}/jugadores" class="row g-2" novalidate>
                        <div class="col-sm-6 col-lg-4">
                            <label for="matricula" class="form-label">{{ __('interfaz.equipos.matricula') }}</label>
                            <input type="text" class="form-control" id="matricula" name="matricula" autocomplete="off">
                            <div class="invalid-feedback" data-error-de="matricula"></div>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <label for="posicion" class="form-label">{{ __('interfaz.equipos.posicion') }}</label>
                            <select class="form-select" id="posicion" name="posicion">
                                <option value="">{{ __('interfaz.formulario.elegir') }}</option>
                                @foreach ($posiciones as $posicion)
                                    <option value="{{ $posicion }}">{{ __('interfaz.posiciones.'.$posicion) }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-error-de="posicion"></div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">{{ __('interfaz.equipos.asignar') }}</button>
                        </div>
                    </form>
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
    <script src="/assets/js/modulos/equipos/ver.js"></script>
@endpush
