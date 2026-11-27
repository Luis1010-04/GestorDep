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
        <li class="nav-item" data-roles="docente entrenador" hidden>
            <a class="nav-link{{ request()->is('estatus*') ? '' : ' collapsed' }}" href="/estatus"@if (request()->is('estatus*')) aria-current="page"@endif>
                <i class="bi bi-activity"></i>
                <span>{{ __('interfaz.menu.estatus') }}</span>
            </a>
        </li>
        <li class="nav-heading" data-roles="entrenador" hidden>{{ __('interfaz.menu.plantel') }}</li>
        <li class="nav-item" data-roles="entrenador" hidden>
            <a class="nav-link{{ request()->is('plantel/categorias*') ? '' : ' collapsed' }}" href="/plantel/categorias"@if (request()->is('plantel/categorias*')) aria-current="page"@endif>
                <i class="bi bi-tags"></i>
                <span>{{ __('interfaz.menu.categorias') }}</span>
            </a>
        </li>
        <li class="nav-item" data-roles="entrenador" hidden>
            <a class="nav-link{{ request()->is('plantel/equipos*') ? '' : ' collapsed' }}" href="/plantel/equipos"@if (request()->is('plantel/equipos*')) aria-current="page"@endif>
                <i class="bi bi-shield"></i>
                <span>{{ __('interfaz.menu.equipos') }}</span>
            </a>
        </li>
    </ul>
</aside>
