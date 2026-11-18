<header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
        <a href="/panel" class="logo d-flex align-items-center">
            <img src="/NiceAdmin/assets/img/logo.png" alt="">
            <span class="d-none d-lg-block">{{ __('interfaz.aplicacion') }}</span>
        </a>
        <i class="bi bi-list toggle-sidebar-btn"></i>
    </div>
    <nav class="header-nav ms-auto">
        <ul class="d-flex align-items-center">
            <li class="nav-item dropdown pe-3">
                <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle fs-4"></i>
                    <span id="usuario-nombre" class="d-none d-md-block dropdown-toggle ps-2"></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                    <li>
                        <button type="button" id="cerrar-sesion" class="dropdown-item d-flex align-items-center">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>{{ __('interfaz.menu.cerrar_sesion') }}</span>
                        </button>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>
</header>
