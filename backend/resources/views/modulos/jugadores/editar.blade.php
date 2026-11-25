@extends('layouts.app')

@section('titulo', __('interfaz.jugadores.editar_titulo'))

@push('estilos')
    <link href="/assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
@endpush

@section('contenido')
    <div class="pagetitle">
        <h1>{{ __('interfaz.jugadores.editar_titulo') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/jugadores">{{ __('interfaz.jugadores.titulo') }}</a></li>
                <li class="breadcrumb-item"><a href="/jugadores/{{ $jugador }}">{{ __('interfaz.jugadores.ficha_titulo') }}</a></li>
                <li class="breadcrumb-item active">{{ __('interfaz.jugadores.editar_titulo') }}</li>
            </ol>
        </nav>
    </div>
    <section class="section" id="edicion" data-jugador="{{ $jugador }}">
        <div id="aviso-no-disponible" class="alert alert-warning d-none" role="alert"></div>
        <div class="row" id="contenido-edicion">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body pt-3">
                        <p class="text-muted">{{ __('interfaz.jugadores.editar_ayuda') }}</p>
                        <div id="aviso-edicion" class="alert alert-danger d-none" role="alert"></div>
                        <form id="formulario-edicion" data-endpoint="PATCH /jugadores/{jugador}" class="row g-3" novalidate>
                            <div class="col-md-6">
                                <label for="nombres" class="form-label">{{ __('interfaz.jugadores.campos.nombres') }}</label>
                                <input type="text" class="form-control" id="nombres" name="nombres" autocomplete="off">
                                <div class="invalid-feedback" data-error-de="nombres"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="apellidos" class="form-label">{{ __('interfaz.jugadores.campos.apellidos') }}</label>
                                <input type="text" class="form-control" id="apellidos" name="apellidos" autocomplete="off">
                                <div class="invalid-feedback" data-error-de="apellidos"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="fecha_nacimiento" class="form-label">{{ __('interfaz.jugadores.campos.fecha_nacimiento') }}</label>
                                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
                                <div class="invalid-feedback" data-error-de="fecha_nacimiento"></div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary" disabled>{{ __('interfaz.jugadores.guardar_cambios') }}</button>
                                <a class="btn btn-outline-secondary" href="/jugadores/{{ $jugador }}">{{ __('interfaz.formulario.cancelar') }}</a>
                            </div>
                        </form>
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
    <script src="/assets/js/modulos/jugadores/editar.js"></script>
@endpush
