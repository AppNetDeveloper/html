@php
// Verifica si el usuario está autenticado
if (!Auth::check()) {
    // Si el usuario no está autenticado, redirige a la página de login
    echo redirect()->route('login')->send();
    exit; // Detiene la ejecución del script después de redirigir
}

// Si está autenticado, continúa ejecutando el resto del código
$users = Auth::user();
$currantLang = $users->currentLanguage();
$logo = asset(Storage::url('uploads/logo/'));
$settings = Utility::settings();
@endphp


<nav class="dash-sidebar light-sidebar transprent-bg">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route('home') }}" class="b-brand">
                <!-- ======== Cambiar el logo aquí ============ -->
                @if (isset($settings['dark_mode']))
                    @if ($settings['dark_mode'] == 'on')
                        <img class="c-sidebar-brand-full pt-3 mt-2 mb-1"
                             src="{{ $logo . (!empty($company_logo) ? $company_logo : 'light_logo.png') }}"
                             height="46" class="navbar-brand-img">
                    @else
                        <img class="c-sidebar-brand-full pt-3 mt-2 mb-1"
                             src="{{ $logo . (!empty($company_logo) ? $company_logo : 'dark_logo.png') }}"
                             height="46" class="navbar-brand-img">
                    @endif
                @else
                    <img class="c-sidebar-brand-full pt-3 mt-2 mb-1"
                         src="{{ $logo . (!empty($company_logo) ? $company_logo : 'dark_logo.png') }}"
                         height="46" class="navbar-brand-img">
                @endif
            </a>
        </div>

        <div class="navbar-content active dash-trigger ps ps--active-y">
            <ul class="dash-navbar" style="display: block;">
                <li class="dash-item dash-hasmenu {{ request()->is('/') ? 'active' : '' }}">
                    <a class="dash-link" href="{{ url('/') }}">
                        <span class="dash-micon"><i class="ti ti-home"></i></span>
                        <span class="dash-mtext custom-weight">{{ __('Dashboard') }}</span>
                    </a>
                </li>
                
                <!-- Gestión de usuarios, roles y permisos según permisos del usuario -->
                @can('manage-user')
                    <li class="dash-item dash-hasmenu {{ request()->is('users*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('users.index') }}">
                            <span class="dash-micon"><i class="ti ti-user"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Users') }}</span>
                        </a>
                    </li>
                @endcan
                
                @can('manage-role')
                    <li class="dash-item dash-hasmenu {{ request()->is('roles*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('roles.index') }}">
                            <span class="dash-micon"><i class="ti ti-key"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Roles') }}</span>
                        </a>
                    </li>
                @endcan
                
                @can('manage-permission')
                    <li class="dash-item dash-hasmenu {{ request()->is('permission*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('permission.index') }}">
                            <span class="dash-micon"><i class="ti ti-lock"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Permissions') }}</span>
                        </a>
                    </li>
                @endcan
                
                @can('manage-module')
                    <li class="dash-item dash-hasmenu {{ request()->is('modules*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('modules.index') }}">
                            <span class="dash-micon"><i class="ti ti-subtask"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Modules') }}</span>
                        </a>
                    </li>
                @endcan
                
                @role('admin')
                    <li class="dash-item dash-hasmenu {{ request()->is('settings*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('settings.index') }}">
                            <span class="dash-micon"><i class="ti ti-settings"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Settings') }}</span>
                        </a>
                    </li>
                @endrole
                
                @can('manage-langauge')
                    <li class="dash-item dash-hasmenu {{ request()->is('index') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('index') }}">
                            <span class="dash-micon"><i class="ti ti-world"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Language') }}</span>
                        </a>
                    </li>
                @endcan

                @role('admin')
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('io_generator_builder') }}">
                            <span class="dash-micon"><i class="ti ti-3d-cube-sphere"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Crud') }}</span>
                        </a>
                    </li>
                @endrole

                @role('admin')
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('customers.index') }}">
                            <span class="dash-micon"><i class="ti ti-list-outline"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Customers') }}</span>
                        </a>
                    </li>
                @endrole
                
                @include('layouts.menu')
            </ul>
        </div>
    </div>
</nav>
