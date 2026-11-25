<aside id="sidebar" class="sidebar" aria-label="{{ __('interfaz.navegacion') }}">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link{{ request()->is('panel') ? '' : ' collapsed' }}" href="/panel"@if (request()->is('panel')) aria-current="page"@endif>
                <i class="bi bi-grid"></i>
                <span>{{ __('interfaz.menu.panel') }}</span>
            </a>
        </li>
        <li class="nav-item" data-roles="docente entrenador" hidden>
            <a class="nav-link{{ request()->is('jugadores*') ? '' : ' collapsed' }}" href="/jugadores"@if (request()->is('jugadores*')) aria-current="page"@endif>
                <i class="bi bi-people"></i>
                <span>{{ __('interfaz.menu.jugadores') }}</span>
            </a>
        </li>
    </ul>
</aside>
