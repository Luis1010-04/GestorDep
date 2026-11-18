<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') · {{ __('interfaz.aplicacion') }}</title>
    <link href="/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body data-textos="{{ json_encode(__('interfaz.js'), JSON_UNESCAPED_UNICODE) }}">
    @include('parciales.cabecera')
    <div class="estructura">
        @include('parciales.menu')
        <main class="contenido">
            @yield('contenido')
        </main>
    </div>
    @include('parciales.pie')
    <script src="/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/api.js"></script>
    <script src="/assets/js/sesion.js"></script>
</body>
</html>
