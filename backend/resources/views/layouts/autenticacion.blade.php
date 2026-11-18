<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') · {{ __('interfaz.aplicacion') }}</title>
    <link href="/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="autenticacion" data-textos="{{ json_encode(__('interfaz.js'), JSON_UNESCAPED_UNICODE) }}">
    <main class="contenedor-ingreso">
        @yield('contenido')
    </main>
    @include('parciales.pie')
    <script src="/assets/js/api.js"></script>
    @yield('scripts')
</body>
</html>
