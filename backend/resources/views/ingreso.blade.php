@extends('layouts.autenticacion')

@section('titulo', __('interfaz.ingreso.titulo'))

@section('contenido')
    <div class="pt-4 pb-2">
        <h1 class="card-title text-center pb-0 fs-4">{{ __('interfaz.ingreso.titulo') }}</h1>
    </div>
    <div id="aviso" class="alert alert-danger d-none" role="alert"></div>
    <form id="formulario-ingreso" class="row g-3" novalidate>
        <div class="col-12">
            <label for="correo" class="form-label">{{ __('interfaz.ingreso.correo') }}</label>
            <input type="email" class="form-control" id="correo" name="correo" autocomplete="username" required>
            <div class="invalid-feedback" data-campo="correo"></div>
        </div>
        <div class="col-12">
            <label for="contrasena" class="form-label">{{ __('interfaz.ingreso.contrasena') }}</label>
            <input type="password" class="form-control" id="contrasena" name="contrasena" autocomplete="current-password" required>
            <div class="invalid-feedback" data-campo="contrasena"></div>
        </div>
        <div class="col-12">
            <button type="submit" id="entrar" class="btn btn-primary w-100">{{ __('interfaz.ingreso.entrar') }}</button>
        </div>
    </form>
@endsection

@section('scripts')
    <script src="/assets/js/ingreso.js"></script>
@endsection
