@extends('layouts.app')

@section('titulo', __('interfaz.materias.ficha_titulo'))

@push('estilos')
    <link href="/assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
@endpush

@section('contenido')
    <div class="pagetitle">
        <h1 id="titulo-materia">{{ __('interfaz.materias.ficha_titulo') }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/academico/materias">{{ __('interfaz.materias.titulo') }}</a></li>
                <li class="breadcrumb-item active" id="periodo-materia">{{ __('interfaz.materias.ficha_titulo') }}</li>
            </ol>
        </nav>
    </div>
    <section class="section" id="materia" data-materia="{{ $materia }}">
        <div id="aviso-no-disponible" class="alert alert-warning d-none" role="alert"></div>
        <div id="contenido-materia" class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body pt-3" id="datos-materia" data-endpoint="GET /materias/{materia}">
                        <h2 class="card-title">{{ __('interfaz.materias.datos_titulo') }}</h2>
                        <dl class="row mb-0">
                            <dt class="col-sm-5">{{ __('interfaz.materias.clave') }}</dt>
                            <dd class="col-sm-7" data-campo="asignatura.clave"></dd>
                            <dt class="col-sm-5">{{ __('interfaz.materias.asignatura') }}</dt>
                            <dd class="col-sm-7" data-campo="asignatura.nombre"></dd>
                            <dt class="col-sm-5">{{ __('interfaz.materias.periodo') }}</dt>
                            <dd class="col-sm-7" data-campo="periodo.nombre"></dd>
                            <dt class="col-sm-5">{{ __('interfaz.materias.criterio_aplicado') }}</dt>
                            <dd class="col-sm-7" data-campo="criterio_aplicado.calificacion_minima criterio_aplicado.ambito"></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body pt-3">
                        <h2 class="card-title">{{ __('interfaz.materias.criterio_titulo') }}</h2>
                        <p class="text-muted small">{{ __('interfaz.materias.criterio_ayuda') }}</p>
                        <div id="aviso-criterio" class="alert alert-danger d-none" role="alert"></div>
                        <form id="formulario-criterio" data-endpoint="PUT /materias/{materia}/criterio" class="row g-2" novalidate>
                            <div class="col-sm-8">
                                <label for="calificacion_minima" class="form-label">{{ __('interfaz.materias.calificacion_minima') }}</label>
                                <input type="text" class="form-control" id="calificacion_minima" name="calificacion_minima" inputmode="decimal" autocomplete="off">
                                <div class="invalid-feedback" data-error-de="calificacion_minima"></div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary" disabled>{{ __('interfaz.materias.definir') }}</button>
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
    <script src="/assets/js/modulos/materias/ver.js"></script>
@endpush
