@extends('layouts.app')

@section('titulo', __('interfaz.panel.titulo'))

@section('contenido')
    <div class="pagetitle">
        <h1>{{ __('interfaz.panel.titulo') }}</h1>
    </div>
    <section class="section">
        <p>{{ __('interfaz.panel.bienvenida') }}, <span id="bienvenida-nombre"></span>.</p>
        <p class="text-muted">{{ __('interfaz.panel.roles') }}: <span id="bienvenida-roles"></span></p>
        <p class="text-muted">{{ __('interfaz.panel.sin_secciones') }}</p>
    </section>
@endsection
