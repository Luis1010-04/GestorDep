@extends('layouts.app')

@section('titulo', __('interfaz.contrasena.titulo'))

@push('estilos')
    <link href="/assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
@endpush

@section('contenido')
    <div class="pagetitle">
        <h1>{{ __('interfaz.contrasena.titulo') }}</h1>
    </div>
    <section class="section">
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body pt-3">
                        <p class="text-muted">{{ __('interfaz.contrasena.nota') }}</p>
                        <div id="aviso-formulario" class="alert alert-danger d-none" role="alert"></div>
                        <form id="formulario-contrasena" data-endpoint="PUT /contrasena" class="row g-3" novalidate>
                            <div class="col-12">
                                <label for="contrasena_actual" class="form-label">{{ __('interfaz.contrasena.actual') }}</label>
                                <input type="password" class="form-control" id="contrasena_actual" name="contrasena_actual" autocomplete="current-password">
                                <div class="invalid-feedback" data-error-de="contrasena_actual"></div>
                            </div>
                            <div class="col-12">
                                <label for="contrasena_nueva" class="form-label">{{ __('interfaz.contrasena.nueva') }}</label>
                                <input type="password" class="form-control" id="contrasena_nueva" name="contrasena_nueva" autocomplete="new-password">
                                <div class="invalid-feedback" data-error-de="contrasena_nueva"></div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">{{ __('interfaz.contrasena.guardar') }}</button>
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
    <script src="/assets/js/modulos/contrasena/editar.js"></script>
@endpush
