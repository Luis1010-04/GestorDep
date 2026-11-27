<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>@yield('titulo') · {{ __('interfaz.aplicacion') }}</title>
    <link href="/NiceAdmin/assets/img/favicon.png" rel="icon">
    <link href="/NiceAdmin/assets/img/apple-touch-icon.png" rel="apple-touch-icon">
    <link href="/NiceAdmin/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/NiceAdmin/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="/NiceAdmin/assets/css/style.css" rel="stylesheet">
    @stack('estilos')
</head>
<body data-textos="{{ json_encode(__('interfaz.js'), JSON_UNESCAPED_UNICODE) }}">
    @include('parciales.cabecera')
    @include('parciales.menu')
    <main id="main" class="main">
        @yield('contenido')
    </main>
    @include('parciales.pie')
    <script src="/NiceAdmin/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/tinymce-vacio.js"></script>
    <script src="/NiceAdmin/assets/js/main.js"></script>
    <script src="/assets/js/api.js"></script>
    <script src="/assets/js/sesion.js"></script>
    @stack('scripts')
</body>
</html>
