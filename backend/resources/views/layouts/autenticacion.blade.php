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
</head>
<body data-textos="{{ json_encode(__('interfaz.js'), JSON_UNESCAPED_UNICODE) }}">
    <main>
        <div class="container">
            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
                            <div class="d-flex justify-content-center py-4">
                                <span class="logo d-flex align-items-center w-auto">
                                    <img src="/NiceAdmin/assets/img/logo.png" alt="">
                                    <span class="d-none d-lg-block">{{ __('interfaz.aplicacion') }}</span>
                                </span>
                            </div>
                            <div class="card mb-3">
                                <div class="card-body">
                                    @yield('contenido')
                                </div>
                            </div>
                            <div class="credits">
                                Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
    <script src="/assets/js/api.js"></script>
    @yield('scripts')
</body>
</html>
