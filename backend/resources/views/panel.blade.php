@extends('layouts.app')

@section('titulo', __('interfaz.panel.titulo'))

@section('contenido')
    <h1 class="h4">{{ __('interfaz.panel.titulo') }}</h1>
    <p>{{ __('interfaz.panel.bienvenida') }}, <span id="bienvenida-nombre"></span>.</p>
    <p class="text-muted">{{ __('interfaz.panel.roles') }}: <span id="bienvenida-roles"></span></p>
    <p class="text-muted">{{ __('interfaz.panel.sin_secciones') }}</p>
@endsection
