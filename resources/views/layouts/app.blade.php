<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body id="page-top">
    <div id="wrapper">
        <div class="sidebar-backdrop d-md-none" id="sidebarBackdrop" aria-hidden="true"></div>
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('dashboard') }}">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="sidebar-brand-text mx-3">{{ config('app.name') }}</div>
            </a>
            <div class="sidebar-heading px-3 text-truncate small text-uppercase text-white-50" title="{{ Auth::user()->email }}">
                {{ Auth::user()->email }}
            </div>
            <hr class="sidebar-divider my-0">
            <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('dashboard') }}">
                    <i class="fas fa-fw fa-gauge-high"></i>
                    <span>{{ __('Dashboard') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->routeIs('sites.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('sites.index') }}">
                    <i class="fas fa-fw fa-globe"></i>
                    <span>{{ __('Siti') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('profile.edit') }}">
                    <i class="fas fa-fw fa-user"></i>
                    <span>{{ __('Profilo') }}</span>
                </a>
            </li>
            <li class="nav-item {{ request()->routeIs('security.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('security.edit') }}">
                    <i class="fas fa-fw fa-shield-halved"></i>
                    <span>{{ __('Sicurezza') }}</span>
                </a>
            </li>
            <hr class="sidebar-divider">
            <li class="nav-item">
                <form method="POST" action="{{ route('logout') }}" class="mb-0 w-100">
                    @csrf
                    <button type="submit" class="nav-link border-0 bg-transparent text-white w-100 shadow-none rounded-0">
                        <i class="fas fa-fw fa-right-from-bracket"></i>
                        <span>{{ __('Esci') }}</span>
                    </button>
                </form>
            </li>
            <hr class="sidebar-divider d-none d-md-block">
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle" type="button" aria-label="Toggle sidebar"></button>
            </div>
        </ul>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="d-md-none border-bottom bg-white py-2 px-3 mb-4">
                    <button id="sidebarToggleTop" class="btn btn-link text-gray-600 p-1" type="button" aria-label="Menu">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                </div>

                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>

            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; {{ config('app.name') }} {{ date('Y') }}</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    @stack('scripts')
</body>
</html>
