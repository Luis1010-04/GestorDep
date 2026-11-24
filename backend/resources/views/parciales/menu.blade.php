<aside id="sidebar" class="sidebar" aria-label="{{ __('interfaz.navegacion') }}">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link{{ request()->is('panel') ? '' : ' collapsed' }}" href="/panel"@if (request()->is('panel')) aria-current="page"@endif>
                <i class="bi bi-grid"></i>
                <span>{{ __('interfaz.menu.panel') }}</span>
            </a>
        </li>
    </ul>
</aside>
